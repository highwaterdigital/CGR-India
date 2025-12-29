<?php
/**
 * Registration Sync Logger System
 * 
 * Provides comprehensive logging for registration form submissions,
 * Google Sheets sync attempts, and debug information.
 * 
 * @package CGR Child Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CGR_Registration_Logger {
    
    private static $log_file;
    private static $max_log_size = 5242880; // 5MB
    
    /**
     * Initialize the logger
     */
    public static function init() {
        $upload_dir = wp_upload_dir();
        self::$log_file = $upload_dir['basedir'] . '/cgr-registration-logs.txt';
        
        // Create log file if it doesn't exist
        if (!file_exists(self::$log_file)) {
            touch(self::$log_file);
            self::log('system', 'Registration logger initialized', [
                'log_file' => self::$log_file,
                'max_size' => self::$max_log_size,
                'timestamp' => current_time('mysql')
            ]);
        }
    }
    
    /**
     * Log an event
     */
    public static function log($type, $message, $data = []) {
        if (!self::$log_file) {
            self::init();
        }
        
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'ip' => self::get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown'
        ];
        
        $log_line = '[' . $log_entry['timestamp'] . '] ' . 
                   strtoupper($log_entry['type']) . ': ' . 
                   $log_entry['message'] . 
                   (!empty($log_entry['data']) ? ' | Data: ' . json_encode($log_entry['data']) : '') . 
                   ' | IP: ' . $log_entry['ip'] . PHP_EOL;
        
        // Rotate log if too large
        self::rotate_log_if_needed();
        
        // Write to log file
        file_put_contents(self::$log_file, $log_line, FILE_APPEND | LOCK_EX);
        
        // Also log to WordPress error log for critical events
        if (in_array($type, ['error', 'critical', 'sync_failed'])) {
            error_log('CGR Registration: ' . $message . ' | ' . json_encode($data));
        }
    }
    
    /**
     * Log form submission attempt
     */
    public static function log_form_submission($form_data) {
        // Sanitize sensitive data for logging
        $safe_data = $form_data;
        if (isset($safe_data['email'])) {
            $safe_data['email'] = self::mask_email($safe_data['email']);
        }
        if (isset($safe_data['phone'])) {
            $safe_data['phone'] = self::mask_phone($safe_data['phone']);
        }
        
        self::log('form_submission', 'Registration form submitted', [
            'form_data' => $safe_data,
            'field_count' => count($form_data),
            'has_required_fields' => self::validate_required_fields($form_data)
        ]);
    }
    
    /**
     * Log Google Sheets sync attempt
     */
    public static function log_sync_attempt($webhook_url, $payload) {
        // Sanitize payload for logging
        $safe_payload = $payload;
        if (isset($safe_payload['email'])) {
            $safe_payload['email'] = self::mask_email($safe_payload['email']);
        }
        if (isset($safe_payload['phone'])) {
            $safe_payload['phone'] = self::mask_phone($safe_payload['phone']);
        }
        
        self::log('sync_attempt', 'Attempting Google Sheets sync', [
            'webhook_url' => $webhook_url,
            'payload_fields' => array_keys($payload),
            'payload_size' => strlen(json_encode($payload))
        ]);
    }
    
    /**
     * Log successful sync
     */
    public static function log_sync_success($response) {
        self::log('sync_success', 'Google Sheets sync completed successfully', [
            'response' => $response,
            'response_code' => wp_remote_retrieve_response_code($response),
            'response_body' => wp_remote_retrieve_body($response)
        ]);
    }
    
    /**
     * Log sync failure
     */
    public static function log_sync_error($error_message, $response = null) {
        $error_data = [
            'error' => $error_message
        ];
        
        if ($response) {
            $error_data['response_code'] = wp_remote_retrieve_response_code($response);
            $error_data['response_body'] = wp_remote_retrieve_body($response);
            $error_data['response_headers'] = wp_remote_retrieve_headers($response);
        }
        
        self::log('sync_failed', 'Google Sheets sync failed', $error_data);
    }
    
    /**
     * Log connection test
     */
    public static function log_connection_test($result, $details = []) {
        self::log('connection_test', 'Connection test performed', [
            'result' => $result,
            'details' => $details
        ]);
    }
    
    /**
     * Get recent logs
     */
    public static function get_recent_logs($limit = 50) {
        if (!file_exists(self::$log_file)) {
            return [];
        }
        
        $lines = file(self::$log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice(array_reverse($lines), 0, $limit);
    }
    
    /**
     * Get logs by type
     */
    public static function get_logs_by_type($type, $limit = 20) {
        $all_logs = self::get_recent_logs(200); // Get more to filter
        $filtered = [];
        
        foreach ($all_logs as $log) {
            if (strpos($log, strtoupper($type) . ':') !== false) {
                $filtered[] = $log;
                if (count($filtered) >= $limit) {
                    break;
                }
            }
        }
        
        return $filtered;
    }
    
    /**
     * Get log statistics
     */
    public static function get_log_stats() {
        $logs = self::get_recent_logs(1000);
        
        $stats = [
            'total_entries' => count($logs),
            'form_submissions' => 0,
            'sync_attempts' => 0,
            'sync_successes' => 0,
            'sync_failures' => 0,
            'connection_tests' => 0,
            'errors' => 0,
            'last_24_hours' => 0
        ];
        
        $yesterday = strtotime('-24 hours');
        
        foreach ($logs as $log) {
            // Count by type
            if (strpos($log, 'FORM_SUBMISSION:') !== false) $stats['form_submissions']++;
            if (strpos($log, 'SYNC_ATTEMPT:') !== false) $stats['sync_attempts']++;
            if (strpos($log, 'SYNC_SUCCESS:') !== false) $stats['sync_successes']++;
            if (strpos($log, 'SYNC_FAILED:') !== false) $stats['sync_failures']++;
            if (strpos($log, 'CONNECTION_TEST:') !== false) $stats['connection_tests']++;
            if (strpos($log, 'ERROR:') !== false) $stats['errors']++;
            
            // Count recent entries
            if (preg_match('/\[([\d\-\s:]+)\]/', $log, $matches)) {
                $log_time = strtotime($matches[1]);
                if ($log_time >= $yesterday) {
                    $stats['last_24_hours']++;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Clear old logs
     */
    public static function clear_logs() {
        if (file_exists(self::$log_file)) {
            unlink(self::$log_file);
            self::init();
            return true;
        }
        return false;
    }
    
    /**
     * Private helper methods
     */
    private static function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    private static function mask_email($email) {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return 'invalid_email';
        
        $username = $parts[0];
        $domain = $parts[1];
        
        if (strlen($username) <= 2) {
            $masked_username = str_repeat('*', strlen($username));
        } else {
            $masked_username = substr($username, 0, 2) . str_repeat('*', strlen($username) - 2);
        }
        
        return $masked_username . '@' . $domain;
    }
    
    private static function mask_phone($phone) {
        if (strlen($phone) <= 4) {
            return str_repeat('*', strlen($phone));
        }
        
        return substr($phone, 0, 2) . str_repeat('*', strlen($phone) - 4) . substr($phone, -2);
    }
    
    private static function validate_required_fields($form_data) {
        $required_fields = ['full_name', 'email', 'phone'];
        
        foreach ($required_fields as $field) {
            if (empty($form_data[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    private static function rotate_log_if_needed() {
        if (!file_exists(self::$log_file)) {
            return;
        }
        
        if (filesize(self::$log_file) >= self::$max_log_size) {
            $backup_file = self::$log_file . '.backup.' . date('Y-m-d-H-i-s');
            rename(self::$log_file, $backup_file);
            
            // Keep only last 3 backup files
            $backup_pattern = dirname(self::$log_file) . '/cgr-registration-logs.txt.backup.*';
            $backup_files = glob($backup_pattern);
            if (count($backup_files) > 3) {
                usort($backup_files, function($a, $b) {
                    return filemtime($a) - filemtime($b);
                });
                
                // Remove oldest files
                for ($i = 0; $i < count($backup_files) - 3; $i++) {
                    unlink($backup_files[$i]);
                }
            }
        }
    }
}

// Initialize logger
CGR_Registration_Logger::init();