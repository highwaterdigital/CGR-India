<?php
/**
 * Handles sending data to Google Sheets via a Webhook.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class CGR_Sheet_Sync {
    
    public static function send_data_to_sheet( $data ) {
        // Load credentials
        require_once get_stylesheet_directory() . '/integrations/google-sheets/credentials.php';
        
        // -----------------------------------------------------------------
        // CGR Google Sheets Integration with Secure Credentials
        // -----------------------------------------------------------------
        
        $credentials = CGR_Google_Credentials::get_api_credentials();
        $webhook_url = $credentials['webhook_url'];
        
        // Also try the direct option if credentials don't have it
        if (empty($webhook_url)) {
            $webhook_url = get_option('cgr_sheets_webhook_url');
        }
        
        // Try alternative option name used in admin
        if (empty($webhook_url)) {
            $webhook_url = get_option('cgr_google_sheets_webhook_url');
        }
        
        if ( empty( $webhook_url ) ) {
            // Log error for debugging
            error_log('CGR Google Sheets: Webhook URL not configured');
            return [
                'success' => false,
                'message' => 'Webhook URL not configured',
                'debug_info' => 'No webhook URL found in credentials or options'
            ];
        }
        
        // Prepare enhanced data for CGR Google Sheets
        $body = [
            'timestamp' => $data['timestamp'] ?? date('Y-m-d H:i:s'),
            'name' => sanitize_text_field($data['name'] ?? $data['full_name'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? $data['mobile'] ?? ''),
            'age' => sanitize_text_field($data['age'] ?? ''),
            'occupation' => sanitize_text_field($data['occupation'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? $data['location'] ?? ''),
            'interest' => sanitize_text_field($data['interest'] ?? ''),
            'message' => sanitize_text_field($data['message'] ?? ''),
            'competition_category' => sanitize_text_field($data['competition_category'] ?? $data['categories'] ?? ''),
            'previous_experience' => sanitize_text_field($data['previous_experience'] ?? $data['experience'] ?? ''),
            'registration_type' => sanitize_text_field($data['registration_type'] ?? 'Website Registration'),
            'source' => sanitize_text_field($data['source'] ?? 'CGR Website'),
            'ip_address' => $data['ip_address'] ?? self::get_user_ip(),
            'user_agent' => $data['user_agent'] ?? (isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 200) : ''),
            'status' => $data['status'] ?? 'New',
            'terms_accepted' => $data['terms_accepted'] ?? 'Unknown',
            'newsletter_subscribed' => $data['newsletter_subscribed'] ?? 'Unknown',
            // Add sheet_name parameter for multi-sheet support
            'sheet_name' => $data['sheet_name'] ?? 'Registrations',
            // Add volunteer-specific fields if present
            'volunteer_role' => sanitize_text_field($data['volunteer_role'] ?? ''),
            'availability' => sanitize_text_field($data['availability'] ?? ''),
            'skills' => sanitize_text_field($data['skills'] ?? ''),
            'photo_consent' => sanitize_text_field($data['photo_consent'] ?? ''),
            // Add writer-specific fields if present
            'writer_category' => sanitize_text_field($data['writer_category'] ?? ''),
            'published_works' => sanitize_text_field($data['published_works'] ?? ''),
            'genres' => sanitize_text_field($data['genres'] ?? ''),
            'social_media' => sanitize_url($data['social_media'] ?? ''),
            'bio' => sanitize_text_field($data['bio'] ?? ''),
            
            // Scientist fields
            'specialization' => sanitize_text_field($data['specialization'] ?? $data['spec'] ?? ''),
            'institution' => sanitize_text_field($data['institution'] ?? $data['inst'] ?? ''),
            
            // Earth Leader fields
            'training_year' => sanitize_text_field($data['training_year'] ?? $data['year'] ?? ''),
            'district' => sanitize_text_field($data['district'] ?? ''),
            'organization' => sanitize_text_field($data['organization'] ?? $data['org'] ?? ''),
            
            // CGR Team fields
            'designation' => sanitize_text_field($data['designation'] ?? $data['desig'] ?? ''),
            'role_type' => sanitize_text_field($data['role_type'] ?? $data['role'] ?? ''),
            
            // Common
            'photo_url' => sanitize_url($data['photo_url'] ?? $data['photo'] ?? '')
        ];

        // Send data to Google Sheets
        $response = wp_remote_post( $webhook_url, [
            'body' => json_encode( $body ),
            'headers' => [ 
                'Content-Type' => 'application/json',
                'User-Agent' => 'CGR-Website/1.0'
            ],
            'timeout' => 30,
            'sslverify' => true,
        ]);

        // Handle response
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            error_log('CGR Google Sheets Error: ' . $error_message);
            return [
                'success' => false,
                'message' => 'HTTP request failed: ' . $error_message,
                'debug_info' => 'WordPress HTTP error'
            ];
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        
        if ( $response_code !== 200 ) {
            error_log('CGR Google Sheets HTTP Error: ' . $response_code . ' - ' . $response_body);
            return [
                'success' => false,
                'message' => 'Webhook returned non-200 status',
                'debug_info' => [
                    'response_code' => $response_code,
                    'response_body' => $response_body
                ]
            ];
        }
        
        // Log success
        error_log('CGR Google Sheets: Successfully sent data for ' . $body['name']);
        
        return [
            'success' => true,
            'message' => 'Data sent to Google Sheet successfully',
            'debug_info' => [
                'response_code' => $response_code,
                'response_body' => json_decode($response_body, true)
            ]
        ];
    }

    /**
     * Send volunteer data to separate Google Sheets tab
     */
    public function append_volunteer_data($data) {
        require_once get_stylesheet_directory() . '/integrations/google-sheets/credentials.php';
        
        $credentials = CGR_Google_Credentials::get_api_credentials();
        $webhook_url = $credentials['volunteer_webhook_url'] ?? $credentials['webhook_url'];
        
        if (empty($webhook_url)) {
            $webhook_url = get_option('cgr_volunteer_webhook_url', get_option('cgr_sheets_webhook_url'));
        }
        
        if (empty($webhook_url)) {
            error_log('CGR Volunteer: Webhook URL not configured');
            return false;
        }
        
        $body = [
            'sheet_type' => 'volunteers',
            'timestamp' => $data['timestamp'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'age' => $data['age'],
            'gender' => $data['gender'],
            'address' => $data['address'],
            'occupation' => $data['occupation'],
            'institution' => $data['institution'],
            'volunteer_role' => $data['volunteer_role'],
            'availability' => $data['availability'],
            'skills' => $data['skills'],
            'why_volunteer' => $data['why_volunteer'],
            'emergency_name' => $data['emergency_name'],
            'emergency_phone' => $data['emergency_phone'],
            'emergency_relation' => $data['emergency_relation'],
            'photo_consent' => $data['photo_consent'],
            'ip_address' => self::get_user_ip()
        ];
        
        $response = wp_remote_post($webhook_url, [
            'body' => json_encode($body),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            error_log('CGR Volunteer Sync Error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code === 200;
    }
    
    /**
     * Get user's IP address
     */
    private static function get_user_ip() {
        $ip_fields = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_fields as $field) {
            if (!empty($_SERVER[$field])) {
                $ip = $_SERVER[$field];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
    }

    /**
     * Test the Google Sheets connection
     */
    public static function test_connection() {
        // Load logger for connection test logging
        if (file_exists(get_stylesheet_directory() . '/integrations/google-sheets/registration-logger.php')) {
            require_once get_stylesheet_directory() . '/integrations/google-sheets/registration-logger.php';
        }
        
        // Try both possible option names
        $webhook_url = get_option('cgr_sheets_webhook_url');
        if (empty($webhook_url)) {
            $webhook_url = get_option('cgr_google_sheets_webhook_url');
        }
        
        if (empty($webhook_url)) {
            $result = [
                'success' => false,
                'message' => 'No webhook URL configured. Please set up your Google Apps Script webhook URL.',
                'debug_info' => 'Webhook URL is empty in both WordPress option locations'
            ];
            
            if (class_exists('CGR_Registration_Logger')) {
                CGR_Registration_Logger::log_connection_test('failed', $result);
            }
            return $result;
        }
        
        // Validate webhook URL format
        if (!filter_var($webhook_url, FILTER_VALIDATE_URL)) {
            $result = [
                'success' => false,
                'message' => 'Invalid webhook URL format. Please check your Google Apps Script URL.',
                'debug_info' => 'URL validation failed: ' . $webhook_url
            ];
            
            if (class_exists('CGR_Registration_Logger')) {
                CGR_Registration_Logger::log_connection_test('failed', $result);
            }
            return $result;
        }
        
        // Check if URL looks like a Google Apps Script URL
        if (strpos($webhook_url, 'script.google.com') === false) {
            $result = [
                'success' => false,
                'message' => 'Webhook URL does not appear to be a Google Apps Script URL.',
                'debug_info' => 'URL does not contain script.google.com domain'
            ];
            
            if (class_exists('CGR_Registration_Logger')) {
                CGR_Registration_Logger::log_connection_test('failed', $result);
            }
            return $result;
        }
        
        // Check if it's a library URL instead of web app URL
        if (strpos($webhook_url, '/macros/library/') !== false) {
            $result = [
                'success' => false,
                'message' => 'Wrong URL type: This is a Library URL. You need a Web App deployment URL that ends with /exec',
                'debug_info' => 'Library URLs cannot receive POST requests. Create a Web app deployment instead.'
            ];
            
            if (class_exists('CGR_Registration_Logger')) {
                CGR_Registration_Logger::log_connection_test('failed', $result);
            }
            return $result;
        }
        
        // Check if it's a /dev URL instead of /exec
        if (strpos($webhook_url, '/dev') !== false) {
            $result = [
                'success' => false,
                'message' => 'Development URL detected: URLs ending with /dev may have permission issues. Try the production URL ending with /exec',
                'debug_info' => 'Replace /dev with /exec in your webhook URL for better reliability.'
            ];
            
            if (class_exists('CGR_Registration_Logger')) {
                CGR_Registration_Logger::log_connection_test('failed', $result);
            }
            return $result;
        }
        
        // Test data matching the expected format
        $test_data = [
            'timestamp' => current_time('mysql'),
            'name' => 'CGR Test Registration',
            'email' => 'test@cgrindia.org',
            'phone' => '9999999999',
            'age' => 25,
            'city' => 'Hyderabad',
            'registration_type' => 'Registrations',
            'interest' => 'Website',
            'source' => 'Connection Test',
            'ip_address' => self::get_user_ip(),
            'user_agent' => 'Mozilla/5.0 (Connection Test)',
            'status' => 'Test'
        ];
        
        if (class_exists('CGR_Registration_Logger')) {
            CGR_Registration_Logger::log_connection_test('attempting', [
                'webhook_url' => $webhook_url,
                'test_data_fields' => array_keys($test_data),
                'option1' => get_option('cgr_sheets_webhook_url'),
                'option2' => get_option('cgr_google_sheets_webhook_url')
            ]);
        }
        
        $result = self::send_data_to_sheet($test_data);
        
        // Log the connection test result
        if (class_exists('CGR_Registration_Logger')) {
            if ($result['success']) {
                CGR_Registration_Logger::log_connection_test('success', [
                    'response_body' => $result['response_body'] ?? 'No response body'
                ]);
            } else {
                CGR_Registration_Logger::log_connection_test('failed', [
                    'error_message' => $result['message'],
                    'response_code' => $result['response_code'] ?? 'No response code',
                    'debug_info' => $result['debug_info'] ?? 'No debug info'
                ]);
            }
        }
        
        return $result;
    }
}
// To get your Webhook URL:
// 1. Go to script.google.com and create a new project.
// 2. Paste this code:
/*
function doPost(e) {
  try {
    var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName('Registrations');
    if (!sheet) {
      sheet = SpreadsheetApp.getActiveSpreadsheet().insertSheet('Registrations');
      sheet.appendRow(['Date', 'Name', 'Email', 'Phone']);
    }
    
    var data = JSON.parse(e.postData.contents);
    
    sheet.appendRow([
      data.date,
      data.name, 
      data.email, 
      data.phone
    ]);
    
    return ContentService.createTextOutput(JSON.stringify({result: 'success'})).setMimeType(ContentService.MimeType.JSON);
  } catch (ex) {
    return ContentService.createTextOutput(JSON.stringify({result: 'error', error: ex.message})).setMimeType(ContentService.MimeType.JSON);
  }
}
*/
// 3. Click Deploy > New Deployment > Select Type (Web app).
// 4. Set "Execute as" to "Me".
// 5. Set "Who has access" to "Anyone".
// 6. Click Deploy and copy the "Web app URL".