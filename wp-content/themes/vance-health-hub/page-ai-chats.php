<?php
/**
 * Template Name: AI Chats Archive
 *
 * Auto-bound to any Page with slug `ai-chats` via WP's page-{slug}.php
 * template hierarchy; also selectable from the Page Attributes template
 * dropdown (Template Name above). The WP Page itself (title/content) is not
 * auto-created — see CLAUDE.md for the same gap on the evidence page.
 *
 * Administrator-only. Read-only report of every saved VANCE-ai conversation
 * across all users, queried live from `_sla_saved_chats` user meta (see
 * vance_ai_autosave_conversation() in inc/askai-functions.php for the write
 * side of that contract). Contains real users' health-related chat content —
 * do not link this page anywhere public, and do not lower the capability
 * check below.
 */

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	wp_safe_redirect( home_url( '/' ) );
	exit;
}

// ---------------------------------------------------------------------
// Data layer — mirrors the read side of vance_ai_autosave_conversation()
// and the legacy vance_rest_save_chat() transcript shapes.
// ---------------------------------------------------------------------

/**
 * Inline markdown (bold + bare URLs) -> escaped HTML.
 */
function vance_ai_chats_md_inline( $text ) {
	$text = esc_html( (string) $text );
	$text = preg_replace( '/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text );
	$text = preg_replace_callback(
		'/(https?:\/\/[^\s<>]+)/',
		function ( $m ) {
			return '<a href="' . $m[1] . '" target="_blank" rel="noopener">' . $m[1] . '</a>';
		},
		$text
	);
	return $text;
}

/**
 * Block-level markdown-lite (paragraphs, bullet/numbered lists, tables) -> HTML.
 * Mirrors the renderer used to build the one-off chat-archive report so the
 * live page and that snapshot read identically.
 */
function vance_ai_chats_md_block( $text ) {
	$text = trim( str_replace( "\r\n", "\n", (string) $text ) );
	if ( '' === $text ) {
		return '';
	}

	$lines    = explode( "\n", $text );
	$n        = count( $lines );
	$out      = array();
	$para_buf = array();

	$flush_para = function () use ( &$para_buf, &$out ) {
		if ( ! empty( $para_buf ) ) {
			$joined = implode( '<br>', array_map( 'vance_ai_chats_md_inline', $para_buf ) );
			$out[]  = '<p>' . $joined . '</p>';
			$para_buf = array();
		}
	};

	$i = 0;
	while ( $i < $n ) {
		$line     = $lines[ $i ];
		$stripped = trim( $line );

		// Table: a "| a | b |" row followed by a "|---|---|" separator row.
		if ( 0 === strpos( $stripped, '|' ) && isset( $lines[ $i + 1 ] ) && preg_match( '/^\|?[\s:|-]+\|?$/', trim( $lines[ $i + 1 ] ) ) ) {
			$flush_para();
			$header = array_map( 'trim', explode( '|', trim( $stripped, '|' ) ) );
			$i     += 2;
			$rows   = array();
			while ( $i < $n && 0 === strpos( trim( $lines[ $i ] ), '|' ) ) {
				$rows[] = array_map( 'trim', explode( '|', trim( trim( $lines[ $i ] ), '|' ) ) );
				$i++;
			}
			$thead = '';
			foreach ( $header as $c ) {
				$thead .= '<th>' . vance_ai_chats_md_inline( $c ) . '</th>';
			}
			$tbody = '';
			foreach ( $rows as $r ) {
				$tbody .= '<tr>';
				foreach ( $r as $c ) {
					$tbody .= '<td>' . vance_ai_chats_md_inline( $c ) . '</td>';
				}
				$tbody .= '</tr>';
			}
			$out[] = '<div class="md-table-wrap"><table class="md-table"><thead><tr>' . $thead . '</tr></thead><tbody>' . $tbody . '</tbody></table></div>';
			continue;
		}

		// Bullet list.
		if ( preg_match( '/^(\*|-)\s+/', $stripped ) && ! preg_match( '/^\*\*/', $stripped ) ) {
			$flush_para();
			$items = '';
			while ( $i < $n && preg_match( '/^\s*(\*|-)\s+/', $lines[ $i ] ) && ! preg_match( '/^\s*\*\*/', $lines[ $i ] ) ) {
				$item_text = preg_replace( '/^\s*(\*|-)\s+/', '', $lines[ $i ] );
				$items    .= '<li>' . vance_ai_chats_md_inline( $item_text ) . '</li>';
				$i++;
			}
			$out[] = '<ul>' . $items . '</ul>';
			continue;
		}

		// Numbered list.
		if ( preg_match( '/^\d+\.\s+/', $stripped ) ) {
			$flush_para();
			$items = '';
			while ( $i < $n && preg_match( '/^\s*\d+\.\s+/', $lines[ $i ] ) ) {
				$item_text = preg_replace( '/^\s*\d+\.\s+/', '', $lines[ $i ] );
				$items    .= '<li>' . vance_ai_chats_md_inline( $item_text ) . '</li>';
				$i++;
			}
			$out[] = '<ol>' . $items . '</ol>';
			continue;
		}

		if ( '' === $stripped ) {
			$flush_para();
			$i++;
			continue;
		}

		$para_buf[] = $stripped;
		$i++;
	}
	$flush_para();

	return implode( "\n", $out );
}

/**
 * Legacy transcript shape: one HTML string, "<p><strong>Who:</strong><br>Msg</p>" per turn.
 */
function vance_ai_chats_parse_legacy( $raw ) {
	$msgs = array();
	if ( ! preg_match_all( '/<p><strong>(.*?):<\/strong><br>(.*?)<\/p>/s', (string) $raw, $matches, PREG_SET_ORDER ) ) {
		return $msgs;
	}
	foreach ( $matches as $m ) {
		$who  = trim( $m[1] );
		$body = preg_replace( '/<br\s*\/?>/i', "\n", $m[2] );
		$body = html_entity_decode( wp_strip_all_tags( $body ), ENT_QUOTES );
		$role = ( 'you' === strtolower( $who ) ) ? 'user' : 'assistant';
		$msgs[] = array(
			'role'    => $role,
			'content' => trim( $body ),
			'label'   => $who,
		);
	}
	return $msgs;
}

/**
 * Current transcript shape: array of {role, content}. Normalizes both shapes
 * to the same [{role, content, label}] list the renderer below expects.
 */
function vance_ai_chats_normalize_transcript( $transcript ) {
	if ( is_string( $transcript ) ) {
		return vance_ai_chats_parse_legacy( $transcript );
	}
	$out = array();
	if ( is_array( $transcript ) ) {
		foreach ( $transcript as $m ) {
			if ( ! is_array( $m ) ) {
				continue;
			}
			$role  = isset( $m['role'] ) && 'user' === $m['role'] ? 'user' : 'assistant';
			$out[] = array(
				'role'    => $role,
				'content' => isset( $m['content'] ) ? $m['content'] : '',
				'label'   => null,
			);
		}
	}
	return $out;
}

function vance_ai_chats_fmt_date( $d ) {
	if ( empty( $d ) ) {
		return '';
	}
	$ts = strtotime( $d );
	return $ts ? date_i18n( 'j M Y, H:i', $ts ) : esc_html( $d );
}

global $wpdb;
$vac_rows = $wpdb->get_results( "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = '_sla_saved_chats'" );

$vac_users          = array();
$vac_total_messages = 0;
$vac_total_chats    = 0;
$vac_all_dates      = array();

foreach ( $vac_rows as $row ) {
	$chats_raw = maybe_unserialize( $row->meta_value );
	if ( ! is_array( $chats_raw ) || empty( $chats_raw ) ) {
		continue;
	}

	$wp_user   = get_userdata( $row->user_id );
	$chats_out = array();

	foreach ( $chats_raw as $c ) {
		if ( ! is_array( $c ) ) {
			continue;
		}
		$msgs                = vance_ai_chats_normalize_transcript( isset( $c['transcript'] ) ? $c['transcript'] : array() );
		$vac_total_messages += count( $msgs );
		++$vac_total_chats;

		foreach ( array( $c['date'] ?? '', $c['updated'] ?? '' ) as $d ) {
			if ( $d ) {
				$vac_all_dates[] = $d;
			}
		}

		$chats_out[] = array(
			'title'    => ! empty( $c['title'] ) ? $c['title'] : __( 'Untitled conversation', 'vance-health-hub' ),
			'date'     => $c['date'] ?? '',
			'updated'  => $c['updated'] ?? '',
			'messages' => $msgs,
		);
	}

	usort(
		$chats_out,
		function ( $a, $b ) {
			return strcmp( $b['updated'] ?: $b['date'], $a['updated'] ?: $a['date'] );
		}
	);

	$vac_users[] = array(
		'user_id' => (int) $row->user_id,
		'login'   => $wp_user ? $wp_user->user_login : '',
		'email'   => $wp_user ? $wp_user->user_email : '',
		'name'    => $wp_user ? $wp_user->display_name : ( __( 'User', 'vance-health-hub' ) . ' #' . $row->user_id ),
		'chats'   => $chats_out,
	);
}

usort(
	$vac_users,
	function ( $a, $b ) {
		return count( $b['chats'] ) - count( $a['chats'] );
	}
);

$vac_date_range = '';
if ( ! empty( $vac_all_dates ) ) {
	sort( $vac_all_dates );
	$vac_date_range = vance_ai_chats_fmt_date( reset( $vac_all_dates ) ) . ' &ndash; ' . vance_ai_chats_fmt_date( end( $vac_all_dates ) );
}

get_header();
?>

<main id="main-content" class="vance-ai-chats-page">
<style>
.vance-ai-chats-page {
	--vac-bg: #F5F7F6;
	--vac-surface: #FFFFFF;
	--vac-surface-alt: #EAF1EF;
	--vac-ink: #16211F;
	--vac-ink-soft: #55635F;
	--vac-ink-faint: #8B9B96;
	--vac-border: #DCE3E1;
	--vac-accent: #0F7A72;
	--vac-accent-strong: #0B5C56;
	--vac-user-bubble: #E4F0EE;
	--vac-shadow: 0 1px 2px rgba(15, 33, 30, 0.04), 0 8px 24px -12px rgba(15, 33, 30, 0.12);
	--vac-mono: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
	background: var(--vac-bg);
	color: var(--vac-ink);
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif;
	line-height: 1.55;
	padding: 0 0 96px;
}
@media (prefers-color-scheme: dark) {
	.vance-ai-chats-page {
		--vac-bg: #0D1413;
		--vac-surface: #141D1B;
		--vac-surface-alt: #1B2624;
		--vac-ink: #E7EEEC;
		--vac-ink-soft: #9FB0AC;
		--vac-ink-faint: #6B7C78;
		--vac-border: #263330;
		--vac-accent: #4FD3C4;
		--vac-accent-strong: #7BE0D3;
		--vac-user-bubble: #1D2E2B;
		--vac-shadow: 0 1px 2px rgba(0,0,0,0.3), 0 8px 24px -12px rgba(0,0,0,0.5);
	}
}
.vance-ai-chats-page * { box-sizing: border-box; }
.vance-ai-chats-page a { color: var(--vac-accent-strong); }
.vance-ai-chats-page .page { max-width: 860px; margin: 0 auto; padding: 0 20px; }
.vance-ai-chats-page .banner { padding: 48px 0 28px; border-bottom: 1px solid var(--vac-border); }
.vance-ai-chats-page .eyebrow {
	font-family: var(--vac-mono); font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase;
	color: var(--vac-accent-strong); margin: 0 0 10px;
}
.vance-ai-chats-page h1 { font-size: 30px; font-weight: 650; letter-spacing: -0.01em; margin: 0 0 10px; text-wrap: balance; }
.vance-ai-chats-page .banner-sub { color: var(--vac-ink-soft); font-size: 15px; max-width: 62ch; margin: 0 0 26px; }
.vance-ai-chats-page .stat-row {
	display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: var(--vac-border);
	border: 1px solid var(--vac-border); border-radius: var(--radius-surface, 24px); overflow: hidden;
}
.vance-ai-chats-page .stat { background: var(--vac-surface); padding: 14px 16px; }
.vance-ai-chats-page .stat-num { font-family: var(--vac-mono); font-size: 22px; font-weight: 600; font-variant-numeric: tabular-nums; }
.vance-ai-chats-page .stat-label { font-size: 11.5px; color: var(--vac-ink-faint); text-transform: uppercase; letter-spacing: 0.06em; margin-top: 2px; }
.vance-ai-chats-page .controls {
	position: sticky; top: 0; z-index: 10; background: color-mix(in srgb, var(--vac-bg) 92%, transparent);
	backdrop-filter: blur(10px); padding: 14px 0; border-bottom: 1px solid var(--vac-border); margin-bottom: 28px;
}
.vance-ai-chats-page .controls-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.vance-ai-chats-page .search-box {
	flex: 1 1 220px; border: 1px solid var(--vac-border); background: var(--vac-surface); border-radius: var(--radius-field, 16px);
	padding: 9px 12px; font-size: 14px; color: var(--vac-ink); font-family: inherit;
}
.vance-ai-chats-page .search-box:focus { outline: 2px solid var(--vac-accent); outline-offset: -1px; }
.vance-ai-chats-page .btn {
	border: 1px solid var(--vac-border); background: var(--vac-surface); color: var(--vac-ink-soft); font-size: 13px;
	padding: 8px 12px; border-radius: var(--radius-control, 10px); cursor: pointer; font-family: inherit;
}
.vance-ai-chats-page .btn:hover { color: var(--vac-ink); border-color: var(--vac-accent); }
.vance-ai-chats-page .chip-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.vance-ai-chats-page .chip {
	display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--vac-border); background: var(--vac-surface);
	border-radius: var(--radius-pill, 999px); padding: 5px 6px 5px 12px; font-size: 12.5px; color: var(--vac-ink-soft); text-decoration: none;
}
.vance-ai-chats-page .chip:hover { border-color: var(--vac-accent); color: var(--vac-ink); }
.vance-ai-chats-page .chip-count {
	background: var(--vac-surface-alt); color: var(--vac-accent-strong); font-family: var(--vac-mono); font-size: 11px;
	border-radius: var(--radius-pill, 999px); padding: 2px 7px;
}
.vance-ai-chats-page .user-section { margin-bottom: 44px; scroll-margin-top: 90px; }
.vance-ai-chats-page .user-head { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
.vance-ai-chats-page .user-id-badge {
	font-family: var(--vac-mono); font-size: 12px; color: var(--vac-ink-faint); border: 1px solid var(--vac-border);
	border-radius: var(--radius-control, 10px); width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.vance-ai-chats-page .user-info h2 { font-size: 18px; margin: 0; font-weight: 650; }
.vance-ai-chats-page .user-sub { font-family: var(--vac-mono); font-size: 12px; color: var(--vac-ink-faint); margin-top: 2px; }
.vance-ai-chats-page .sep { opacity: 0.5; }
.vance-ai-chats-page .user-count { margin-left: auto; font-size: 12px; color: var(--vac-ink-faint); white-space: nowrap; }
.vance-ai-chats-page .chat-list { display: flex; flex-direction: column; gap: 10px; }
.vance-ai-chats-page .chat-card { background: var(--vac-surface); border: 1px solid var(--vac-border); border-radius: var(--radius-surface, 24px); box-shadow: var(--vac-shadow); overflow: hidden; }
.vance-ai-chats-page .chat-head {
	width: 100%; display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: none; border: none;
	cursor: pointer; text-align: left; font-family: inherit; color: inherit;
}
.vance-ai-chats-page .chat-head:focus-visible { outline: 2px solid var(--vac-accent); outline-offset: -2px; }
.vance-ai-chats-page .chat-head-main { flex: 1; min-width: 0; }
.vance-ai-chats-page .chat-title { font-size: 14.5px; font-weight: 600; margin: 0 0 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.vance-ai-chats-page .chat-meta { font-family: var(--vac-mono); font-size: 11.5px; color: var(--vac-ink-faint); }
.vance-ai-chats-page .chat-meta .dot { margin: 0 2px; }
.vance-ai-chats-page .chat-toggle { flex-shrink: 0; width: 18px; height: 18px; position: relative; }
.vance-ai-chats-page .chat-toggle::before, .vance-ai-chats-page .chat-toggle::after { content: ""; position: absolute; background: var(--vac-ink-faint); border-radius: var(--radius-control, 10px); }
.vance-ai-chats-page .chat-toggle::before { width: 10px; height: 2px; top: 8px; left: 4px; }
.vance-ai-chats-page .chat-toggle::after { width: 2px; height: 10px; top: 4px; left: 8px; transition: transform 0.15s ease; }
.vance-ai-chats-page .chat-head[aria-expanded="true"] .chat-toggle::after { transform: rotate(90deg); }
.vance-ai-chats-page .chat-transcript { display: none; padding: 4px 16px 18px; border-top: 1px solid var(--vac-border); }
.vance-ai-chats-page .chat-card.open .chat-transcript { display: block; }
.vance-ai-chats-page .msg { padding: 14px 0; border-bottom: 1px dashed var(--vac-border); }
.vance-ai-chats-page .msg:last-child { border-bottom: none; padding-bottom: 4px; }
.vance-ai-chats-page .msg-role { font-family: var(--vac-mono); font-size: 10.5px; letter-spacing: 0.07em; text-transform: uppercase; margin-bottom: 6px; font-weight: 600; }
.vance-ai-chats-page .msg-user .msg-role { color: var(--vac-accent-strong); }
.vance-ai-chats-page .msg-assistant .msg-role { color: var(--vac-ink-faint); }
.vance-ai-chats-page .msg-user .msg-body { background: var(--vac-user-bubble); border-radius: var(--radius-surface, 24px); padding: 10px 12px; }
.vance-ai-chats-page .msg-body { font-size: 14px; color: var(--vac-ink); }
.vance-ai-chats-page .msg-body p { margin: 0 0 10px; }
.vance-ai-chats-page .msg-body p:last-child { margin-bottom: 0; }
.vance-ai-chats-page .msg-body ul, .vance-ai-chats-page .msg-body ol { margin: 0 0 10px; padding-left: 20px; }
.vance-ai-chats-page .msg-body li { margin-bottom: 4px; }
.vance-ai-chats-page .md-table-wrap { overflow-x: auto; margin: 0 0 10px; }
.vance-ai-chats-page .md-table { border-collapse: collapse; font-size: 13px; width: 100%; }
.vance-ai-chats-page .md-table th, .vance-ai-chats-page .md-table td { border: 1px solid var(--vac-border); padding: 6px 9px; text-align: left; }
.vance-ai-chats-page .md-table th { background: var(--vac-surface-alt); font-weight: 600; }
.vance-ai-chats-page .hidden { display: none !important; }
.vance-ai-chats-page .empty-state { text-align: center; color: var(--vac-ink-faint); padding: 60px 0; font-size: 14px; }
.vance-ai-chats-page .footer-note { margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--vac-border); color: var(--vac-ink-faint); font-size: 12px; font-family: var(--vac-mono); }
@media (max-width: 560px) {
	.vance-ai-chats-page .stat-row { grid-template-columns: repeat(2, 1fr); }
	.vance-ai-chats-page h1 { font-size: 24px; }
	.vance-ai-chats-page .user-count { display: none; }
}
</style>

<div class="page">
	<div class="banner">
		<p class="eyebrow"><?php esc_html_e( 'Vance Medical Hub &middot; Admin only', 'vance-health-hub' ); ?></p>
		<h1><?php esc_html_e( 'VANCE-ai Chat Archive', 'vance-health-hub' ); ?></h1>
		<p class="banner-sub"><?php esc_html_e( 'Every conversation saved to a user’s dashboard, queried live from user meta. Visible to administrators only.', 'vance-health-hub' ); ?></p>
		<div class="stat-row">
			<div class="stat"><div class="stat-num"><?php echo (int) count( $vac_users ); ?></div><div class="stat-label"><?php esc_html_e( 'Users', 'vance-health-hub' ); ?></div></div>
			<div class="stat"><div class="stat-num"><?php echo (int) $vac_total_chats; ?></div><div class="stat-label"><?php esc_html_e( 'Conversations', 'vance-health-hub' ); ?></div></div>
			<div class="stat"><div class="stat-num"><?php echo (int) $vac_total_messages; ?></div><div class="stat-label"><?php esc_html_e( 'Messages', 'vance-health-hub' ); ?></div></div>
			<div class="stat"><div class="stat-num" style="font-size:14px; padding-top:4px;"><?php echo $vac_date_range ? wp_kses( $vac_date_range, array() ) : '&mdash;'; ?></div><div class="stat-label"><?php esc_html_e( 'Date range', 'vance-health-hub' ); ?></div></div>
		</div>
	</div>

	<?php if ( empty( $vac_users ) ) : ?>

		<p class="empty-state"><?php esc_html_e( 'No saved conversations yet.', 'vance-health-hub' ); ?></p>

	<?php else : ?>

		<div class="controls">
			<div class="controls-row">
				<input class="search-box" id="vac-search" type="search" placeholder="<?php esc_attr_e( 'Search titles, messages, users&hellip;', 'vance-health-hub' ); ?>" autocomplete="off">
				<button class="btn" id="vac-expand-all" type="button"><?php esc_html_e( 'Expand all', 'vance-health-hub' ); ?></button>
				<button class="btn" id="vac-collapse-all" type="button"><?php esc_html_e( 'Collapse all', 'vance-health-hub' ); ?></button>
			</div>
			<div class="chip-row">
				<?php foreach ( $vac_users as $u ) : ?>
					<a class="chip" href="#user-<?php echo (int) $u['user_id']; ?>">
						<span class="chip-name"><?php echo esc_html( $u['name'] ); ?></span>
						<span class="chip-count"><?php echo (int) count( $u['chats'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<div id="vac-sections">
			<?php foreach ( $vac_users as $u ) : ?>
				<section class="user-section" id="user-<?php echo (int) $u['user_id']; ?>">
					<header class="user-head">
						<div class="user-id-badge"><?php echo (int) $u['user_id']; ?></div>
						<div class="user-info">
							<h2><?php echo esc_html( $u['name'] ); ?></h2>
							<div class="user-sub"><?php echo esc_html( $u['login'] ); ?> <span class="sep">&middot;</span> <?php echo esc_html( $u['email'] ); ?></div>
						</div>
						<div class="user-count">
							<?php
							printf(
								/* translators: %d: number of saved conversations */
								esc_html( _n( '%d conversation', '%d conversations', count( $u['chats'] ), 'vance-health-hub' ) ),
								(int) count( $u['chats'] )
							);
							?>
						</div>
					</header>
					<div class="chat-list">
						<?php foreach ( $u['chats'] as $c ) : ?>
							<article class="chat-card">
								<button class="chat-head" type="button" aria-expanded="false">
									<div class="chat-head-main">
										<h3 class="chat-title"><?php echo esc_html( $c['title'] ); ?></h3>
										<div class="chat-meta">
											<span class="meta-item"><?php echo (int) count( $c['messages'] ); ?> <?php esc_html_e( 'messages', 'vance-health-hub' ); ?></span>
											<span class="dot">&middot;</span>
											<span class="meta-item"><?php esc_html_e( 'created', 'vance-health-hub' ); ?> <?php echo esc_html( vance_ai_chats_fmt_date( $c['date'] ) ); ?></span>
											<?php if ( $c['updated'] && $c['updated'] !== $c['date'] ) : ?>
												<span class="dot">&middot;</span>
												<span class="meta-item"><?php esc_html_e( 'updated', 'vance-health-hub' ); ?> <?php echo esc_html( vance_ai_chats_fmt_date( $c['updated'] ) ); ?></span>
											<?php endif; ?>
										</div>
									</div>
									<span class="chat-toggle" aria-hidden="true"></span>
								</button>
								<div class="chat-transcript">
									<?php foreach ( $c['messages'] as $m ) : ?>
										<?php $role_label = $m['label'] ? $m['label'] : ( 'user' === $m['role'] ? __( 'You', 'vance-health-hub' ) : 'VANCE-ai' ); ?>
										<div class="msg msg-<?php echo esc_attr( $m['role'] ); ?>">
											<div class="msg-role"><?php echo esc_html( $role_label ); ?></div>
											<div class="msg-body"><?php echo wp_kses_post( vance_ai_chats_md_block( $m['content'] ) ); ?></div>
										</div>
									<?php endforeach; ?>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endforeach; ?>
		</div>
		<p class="empty-state hidden" id="vac-empty-state"></p>

	<?php endif; ?>

	<div class="footer-note"><?php esc_html_e( 'Live query against wp_usermeta._sla_saved_chats. Refresh to see new conversations.', 'vance-health-hub' ); ?></div>
</div>

<script>
(function () {
	document.querySelectorAll( '.vance-ai-chats-page .chat-head' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var card = btn.closest( '.chat-card' );
			var open = card.classList.toggle( 'open' );
			btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );
	} );

	var expandAll = document.getElementById( 'vac-expand-all' );
	var collapseAll = document.getElementById( 'vac-collapse-all' );
	if ( expandAll ) {
		expandAll.addEventListener( 'click', function () {
			document.querySelectorAll( '.vance-ai-chats-page .chat-card' ).forEach( function ( c ) {
				c.classList.add( 'open' );
				c.querySelector( '.chat-head' ).setAttribute( 'aria-expanded', 'true' );
			} );
		} );
	}
	if ( collapseAll ) {
		collapseAll.addEventListener( 'click', function () {
			document.querySelectorAll( '.vance-ai-chats-page .chat-card' ).forEach( function ( c ) {
				c.classList.remove( 'open' );
				c.querySelector( '.chat-head' ).setAttribute( 'aria-expanded', 'false' );
			} );
		} );
	}

	var searchBox = document.getElementById( 'vac-search' );
	var emptyState = document.getElementById( 'vac-empty-state' );
	if ( ! searchBox ) {
		return;
	}

	searchBox.addEventListener( 'input', function () {
		var q = searchBox.value.trim().toLowerCase();
		var anyVisible = false;

		document.querySelectorAll( '.vance-ai-chats-page .user-section' ).forEach( function ( section ) {
			var sectionHasMatch = false;
			var userName = section.querySelector( '.user-info h2' ).textContent.toLowerCase();
			var userSub = section.querySelector( '.user-sub' ).textContent.toLowerCase();
			var userMatches = userName.indexOf( q ) !== -1 || userSub.indexOf( q ) !== -1;

			section.querySelectorAll( '.chat-card' ).forEach( function ( card ) {
				var text = card.textContent.toLowerCase();
				var match = q === '' || userMatches || text.indexOf( q ) !== -1;
				card.classList.toggle( 'hidden', ! match );
				if ( match ) {
					sectionHasMatch = true;
				}
			} );

			section.classList.toggle( 'hidden', ! sectionHasMatch );
			if ( sectionHasMatch ) {
				anyVisible = true;
			}
		} );

		if ( emptyState ) {
			emptyState.textContent = q === '' ? '' : 'No conversations match “' + searchBox.value.trim() + '”.';
			emptyState.classList.toggle( 'hidden', anyVisible || q === '' );
		}
	} );
})();
</script>

</main>

<?php get_footer(); ?>
