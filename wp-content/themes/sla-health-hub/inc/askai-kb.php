<?php
/**
 * VANCE-Ai Primary Knowledge Base.
 *
 * A curated store of material the assistant should know but that no published
 * article covers: a glossary of medical terms, facts about Vance Medical Hub,
 * Vance Medical Foods Ltd and SLA Pharma, policies, and anything else the team
 * wants the assistant to answer from.
 *
 * Each entry is a `vance_kb` post whose body text is what the model reads. An
 * entry can carry a source URL (for provenance) and an attached PDF, and can be
 * marked "always include" so it is injected into every conversation regardless
 * of the question — that is how core company facts stay reliably available.
 *
 * Retrieval: always-include entries first, then keyword matches, both ahead of
 * (and budgeted separately from) the article search in inc/askai-functions.php.
 *
 * Admin: Dashboard → VANCE-Ai KB.
 *
 * @package sla-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VANCE_KB_POST_TYPE = 'vance_kb';
const VANCE_KB_TAXONOMY  = 'vance_kb_type';

// =========================================================================
// Registration
// =========================================================================

/**
 * Register the knowledge base post type and its category taxonomy.
 */
function vance_kb_register() {
	register_post_type(
		VANCE_KB_POST_TYPE,
		array(
			'labels'          => array(
				'name'               => __( 'VANCE-Ai KB', 'sla-health-hub' ),
				'singular_name'      => __( 'KB Entry', 'sla-health-hub' ),
				'add_new'            => __( 'Add Entry', 'sla-health-hub' ),
				'add_new_item'       => __( 'Add Knowledge Base Entry', 'sla-health-hub' ),
				'edit_item'          => __( 'Edit Knowledge Base Entry', 'sla-health-hub' ),
				'search_items'       => __( 'Search Knowledge Base', 'sla-health-hub' ),
				'not_found'          => __( 'No knowledge base entries yet.', 'sla-health-hub' ),
				'menu_name'          => __( 'VANCE-Ai KB', 'sla-health-hub' ),
			),
			'public'          => false,           // Never served on the front end.
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 26,
			'menu_icon'       => 'dashicons-book-alt',
			'supports'        => array( 'title', 'editor', 'page-attributes', 'revisions' ),
			'capability_type' => 'post',
			'has_archive'     => false,
			'rewrite'         => false,
			'show_in_rest'    => false,
		)
	);

	register_taxonomy(
		VANCE_KB_TAXONOMY,
		VANCE_KB_POST_TYPE,
		array(
			'labels'            => array(
				'name'          => __( 'KB Categories', 'sla-health-hub' ),
				'singular_name' => __( 'KB Category', 'sla-health-hub' ),
				'menu_name'     => __( 'Categories', 'sla-health-hub' ),
			),
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
			'rewrite'           => false,
		)
	);
}
add_action( 'init', 'vance_kb_register' );

/**
 * Seed the starter categories once, the first time the KB screen is opened.
 */
function vance_kb_seed_terms() {
	if ( get_option( 'vance_kb_terms_seeded' ) ) {
		return;
	}

	$terms = array(
		'Glossary'          => __( 'Definitions of medical and nutritional terms, including acronyms.', 'sla-health-hub' ),
		'About the Hub'     => __( 'What Vance Medical Hub is, what it publishes, who it is for.', 'sla-health-hub' ),
		'Vance Medical'     => __( 'Vance Medical Foods Ltd: company, products, contact.', 'sla-health-hub' ),
		'SLA Pharma'        => __( 'SLA Pharma: the parent entity.', 'sla-health-hub' ),
		'Policies'          => __( 'Editorial, privacy and clinical governance positions.', 'sla-health-hub' ),
		'Uncategorised'     => __( 'Anything not yet filed.', 'sla-health-hub' ),
	);

	foreach ( $terms as $name => $description ) {
		if ( ! term_exists( $name, VANCE_KB_TAXONOMY ) ) {
			wp_insert_term( $name, VANCE_KB_TAXONOMY, array( 'description' => $description ) );
		}
	}

	update_option( 'vance_kb_terms_seeded', 1 );
}
add_action( 'admin_init', 'vance_kb_seed_terms' );

// =========================================================================
// Entry settings (meta box)
// =========================================================================

/**
 * Add the entry settings box.
 */
function vance_kb_add_meta_boxes() {
	add_meta_box(
		'vance_kb_settings',
		__( 'Knowledge Base Settings', 'sla-health-hub' ),
		'vance_kb_render_meta_box',
		VANCE_KB_POST_TYPE,
		'side',
		'high'
	);
	add_meta_box(
		'vance_kb_help',
		__( 'How VANCE-Ai uses this entry', 'sla-health-hub' ),
		'vance_kb_render_help_box',
		VANCE_KB_POST_TYPE,
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'vance_kb_add_meta_boxes' );

/**
 * Entry settings: source URL, always-include flag, PDF attachment.
 *
 * @param WP_Post $post Current entry.
 */
function vance_kb_render_meta_box( $post ) {
	wp_nonce_field( 'vance_kb_save', 'vance_kb_nonce' );

	$source_url = get_post_meta( $post->ID, '_vance_kb_source_url', true );
	$always     = get_post_meta( $post->ID, '_vance_kb_always', true );
	$pdf_id     = (int) get_post_meta( $post->ID, '_vance_kb_pdf_id', true );
	$pdf_url    = $pdf_id ? wp_get_attachment_url( $pdf_id ) : '';
	?>
	<p>
		<label for="vance_kb_source_url"><strong><?php esc_html_e( 'Source URL (optional)', 'sla-health-hub' ); ?></strong></label><br>
		<input type="url" id="vance_kb_source_url" name="vance_kb_source_url" class="widefat"
			value="<?php echo esc_attr( $source_url ); ?>" placeholder="https://">
		<span class="description"><?php esc_html_e( 'Where this came from. Shown to the assistant for provenance; it is never offered to readers as a "Read more" link.', 'sla-health-hub' ); ?></span>
	</p>

	<p>
		<label>
			<input type="checkbox" name="vance_kb_always" value="1" <?php checked( $always, '1' ); ?>>
			<strong><?php esc_html_e( 'Always include', 'sla-health-hub' ); ?></strong>
		</label><br>
		<span class="description"><?php esc_html_e( 'Inject this entry into every conversation, whatever the question. Use for core facts (who we are, what the hub is). Keep these few and short, as they are sent on every request.', 'sla-health-hub' ); ?></span>
	</p>

	<p>
		<label for="vance_kb_pdf_id"><strong><?php esc_html_e( 'Attached PDF (optional)', 'sla-health-hub' ); ?></strong></label><br>
		<input type="hidden" id="vance_kb_pdf_id" name="vance_kb_pdf_id" value="<?php echo esc_attr( $pdf_id ); ?>">
		<span id="vance-kb-pdf-name"><?php echo $pdf_url ? esc_html( basename( $pdf_url ) ) : esc_html__( 'None selected', 'sla-health-hub' ); ?></span><br>
		<button type="button" class="button" id="vance-kb-pdf-select"><?php esc_html_e( 'Choose PDF', 'sla-health-hub' ); ?></button>
		<button type="button" class="button" id="vance-kb-pdf-clear"><?php esc_html_e( 'Remove', 'sla-health-hub' ); ?></button>
	</p>

	<?php if ( $pdf_id ) : ?>
		<p>
			<button type="button" class="button button-secondary" id="vance-kb-pdf-extract"><?php esc_html_e( 'Pull text from PDF', 'sla-health-hub' ); ?></button>
			<span class="description"><?php esc_html_e( 'Appends the PDF text to the entry body below, where you can tidy it. Best-effort: if extraction fails, copy and paste the text in yourself. The body is what the assistant reads.', 'sla-health-hub' ); ?></span>
			<span id="vance-kb-extract-status" style="display:block;margin-top:6px;"></span>
		</p>
	<?php endif; ?>
	<?php
}

/**
 * Explain the workflow on the edit screen so contributors do not have to guess.
 */
function vance_kb_render_help_box() {
	?>
	<p style="margin-top:0;">
		<?php esc_html_e( 'The body text below is exactly what VANCE-Ai reads. Write it as plain, factual prose. The assistant quotes and paraphrases from it, so anything vague or out of date will surface in answers.', 'sla-health-hub' ); ?>
	</p>
	<ul style="list-style:disc;margin-left:20px;">
		<li><?php esc_html_e( 'One topic per entry. A glossary term, a company fact, a policy, not a whole document dumped in.', 'sla-health-hub' ); ?></li>
		<li><?php esc_html_e( 'Put the words a reader would actually type into the title and the first line, so keyword matching finds it.', 'sla-health-hub' ); ?></li>
		<li><?php esc_html_e( 'Entries have no public page. The assistant will not offer them as "Read more" links, so put anything you want linked into a published article instead.', 'sla-health-hub' ); ?></li>
		<li><?php esc_html_e( 'Draft entries are ignored. Only published entries reach the assistant.', 'sla-health-hub' ); ?></li>
	</ul>
	<?php
}

/**
 * Persist the entry settings.
 *
 * @param int $post_id Entry id.
 */
function vance_kb_save_meta( $post_id ) {
	if ( ! isset( $_POST['vance_kb_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vance_kb_nonce'] ) ), 'vance_kb_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$url = isset( $_POST['vance_kb_source_url'] ) ? esc_url_raw( wp_unslash( $_POST['vance_kb_source_url'] ) ) : '';
	update_post_meta( $post_id, '_vance_kb_source_url', $url );

	update_post_meta( $post_id, '_vance_kb_always', isset( $_POST['vance_kb_always'] ) ? '1' : '' );

	$pdf_id = isset( $_POST['vance_kb_pdf_id'] ) ? absint( $_POST['vance_kb_pdf_id'] ) : 0;
	update_post_meta( $post_id, '_vance_kb_pdf_id', $pdf_id );
}
add_action( 'save_post_' . VANCE_KB_POST_TYPE, 'vance_kb_save_meta' );

/**
 * Media picker + extraction wiring on the entry screen.
 *
 * @param string $hook Current admin page.
 */
function vance_kb_admin_assets( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || VANCE_KB_POST_TYPE !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	wp_enqueue_media();

	$inline = "
	jQuery(function ($) {
		var frame;
		$('#vance-kb-pdf-select').on('click', function (e) {
			e.preventDefault();
			if (frame) { frame.open(); return; }
			frame = wp.media({ title: 'Choose a PDF', library: { type: 'application/pdf' }, multiple: false });
			frame.on('select', function () {
				var a = frame.state().get('selection').first().toJSON();
				$('#vance_kb_pdf_id').val(a.id);
				$('#vance-kb-pdf-name').text(a.filename || a.title);
			});
			frame.open();
		});
		$('#vance-kb-pdf-clear').on('click', function (e) {
			e.preventDefault();
			$('#vance_kb_pdf_id').val('');
			$('#vance-kb-pdf-name').text('None selected');
		});
		$('#vance-kb-pdf-extract').on('click', function (e) {
			e.preventDefault();
			var \$s = $('#vance-kb-extract-status').text('Extracting…');
			$.post(ajaxurl, {
				action: 'vance_kb_extract_pdf',
				nonce: '" . esc_js( wp_create_nonce( 'vance_kb_extract' ) ) . "',
				post_id: $('#post_ID').val()
			}, function (r) {
				if (r && r.success && r.data.text) {
					if (typeof tinymce !== 'undefined' && tinymce.get('content') && !tinymce.get('content').isHidden()) {
						tinymce.get('content').setContent(tinymce.get('content').getContent() + '<p>' + r.data.text.replace(/</g, '&lt;') + '</p>');
					} else {
						var \$c = $('#content');
						\$c.val(\$c.val() + '\\n\\n' + r.data.text);
					}
					\$s.text(r.data.message);
				} else {
					\$s.text((r && r.data && r.data.message) ? r.data.message : 'Extraction failed. Please paste the text in manually.');
				}
			});
		});
	});
	";

	wp_add_inline_script( 'jquery-core', $inline );
}
add_action( 'admin_enqueue_scripts', 'vance_kb_admin_assets' );

// =========================================================================
// PDF text extraction (best effort)
// =========================================================================

/**
 * Pull readable text out of a PDF.
 *
 * Tries the `pdftotext` binary first (accurate, present on some hosts), then a
 * pure-PHP pass that inflates the page content streams and lifts the text-show
 * operators. The pure-PHP path handles ordinary text PDFs; it cannot read
 * scanned/image PDFs, which have no text layer at all — those need the copy and
 * paste route, which is why the body field always stays editable.
 *
 * @param string $path Absolute path to the PDF.
 * @return string Extracted text, or '' on failure.
 */
function vance_kb_extract_pdf_text( $path ) {
	if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
		return '';
	}

	// 1. pdftotext, when the host allows shelling out.
	if ( function_exists( 'shell_exec' ) && ! in_array( 'shell_exec', array_map( 'trim', explode( ',', (string) ini_get( 'disable_functions' ) ) ), true ) ) {
		$which = @shell_exec( 'command -v pdftotext 2>/dev/null' );
		if ( ! empty( $which ) ) {
			$out = @shell_exec( 'pdftotext -q -enc UTF-8 ' . escapeshellarg( $path ) . ' - 2>/dev/null' );
			if ( ! empty( $out ) && strlen( trim( $out ) ) > 40 ) {
				return vance_kb_tidy_text( $out );
			}
		}
	}

	// 2. Pure-PHP fallback.
	$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( false === $raw ) {
		return '';
	}

	$text = '';

	// Inflate every FlateDecode stream we can, then read text operators from it.
	if ( preg_match_all( '/stream\r?\n(.*?)\r?\nendstream/s', $raw, $streams ) ) {
		foreach ( $streams[1] as $stream ) {
			$data = @gzuncompress( $stream );
			if ( false === $data ) {
				$data = @gzinflate( substr( $stream, 2 ) );
			}
			if ( false === $data || '' === $data ) {
				continue;
			}
			$text .= vance_kb_text_from_stream( $data );
		}
	}

	// Uncompressed PDFs expose their operators directly.
	if ( '' === trim( $text ) ) {
		$text = vance_kb_text_from_stream( $raw );
	}

	return vance_kb_tidy_text( $text );
}

/**
 * Lift the strings out of a decoded PDF content stream.
 *
 * Handles Tj/TJ show-text operators; TJ arrays are flattened and their kerning
 * numbers dropped.
 *
 * @param string $data Decoded stream.
 * @return string
 */
function vance_kb_text_from_stream( $data ) {
	$out = '';

	if ( preg_match_all( '/\[(.*?)\]\s*TJ/s', $data, $arrays ) ) {
		foreach ( $arrays[1] as $chunk ) {
			if ( preg_match_all( '/\(((?:\\\\.|[^\\\\()])*)\)/s', $chunk, $parts ) ) {
				$out .= implode( '', array_map( 'vance_kb_unescape_pdf_string', $parts[1] ) ) . ' ';
			}
		}
	}

	if ( preg_match_all( '/\((?:\\\\.|[^\\\\()])*\)\s*Tj/s', $data, $singles ) ) {
		foreach ( $singles[0] as $single ) {
			if ( preg_match( '/\(((?:\\\\.|[^\\\\()])*)\)/s', $single, $m ) ) {
				$out .= vance_kb_unescape_pdf_string( $m[1] ) . ' ';
			}
		}
	}

	return $out;
}

/**
 * Resolve PDF string escapes.
 *
 * @param string $string Raw literal.
 * @return string
 */
function vance_kb_unescape_pdf_string( $string ) {
	$map = array(
		'\\n'  => "\n",
		'\\r'  => "\r",
		'\\t'  => "\t",
		'\\('  => '(',
		'\\)'  => ')',
		'\\\\' => '\\',
	);
	$string = strtr( $string, $map );

	// Octal escapes.
	return preg_replace_callback(
		'/\\\\([0-7]{1,3})/',
		function ( $m ) {
			return chr( octdec( $m[1] ) );
		},
		$string
	);
}

/**
 * Collapse whitespace and drop control characters from extracted text.
 *
 * @param string $text Raw text.
 * @return string
 */
function vance_kb_tidy_text( $text ) {
	$text = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', ' ', (string) $text );
	$text = preg_replace( '/[ \t]+/', ' ', $text );
	$text = preg_replace( '/\s*\n\s*\n\s*/', "\n\n", $text );
	return trim( (string) $text );
}

/**
 * AJAX: extract the attached PDF's text for the current entry.
 */
function vance_kb_ajax_extract_pdf() {
	check_ajax_referer( 'vance_kb_extract', 'nonce' );

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sla-health-hub' ) ) );
	}

	$pdf_id = (int) get_post_meta( $post_id, '_vance_kb_pdf_id', true );
	$path   = $pdf_id ? get_attached_file( $pdf_id ) : '';
	if ( ! $path ) {
		wp_send_json_error( array( 'message' => __( 'No PDF is attached to this entry. Save the entry after choosing one.', 'sla-health-hub' ) ) );
	}

	$text = vance_kb_extract_pdf_text( $path );
	if ( '' === $text || strlen( $text ) < 40 ) {
		wp_send_json_error(
			array(
				'message' => __( 'Could not read text from this PDF. It is most likely a scan with no text layer. Open it, copy the text, and paste it into the entry body.', 'sla-health-hub' ),
			)
		);
	}

	// Keep a single entry to a sane size; the model reads a trimmed version anyway.
	$words = preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
	if ( count( $words ) > 4000 ) {
		$text = implode( ' ', array_slice( $words, 0, 4000 ) );
	}

	wp_send_json_success(
		array(
			'text'    => $text,
			'message' => sprintf(
				/* translators: %d: word count */
				__( 'Pulled %d words in. Please read it through and tidy it before publishing.', 'sla-health-hub' ),
				min( count( $words ), 4000 )
			),
		)
	);
}
add_action( 'wp_ajax_vance_kb_extract_pdf', 'vance_kb_ajax_extract_pdf' );

// =========================================================================
// Retrieval
// =========================================================================

/**
 * Turn a KB entry into a source block for the prompt.
 *
 * @param WP_Post  $post   Entry.
 * @param string[] $terms  Search terms, for excerpt centring.
 * @param int      $budget Word budget.
 * @return array|null
 */
function vance_kb_source_from_post( $post, $terms, $budget ) {
	$excerpt = function_exists( 'vance_ai_build_excerpt' )
		? vance_ai_build_excerpt( $post, $terms, $budget )
		: wp_trim_words( wp_strip_all_tags( $post->post_content ), $budget );

	if ( '' === trim( (string) $excerpt ) ) {
		return null;
	}

	return array(
		'id'        => $post->ID,
		'title'     => get_the_title( $post ),
		'url'       => (string) get_post_meta( $post->ID, '_vance_kb_source_url', true ),
		'excerpt'   => $excerpt,
		'primary'   => false,
		'reference' => true,
	);
}

/**
 * Collect knowledge base entries for this conversation.
 *
 * Always-include entries come first (they are the assistant's standing brief),
 * then keyword matches. Both are capped so the prompt cannot balloon.
 *
 * @param array    $messages Conversation.
 * @param string[] $terms    Extracted search terms.
 * @return array[] Source blocks.
 */
function vance_kb_retrieve_sources( $messages, $terms ) {
	$always_cap = (int) apply_filters( 'vance_kb_always_cap', 4 );
	$match_cap  = (int) apply_filters( 'vance_kb_match_cap', 4 );

	$sources = array();
	$seen    = array();

	// 1. Standing brief.
	$always = get_posts(
		array(
			'post_type'        => VANCE_KB_POST_TYPE,
			'post_status'      => 'publish',
			'posts_per_page'   => $always_cap,
			'orderby'          => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'meta_key'         => '_vance_kb_always', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'       => '1',                // phpcs:ignore WordPress.DB.SlowDBQuery
			'suppress_filters' => false,
		)
	);

	foreach ( $always as $entry ) {
		$source = vance_kb_source_from_post( $entry, $terms, 500 );
		if ( $source ) {
			$sources[]           = $source;
			$seen[ $entry->ID ] = true;
		}
	}

	// 2. Keyword matches. Same multi-pass shape as the article search, because
	// WordPress's `s` is AND-matching and a KB hit is often a single term.
	$last_user = '';
	foreach ( array_reverse( (array) $messages ) as $message ) {
		if ( isset( $message['role'] ) && 'user' === $message['role'] ) {
			$last_user = (string) $message['content'];
			break;
		}
	}

	$searches = array();
	if ( '' !== $last_user ) {
		$searches[] = wp_trim_words( $last_user, 12, '' );
	}
	if ( count( $terms ) > 1 ) {
		$searches[] = implode( ' ', array_slice( $terms, 0, 3 ) );
	}
	foreach ( array_slice( $terms, 0, 6 ) as $term ) {
		$searches[] = $term;
	}

	foreach ( $searches as $search ) {
		if ( count( $sources ) >= ( $always_cap + $match_cap ) ) {
			break;
		}
		$search = trim( (string) $search );
		if ( '' === $search ) {
			continue;
		}

		$found = get_posts(
			array(
				'post_type'        => VANCE_KB_POST_TYPE,
				'post_status'      => 'publish',
				'posts_per_page'   => $match_cap,
				's'                => $search,
				'post__not_in'     => array_keys( $seen ),
				'suppress_filters' => false,
			)
		);

		foreach ( $found as $entry ) {
			if ( isset( $seen[ $entry->ID ] ) || count( $sources ) >= ( $always_cap + $match_cap ) ) {
				continue;
			}
			$source = vance_kb_source_from_post( $entry, $terms, 400 );
			if ( $source ) {
				$sources[]          = $source;
				$seen[ $entry->ID ] = true;
			}
		}
	}

	return $sources;
}

// =========================================================================
// Admin list niceties
// =========================================================================

/**
 * Add an "Always" column so the standing brief is visible at a glance.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function vance_kb_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['vance_kb_always'] = __( 'Always included', 'sla-health-hub' );
		}
	}
	return $new;
}
add_filter( 'manage_' . VANCE_KB_POST_TYPE . '_posts_columns', 'vance_kb_columns' );

/**
 * Render the Always column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Entry id.
 */
function vance_kb_column_content( $column, $post_id ) {
	if ( 'vance_kb_always' !== $column ) {
		return;
	}
	echo get_post_meta( $post_id, '_vance_kb_always', true )
		? '<span style="color:#008080;font-weight:600;">' . esc_html__( 'Yes', 'sla-health-hub' ) . '</span>'
		: '-';
}
add_action( 'manage_' . VANCE_KB_POST_TYPE . '_posts_custom_column', 'vance_kb_column_content', 10, 2 );
