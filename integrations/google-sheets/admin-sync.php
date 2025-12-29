<?php
/**
 * Admin Sync Handler
 * 
 * Syncs data to Google Sheets when a post is saved in WP Admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGR_Admin_Sync {

    public static function init() {
        add_action( 'save_post', array( __CLASS__, 'handle_save_post' ), 20, 3 );
    }

    public static function handle_save_post( $post_id, $post, $update ) {
        // 1. Basic Checks
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( wp_is_post_revision( $post_id ) ) return;
        
        // Prevent sync loop if this save was triggered by the importer
        // The importer should set a global or transient, but for now we can check if we are in a REST request from our own sync endpoint?
        // Or better, check if the current user is the one running the import?
        // Actually, the importer runs via REST API or manual trigger.
        // If it's a REST request to /cgr/v1/sync, we should skip.
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST && strpos( $_SERVER['REQUEST_URI'], '/cgr/v1/sync' ) !== false ) {
            return;
        }

        // 2. Check Post Type and Prepare Data
        $data = [];
        $sheet_name = '';

        if ( 'earth_leader' === $post->post_type ) {
            // Verify Nonce
            if ( ! isset( $_POST['cgr_earth_leader_nonce'] ) || ! wp_verify_nonce( $_POST['cgr_earth_leader_nonce'], 'cgr_earth_leader_save' ) ) {
                return;
            }
            
            $sheet_name = 'EarthLeaders';
            $data = [
                'full_name' => $post->post_title,
                'email' => get_post_meta( $post_id, '_cgr_email', true ),
                'training_year' => get_post_meta( $post_id, '_cgr_training_year', true ),
                'district' => get_post_meta( $post_id, '_cgr_district', true ),
                'organization' => get_post_meta( $post_id, '_cgr_organization', true ),
                'photo_url' => get_the_post_thumbnail_url( $post_id, 'full' ) ?: '',
            ];
        } 
        elseif ( 'earth_scientist' === $post->post_type ) {
             // Verify Nonce
             if ( ! isset( $_POST['cgr_scientist_nonce'] ) || ! wp_verify_nonce( $_POST['cgr_scientist_nonce'], 'cgr_scientist_save' ) ) {
                return;
            }

            $sheet_name = 'Scientists';
            $data = [
                'full_name' => $post->post_title,
                'email' => get_post_meta( $post_id, '_cgr_email', true ),
                'specialization' => get_post_meta( $post_id, '_cgr_specialization', true ),
                'institution' => get_post_meta( $post_id, '_cgr_institution', true ),
                'location' => get_post_meta( $post_id, '_cgr_location', true ),
                'bio' => $post->post_content,
                'photo_url' => get_the_post_thumbnail_url( $post_id, 'full' ) ?: '',
            ];
        }
        elseif ( 'cgr_member' === $post->post_type ) {
             // Verify Nonce
             if ( ! isset( $_POST['cgr_member_nonce'] ) || ! wp_verify_nonce( $_POST['cgr_member_nonce'], 'cgr_member_save' ) ) {
                return;
            }

            $sheet_name = 'CGRTeam';
            $data = [
                'full_name' => $post->post_title,
                'email' => get_post_meta( $post_id, '_cgr_email', true ),
                'designation' => get_post_meta( $post_id, '_cgr_designation', true ),
                'role_type' => get_post_meta( $post_id, '_cgr_role_type', true ),
                'bio' => $post->post_content,
                'photo_url' => get_the_post_thumbnail_url( $post_id, 'full' ) ?: '',
            ];
        }
        else {
            return;
        }

        // 3. Validate Data
        if ( empty( $data['email'] ) ) {
            // We can't sync without email as it's the key
            // Maybe log a warning?
            return;
        }

        // 4. Add Common Fields
        $data['sheet_name'] = $sheet_name;
        $data['timestamp'] = current_time( 'mysql' );
        $data['source'] = 'WP Admin';

        // 5. Send to Google Sheet
        // We use the existing CGR_Sheet_Sync class
        if ( class_exists( 'CGR_Sheet_Sync' ) ) {
            // Run in background? No, let's run immediately to see errors, or use a shutdown action.
            // wp_remote_post is blocking.
            $result = CGR_Sheet_Sync::send_data_to_sheet( $data );
            
            if ( is_wp_error( $result ) ) {
                error_log( "CGR Admin Sync Error: " . $result->get_error_message() );
            }
        }
    }
}

CGR_Admin_Sync::init();
