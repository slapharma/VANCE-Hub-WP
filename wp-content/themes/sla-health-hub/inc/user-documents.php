<?php
/**
 * My Documents — member-uploaded documents, with a reader and an
 * "Ask VANCE-Ai about this document" flow.
 *
 * STORAGE
 * -------
 * Documents live in the existing `_sla_profile_docs` user meta, the same key
 * the old My Profile uploader wrote to (CLAUDE.md constraint 2: do not rename
 * `_sla_*` keys). Moving the feature to its own tab therefore carries every
 * document a member already uploaded across with it, rather than stranding
 * them behind a control that no longer exists.
 *
 * Each record is {id, url, name, date} as before, plus, for anything uploaded
 * through this tab, {mime, size, text, text_status} — `text` is the extracted
 * plain text used to ground the AI, and `text_status` records why it is empty
 * when it is, so the UI can say something truthful instead of failing silently.
 *
 * PRIVACY
 * -------
 * These are health documents. Two things about the current storage model are
 * worth stating plainly rather than leaving implied:
 *
 *  1. Uploads go to the standard WordPress uploads directory and are served by
 *     the web server directly. The URL contains a random-ish path but is NOT
 *     access-controlled — anyone holding the link can open it. That is why the
 *     UI carries an explicit warning and why `vance_user_docs_reader_url()`
 *     exists: the reader streams through PHP with a capability check, so the
 *     dashboard never has to hand the raw uploads URL to the browser.
 *  2. Document text is sent to the AI provider when, and only when, the member
 *     uses "Ask VANCE-Ai" on that document. The modal says so before they ask.
 *
 * Hardening the uploads directory itself (a per-user subdirectory plus a deny
 * rule in .htaccess/nginx) is a server-side change and is noted in the
 * dashboard's own disclaimer rather than silently assumed.
 *
 * @package sla-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Most documents one member may hold. */
const VANCE_DOCS_MAX = 10;

/** Largest single upload, in bytes. */
const VANCE_DOCS_MAX_BYTES = 10485760; // 10 MB

/** Extracted text kept per document, in characters. */
const VANCE_DOCS_MAX_TEXT = 40000;

/**
 * Upload types we accept, in the shape WordPress actually wants: an extension
 * pattern mapped to the mime type it must resolve to.
 *
 * The KEYS have to be extension patterns, and that is load-bearing rather than
 * stylistic. `wp_check_filetype()` wraps each key as the regex `!\.(<key>)$!i`
 * and matches it against the filename. Hand it mime types as keys and it builds
 * `!\.(application/pdf)$!i`, which matches no filename that has ever existed, so
 * every upload comes back with a false ext/type and `_wp_handle_upload()`
 * rejects it as "Sorry, you are not allowed to upload this file type" — PDFs
 * included, which is the one format this uploader most exists to accept.
 *
 * Deliberately narrow. The WordPress default allowlist includes archives and
 * audio, none of which belongs in a health-record uploader, and every extra
 * type is another parser exposed to a hostile file.
 *
 * @return array<string,string> extension pattern => mime type
 */
function vance_user_docs_upload_mimes() {
	return array(
		'pdf'      => 'application/pdf',
		'docx'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'doc'      => 'application/msword',
		'txt'      => 'text/plain',
		'csv'      => 'text/csv',
		'jpg|jpeg' => 'image/jpeg',
		'png'      => 'image/png',
		'heic'     => 'image/heic',
	);
}

/**
 * A member's documents, normalised so older records read safely.
 *
 * @param int $user_id User.
 * @return array<int, array>
 */
function vance_user_docs_get( $user_id ) {
	$docs = get_user_meta( (int) $user_id, '_sla_profile_docs', true );
	if ( ! is_array( $docs ) ) {
		return array();
	}

	$out = array();
	foreach ( $docs as $d ) {
		if ( ! is_array( $d ) || empty( $d['id'] ) ) {
			continue;
		}
		$out[] = array(
			'id'          => (int) $d['id'],
			'url'         => isset( $d['url'] ) ? (string) $d['url'] : '',
			'name'        => isset( $d['name'] ) ? (string) $d['name'] : 'Document',
			'date'        => isset( $d['date'] ) ? (string) $d['date'] : '',
			'mime'        => isset( $d['mime'] ) ? (string) $d['mime'] : (string) get_post_mime_type( (int) $d['id'] ),
			'size'        => isset( $d['size'] ) ? (int) $d['size'] : 0,
			// Records uploaded through the old profile control have no extracted
			// text; treat them as "not processed" rather than "extraction failed".
			'text'        => isset( $d['text'] ) ? (string) $d['text'] : '',
			'text_status' => isset( $d['text_status'] ) ? (string) $d['text_status'] : 'legacy',
		);
	}
	return $out;
}

/**
 * Does this document belong to this user?
 *
 * Ownership is the only gate on reading and on AI grounding, so it is checked
 * against the user's own meta rather than against the attachment's post_author
 * — the latter can be edited in wp-admin, the former cannot.
 *
 * @param int $user_id User.
 * @param int $doc_id  Attachment id.
 * @return array|null The record, or null when it is not theirs.
 */
function vance_user_docs_find( $user_id, $doc_id ) {
	foreach ( vance_user_docs_get( $user_id ) as $d ) {
		if ( (int) $d['id'] === (int) $doc_id ) {
			return $d;
		}
	}
	return null;
}

/**
 * Signed-ish reader URL — an admin-ajax endpoint that streams the file after
 * checking ownership, so the uploads path never reaches the page source.
 *
 * @param int $doc_id Attachment id.
 * @return string
 */
function vance_user_docs_reader_url( $doc_id ) {
	return add_query_arg(
		array(
			'action' => 'vance_doc_stream',
			'doc'    => (int) $doc_id,
			'nonce'  => wp_create_nonce( 'vance_doc_stream_' . (int) $doc_id ),
		),
		admin_url( 'admin-ajax.php' )
	);
}

/* ============================================================================
 * TEXT EXTRACTION
 * ========================================================================= */

/**
 * Plain text from an uploaded file, for AI grounding.
 *
 * Returns [text, status]. Status is one of:
 *   ok         — text was extracted
 *   empty      — the format is readable but held no text (e.g. a scanned PDF)
 *   unsupported— images and anything else we cannot read without OCR
 *   error      — the file could not be opened or parsed
 *
 * The status matters: an empty string on its own cannot distinguish "this is a
 * photo of a letter" from "something broke", and the UI needs to tell the
 * member which it is.
 *
 * @param string $path Absolute path.
 * @param string $mime Mime type.
 * @return array{0:string,1:string}
 */
function vance_user_docs_extract_text( $path, $mime ) {
	if ( ! is_readable( $path ) ) {
		return array( '', 'error' );
	}

	$text = '';

	if ( 'text/plain' === $mime || 'text/csv' === $mime ) {
		$text = (string) file_get_contents( $path );
	} elseif ( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' === $mime ) {
		// No zip extension on this server means .docx cannot be read at all.
		// That is an environment limitation, not a bad file, and the member
		// should be told the format is unsupported here rather than that their
		// document is broken.
		if ( ! class_exists( 'ZipArchive' ) ) {
			return array( '', 'unsupported' );
		}
		$text = vance_user_docs_extract_docx( $path );
		if ( null === $text ) {
			return array( '', 'error' );
		}
	} elseif ( 'application/pdf' === $mime ) {
		$text = vance_user_docs_extract_pdf( $path );
		if ( null === $text ) {
			return array( '', 'error' );
		}
	} else {
		// Images (and legacy .doc, which is a binary OLE format) would need OCR
		// or a parser we are not going to ship. Say so rather than guessing.
		return array( '', 'unsupported' );
	}

	// Normalise whitespace so the excerpt budget is spent on words.
	$text = preg_replace( '/[ \t]+/', ' ', (string) $text );
	$text = preg_replace( '/\n{3,}/', "\n\n", $text );
	$text = trim( $text );

	if ( '' === $text ) {
		return array( '', 'empty' );
	}
	if ( strlen( $text ) > VANCE_DOCS_MAX_TEXT ) {
		$text = substr( $text, 0, VANCE_DOCS_MAX_TEXT );
	}
	return array( $text, 'ok' );
}

/**
 * .docx text. A docx is a zip; the body is word/document.xml, and paragraph
 * boundaries are </w:p>, which have to become newlines before tags are stripped
 * or the whole document collapses into one run-on line.
 *
 * @param string $path Absolute path.
 * @return string|null Null on a read failure.
 */
function vance_user_docs_extract_docx( $path ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return null;
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $path ) ) {
		return null;
	}
	$xml = $zip->getFromName( 'word/document.xml' );
	$zip->close();
	if ( false === $xml ) {
		return null;
	}
	return vance_user_docs_docx_xml_to_text( $xml );
}

/**
 * word/document.xml -> plain text.
 *
 * Split out from the zip handling so the transform can be exercised without a
 * zip extension present, and so the paragraph-boundary rule is testable on its
 * own. That rule is the whole trick: </w:p> has to become a newline BEFORE the
 * tags are stripped, or a multi-paragraph document collapses into one line.
 *
 * @param string $xml Raw document.xml.
 * @return string
 */
function vance_user_docs_docx_xml_to_text( $xml ) {
	$xml = str_replace(
		array( '</w:p>', '<w:br/>', '<w:br />', '<w:tab/>', '<w:tab />' ),
		array( "\n", "\n", "\n", ' ', ' ' ),
		(string) $xml
	);
	return html_entity_decode( wp_strip_all_tags( $xml ), ENT_QUOTES | ENT_XML1, 'UTF-8' );
}

/**
 * Best-effort PDF text.
 *
 * This reads Flate-compressed content streams and pulls the strings out of the
 * text-showing operators. It handles the ordinary case — a PDF produced by a
 * word processor or a report generator — and does NOT handle scanned pages (no
 * text to find), custom font encodings that do not map to ASCII, or object
 * streams. That is an accepted limitation, not a bug: anything it cannot read
 * comes back empty and the UI offers the member the paste-it-in fallback.
 *
 * @param string $path Absolute path.
 * @return string|null Null on a read failure.
 */
function vance_user_docs_extract_pdf( $path ) {
	$raw = file_get_contents( $path );
	if ( false === $raw ) {
		return null;
	}

	$out = '';

	// Every stream ... endstream pair; inflate the ones that inflate.
	if ( preg_match_all( '/stream\r?\n(.*?)endstream/s', $raw, $matches ) ) {
		foreach ( $matches[1] as $chunk ) {
			$data = @gzuncompress( $chunk );
			if ( false === $data ) {
				$data = @gzinflate( $chunk );
			}
			if ( false === $data ) {
				continue; // not Flate, or not a content stream
			}

			// Tj / TJ operands: (literal strings) inside the text object.
			if ( preg_match_all( '/\((?:\\\\.|[^\\\\()])*\)/s', $data, $strings ) ) {
				foreach ( $strings[0] as $s ) {
					$s = substr( $s, 1, -1 );
					$s = str_replace(
						array( '\\(', '\\)', '\\\\', '\\n', '\\r', '\\t' ),
						array( '(', ')', '\\', "\n", "\r", "\t" ),
						$s
					);
					$out .= $s . ' ';
				}
				$out .= "\n";
			}
		}
	}

	// Strip anything that is not printable text — malformed encodings otherwise
	// arrive as control-character soup and waste the excerpt budget.
	$out = preg_replace( '/[^\P{C}\n]+/u', '', $out );
	return (string) $out;
}

/**
 * Extract text for any of a member's documents that has never been through
 * extraction, and persist the result.
 *
 * Documents uploaded before this tab existed were written by the old profile
 * uploader as bare {id, url, name, date} records. Nothing in the upload path
 * ever revisits them, so without this they keep `text_status = 'legacy'`
 * forever: "Ask VANCE-Ai" stays disabled on the row and the grounding filter
 * bails on the empty string. The member's only route to a working document
 * would be to delete and re-upload one they can already see in the list, which
 * is not a thing anyone would think to try.
 *
 * Runs at most once per document, because it writes a definite `text_status`
 * every time — including for the failures. A file that has gone missing under
 * the attachment is recorded as `error` rather than retried on every page load.
 *
 * @param int $user_id User.
 * @return bool True when at least one record was updated.
 */
function vance_user_docs_backfill_text( $user_id ) {
	$user_id = (int) $user_id;
	$docs    = get_user_meta( $user_id, '_sla_profile_docs', true );
	if ( ! is_array( $docs ) || empty( $docs ) ) {
		return false;
	}

	$changed = false;
	foreach ( $docs as $i => $d ) {
		if ( ! is_array( $d ) || empty( $d['id'] ) ) {
			continue;
		}
		// A record that already carries a status has been processed; leave it.
		if ( ! empty( $d['text_status'] ) ) {
			continue;
		}

		$doc_id = (int) $d['id'];
		$mime   = ! empty( $d['mime'] ) ? (string) $d['mime'] : (string) get_post_mime_type( $doc_id );
		$path   = get_attached_file( $doc_id );

		$docs[ $i ]['mime'] = $mime;

		if ( ! $path || ! file_exists( $path ) ) {
			$docs[ $i ]['size']        = isset( $d['size'] ) ? (int) $d['size'] : 0;
			$docs[ $i ]['text']        = '';
			$docs[ $i ]['text_status'] = 'error';
			$changed                   = true;
			continue;
		}

		list( $text, $status ) = vance_user_docs_extract_text( $path, $mime );

		$size = ! empty( $d['size'] ) ? (int) $d['size'] : (int) filesize( $path );

		$docs[ $i ]['size']        = $size;
		$docs[ $i ]['text']        = $text;
		$docs[ $i ]['text_status'] = $status;
		$changed                   = true;
	}

	if ( $changed ) {
		update_user_meta( $user_id, '_sla_profile_docs', $docs );
	}
	return $changed;
}

/**
 * Is My Documents switched on?
 *
 * Every endpoint below asks this first. Hiding the tab is not enough on its own:
 * the upload, delete and stream actions are admin-ajax URLs, and anyone who
 * bookmarked one, or who reads the JavaScript, can still call it. For a feature
 * holding special category health data, "switched off" has to mean the server
 * stops answering, not merely that the link is gone from the sidebar.
 *
 * Defaults to on when the toggle file is absent, so this feature keeps working
 * if user-documents.php is ever used without dashboard-features.php.
 *
 * @return bool
 */
function vance_user_docs_enabled() {
	return function_exists( 'vance_dashboard_feature_enabled' )
		? vance_dashboard_feature_enabled( 'documents' )
		: true;
}

/* ============================================================================
 * AJAX: upload / delete / stream / text
 * ========================================================================= */

/**
 * AJAX: upload a document to My Documents.
 *
 * POST: nonce, doc (file)
 */
function vance_user_docs_ajax_upload() {
	check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );
	if ( ! vance_user_docs_enabled() ) {
		wp_send_json_error( array( 'message' => 'My Documents is not available on this site.' ) );
	}
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Please sign in.' ) );
	}
	if ( empty( $_FILES['doc'] ) || ! isset( $_FILES['doc']['tmp_name'] ) ) {
		wp_send_json_error( array( 'message' => 'No file was received.' ) );
	}

	$user_id = get_current_user_id();
	$docs    = get_user_meta( $user_id, '_sla_profile_docs', true );
	$docs    = is_array( $docs ) ? $docs : array();

	if ( count( $docs ) >= VANCE_DOCS_MAX ) {
		wp_send_json_error( array( 'message' => sprintf( 'You can store up to %d documents. Delete one to make room.', VANCE_DOCS_MAX ) ) );
	}

	$file = $_FILES['doc'];
	if ( ! empty( $file['error'] ) ) {
		wp_send_json_error( array( 'message' => 'That file could not be uploaded, please try again.' ) );
	}
	if ( (int) $file['size'] > VANCE_DOCS_MAX_BYTES ) {
		wp_send_json_error( array( 'message' => 'That file is larger than 10 MB.' ) );
	}

	// Trust the file's contents, not the browser-supplied type: wp_check_filetype_and_ext
	// re-derives both from the bytes and the extension together. Our own map is
	// passed here as well as to the upload below, so the pre-flight check and the
	// upload cannot disagree about what is allowed — the default allowlist is
	// wider than ours, and on some builds narrower for types like HEIC.
	$allowed = vance_user_docs_upload_mimes();
	$check   = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed );
	$mime    = ! empty( $check['type'] ) ? $check['type'] : '';
	if ( ! $mime || ! in_array( $mime, $allowed, true ) ) {
		wp_send_json_error( array(
			'message' => 'That file type is not supported. Please upload a PDF, Word document, text file, CSV or image.',
		) );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$att_id = media_handle_upload( 'doc', 0, array(), array(
		'test_form' => false,
		'mimes'     => $allowed,
	) );
	if ( is_wp_error( $att_id ) ) {
		wp_send_json_error( array( 'message' => $att_id->get_error_message() ) );
	}

	// Owned by the uploader, so a stray media-library query cannot mix members' files up.
	wp_update_post( array( 'ID' => $att_id, 'post_author' => $user_id ) );

	$path = get_attached_file( $att_id );
	list( $text, $status ) = vance_user_docs_extract_text( $path, $mime );

	$docs[] = array(
		'id'          => (int) $att_id,
		'url'         => wp_get_attachment_url( $att_id ),
		'name'        => sanitize_text_field( $file['name'] ),
		'date'        => current_time( 'mysql' ),
		'mime'        => $mime,
		'size'        => (int) $file['size'],
		'text'        => $text,
		'text_status' => $status,
	);
	update_user_meta( $user_id, '_sla_profile_docs', $docs );

	wp_send_json_success( array(
		'message'     => 'Document uploaded.',
		'id'          => (int) $att_id,
		'text_status' => $status,
	) );
}
add_action( 'wp_ajax_vance_doc_upload', 'vance_user_docs_ajax_upload' );

/**
 * AJAX: delete one of the member's documents (and the underlying file).
 *
 * POST: nonce, id
 */
function vance_user_docs_ajax_delete() {
	check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );
	// Closed along with the rest of the feature. There is no UI to reach it from
	// once the tab is hidden, and leaving the one destructive endpoint open while
	// the read endpoints are shut would be a strange place to draw the line.
	if ( ! vance_user_docs_enabled() ) {
		wp_send_json_error( array( 'message' => 'My Documents is not available on this site.' ) );
	}
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Please sign in.' ) );
	}

	$doc_id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$user_id = get_current_user_id();
	$docs    = get_user_meta( $user_id, '_sla_profile_docs', true );
	$docs    = is_array( $docs ) ? $docs : array();

	$kept  = array();
	$found = false;
	foreach ( $docs as $d ) {
		if ( isset( $d['id'] ) && (int) $d['id'] === $doc_id ) {
			$found = true;
			continue;
		}
		$kept[] = $d;
	}

	if ( ! $found ) {
		wp_send_json_error( array( 'message' => 'That document is not in your list.' ) );
	}

	// Only after confirming it was in THIS user's list.
	wp_delete_attachment( $doc_id, true );
	update_user_meta( $user_id, '_sla_profile_docs', $kept );

	wp_send_json_success( array( 'message' => 'Document deleted.' ) );
}
add_action( 'wp_ajax_vance_doc_delete', 'vance_user_docs_ajax_delete' );

/**
 * AJAX: stream a document to its owner.
 *
 * Exists so the dashboard can show a document without publishing the uploads
 * URL into the page. Inline disposition so PDFs and images render in the modal.
 *
 * GET: doc, nonce
 */
function vance_user_docs_ajax_stream() {
	$doc_id = isset( $_GET['doc'] ) ? absint( $_GET['doc'] ) : 0;

	if ( ! vance_user_docs_enabled() ) {
		status_header( 403 );
		exit;
	}

	if ( ! is_user_logged_in()
		|| ! $doc_id
		|| ! isset( $_GET['nonce'] )
		|| ! wp_verify_nonce( $_GET['nonce'], 'vance_doc_stream_' . $doc_id ) ) {
		status_header( 403 );
		exit;
	}

	$doc = vance_user_docs_find( get_current_user_id(), $doc_id );
	if ( ! $doc ) {
		status_header( 404 );
		exit;
	}

	$path = get_attached_file( $doc_id );
	if ( ! $path || ! is_readable( $path ) ) {
		status_header( 404 );
		exit;
	}

	nocache_headers();
	header( 'Content-Type: ' . ( $doc['mime'] ?: 'application/octet-stream' ) );
	header( 'Content-Length: ' . filesize( $path ) );
	header( 'Content-Disposition: inline; filename="' . rawurlencode( $doc['name'] ) . '"' );
	// The file is the member's own; never let a shared cache hold it.
	header( 'X-Content-Type-Options: nosniff' );
	readfile( $path );
	exit;
}
add_action( 'wp_ajax_vance_doc_stream', 'vance_user_docs_ajax_stream' );

/* ============================================================================
 * AI GROUNDING
 * ========================================================================= */

/**
 * Make a member's document the primary source when the conversation was opened
 * from My Documents.
 *
 * The document is only ever used for the member who owns it: `doc_id` is
 * resolved against the requesting user's own list, so passing someone else's
 * attachment id returns nothing.
 *
 * @param array[]         $sources  Retrieved sources.
 * @param array           $messages Conversation.
 * @param WP_REST_Request $request  Request.
 * @return array[]
 */
function vance_user_docs_ai_sources( $sources, $messages, $request ) {
	$params = is_object( $request ) && method_exists( $request, 'get_json_params' )
		? (array) $request->get_json_params()
		: array();

	$doc_id = isset( $params['doc_id'] ) ? absint( $params['doc_id'] ) : 0;
	if ( ! $doc_id || ! is_user_logged_in() || ! vance_user_docs_enabled() ) {
		return $sources;
	}

	$doc = vance_user_docs_find( get_current_user_id(), $doc_id );
	if ( ! $doc || '' === trim( $doc['text'] ) ) {
		return $sources;
	}

	// The document outranks the hub's own articles for this conversation, so
	// anything else claiming primary is demoted first.
	foreach ( $sources as $i => $s ) {
		if ( ! empty( $s['primary'] ) ) {
			$sources[ $i ]['primary'] = false;
		}
	}

	// Generous but bounded: enough for a discharge summary or a set of results,
	// not so much that it crowds the model's context.
	$excerpt = $doc['text'];
	if ( strlen( $excerpt ) > 6000 ) {
		$excerpt = substr( $excerpt, 0, 6000 );
	}

	// `member_document` is what makes the difference between the model reading
	// this and refusing it. Without the flag it is labelled "the article the
	// reader is currently reading" and falls under the hub's rule that clinical
	// questions may only be answered from the library — so the model reports it
	// cannot find the answer while the document sits in its context.
	array_unshift( $sources, array(
		'id'              => 0,
		'title'           => $doc['name'],
		'url'             => '',
		'excerpt'         => $excerpt,
		'primary'         => true,
		'member_document' => true,
	) );

	return $sources;
}
add_filter( 'vance_ai_sources', 'vance_user_docs_ai_sources', 10, 3 );
