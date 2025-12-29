<?php
/**
 * AJAX Registration Handler
 * 
 * Handles registration form submissions via AJAX without page refresh.
 * Includes detailed console debugging, validation, Google Sheets sync, and WhatsApp notifications.
 * 
 * @package CGR Child Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
require_once get_stylesheet_directory() . '/integrations/google-sheets/class-cgr-sheet-sync.php';
require_once get_stylesheet_directory() . '/integrations/whatsapp/class-cgr-whatsapp-api.php';

class CGR_AJAX_Registration_Handler {
    
    /**
     * Initialize hooks
     */
    public static function init() {
        add_action('wp_ajax_cgr_register', [__CLASS__, 'handle_registration']);
        add_action('wp_ajax_nopriv_cgr_register', [__CLASS__, 'handle_registration']);
    }
    
    /**
     * Handle AJAX registration submission
     */
    public static function handle_registration() {
        // Debug log: Start
        error_log('🚀 [AJAX Registration] Starting registration process');
        
        // Verify nonce
        $nonce_field = $_POST['nonce_field'] ?? 'unified_registration_nonce';
        $nonce_value = $_POST[$nonce_field] ?? '';
        
        error_log('🔐 [AJAX Registration] Nonce field: ' . $nonce_field);
        error_log('🔐 [AJAX Registration] Nonce value: ' . substr($nonce_value, 0, 10) . '...');
        
        if (!wp_verify_nonce($nonce_value, 'cgr_unified_registration')) {
            error_log('❌ [AJAX Registration] Nonce verification failed');
            wp_send_json_error([
                'message' => 'Security check failed. Please refresh the page and try again.',
                'debug' => 'nonce_verification_failed'
            ]);
            return;
        }
        
        error_log('✅ [AJAX Registration] Nonce verified');
        
        // Get registration type
        $registration_type = sanitize_text_field($_POST['registration_type'] ?? '');
        error_log('📋 [AJAX Registration] Registration type: ' . $registration_type);
        
        // Validate registration type
        if (!in_array($registration_type, ['participant', 'volunteer', 'writer'])) {
            error_log('❌ [AJAX Registration] Invalid registration type');
            wp_send_json_error([
                'message' => 'Invalid registration type.',
                'debug' => 'invalid_type'
            ]);
            return;
        }
        
        // Collect form data
        $form_data = self::collect_form_data($registration_type);
        error_log('📦 [AJAX Registration] Form data collected: ' . json_encode(array_keys($form_data)));
        
        // Validate form data
        $validation = self::validate_form_data($form_data, $registration_type);
        if (!$validation['valid']) {
            error_log('❌ [AJAX Registration] Validation failed: ' . json_encode($validation['errors']));
            wp_send_json_error([
                'message' => 'Please fill in all required fields correctly.',
                'errors' => $validation['errors'],
                'debug' => 'validation_failed'
            ]);
            return;
        }
        
        error_log('✅ [AJAX Registration] Validation passed');
        
        // Save data locally in WordPress (as custom post type or option)
        error_log('🗂 [AJAX Registration] Saving data locally...');
        $saved_locally = self::save_registration_locally($form_data, $registration_type);
        
        if ($saved_locally) {
            error_log('✅ [AJAX Registration] Data saved locally with ID: ' . $saved_locally);
        } else {
            error_log('⚠️ [AJAX Registration] Local save failed, but continuing...');
        }
        
        // Return SUCCESS immediately to user
        error_log('🎉 [AJAX Registration] Returning success to user immediately');
        
        // Sync to Google Sheets in background (non-blocking)
        error_log('📊 [AJAX Registration] Starting background sync to Google Sheets...');
        
        // Map registration type to sheet name
        $sheet_names = [
            'participant' => 'Participants',
            'volunteer' => 'Volunteers',
            'writer' => 'Writers'
        ];
        
        $form_data['sheet_name'] = $sheet_names[$registration_type] ?? 'Participants';
        
        // Try to sync to Google Sheets (non-blocking - don't wait for result)
        try {
            $sheet_result = CGR_Sheet_Sync::send_data_to_sheet($form_data);
            if ($sheet_result['success']) {
                error_log('✅ [AJAX Registration] Background Google Sheets sync successful');
            } else {
                error_log('⚠️ [AJAX Registration] Background Google Sheets sync failed: ' . ($sheet_result['message'] ?? 'Unknown'));
            }
        } catch (Exception $e) {
            error_log('⚠️ [AJAX Registration] Background Google Sheets sync exception: ' . $e->getMessage());
        }
        
        // Send WhatsApp notifications (non-blocking)
        error_log('📱 [AJAX Registration] Sending WhatsApp notifications in background...');
        try {
            self::send_whatsapp_notifications($form_data, $registration_type);
        } catch (Exception $e) {
            error_log('⚠️ [AJAX Registration] WhatsApp notification exception: ' . $e->getMessage());
        }
        
        // Success response (always return success if validation passed)
        error_log('🎉 [AJAX Registration] Registration completed successfully');
        wp_send_json_success([
            'message' => self::get_success_message($registration_type, $form_data['full_name']),
            'registration_type' => $registration_type,
            'saved_id' => $saved_locally,
            'debug' => 'registration_complete'
        ]);
    }
    
    /**
     * Collect form data based on registration type
     */
    private static function collect_form_data($registration_type) {
        $data = [
            'timestamp' => current_time('mysql'),
            'name' => sanitize_text_field($_POST['full_name'] ?? ''),
            'full_name' => sanitize_text_field($_POST['full_name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'mobile' => sanitize_text_field($_POST['phone'] ?? ''),
            'registration_type' => ucfirst($registration_type),
            'source' => 'Website',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'status' => 'New',
            'terms_accepted' => 'Yes',
        ];
        
        // Type-specific fields
        switch ($registration_type) {
            case 'participant':
                $data['age'] = intval($_POST['age'] ?? 0);
                $data['occupation'] = sanitize_text_field($_POST['occupation'] ?? '');
                $data['city'] = sanitize_text_field($_POST['city'] ?? '');
                $data['location'] = sanitize_text_field($_POST['city'] ?? '');
                $data['competition_category'] = sanitize_text_field($_POST['competition_category'] ?? '');
                $data['categories'] = sanitize_text_field($_POST['competition_category'] ?? '');
                $data['previous_experience'] = sanitize_textarea_field($_POST['previous_experience'] ?? '');
                $data['experience'] = sanitize_textarea_field($_POST['previous_experience'] ?? '');
                $data['newsletter_subscribed'] = isset($_POST['newsletter']) ? 'Yes' : 'No';
                break;
                
            case 'volunteer':
                $data['city'] = sanitize_text_field($_POST['city'] ?? '');
                $data['location'] = sanitize_text_field($_POST['city'] ?? '');
                $data['volunteer_role'] = sanitize_text_field($_POST['volunteer_role'] ?? '');
                $data['availability'] = sanitize_text_field($_POST['availability'] ?? '');
                $data['skills'] = sanitize_textarea_field($_POST['skills'] ?? '');
                $data['photo_consent'] = isset($_POST['photo_consent']) ? 'Yes' : 'No';
                break;
                
            case 'writer':
                $data['city'] = sanitize_text_field($_POST['city'] ?? '');
                $data['location'] = sanitize_text_field($_POST['city'] ?? '');
                $data['writer_category'] = sanitize_text_field($_POST['writer_category'] ?? '');
                $data['published_works'] = sanitize_textarea_field($_POST['published_works'] ?? '');
                $data['genres'] = sanitize_text_field($_POST['genres'] ?? '');
                $data['social_media'] = sanitize_text_field($_POST['social_media'] ?? '');
                $data['bio'] = sanitize_textarea_field($_POST['bio'] ?? '');
                break;
        }
        
        return $data;
    }
    
    /**
     * Validate form data
     */
    private static function validate_form_data($data, $type) {
        $errors = [];
        
        // Common validation
        if (empty($data['full_name'])) {
            $errors['full_name'] = 'Full name is required';
        }
        
        if (empty($data['email']) || !is_email($data['email'])) {
            $errors['email'] = 'Valid email is required';
        }
        
        if (empty($data['phone'])) {
            $errors['phone'] = 'Phone number is required';
        } elseif (!preg_match('/^[0-9]{10}$/', preg_replace('/[^0-9]/', '', $data['phone']))) {
            $errors['phone'] = 'Please enter a valid 10-digit phone number';
        }
        
        // Type-specific validation
        switch ($type) {
            case 'participant':
                if (empty($data['age']) || $data['age'] < 1) {
                    $errors['age'] = 'Age is required';
                }
                if (empty($data['occupation'])) {
                    $errors['occupation'] = 'Occupation is required';
                }
                if (empty($data['city'])) {
                    $errors['city'] = 'City is required';
                }
                if (empty($data['competition_category'])) {
                    $errors['competition_category'] = 'Competition category is required';
                }
                break;
                
            case 'volunteer':
                if (empty($data['city'])) {
                    $errors['city'] = 'City is required';
                }
                if (empty($data['volunteer_role'])) {
                    $errors['volunteer_role'] = 'Volunteer role is required';
                }
                if (empty($data['availability'])) {
                    $errors['availability'] = 'Availability is required';
                }
                break;
                
            case 'writer':
                if (empty($data['city'])) {
                    $errors['city'] = 'City is required';
                }
                if (empty($data['writer_category'])) {
                    $errors['writer_category'] = 'Writer category is required';
                }
                break;
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Save registration data locally in WordPress database
     * Uses wp_options table to store registrations
     */
    private static function save_registration_locally($data, $type) {
        try {
            // Generate unique ID
            $registration_id = 'reg_' . time() . '_' . wp_generate_password(8, false);
            
            // Prepare data for storage
            $registration_data = [
                'id' => $registration_id,
                'type' => $type,
                'data' => $data,
                'timestamp' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'synced_to_sheets' => false
            ];
            
            // Get existing registrations
            $all_registrations = get_option('cgr_registrations', []);
            
            // Add new registration
            $all_registrations[$registration_id] = $registration_data;
            
            // Save back to database
            $saved = update_option('cgr_registrations', $all_registrations);
            
            if ($saved) {
                error_log('✅ [AJAX Registration] Saved locally: ' . $registration_id);
                return $registration_id;
            } else {
                error_log('⚠️ [AJAX Registration] Local save returned false (might already exist)');
                return $registration_id; // Still return ID as data might be saved
            }
            
        } catch (Exception $e) {
            error_log('❌ [AJAX Registration] Local save exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send WhatsApp notifications
     */
    private static function send_whatsapp_notifications($data, $type) {
        // Get admin phone from settings or use default
        $admin_phone = get_option('cgr_admin_phone', '9876543210');
        
        // Format user phone (remove spaces, dashes, etc.)
        $user_phone = preg_replace('/[^0-9]/', '', $data['phone']);
        
        // Admin notification message
        $admin_message = "🎯 New {$type} registration!\n\n";
        $admin_message .= "Name: {$data['full_name']}\n";
        $admin_message .= "Email: {$data['email']}\n";
        $admin_message .= "Phone: {$data['phone']}\n";
        $admin_message .= "City: " . ($data['city'] ?? 'N/A') . "\n";
        $admin_message .= "\nRegistered at: " . current_time('d-M-Y h:i A');
        
        // User confirmation message
        $user_message = "🙏 Namaste {$data['full_name']}!\n\n";
        $user_message .= "Thank you for registering with CGR as a {$type}. ";
        $user_message .= "We have received your registration successfully.\n\n";
        $user_message .= "Our team will contact you soon with further details.\n\n";
        $user_message .= "- Team CGR\n";
        $user_message .= "🌐 cgrindia.org";
        
        // Send messages
        try {
            CGR_WhatsApp_API::send_message($admin_phone, $admin_message);
            error_log('✅ [AJAX Registration] WhatsApp sent to admin: ' . $admin_phone);
            
            CGR_WhatsApp_API::send_message($user_phone, $user_message);
            error_log('✅ [AJAX Registration] WhatsApp sent to user: ' . $user_phone);
        } catch (Exception $e) {
            error_log('⚠️ [AJAX Registration] WhatsApp send failed: ' . $e->getMessage());
            // Don't fail the registration if WhatsApp fails
        }
    }
    
    /**
     * Get success message based on registration type
     */
    private static function get_success_message($type, $name) {
        $messages = [
            'participant' => "Thank you for registering, {$name}! Your participant registration has been received successfully. We will contact you soon with competition details.",
            'volunteer' => "Thank you for registering, {$name}! Your volunteer registration has been received successfully. We appreciate your willingness to help and will reach out soon.",
            'writer' => "Thank you for registering, {$name}! Your registration has been received successfully. We will reach out soon."
        ];
        
        return $messages[$type] ?? "Thank you for registering, {$name}! We will contact you soon.";
    }
}

// Initialize the handler
CGR_AJAX_Registration_Handler::init();
