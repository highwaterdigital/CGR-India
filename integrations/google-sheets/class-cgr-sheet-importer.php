<?php
/**
 * CGR Sheet Importer
 * Handles pulling data from Google Sheets into WordPress CPTs.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGR_Sheet_Importer {

    /**
     * Fetch data from Google Sheet via Web App URL
     */
    public static function fetch_sheet_data( $sheet_name ) {
        $webhook_url = get_option('cgr_sheets_webhook_url');
        
        // Fallback for option name
        if ( empty( $webhook_url ) ) {
            $webhook_url = get_option('cgr_google_sheets_webhook_url');
        }
        
        if ( empty( $webhook_url ) ) {
            error_log("CGR Sync Error: Webhook URL is missing in WP Settings.");
            return new WP_Error( 'no_url', 'Webhook URL not configured in WP Settings.' );
        }

        // Append query param for GET request
        $url = add_query_arg( 'sheet', $sheet_name, $webhook_url );
        
        error_log("CGR Sync: Fetching data from $url");

        // Important: Google Apps Script redirects, so we need to follow redirects
        $response = wp_remote_get( $url, array(
            'timeout' => 30,
            'redirection' => 5
        ));

        if ( is_wp_error( $response ) ) {
            error_log("CGR Sync Error: wp_remote_get failed. " . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        
        error_log("CGR Sync: Response Code: $response_code");

        // Check for HTML response (Permissions Error)
        if ( strpos( trim($body), '<!DOCTYPE html>' ) === 0 || strpos( trim($body), '<html' ) === 0 ) {
            error_log("CGR Sync Error: Received HTML instead of JSON. Permissions issue.");
            return new WP_Error( 'permissions_error', 'Google Permissions Error: The Web App is returning HTML. Redeploy with "Who has access: Anyone".' );
        }

        $data = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            error_log('CGR Import Error: Invalid JSON. Body: ' . substr($body, 0, 500));
            return new WP_Error( 'invalid_json', 'Invalid JSON received from Google Sheet.' );
        }

        if ( isset( $data['error'] ) ) {
            error_log("CGR Sync Error from Sheet: " . $data['error']);
            return new WP_Error( 'sheet_error', $data['error'] );
        }

        if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
            return $data['data'];
        }

        error_log("CGR Sync Error: Invalid data format.");
        return new WP_Error( 'invalid_format', 'Invalid data format received from Sheet' );
    }

    /**
     * Import Scientists
     */
    public static function import_scientists() {
        $data = self::fetch_sheet_data( 'Scientists' );
        if ( is_wp_error( $data ) ) {
            return "Error importing Scientists: " . $data->get_error_message();
        }

        error_log('CGR Sync: Received ' . count($data) . ' rows for Scientists.');

        $count = 0;
        $updated = 0;
        $created = 0;

        foreach ( $data as $row ) {
            $name = $row['Full Name'] ?? '';
            if ( empty( $name ) ) continue;

            $args = [
                'post_type' => 'earth_scientist',
                'post_title' => $name,
                'post_status' => 'publish',
                'meta_input' => [
                    '_cgr_specialization' => $row['Specialization'] ?? '',
                    '_cgr_institution' => $row['Institution'] ?? '',
                    '_cgr_email' => $row['Email'] ?? '', // Store email to check duplicates
                ]
            ];

            // Check if exists by Email or Title
            $existing = self::find_existing_post( 'earth_scientist', $name, $row['Email'] ?? '' );
            
            if ( $existing ) {
                $args['ID'] = $existing;
                wp_update_post( $args );
                $updated++;
                error_log("CGR Sync: Updated Scientist '$name' (ID: $existing)");
            } else {
                $id = wp_insert_post( $args );
                $created++;
                error_log("CGR Sync: Created Scientist '$name' (ID: $id)");
            }
            
            // Handle Bio (Content)
            if ( !empty( $row['Bio'] ) ) {
                $post_id = $existing ? $existing : $id;
                wp_update_post( ['ID' => $post_id, 'post_content' => $row['Bio']] );
            }

            $count++;
        }
        return "Scientists: Processed $count (Created: $created, Updated: $updated)";
    }

    /**
     * Import Earth Leaders
     */
    public static function import_earth_leaders() {
        $data = self::fetch_sheet_data( 'EarthLeaders' );
        if ( is_wp_error( $data ) ) {
            return "Error importing Earth Leaders: " . $data->get_error_message();
        }

        error_log('CGR Sync: Received ' . count($data) . ' rows for Earth Leaders.');

        $count = 0;
        $updated = 0;
        $created = 0;

        foreach ( $data as $row ) {
            $name = $row['Full Name'] ?? '';
            if ( empty( $name ) ) continue;

            $args = [
                'post_type' => 'earth_leader',
                'post_title' => $name,
                'post_status' => 'publish',
                'meta_input' => [
                    '_cgr_training_year' => $row['Training Year'] ?? '',
                    '_cgr_district' => $row['District'] ?? '',
                    '_cgr_organization' => $row['Organization'] ?? '',
                    '_cgr_email' => $row['Email'] ?? '',
                ]
            ];

            $existing = self::find_existing_post( 'earth_leader', $name, $row['Email'] ?? '' );
            
            if ( $existing ) {
                $args['ID'] = $existing;
                wp_update_post( $args );
                $updated++;
                error_log("CGR Sync: Updated Earth Leader '$name' (ID: $existing)");
            } else {
                $id = wp_insert_post( $args );
                $created++;
                error_log("CGR Sync: Created Earth Leader '$name' (ID: $id)");
            }
            $count++;
        }
        return "Earth Leaders: Processed $count (Created: $created, Updated: $updated)";
    }

    /**
     * Import CGR Team
     */
    public static function import_cgr_team() {
        $data = self::fetch_sheet_data( 'CGRTeam' );
        if ( is_wp_error( $data ) ) {
            return "Error importing CGR Team: " . $data->get_error_message();
        }

        error_log('CGR Sync: Received ' . count($data) . ' rows for CGR Team.');

        $count = 0;
        $updated = 0;
        $created = 0;

        foreach ( $data as $row ) {
            $name = $row['Full Name'] ?? '';
            if ( empty( $name ) ) continue;

            $args = [
                'post_type' => 'cgr_member',
                'post_title' => $name,
                'post_status' => 'publish',
                'meta_input' => [
                    '_cgr_designation' => $row['Designation'] ?? '',
                    '_cgr_role_type' => $row['Role Type'] ?? '',
                    '_cgr_email' => $row['Email'] ?? '',
                ]
            ];

            $existing = self::find_existing_post( 'cgr_member', $name, $row['Email'] ?? '' );
            
            if ( $existing ) {
                $args['ID'] = $existing;
                wp_update_post( $args );
                $updated++;
                error_log("CGR Sync: Updated Team Member '$name' (ID: $existing)");
            } else {
                $id = wp_insert_post( $args );
                $created++;
                error_log("CGR Sync: Created Team Member '$name' (ID: $id)");
            }
            
            if ( !empty( $row['Bio'] ) ) {
                $post_id = $existing ? $existing : $id;
                wp_update_post( ['ID' => $post_id, 'post_content' => $row['Bio']] );
            }
            
            $count++;
        }
        return "CGR Team: Processed $count (Created: $created, Updated: $updated)";
    }

    /**
     * Helper: Find existing post by Email (Meta) or Title
     */
    private static function find_existing_post( $post_type, $title, $email ) {
        // 1. Try by Email first if provided
        if ( !empty( $email ) ) {
            $posts = get_posts([
                'post_type' => $post_type,
                'meta_key' => '_cgr_email',
                'meta_value' => $email,
                'posts_per_page' => 1
            ]);
            if ( !empty( $posts ) ) return $posts[0]->ID;
        }

        // 2. Fallback to Title
        $post = get_page_by_title( $title, OBJECT, $post_type );
        if ( $post ) return $post->ID;

        return false;
    }

    /**
     * Register REST API Route for Sync Trigger
     */
    public static function register_routes() {
        register_rest_route( 'cgr/v1', '/sync', array(
            'methods' => 'POST',
            'callback' => array( __CLASS__, 'handle_sync_request' ),
            'permission_callback' => '__return_true', // We will check secret manually
        ));
    }

    /**
     * Handle Sync Request
     */
    public static function handle_sync_request( $request ) {
        $secret = trim((string) $request->get_param( 'secret' ));
        $stored_secret = trim((string) get_option('cgr_sheets_client_secret'));

        // Allow a fallback secret or check if stored secret is empty (not secure but helpful for debug)
        if ( empty( $stored_secret ) ) {
             // Fallback: Try to get it from the other option name
             $stored_secret = trim((string) get_option('cgr_google_client_secret'));
             if ( empty( $stored_secret ) ) {
                 if ( defined('CGR_SYNC_SECRET') && !empty(constant('CGR_SYNC_SECRET')) ) {
                     $stored_secret = trim((string) constant('CGR_SYNC_SECRET'));
                 }
             }
             if ( empty( $stored_secret ) ) {
                 return new WP_Error( 'configuration_error', 'Client Secret not set in WP Admin', array( 'status' => 500 ) );
             }
        }

        if ( empty($secret) || empty($stored_secret) || !hash_equals( $stored_secret, $secret ) ) {
            // Helpful debug without exposing full secrets
            $mask = function( $value ) {
                if ( empty( $value ) ) return '[empty]';
                $len = strlen( $value );
                return array(
                    'len'  => $len,
                    'hash' => substr( hash( 'sha256', $value ), 0, 10 ),
                );
            };
            $provided = $mask( $secret );
            $expected = $mask( $stored_secret );
            error_log(
                sprintf(
                    '[CGR Sync] Invalid secret from Apps Script. Provided len:%s hash:%s expected len:%s hash:%s',
                    $provided['len'], $provided['hash'], $expected['len'], $expected['hash']
                )
            );
            return new WP_Error( 'forbidden', 'Invalid Secret', array(
                'status' => 403,
                'debug'  => array(
                    'provided' => $provided,
                    'expected' => $expected,
                    'using_constant' => ( defined('CGR_SYNC_SECRET') && !empty(constant('CGR_SYNC_SECRET')) ),
                ),
            ) );
        }

        $results = [];
        $results['scientists'] = self::import_scientists();
        $results['earth_leaders'] = self::import_earth_leaders();
        $results['cgr_team'] = self::import_cgr_team();

        return rest_ensure_response( $results );
    }

}

// Register the REST route
add_action( 'rest_api_init', array( 'CGR_Sheet_Importer', 'register_routes' ) );
