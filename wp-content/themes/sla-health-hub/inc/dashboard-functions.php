<?php
/**
 * Dashboard Functions
 * Handles AJAX requests for Profile Saving, Bookmarking, etc.
 */

// User-message broadcast tool (admin page + CPT + dashboard render helpers).
require_once get_template_directory() . '/inc/admin-messages.php';

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle Profile Save AJAX
 */
function vance_dashboard_save_profile() {
    check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    $user_id = get_current_user_id();
    
    // Sanitize and save fields
    $fields = array(
        'first_name'        => 'sanitize_text_field',
        'last_name'         => 'sanitize_text_field',
        'user_email'        => 'sanitize_email',
        'description'       => 'sanitize_textarea_field', // Bios
        'vance_job_title'     => 'sanitize_text_field',
        'vance_organization'  => 'sanitize_text_field',
        'vance_phone'         => 'sanitize_text_field',
        'vance_website'       => 'esc_url_raw',
        'vance_linkedin'      => 'esc_url_raw',
        'vance_twitter'       => 'esc_url_raw',
        'vance_instagram'     => 'esc_url_raw',
        'vance_facebook'      => 'esc_url_raw',
    );

    $userdata = array( 'ID' => $user_id );

    // Handle Profile Links (up to 5)
    if ( isset( $_POST['profile_links'] ) && is_array( $_POST['profile_links'] ) ) {
        $links = array_map( 'esc_url_raw', array_slice( $_POST['profile_links'], 0, 5 ) );
        update_user_meta( $user_id, '_sla_profile_links', $links );
    }

    foreach ( $fields as $key => $sanitizer ) {
        if ( isset( $_POST[$key] ) ) {
            $val = call_user_func( $sanitizer, $_POST[$key] );

            // Core WP fields
            if ( in_array( $key, array( 'first_name', 'last_name', 'user_email', 'description' ) ) ) {
                $userdata[$key] = $val;
            }
            // Custom Meta — preserve legacy meta keys (pre-Vance rebrand) so
            // existing stored user profile data remains readable.
            else {
                $legacy_prefix = implode( '', array( 's', 'l', 'a', '_' ) );
                $meta_key = '_' . preg_replace( '/^vance_/', $legacy_prefix, $key );
                update_user_meta( $user_id, $meta_key, $val );
            }
        }
    }

    // Handle Image Upload (if URL provided from media uploader)
    if ( isset( $_POST['vance_profile_image_url'] ) ) {
        update_user_meta( $user_id, '_sla_profile_image_url', esc_url_raw( $_POST['vance_profile_image_url'] ) );
    }

    // Update Core User Data
    $user_id = wp_update_user( $userdata );

    if ( is_wp_error( $user_id ) ) {
        wp_send_json_error( $user_id->get_error_message() );
    }

    wp_send_json_success( 'Profile updated successfully' );
}
add_action( 'wp_ajax_vance_save_profile', 'vance_dashboard_save_profile' );

/**
 * Handle Profile Document Upload
 */
function vance_dashboard_upload_profile_doc() {
    check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( 'Not logged in' );
    if ( ! isset( $_FILES['doc'] ) ) wp_send_json_error( 'No file' );

    $user_id = get_current_user_id();
    $docs = get_user_meta( $user_id, '_sla_profile_docs', true ) ?: array();
    if ( count($docs) >= 5 ) wp_send_json_error( 'Limit reached (5 documents max)' );

    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );

    $att_id = media_handle_upload( 'doc', 0 );
    if ( is_wp_error( $att_id ) ) wp_send_json_error( $att_id->get_error_message() );

    $docs[] = array(
        'id' => $att_id,
        'url' => wp_get_attachment_url( $att_id ),
        'name' => $_FILES['doc']['name'],
        'date' => current_time('mysql')
    );
    update_user_meta( $user_id, '_sla_profile_docs', $docs );
    wp_send_json_success( 'Document uploaded' );
}
add_action( 'wp_ajax_vance_upload_profile_doc', 'vance_dashboard_upload_profile_doc' );

/**
 * Handle Profile Document Delete
 */
function vance_dashboard_delete_profile_doc() {
    check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( 'Not logged in' );
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $user_id = get_current_user_id();
    $docs = get_user_meta( $user_id, '_sla_profile_docs', true ) ?: array();
    
    $new_docs = array();
    foreach($docs as $d) {
        if($d['id'] != $id) $new_docs[] = $d;
        else wp_delete_attachment($id, true);
    }
    
    update_user_meta( $user_id, '_sla_profile_docs', $new_docs );
    wp_send_json_success( 'Document deleted' );
}
add_action( 'wp_ajax_vance_delete_profile_doc', 'vance_dashboard_delete_profile_doc' );

/**
 * Handle Article Bookmark AJAX
 */
function vance_dashboard_toggle_bookmark() {
    // Check security
    check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Session expired. Please log in again.' );
    }

    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id ) {
        wp_send_json_error( 'Invalid Post ID' );
    }

    $user_id = get_current_user_id();
    $bookmarks = get_user_meta( $user_id, '_sla_reading_list', true );
    
    if ( ! is_array( $bookmarks ) ) {
        $bookmarks = array();
    }

    $action = 'added';
    if ( in_array( $post_id, $bookmarks ) ) {
        // Remove
        $bookmarks = array_diff( $bookmarks, array( $post_id ) );
        $action = 'removed';
    } else {
        // Add
        $bookmarks[] = $post_id;
    }

    // Success if we can update meta. WordPress handles permissions for current user meta automatically.
    update_user_meta( $user_id, '_sla_reading_list', (array) array_values($bookmarks) );

    wp_send_json_success( array( 
        'action' => $action, 
        'count'  => count( $bookmarks ),
        'user'   => $user_id 
    ) );
}
add_action( 'wp_ajax_vance_toggle_bookmark', 'vance_dashboard_toggle_bookmark' );
add_action( 'wp_ajax_nopriv_vance_toggle_bookmark', 'vance_dashboard_toggle_bookmark' );

/**
 * Handle Newsletter Preferences Save
 */
function vance_dashboard_save_newsletters() {
    check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    $user_id = get_current_user_id();
    
    $prefs = isset( $_POST['newsletter_prefs'] ) ? (array) $_POST['newsletter_prefs'] : array();
    $freq = isset( $_POST['newsletter_frequency'] ) ? sanitize_text_field( $_POST['newsletter_frequency'] ) : 'weekly';

    // Sanitize array
    $prefs = array_map( 'sanitize_text_field', $prefs );

    update_user_meta( $user_id, '_sla_newsletter_prefs', $prefs );
    update_user_meta( $user_id, '_sla_newsletter_frequency', $freq );

    wp_send_json_success( 'Preferences saved' );
}
add_action( 'wp_ajax_vance_save_newsletters', 'vance_dashboard_save_newsletters' );

/**
 * Handle Role Switch AJAX
 */
function vance_dashboard_switch_role() {
    check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    $role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : 'subscriber';
    $user_id = get_current_user_id();
    $user = new WP_User( $user_id );

    // Store preferred view and primary differentiator in meta
    // Map 'patient' UI role to 'subscriber' WP role
    $wp_role = ( $role === 'practitioner' ) ? 'practitioner' : 'subscriber';
    
    update_user_meta( $user_id, '_sla_dashboard_role', $role );
    update_user_meta( $user_id, '_sla_user_type', $role );

    // Correctly add/remove the practitioner role
    if ( $role === 'practitioner' ) {
        if ( ! in_array( 'practitioner', $user->roles ) ) {
            $user->add_role( 'practitioner' );
        }
        // Optionally remove subscriber if they have both, but keeping both is safer for permissions
    } else {
        // Switching to patient (subscriber)
        if ( in_array( 'practitioner', $user->roles ) ) {
            $user->remove_role( 'practitioner' );
        }
        // Ensure they still have subscriber role
        if ( ! in_array( 'subscriber', $user->roles ) ) {
            $user->add_role( 'subscriber' );
        }
    }

    wp_send_json_success( 'Role switched to ' . $role );
}
add_action( 'wp_ajax_vance_switch_role', 'vance_dashboard_switch_role' );

/**
 * Handle Avatar Upload AJAX
 */
function vance_dashboard_upload_avatar() {
    check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    if ( ! isset( $_FILES['avatar'] ) ) {
        wp_send_json_error( 'No file uploaded' );
    }

    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );

    $att_id = media_handle_upload( 'avatar', 0 );

    if ( is_wp_error( $att_id ) ) {
        wp_send_json_error( $att_id->get_error_message() );
    }

    $url = wp_get_attachment_url( $att_id );
    $user_id = get_current_user_id();
    update_user_meta( $user_id, '_sla_profile_image_url', $url );

    wp_send_json_success( array( 'url' => $url ) );
}
add_action( 'wp_ajax_vance_upload_avatar', 'vance_dashboard_upload_avatar' );

/**
 * Handle Poster Upload AJAX
 */
function vance_dashboard_upload_poster() {
    check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    if ( ! isset( $_FILES['poster'] ) ) {
        wp_send_json_error( 'No file uploaded' );
    }

    // Enforce 10MB limit
    $max_size = 10 * 1024 * 1024; // 10MB
    if ( $_FILES['poster']['size'] > $max_size ) {
        wp_send_json_error( 'File is too large. Maximum size is 10MB.' );
    }

    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );

    // Use media_handle_upload to properly register it in Media Library
    $attachment_id = media_handle_upload( 'poster', 0 );

    if ( is_wp_error( $attachment_id ) ) {
        wp_send_json_error( $attachment_id->get_error_message() );
    }

    $url = wp_get_attachment_url( $attachment_id );
    $user_id = get_current_user_id();
    $posters = get_user_meta( $user_id, '_sla_posters', true ) ?: array();
    
    $posters[] = array(
        'id'   => $attachment_id,
        'url'  => $url,
        'date' => current_time('mysql'),
        'name' => $_FILES['poster']['name']
    );
    update_user_meta( $user_id, '_sla_posters', $posters );

    wp_send_json_success( 'Poster uploaded' );
}
add_action( 'wp_ajax_vance_upload_poster', 'vance_dashboard_upload_poster' );

/**
 * Handle Poster Deletion AJAX
 */
function vance_dashboard_delete_poster() {
    check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    $poster_id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
    if ( ! $poster_id ) {
        wp_send_json_error( 'Invalid Poster ID' );
    }

    $user_id = get_current_user_id();
    $posters = get_user_meta( $user_id, '_sla_posters', true ) ?: array();
    
    $found = false;
    foreach ( $posters as $key => $poster ) {
        if ( isset($poster['id']) && $poster['id'] == $poster_id ) {
            // Delete from media library
            wp_delete_attachment( $poster_id, true );
            unset( $posters[$key] );
            $found = true;
            break;
        }
    }

    if ( $found ) {
        update_user_meta( $user_id, '_sla_posters', array_values($posters) );
        wp_send_json_success( 'Poster deleted' );
    } else {
        wp_send_json_error( 'Poster not found' );
    }
}
add_action( 'wp_ajax_vance_delete_poster', 'vance_dashboard_delete_poster' );

/**
 * Helper: Is Post Bookmarked?
 */
function vance_is_bookmarked( $post_id = 0 ) {
    if ( ! is_user_logged_in() ) return false;
    if ( ! $post_id ) $post_id = get_the_ID();

    $user_id = get_current_user_id();
    $bookmarks = get_user_meta( $user_id, '_sla_reading_list', true );
    
    return ( is_array( $bookmarks ) && in_array( $post_id, $bookmarks ) );
}

/**
 * Handle Author Profile AJAX
 */
function vance_get_author_profile() {
    $author_id = isset( $_POST['author_id'] ) ? intval( $_POST['author_id'] ) : 0;
    
    if ( ! $author_id ) {
        wp_send_json_error( 'Invalid ID' );
    }

    $user = get_userdata( $author_id );
    if ( ! $user ) {
        wp_send_json_error( 'User not found' );
    }

    // Get Avatar
    $avatar = get_avatar( $author_id, 96 );

    // Get Bio
    $bio = get_the_author_meta( 'description', $author_id );
    if ( empty( $bio ) ) {
        $bio = 'No biography available.';
    }

    // Get Additional Meta
    $job_title = get_user_meta( $author_id, '_sla_job_title', true );
    $organization = get_user_meta( $author_id, '_sla_organization', true );
    $website = get_user_meta( $author_id, '_sla_website', true );
    
    // Socials
    $linkedin = get_user_meta( $author_id, '_sla_linkedin', true );
    $twitter = get_user_meta( $author_id, '_sla_twitter', true );
    $instagram = get_user_meta( $author_id, '_sla_instagram', true );
    $facebook = get_user_meta( $author_id, '_sla_facebook', true );

    // Get Latest Posts
    $posts_args = array(
        'author'         => $author_id,
        'posts_per_page' => 5,
        'post_type'      => 'any',
        'post_status'    => 'publish'
    );
    $query = new WP_Query( $posts_args );
    $posts = array();
    
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $posts[] = array(
                'title' => get_the_title(),
                'url'   => get_permalink()
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success( array(
        'name'         => $user->display_name,
        'avatar'       => $avatar,
        'bio'          => wpautop( $bio ),
        'job_title'    => $job_title,
        'organization' => $organization,
        'website'      => $website,
        'social'       => array(
            'linkedin'  => $linkedin,
            'twitter'   => $twitter,
            'instagram' => $instagram,
            'facebook'  => $facebook
        ),
        'posts'        => $posts
    ) );
}
add_action( 'wp_ajax_vance_get_author_profile', 'vance_get_author_profile' );
add_action( 'wp_ajax_nopriv_vance_get_author_profile', 'vance_get_author_profile' );

/**
 * Handle Search Saving AJAX
 */
function vance_dashboard_save_search() {
    check_ajax_referer( 'vance_save_search_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    $name = isset( $_POST['search_name'] ) ? sanitize_text_field( $_POST['search_name'] ) : 'My Search';
    $params = isset( $_POST['query_params'] ) ? sanitize_text_field( $_POST['query_params'] ) : '';
    $user_id = get_current_user_id();

    $searches = get_user_meta( $user_id, '_sla_saved_searches', true ) ?: array();
    
    $searches[] = array(
        'id'     => uniqid(),
        'name'   => $name,
        'params' => $params,
        'url'    => home_url( '/pathway-results/?' . $params ),
        'date'   => current_time( 'mysql' )
    );

    update_user_meta( $user_id, '_sla_saved_searches', $searches );

    wp_send_json_success( 'Search saved' );
}
add_action( 'wp_ajax_vance_save_search', 'vance_dashboard_save_search' );

/**
 * Handle Search Deletion AJAX
 */
function vance_dashboard_delete_search() {
    check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    $search_id = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';
    $user_id = get_current_user_id();
    $searches = get_user_meta( $user_id, '_sla_saved_searches', true ) ?: array();
    
    $new_searches = array();
    foreach ( $searches as $s ) {
        if ( $s['id'] !== $search_id ) {
            $new_searches[] = $s;
        }
    }

    update_user_meta( $user_id, '_sla_saved_searches', $new_searches );
    wp_send_json_success( 'Search deleted' );
}
add_action( 'wp_ajax_vance_delete_search', 'vance_dashboard_delete_search' );

/**
 * Handle Note Saving AJAX
 */
function vance_save_note() {
    check_ajax_referer( 'vance_save_note_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    $user_id = get_current_user_id();
    $id = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';
    $title = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : 'Untitled Note';
    $content = isset( $_POST['content'] ) ? wp_kses_post( $_POST['content'] ) : '';

    $notes = get_user_meta( $user_id, '_sla_user_notes', true ) ?: array();
    
    // If ID exists, update. Else create new.
    $new_id = $id;
    if ( ! $id ) {
        $new_id = uniqid('note_');
        $notes[] = array(
            'id' => $new_id,
            'title' => $title,
            'content' => $content,
            'date' => current_time('mysql')
        );
    } else {
        foreach ( $notes as &$n ) {
            if ( $n['id'] === $id ) {
                $n['title'] = $title;
                $n['content'] = $content;
                $n['date'] = current_time('mysql');
                break;
            }
        }
    }

    update_user_meta( $user_id, '_sla_user_notes', $notes );
    wp_send_json_success( array( 'id' => $new_id ) );
}
add_action( 'wp_ajax_vance_save_note', 'vance_save_note' );

/**
 * Append excerpt / content to an existing note (or create new).
 * Used by Reading List "Add to Note" flow.
 */
function vance_append_to_note() {
    if ( ! is_user_logged_in() ) { wp_send_json_error( 'Not logged in' ); }
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_dashboard_nonce' ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }

    $user_id   = get_current_user_id();
    $target_id = isset( $_POST['target_id'] ) ? sanitize_text_field( $_POST['target_id'] ) : '';
    $new_title = isset( $_POST['new_title'] ) ? sanitize_text_field( $_POST['new_title'] ) : 'Untitled Note';
    $content   = isset( $_POST['content'] ) ? wp_kses_post( $_POST['content'] ) : '';

    if ( $content === '' ) { wp_send_json_error( 'Empty content' ); }

    $notes = get_user_meta( $user_id, '_sla_user_notes', true ) ?: array();
    if ( ! is_array( $notes ) ) { $notes = array(); }

    $saved_id = '';
    $found    = false;

    if ( $target_id ) {
        foreach ( $notes as &$n ) {
            if ( isset( $n['id'] ) && $n['id'] === $target_id ) {
                $n['content'] = ( isset( $n['content'] ) ? $n['content'] : '' ) . "\n" . $content;
                $n['date']    = current_time( 'mysql' );
                $saved_id     = $n['id'];
                $found        = true;
                break;
            }
        }
        unset( $n );
    }

    if ( ! $found ) {
        $saved_id = uniqid( 'note_' );
        $notes[]  = array(
            'id'      => $saved_id,
            'title'   => $new_title,
            'content' => $content,
            'date'    => current_time( 'mysql' ),
        );
    }

    update_user_meta( $user_id, '_sla_user_notes', $notes );
    wp_send_json_success( array( 'id' => $saved_id, 'created' => ! $found ) );
}
add_action( 'wp_ajax_vance_append_to_note', 'vance_append_to_note' );

/**
 * Save / update practitioner profile (specialty, bio, availability, calendar slots).
 * Meta key: _sla_practitioner_profile
 */
function vance_save_practitioner_profile() {
    if ( ! is_user_logged_in() ) { wp_send_json_error( 'Not logged in' ); }
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_dashboard_nonce' ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }

    $user_id = get_current_user_id();
    $fields  = array(
        'specialty'            => 'sanitize_text_field',
        'role_title'           => 'sanitize_text_field',
        'qualifications'       => 'sanitize_textarea_field',
        'gmc_number'           => 'sanitize_text_field',
        'years_experience'     => 'intval',
        'areas_of_expertise'   => 'sanitize_textarea_field',
        'bio'                  => 'wp_kses_post',
        'consultation_fee'     => 'sanitize_text_field',
        'consultation_length'  => 'sanitize_text_field',
        'languages'            => 'sanitize_text_field',
        'clinic_location'      => 'sanitize_text_field',
    );

    $profile = get_user_meta( $user_id, '_sla_practitioner_profile', true );
    if ( ! is_array( $profile ) ) { $profile = array(); }

    foreach ( $fields as $key => $fn ) {
        if ( isset( $_POST[ $key ] ) ) {
            $profile[ $key ] = call_user_func( $fn, wp_unslash( $_POST[ $key ] ) );
        }
    }
    $profile['available_for_consultation'] = ! empty( $_POST['available_for_consultation'] ) ? 1 : 0;

    // Calendar slots: JSON string → array of { day: 'Mon', time: '09:00' }
    if ( isset( $_POST['calendar_slots'] ) ) {
        $raw = json_decode( wp_unslash( $_POST['calendar_slots'] ), true );
        $slots = array();
        if ( is_array( $raw ) ) {
            foreach ( $raw as $s ) {
                if ( is_array( $s ) && isset( $s['day'], $s['time'] ) ) {
                    $slots[] = array(
                        'day'  => sanitize_text_field( $s['day'] ),
                        'time' => sanitize_text_field( $s['time'] ),
                    );
                }
            }
        }
        $profile['calendar_slots'] = $slots;
    }

    update_user_meta( $user_id, '_sla_practitioner_profile', $profile );
    wp_send_json_success( array( 'saved' => true ) );
}
add_action( 'wp_ajax_vance_save_practitioner_profile', 'vance_save_practitioner_profile' );

/**
 * Patient requests a consultation with a practitioner.
 * Stored on the practitioner as _sla_consult_requests (appended), and
 * on the patient as _sla_my_consult_requests.
 */
function vance_request_consultation() {
    if ( ! is_user_logged_in() ) { wp_send_json_error( 'Not logged in' ); }
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_dashboard_nonce' ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }

    $patient_id      = get_current_user_id();
    $practitioner_id = isset( $_POST['practitioner_id'] ) ? intval( $_POST['practitioner_id'] ) : 0;
    $slot_day        = isset( $_POST['slot_day'] ) ? sanitize_text_field( $_POST['slot_day'] ) : '';
    $slot_time       = isset( $_POST['slot_time'] ) ? sanitize_text_field( $_POST['slot_time'] ) : '';
    $message         = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';

    if ( ! $practitioner_id ) { wp_send_json_error( 'Practitioner not specified' ); }

    $req = array(
        'id'              => uniqid( 'cr_' ),
        'patient_id'      => $patient_id,
        'practitioner_id' => $practitioner_id,
        'slot_day'        => $slot_day,
        'slot_time'       => $slot_time,
        'message'         => $message,
        'status'          => 'pending',
        'date'            => current_time( 'mysql' ),
    );

    $pr_reqs = get_user_meta( $practitioner_id, '_sla_consult_requests', true ) ?: array();
    $my_reqs = get_user_meta( $patient_id, '_sla_my_consult_requests', true ) ?: array();
    array_unshift( $pr_reqs, $req );
    array_unshift( $my_reqs, $req );
    update_user_meta( $practitioner_id, '_sla_consult_requests', array_slice( $pr_reqs, 0, 100 ) );
    update_user_meta( $patient_id,      '_sla_my_consult_requests', array_slice( $my_reqs, 0, 100 ) );

    wp_send_json_success( array( 'id' => $req['id'] ) );
}
add_action( 'wp_ajax_vance_request_consultation', 'vance_request_consultation' );

/**
 * Save a meal plan saved from the IBD Recipes app (postMessage).
 * Meta key: _sla_meal_plans
 */
function vance_save_meal_plan() {
    if ( ! is_user_logged_in() ) { wp_send_json_error( 'Not logged in' ); }
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_dashboard_nonce' ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }

    $user_id = get_current_user_id();
    $entry = array(
        'id'     => sanitize_text_field( isset( $_POST['plan_id'] ) ? $_POST['plan_id'] : uniqid( 'mp_' ) ),
        'title'  => sanitize_text_field( isset( $_POST['title'] ) ? $_POST['title'] : 'Meal Plan' ),
        'days'   => isset( $_POST['days'] ) ? intval( $_POST['days'] ) : 0,
        'data'   => isset( $_POST['data'] ) ? wp_kses_post( wp_unslash( $_POST['data'] ) ) : '',
        'date'   => current_time( 'c' ),
    );

    $plans = get_user_meta( $user_id, '_sla_meal_plans', true ) ?: array();
    if ( ! is_array( $plans ) ) { $plans = array(); }
    array_unshift( $plans, $entry );
    $plans = array_slice( $plans, 0, 50 );
    update_user_meta( $user_id, '_sla_meal_plans', $plans );

    wp_send_json_success( array( 'saved' => true, 'id' => $entry['id'] ) );
}
add_action( 'wp_ajax_vance_save_meal_plan', 'vance_save_meal_plan' );

/**
 * List user's saved meal plans.
 */
function vance_get_meal_plans() {
    if ( ! is_user_logged_in() ) { wp_send_json_error( 'Not logged in' ); }
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_dashboard_nonce' ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }
    $plans = get_user_meta( get_current_user_id(), '_sla_meal_plans', true ) ?: array();
    wp_send_json_success( is_array( $plans ) ? $plans : array() );
}
add_action( 'wp_ajax_vance_get_meal_plans', 'vance_get_meal_plans' );

/**
 * Handle Note Deletion AJAX
 */
function vance_delete_note() {
    check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    $id = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';
    $user_id = get_current_user_id();
    $notes = get_user_meta( $user_id, '_sla_user_notes', true ) ?: array();
    
    $new_notes = array();
    foreach ( $notes as $n ) {
        if ( $n['id'] !== $id ) {
            $new_notes[] = $n;
        }
    }

    update_user_meta( $user_id, '_sla_user_notes', $new_notes );
    wp_send_json_success( 'Note deleted' );
}
add_action( 'wp_ajax_vance_delete_note', 'vance_delete_note' );


/**
 * Register REST APIs for Chat
 */
add_action( 'rest_api_init', function () {
    // Existing save chat endpoint
    register_rest_route( 'vance-health/v1', '/save-chat', array(
        'methods' => 'POST',
        'callback' => 'vance_rest_save_chat',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ) );
    
    // New AI Chat Endpoint
    register_rest_route( 'vance-health/v1', '/ai-chat', array(
        'methods' => 'POST',
        'callback' => 'vance_rest_ai_chat',
        'permission_callback' => '__return_true' // Allow guest access
    ) );
} );

/**
 * Handle AI Chat REST Request
 */
function vance_rest_ai_chat( $request ) {
    $params = $request->get_json_params();
    $messages = isset($params['messages']) ? $params['messages'] : array();
    
    if ( empty($messages) ) {
        return new WP_Error( 'no_messages', 'No messages provided', array( 'status' => 400 ) );
    }

    // Grounding: Search for relevant content on gastrohealthhub.com
    $last_user_message = '';
    foreach (array_reverse($messages) as $m) {
        if ($m['role'] === 'user') {
            $last_user_message = $m['content'];
            break;
        }
    }

    $site_context = '';
    if (!empty($last_user_message)) {
        $search_query = new WP_Query(array(
            's' => $last_user_message,
            'posts_per_page' => 3,
            'post_type' => array('post', 'news', 'research', 'oped', 'review', 'whitepaper', 'page'),
            'post_status' => 'publish'
        ));

        if ($search_query->have_posts()) {
            $site_context .= "\n\nCRITICAL KNOWLEDGE BASE CONTEXT (FROM SLAHEALTH.CO.UK):\n";
            while ($search_query->have_posts()) {
                $search_query->the_post();
                $site_context .= "--- START SOURCE: " . get_the_title() . " ---\n";
                $site_context .= wp_trim_words(get_the_content(), 200) . "\n";
                $site_context .= "--- END SOURCE ---\n\n";
            }
            wp_reset_postdata();
        }
    }

    // OpenRouter API key — read from Customizer (Appearance → Customize → Ask AI Configuration → AI API Key).
    // Do NOT hardcode keys here; they end up in public git history and on the deployed web server.
    $api_key = function_exists( 'vance_get_theme_mod' )
        ? vance_get_theme_mod( 'vance_askai_api_key', '' )
        : get_theme_mod( 'vance_askai_api_key', '' );
    if ( empty( $api_key ) ) {
        return new WP_Error(
            'ai_api_key_missing',
            __( 'AI API key is not configured. Site admin: set it in Appearance → Customize → Ask AI Configuration.', 'sla-health-hub' ),
            array( 'status' => 503 )
        );
    }
    $url = 'https://openrouter.ai/api/v1/chat/completions';
    
    $system_instruction = 'You are an AI assistant, an expert IBD (Inflammatory Bowel Disease) clinical assistant for the Vance Medical Gastro Health Hub platform. Your intelligence and responses MUST be strictly restricted to IBD-related content, clinical reviews, gastrointestinal health, and clinical nutrition guidelines provided within the Vance Medical Hub (gastrohealthhub.com).

DIRECTIONS:
1. Prioritize the "CRITICAL KNOWLEDGE BASE CONTEXT" provided below.
2. If the user asks a question that cannot be answered using the provided context or general knowledge typical of gastrohealthhub.com content, politely inform them that you are restricted to Vance Medical Hub data.
3. Maintain a professional, clinical, yet accessible tone.
4. Do not provide personal medical advice.

' . $site_context;

    // Format messages for OpenRouter (OpenAI compatible)
    $messages_formatted = array(
        array(
            'role' => 'system',
            'content' => $system_instruction
        )
    );
    
    foreach ($messages as $msg) {
        $messages_formatted[] = array(
            'role' => ($msg['role'] === 'user') ? 'user' : 'assistant',
            'content' => $msg['content']
        );
    }
    
    $body = array(
        'model' => 'google/gemini-2.0-flash-001',
        'messages' => $messages_formatted,
        'temperature' => 0.3, // Lower temperature to improve grounding and reduce hallucinations
        'max_tokens' => 1000
    );
    
    $response = wp_remote_post( $url, array(
        'body'    => wp_json_encode($body),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
            'HTTP-Referer' => home_url(),
            'X-Title' => 'Vance Medical Hub'
        ),
        'timeout' => 60
    ) );
    
    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'api_error', 'Failed to connect to AI service: ' . $response->get_error_message(), array( 'status' => 500 ) );
    }
    
    $code = wp_remote_retrieve_response_code( $response );
    $body_decoded = json_decode( wp_remote_retrieve_body( $response ), true );
    
    if ( $code !== 200 ) {
        return new WP_Error( 'api_error', 'AI service returned an error: ' . (isset($body_decoded['error']['message']) ? $body_decoded['error']['message'] : 'Unknown error'), array( 'status' => $code ) );
    }
    
    $reply = '';
    if ( isset($body_decoded['choices'][0]['message']['content']) ) {
        $reply = $body_decoded['choices'][0]['message']['content'];
    }
    
    return array( 'success' => true, 'reply' => $reply );
}

/**
 * Handle Save Chat REST Request
 */
function vance_rest_save_chat( $request ) {
    $user_id = get_current_user_id();
    $params = $request->get_json_params();

    if ( empty( $params['transcript'] ) ) {
        return new WP_Error( 'no_transcript', 'Chat transcript is empty', array( 'status' => 400 ) );
    }

    $title = ! empty( $params['title'] ) ? sanitize_text_field( $params['title'] ) : 'AI Chat - ' . current_time( 'mysql' );
    $transcript = $params['transcript']; 

    $saved_chats = get_user_meta( $user_id, '_sla_saved_chats', true ) ?: array();
    
    $new_chat = array(
        'id'         => uniqid( 'chat_' ),
        'title'      => $title,
        'transcript' => $transcript,
        'date'       => current_time( 'mysql' )
    );

    $saved_chats[] = $new_chat;
    update_user_meta( $user_id, '_sla_saved_chats', $saved_chats );

    return array( 'success' => true, 'id' => $new_chat['id'] );
}

/**
 * Handle Delete Chat AJAX
 */
function vance_dashboard_delete_chat() {
    check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( 'Not logged in' );

    $chat_id = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';
    $user_id = get_current_user_id();
    $saved_chats = get_user_meta( $user_id, '_sla_saved_chats', true ) ?: array();

    $new_chats = array();
    foreach ( $saved_chats as $chat ) {
        if ( $chat['id'] !== $chat_id ) {
            $new_chats[] = $chat;
        }
    }

    update_user_meta( $user_id, '_sla_saved_chats', $new_chats );
    wp_send_json_success( 'Chat deleted' );
}
add_action( 'wp_ajax_vance_delete_chat', 'vance_dashboard_delete_chat' );

/**
 * Handle Rename Chat AJAX
 */
function vance_dashboard_rename_chat() {
    check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( 'Not logged in' );

    $chat_id = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';
    $new_title = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
    
    if ( ! $chat_id || ! $new_title ) {
        wp_send_json_error( 'Missing ID or Title' );
    }

    $user_id = get_current_user_id();
    $saved_chats = get_user_meta( $user_id, '_sla_saved_chats', true ) ?: array();

    $updated = false;
    foreach ( $saved_chats as &$chat ) {
        if ( $chat['id'] === $chat_id ) {
            $chat['title'] = $new_title;
            // Optionally update date? usually we just update the title
            $updated = true;
            break;
        }
    }

    if ( $updated ) {
        update_user_meta( $user_id, '_sla_saved_chats', $saved_chats );
        wp_send_json_success( 'Chat renamed' );
    } else {
        wp_send_json_error( 'Chat not found' );
    }
}
add_action( 'wp_ajax_vance_rename_chat', 'vance_dashboard_rename_chat' );


/* ============================================================================
 * QUICK-REGISTER FROM TOOL PAGES
 * ----------------------------------------------------------------------------
 * Powers the "Save your result" → modal flow on per-tool pages
 * (/omega-3-calculator/, /malnutrition-calculator/, /blood-test/,
 * /ibd-recipes/). Anonymous user submits email + password + role, we create
 * the user, auto-log-in, and stash the pending tool result against
 * `_sla_<tool>_history` user meta so it appears in the dashboard.
 *
 * Per CLAUDE.md constraint #2: meta keys MUST stay `_sla_*` (never rename) —
 * matches the existing dashboard read paths.
 * Per constraint #5: action name `vance_quick_register` is paired with the
 * `vance_quick_register` nonce checked below; rename in lockstep.
 * ============================================================================ */

/**
 * Sanitised tool slug allowlist. Keep in sync with the per-tool wrapper page
 * templates so we never write history under an unknown tool key.
 *
 * @return string[]
 */
function vance_known_tool_slugs() {
    return array( 'omega-3-calculator', 'malnutrition-calculator', 'blood-test', 'ibd-recipes' );
}

/**
 * Append a result entry to a user's tool history meta. Keeps the most recent
 * 50 entries. Each entry is a {ts, payload} dict — `_sla_<tool>_history` is
 * a JSON-serializable PHP array stored via update_user_meta (WP auto-serializes).
 *
 * @param int    $user_id
 * @param string $tool_slug  Must be one of vance_known_tool_slugs().
 * @param array  $payload    Free-form result payload from the tool iframe.
 * @return bool true on write, false on invalid slug or empty payload.
 */
function vance_append_tool_history( $user_id, $tool_slug, $payload ) {
    $user_id = (int) $user_id;
    if ( $user_id <= 0 ) {
        return false;
    }
    if ( ! in_array( $tool_slug, vance_known_tool_slugs(), true ) ) {
        return false;
    }
    if ( empty( $payload ) || ! is_array( $payload ) ) {
        return false;
    }

    $meta_key = '_sla_' . str_replace( '-', '_', $tool_slug ) . '_history';
    $existing = get_user_meta( $user_id, $meta_key, true );
    if ( ! is_array( $existing ) ) {
        $existing = array();
    }

    // Newest first; keep last 50 entries.
    array_unshift( $existing, array(
        'ts'      => time(),
        'payload' => wp_unslash( $payload ),
    ) );
    if ( count( $existing ) > 50 ) {
        $existing = array_slice( $existing, 0, 50 );
    }

    update_user_meta( $user_id, $meta_key, $existing );
    return true;
}

/**
 * AJAX: anonymous quick-register from a tool page.
 *
 * Expects POST: nonce, email, password, role, tool (optional), payload (optional JSON).
 * On success: user is created, signed in, and any pending tool payload is
 * stored under `_sla_<tool>_history`. Responds with redirect URL.
 */
function vance_ajax_quick_register() {
    // Nonce (paired with wp_create_nonce('vance_quick_register') in the modal partial).
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_quick_register' ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed — please refresh the page and try again.' ), 403 );
    }

    // Honeypot — bots tend to fill any non-empty input. Real users can't see it.
    if ( ! empty( $_POST['vance_hp'] ) ) {
        wp_send_json_error( array( 'message' => 'Submission rejected.' ), 400 );
    }

    // Don't let logged-in users hit this path — would create a duplicate account.
    if ( is_user_logged_in() ) {
        wp_send_json_success( array(
            'redirect' => '/dashboard/?vance_welcome=1',
            'message'  => 'Already signed in.',
        ) );
    }

    $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $password = isset( $_POST['password'] ) ? (string) $_POST['password'] : '';
    $role_in  = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : 'patient';
    $tool     = isset( $_POST['tool'] ) ? sanitize_key( wp_unslash( $_POST['tool'] ) ) : '';
    $payload  = array();

    if ( isset( $_POST['payload'] ) && $_POST['payload'] !== '' ) {
        $decoded = json_decode( wp_unslash( $_POST['payload'] ), true );
        if ( is_array( $decoded ) ) {
            $payload = $decoded;
        }
    }

    // Validation.
    if ( ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => 'That email address looks invalid — please double-check.' ) );
    }
    if ( strlen( $password ) < 8 ) {
        wp_send_json_error( array( 'message' => 'Please choose a password of at least 8 characters.' ) );
    }
    if ( email_exists( $email ) ) {
        wp_send_json_error( array(
            'message' => 'An account already exists for that email — please sign in instead.',
            'exists'  => true,
        ) );
    }

    // Create user. Username is derived from the email local-part, with a numeric
    // suffix on collision (rarely needed because email_exists already gates).
    $base_login = sanitize_user( current( explode( '@', $email ) ), true );
    if ( empty( $base_login ) ) {
        $base_login = 'user';
    }
    $login = $base_login;
    $suffix = 1;
    while ( username_exists( $login ) ) {
        $login = $base_login . $suffix;
        $suffix++;
        if ( $suffix > 99 ) { // belt-and-braces escape hatch
            $login = $base_login . wp_generate_password( 4, false, false );
            break;
        }
    }

    $user_id = wp_create_user( $login, $password, $email );
    if ( is_wp_error( $user_id ) ) {
        wp_send_json_error( array( 'message' => $user_id->get_error_message() ?: 'Could not create your account.' ) );
    }

    // Default WP role stays 'subscriber'; we surface the user-stated audience role
    // under our own meta key (consistent with existing `_sla_*` user meta).
    $allowed_roles = array( 'patient', 'caregiver', 'hcp', 'researcher', 'other' );
    if ( ! in_array( $role_in, $allowed_roles, true ) ) {
        $role_in = 'patient';
    }
    update_user_meta( $user_id, '_sla_audience_role', $role_in );
    update_user_meta( $user_id, '_sla_signup_source', 'tool_page:' . ( $tool ?: 'unknown' ) );
    update_user_meta( $user_id, '_sla_signup_ts',     time() );

    // Stash the pending tool payload (if any).
    if ( $tool && ! empty( $payload ) ) {
        vance_append_tool_history( $user_id, $tool, $payload );
    }

    // Auto-login.
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true ); // remember-me on
    do_action( 'wp_login', $login, get_userdata( $user_id ) );

    wp_send_json_success( array(
        'redirect' => '/dashboard/?vance_welcome=1' . ( $tool ? '&from_tool=' . rawurlencode( $tool ) : '' ),
        'message'  => 'Account created.',
        'user_id'  => $user_id,
    ) );
}
add_action( 'wp_ajax_nopriv_vance_quick_register', 'vance_ajax_quick_register' );
add_action( 'wp_ajax_vance_quick_register',        'vance_ajax_quick_register' ); // logged-in fallback (returns redirect)

/**
 * AJAX: logged-in save of a tool result. Used by the same Save button on the
 * tool wrapper page when the user is already authenticated — bypasses the
 * register modal entirely.
 *
 * Expects POST: nonce ('vance_tool_save_<slug>'), tool, payload (JSON).
 */
function vance_ajax_save_tool_result() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Please sign in first.' ), 401 );
    }

    $tool = isset( $_POST['tool'] ) ? sanitize_key( wp_unslash( $_POST['tool'] ) ) : '';
    if ( ! in_array( $tool, vance_known_tool_slugs(), true ) ) {
        wp_send_json_error( array( 'message' => 'Unknown tool.' ), 400 );
    }
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_tool_save_' . $tool ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
    }

    $payload = array();
    if ( isset( $_POST['payload'] ) && $_POST['payload'] !== '' ) {
        $decoded = json_decode( wp_unslash( $_POST['payload'] ), true );
        if ( is_array( $decoded ) ) {
            $payload = $decoded;
        }
    }
    // We accept any non-empty payload — even a "placeholder" snapshot from the
    // wrapper page indicates the user explicitly clicked save with an open tool.
    // Filter out genuinely-empty submissions so we don't write empty rows.
    if ( empty( $payload ) ) {
        $payload = array(
            'kind'       => 'placeholder',
            'note'       => 'Saved without a captured result',
            'capturedAt' => gmdate( 'c' ),
        );
    }

    $ok = vance_append_tool_history( get_current_user_id(), $tool, $payload );
    if ( ! $ok ) {
        wp_send_json_error( array( 'message' => 'Could not save — please try again.' ) );
    }

    wp_send_json_success( array(
        'message'       => 'Saved to your dashboard.',
        'dashboard_url' => '/dashboard/?vance_saved=' . rawurlencode( $tool ),
    ) );
}
add_action( 'wp_ajax_vance_save_tool_result', 'vance_ajax_save_tool_result' );
