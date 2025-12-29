<?php
/**
 * Google API Credentials Configuration
 * CGR India
 * 
 * IMPORTANT: Keep this file secure and never commit to public repositories
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Google OAuth 2.0 Credentials
 * For Google Sheets API Integration
 */
class CGR_Google_Credentials {
    
    /**
     * Get Google OAuth Client ID
     */
    public static function get_client_id() {
        return get_option('cgr_google_client_id', '');
    }
    
    /**
     * Get Google OAuth Client Secret
     */
    public static function get_client_secret() {
        return get_option('cgr_google_client_secret', '');
    }
    
    /**
     * Get Target Spreadsheet ID
     */
    public static function get_spreadsheet_id() {
        return get_option('cgr_google_spreadsheet_id', '');
    }
    
    /**
     * Get Webhook URL (configured in admin)
     */
    public static function get_webhook_url() {
        // Try both possible option names
        $webhook_url = get_option('cgr_google_sheets_webhook_url', '');
        if (empty($webhook_url)) {
            $webhook_url = get_option('cgr_sheets_webhook_url', '');
        }
        return $webhook_url;
    }
    
    /**
     * Get Google Apps Script Web App URL template
     */
    public static function get_script_url_template() {
        return 'https://script.google.com/macros/s/{SCRIPT_ID}/exec';
    }
    
    /**
     * Validate credentials
     */
    public static function validate_credentials() {
        $client_id = self::get_client_id();
        $client_secret = self::get_client_secret();
        $webhook_url = self::get_webhook_url();
        
        $validation = [
            'client_id_valid' => !empty($client_id) && strpos($client_id, '.apps.googleusercontent.com') !== false,
            'client_secret_valid' => !empty($client_secret),
            'webhook_configured' => !empty($webhook_url) && filter_var($webhook_url, FILTER_VALIDATE_URL),
            'spreadsheet_id_valid' => !empty(self::get_spreadsheet_id())
        ];
        
        $validation['all_valid'] = array_reduce($validation, function($carry, $item) {
            return $carry && $item;
        }, true);
        
        return $validation;
    }
    
    /**
     * Get credentials for API requests
     */
    public static function get_api_credentials() {
        return [
            'client_id' => self::get_client_id(),
            'client_secret' => self::get_client_secret(),
            'spreadsheet_id' => self::get_spreadsheet_id(),
            'webhook_url' => self::get_webhook_url(),
            'redirect_uri' => home_url('/google-sheets-callback/')
        ];
    }
    
    /**
     * Initialize credentials in WordPress options (run once)
     */
    public static function initialize_credentials() {
        // Set default values if not already set
        if (empty(get_option('cgr_google_client_id'))) {
            update_option('cgr_google_client_id', '');
        }
        if (empty(get_option('cgr_google_client_secret'))) {
            update_option('cgr_google_client_secret', '');
        }
        if (empty(get_option('cgr_google_sheets_webhook_url'))) {
            update_option('cgr_google_sheets_webhook_url', '');
        }
        if (empty(get_option('cgr_google_spreadsheet_id'))) {
            update_option('cgr_google_spreadsheet_id', '');
        }
    }
}

// Initialize on theme setup
add_action('after_setup_theme', ['CGR_Google_Credentials', 'initialize_credentials']);
?>