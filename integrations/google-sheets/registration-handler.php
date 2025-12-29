<?php
/**
 * Registration Form Handler
 * 
 * Processes registration form submissions, validates data,
 * and syncs to Google Sheets with comprehensive logging.
 * 
 * @package CGR Child Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load required dependencies
require_once get_stylesheet_directory() . '/integrations/google-sheets/registration-logger.php';
require_once get_stylesheet_directory() . '/integrations/google-sheets/class-cgr-sheet-sync.php';

class CGR_Registration_Handler {
    
    /**
     * Initialize the handler
     */
    public static function init() {
        add_action('wp_ajax_cgr_registration_submit', [__CLASS__, 'handle_ajax_submission']);
        add_action('wp_ajax_nopriv_cgr_registration_submit', [__CLASS__, 'handle_ajax_submission']);
        add_action('init', [__CLASS__, 'handle_form_submission']);
    }
    
    /**
     * Handle AJAX form submission
     */
    public static function handle_ajax_submission() {
        // Log the attempt with received data (safely)
        CGR_Registration_Logger::log('ajax_attempt', 'AJAX registration submission received', [
            'post_fields' => array_keys($_POST),
            'has_nonce' => isset($_POST['registration_nonce']),
            'user_ip' => self::get_client_ip()
        ]);
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['registration_nonce'] ?? '', 'cgr_registration')) {
            CGR_Registration_Logger::log('error', 'Invalid nonce for AJAX registration submission');
            wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.']);
            return;
        }
        
        $result = self::process_registration($_POST);
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],
                'redirect' => false
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message'],
                'errors' => $result['errors'] ?? []
            ]);
        }
    }
    
    /**
     * Handle regular form submission
     */
    public static function handle_form_submission() {
        if (!isset($_POST['cgr_registration_submit'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['registration_nonce'] ?? '', 'cgr_registration')) {
            CGR_Registration_Logger::log('error', 'Invalid nonce for form registration submission');
            return;
        }
        
        $result = self::process_registration($_POST);
        
        // Set transient for display on page reload
        if ($result['success']) {
            set_transient('cgr_registration_success', $result['message'], 300);
        } else {
            set_transient('cgr_registration_error', $result['message'], 300);
        }
        
        // Redirect to prevent resubmission
        wp_redirect($_SERVER['REQUEST_URI']);
        exit;
    }
    
    /**
     * Process registration data
     */
    public static function process_registration($form_data) {
        // Log the submission attempt
        CGR_Registration_Logger::log_form_submission($form_data);
        
        // Validate required fields
        $validation = self::validate_form_data($form_data);
        if (!$validation['is_valid']) {
            CGR_Registration_Logger::log('validation_failed', 'Form validation failed', $validation['errors']);
            return [
                'success' => false,
                'message' => 'Please fill all required fields.',
                'errors' => $validation['errors']
            ];
        }
        
        // Sanitize and prepare data
        $clean_data = self::sanitize_and_prepare_data($form_data);
        
        // Create a new post of type 'registration'
        $post_id = self::create_registration_post($clean_data);
        if (!$post_id) {
            CGR_Registration_Logger::log('error', 'Failed to create registration post');
            return [
                'success' => false,
                'message' => 'An internal error occurred. Could not save registration.'
            ];
        }
        
        // Add sheet_name for multi-sheet support
        $sheet_name = 'Registrations'; // Default
        if ($clean_data['registration_type'] === 'volunteer') {
            $sheet_name = 'Volunteers';
        } elseif ($clean_data['registration_type'] === 'writer') {
            $sheet_name = 'Writers';
        }
        $clean_data['sheet_name'] = $sheet_name;

        // Sync to Google Sheets
        try {
            $sync_result = CGR_Sheet_Sync::send_data_to_sheet($clean_data);
            
            // Log the sync result
            CGR_Registration_Logger::log_sync_result($sync_result, $post_id);
        } catch (Exception $e) {
            CGR_Registration_Logger::log('error', 'Exception during sheet sync: ' . $e->getMessage());
            // Continue execution to return success to user
        }
        
        if (isset($sync_result) && !$sync_result['success']) {
            // Even if sync fails, the registration is saved in WordPress.
            // The message to the user should still be positive.
            // The admin will be aware of the sync failure from the logs.
        }
        
        // Return success message
        return [
            'success' => true,
            'message' => 'Thank you for registering! We will be in touch soon.'
        ];
    }
    
    /**
     * Validate form data
     */
    private static function validate_form_data($data) {
        $errors = [];
        $required_fields = ['full_name', 'email', 'phone', 'city', 'registration_type'];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = 'This field is required.';
            }
        }
        
        if (!empty($data['email']) && !is_email($data['email'])) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Sanitize and prepare data for storage
     */
    private static function sanitize_and_prepare_data($data) {
        $sanitized_data = [];
        
        // Sanitize all fields from the form
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized_data[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized_data[$key] = sanitize_text_field($value);
            }
        }
        
        // Add metadata
        $sanitized_data['timestamp'] = current_time('mysql');
        $sanitized_data['ip_address'] = self::get_client_ip();
        $sanitized_data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return $sanitized_data;
    }
    
    /**
     * Create a new post of type 'registration'
     */
    private static function create_registration_post($data) {
        $post_title = $data['full_name'] . ' - ' . ucfirst($data['registration_type']) . ' (' . date('Y-m-d') . ')';
        
        $post_data = [
            'post_title'  => $post_title,
            'post_status' => 'publish',
            'post_type'   => 'registration',
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id) {
            // Store all data in post meta
            update_post_meta($post_id, 'registration_data', $data);
            update_post_meta($post_id, 'registration_type', $data['registration_type']);
            update_post_meta($post_id, 'email', $data['email']);
            update_post_meta($post_id, 'phone', $data['phone']);
            update_post_meta($post_id, 'city', $data['city']);
        }
        
        return $post_id;
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}

// Initialize the handler
CGR_Registration_Handler::init();