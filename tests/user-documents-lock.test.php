<?php
/**
 * Tests for the member lock in inc/user-documents.php (plain PHP, no WordPress).
 *
 *   1  upload: the lock is taken only for the list write, after the slow work
 *   2  upload: no lock, or no room, removes the stored file again
 *   3  delete: lock on every path, nothing deleted without it
 *   4  backfill: a document deleted meanwhile does not come back
 */
$TMP = sys_get_temp_dir() . '/vance-docs-lock-' . getmypid() . '/';
@mkdir( $TMP . 'wp-admin/includes', 0777, true );
foreach ( array( 'file', 'media', 'image' ) as $f ) {
	file_put_contents( $TMP . "wp-admin/includes/$f.php", "<?php\n" );
}
define( 'ABSPATH', $TMP );
error_reporting( E_ALL );
set_error_handler( function ( $no, $str, $file, $line ) { throw new ErrorException( $str, 0, $no, $file, $line ); } );

$PASS = 0;
$FAIL = array();
function check( $name, $ok ) { global $PASS, $FAIL; if ( $ok ) { $PASS++; } else { $FAIL[] = $name; } }
function section( $t ) { echo "\n-- $t\n"; }

class Json_Done extends Exception {
	public $ok;
	public $data;
	public function __construct( $ok, $data ) { parent::__construct( 'json' ); $this->ok = $ok; $this->data = $data; }
}

$GLOBALS['UMETA']      = array();
$GLOBALS['LOG']        = array();
$GLOBALS['LOCK_FAILS'] = false;
$GLOBALS['ATT']        = array();
$GLOBALS['NEXT_ATT']   = 500;
$GLOBALS['ON_UPLOAD']  = null;
$GLOBALS['ON_ATTACHED'] = null;

function add_action() {}
function add_filter() {}
function check_ajax_referer() {}
function is_user_logged_in() { return true; }
function get_current_user_id() { return 7; }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function current_time( $t ) { return '2026-11-26 10:00:00'; }
function is_wp_error( $x ) { return false; }
function wp_update_post() { $GLOBALS['LOG'][] = 'update_post'; }
function get_attached_file( $id ) {
	if ( is_callable( $GLOBALS['ON_ATTACHED'] ) ) { $h = $GLOBALS['ON_ATTACHED']; $GLOBALS['ON_ATTACHED'] = null; $h(); }
	return '';
}
function get_post_mime_type( $id ) { return 'text/plain'; }
function wp_get_attachment_url( $id ) { return 'https://example.test/f/' . $id; }
function wp_check_filetype_and_ext( $tmp, $name, $allowed ) { return array( 'type' => 'text/plain' ); }
function media_handle_upload( $key, $post, $a, $b ) {
	$GLOBALS['LOG'][] = 'media_handle_upload';
	$id = $GLOBALS['NEXT_ATT']++;
	$GLOBALS['ATT'][ $id ] = true;
	if ( is_callable( $GLOBALS['ON_UPLOAD'] ) ) { call_user_func( $GLOBALS['ON_UPLOAD'] ); }
	return $id;
}
function wp_delete_attachment( $id, $force = false ) {
	$GLOBALS['LOG'][] = 'delete_att:' . $id;
	if ( ! isset( $GLOBALS['ATT'][ $id ] ) ) { return false; }
	unset( $GLOBALS['ATT'][ $id ] );
	return (object) array( 'ID' => $id );
}
function get_user_meta( $u, $k, $single = false ) { return isset( $GLOBALS['UMETA'][ $u ][ $k ] ) ? $GLOBALS['UMETA'][ $u ][ $k ] : ''; }
function update_user_meta( $u, $k, $v ) { $GLOBALS['LOG'][] = 'update_meta'; $GLOBALS['UMETA'][ $u ][ $k ] = $v; return true; }
function wp_send_json_error( $d ) { throw new Json_Done( false, $d ); }
function wp_send_json_success( $d ) { throw new Json_Done( true, $d ); }
function vance_hub_checkin_lock( $uid, $wait = 5 ) {
	if ( $GLOBALS['LOCK_FAILS'] ) { $GLOBALS['LOG'][] = 'lock_refused'; return false; }
	$GLOBALS['LOG'][] = 'lock';
	$GLOBALS['LAST_WAIT'] = $wait;
	return true;
}
function vance_hub_checkin_unlock( $uid ) { $GLOBALS['LOG'][] = 'unlock'; }
function vance_user_docs_extract_text_stub() {}

require __DIR__ . '/../wp-content/themes/vance-health-hub/inc/user-documents.php';

function run( $fn ) {
	$GLOBALS['LOG'] = array();
	try {
		$fn();
	} catch ( Json_Done $e ) {
		return $e;
	}
	return null;
}
function upload() {
	$_FILES = array( 'doc' => array( 'tmp_name' => '/tmp/x', 'name' => 'note.txt', 'size' => 10, 'error' => 0 ) );
	return run( 'vance_user_docs_ajax_upload' );
}
function del( $id ) {
	$_POST = array( 'id' => $id );
	return run( 'vance_user_docs_ajax_delete' );
}
function docs() { return isset( $GLOBALS['UMETA'][7]['_sla_profile_docs'] ) ? $GLOBALS['UMETA'][7]['_sla_profile_docs'] : array(); }
function ids() { return array_column( docs(), 'id' ); }
function at( $what ) { $p = array_search( $what, $GLOBALS['LOG'], true ); return false === $p ? -1 : $p; }

section( '1. Upload: the lock covers only the list write' );
$GLOBALS['UMETA'][7]['_sla_profile_docs'] = array( array( 'id' => 1, 'name' => 'old' ) );
$R = upload();
check( 'upload: succeeds and the list holds the old and the new document', $R && $R->ok && array( 1, 500 ) === ids() );
check( 'upload: the lock is taken after the upload itself, not before', at( 'media_handle_upload' ) >= 0 && at( 'lock' ) > at( 'media_handle_upload' ) );
check( 'upload: the list is written inside the lock, and the lock is freed after', at( 'update_meta' ) > at( 'lock' ) && at( 'unlock' ) > at( 'update_meta' ) );
check( 'upload: one lock, one unlock', 1 === count( array_keys( $GLOBALS['LOG'], 'lock', true ) ) && 1 === count( array_keys( $GLOBALS['LOG'], 'unlock', true ) ) );

$GLOBALS['UMETA'][7]['_sla_profile_docs'] = array( array( 'id' => 1, 'name' => 'old' ) );
$GLOBALS['ON_UPLOAD'] = function () { $GLOBALS['UMETA'][7]['_sla_profile_docs'] = array( array( 'id' => 2, 'name' => 'added-meanwhile' ) ); };
$R = upload();
$GLOBALS['ON_UPLOAD'] = null;
check( 'upload: the list is read again inside the lock, so a change made meanwhile is kept', $R && $R->ok && array( 2, 501 ) === ids() );

section( '2. Upload: no lock or no room removes the stored file' );
$GLOBALS['UMETA'][7]['_sla_profile_docs'] = array();
$GLOBALS['LOCK_FAILS'] = true;
$R = upload();
$GLOBALS['LOCK_FAILS'] = false;
check( 'lock refused: an error is returned', $R && ! $R->ok );
check( 'lock refused: nothing is written to the list', array() === docs() && -1 === at( 'update_meta' ) );
check( 'lock refused: the file that was stored is deleted again', in_array( 'delete_att:502', $GLOBALS['LOG'], true ) && ! isset( $GLOBALS['ATT'][502] ) );

$GLOBALS['UMETA'][7]['_sla_profile_docs'] = array();
$GLOBALS['ON_UPLOAD'] = function () { $rows = array(); for ( $i = 1; $i <= VANCE_DOCS_MAX; $i++ ) { $rows[] = array( 'id' => 900 + $i, 'name' => 'n' ); } $GLOBALS['UMETA'][7]['_sla_profile_docs'] = $rows; };
$R = upload();
$GLOBALS['ON_UPLOAD'] = null;
check( 'no room (full while uploading): an error, the lock is freed, the list is not changed', $R && ! $R->ok && VANCE_DOCS_MAX === count( docs() ) && in_array( 'unlock', $GLOBALS['LOG'], true ) );
check( 'no room: the file that was stored is deleted again', in_array( 'delete_att:503', $GLOBALS['LOG'], true ) && ! isset( $GLOBALS['ATT'][503] ) );

section( '3. Delete' );
$GLOBALS['ATT'][11] = true;
$GLOBALS['ATT'][12] = true;
$GLOBALS['UMETA'][7]['_sla_profile_docs'] = array( array( 'id' => 11, 'name' => 'a' ), array( 'id' => 12, 'name' => 'b' ) );
$R = del( 11 );
check( 'delete: succeeds, the file and the row are gone, the other stays', $R && $R->ok && array( 12 ) === ids() && ! isset( $GLOBALS['ATT'][11] ) && isset( $GLOBALS['ATT'][12] ) );
check( 'delete: lock before the delete, unlock after the write', at( 'lock' ) >= 0 && at( 'delete_att:11' ) > at( 'lock' ) && at( 'unlock' ) > at( 'update_meta' ) );
$R = del( 999 );
check( 'delete: an id not in the list gives an error and frees the lock', $R && ! $R->ok && 'unlock' === end( $GLOBALS['LOG'] ) && 1 === count( array_keys( $GLOBALS['LOG'], 'lock', true ) ) );
check( 'delete: an id not in the list deletes no file', ! in_array( 'delete_att:999', $GLOBALS['LOG'], true ) );
$GLOBALS['LOCK_FAILS'] = true;
$R = del( 12 );
$GLOBALS['LOCK_FAILS'] = false;
check( 'delete: lock refused gives an error and deletes nothing', $R && ! $R->ok && array( 12 ) === ids() && isset( $GLOBALS['ATT'][12] ) && ! in_array( 'delete_att:12', $GLOBALS['LOG'], true ) );

section( '4. Backfill' );
// A record with no text_status is "not processed". The file is missing, so the
// backfill records status "error", which needs no extraction.
$GLOBALS['UMETA'][7]['_sla_profile_docs'] = array( array( 'id' => 21, 'name' => 'x' ), array( 'id' => 22, 'name' => 'y', 'text_status' => 'ok', 'text' => 'kept' ) );
$GLOBALS['LOG'] = array();
check( 'backfill: updates the row with no status and reports true', true === vance_user_docs_backfill_text( 7 ) );
$row = docs()[0];
check( 'backfill: the row now has a status, the processed row is unchanged', 'error' === $row['text_status'] && 'kept' === docs()[1]['text'] );
check( 'backfill: the write is inside the lock', at( 'lock' ) >= 0 && at( 'update_meta' ) > at( 'lock' ) && at( 'unlock' ) > at( 'update_meta' ) );
check( 'backfill: nothing to do means no lock and no write', ( function () { $GLOBALS['LOG'] = array(); return false === vance_user_docs_backfill_text( 7 ) && array() === $GLOBALS['LOG']; } )() );

// A delete that happens while the text is read: the row must not come back.
$GLOBALS['UMETA'][7]['_sla_profile_docs'] = array( array( 'id' => 31, 'name' => 'x' ), array( 'id' => 32, 'name' => 'y' ) );
$GLOBALS['ON_ATTACHED'] = function () { $GLOBALS['UMETA'][7]['_sla_profile_docs'] = array( array( 'id' => 31, 'name' => 'x' ) ); };
$GLOBALS['LOG'] = array();
vance_user_docs_backfill_text( 7 );
check( 'backfill: a document deleted while the text was read does not come back', array( 31 ) === ids() && 'error' === docs()[0]['text_status'] );

$GLOBALS['UMETA'][7]['_sla_profile_docs'] = array( array( 'id' => 51, 'name' => 'x' ) );
$GLOBALS['ON_ATTACHED'] = function () { $GLOBALS['UMETA'][7]['_sla_profile_docs'] = array(); };
$GLOBALS['LOG'] = array();
$R = vance_user_docs_backfill_text( 7 );
check( 'backfill: when everything was deleted meanwhile nothing is written back, and the lock is freed', false === $R && array() === docs() && -1 === at( 'update_meta' ) && 'unlock' === end( $GLOBALS['LOG'] ) );
$GLOBALS['UMETA'][7]['_sla_profile_docs'] = array( array( 'id' => 52, 'name' => 'x' ) );
$GLOBALS['LAST_WAIT'] = null;
vance_user_docs_backfill_text( 7 );
check( 'backfill: waits 1 second for the lock, as it runs while the page is built', 1 === $GLOBALS['LAST_WAIT'] );
$GLOBALS['LAST_WAIT'] = null;
$GLOBALS['UMETA'][7]['_sla_profile_docs'] = array();
upload();
check( 'upload: waits the full 5 seconds', 5 === $GLOBALS['LAST_WAIT'] );

$GLOBALS['UMETA'][7]['_sla_profile_docs'] = array( array( 'id' => 41, 'name' => 'x' ) );
$GLOBALS['LOCK_FAILS'] = true;
$GLOBALS['LOG'] = array();
$R = vance_user_docs_backfill_text( 7 );
$GLOBALS['LOCK_FAILS'] = false;
check( 'backfill: with no lock nothing is written, and the row stays unprocessed for the next page load', false === $R && empty( docs()[0]['text_status'] ) && -1 === at( 'update_meta' ) );

echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . ' FAILED of ' . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) {
		echo "  x  $f\n";
	}
	exit( 1 );
}
echo "OK - $PASS checks passed.\n";
