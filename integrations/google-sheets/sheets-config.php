<?php
/**
 * CGR Google Sheets Configuration and Admin Settings
 *
 * Refactored for CGR website use (Registrations/Volunteers/Participants/Contacts)
 */

// Security: prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add admin menu for Google Sheets configuration
add_action('admin_menu', 'cgr_sheets_admin_menu');

function cgr_sheets_admin_menu() {
    add_options_page(
        'CGR Google Sheets Settings',
        'Google Sheets',
        'manage_options',
        'cgr-sheets',
        'cgr_sheets_admin_page'
    );
}

function cgr_sheets_admin_page() {
    // Handle log clearing
    if (isset($_GET['clear_logs']) && $_GET['clear_logs'] == '1') {
        if (class_exists('CGR_Registration_Logger')) {
            CGR_Registration_Logger::clear_logs();
            echo '<div class="notice notice-success"><p>Logs cleared successfully!</p></div>';
        }
    }
    
    // Handle Sync Action
    if (isset($_POST['trigger_sync'])) {
        if (!class_exists('CGR_Sheet_Importer')) {
            echo '<div class="notice notice-error"><p>Importer class not found.</p></div>';
        } else {
            $results = [];
            $results[] = CGR_Sheet_Importer::import_scientists();
            $results[] = CGR_Sheet_Importer::import_earth_leaders();
            $results[] = CGR_Sheet_Importer::import_cgr_team();
            
            echo '<div class="notice notice-success"><p>Sync Completed:</p><ul>';
            foreach($results as $res) {
                if (is_wp_error($res)) {
                    echo '<li>Error: ' . $res->get_error_message() . '</li>';
                } else {
                    echo '<li>' . esc_html($res) . '</li>';
                }
            }
            echo '</ul></div>';
        }
    }
    
    // Handle Configuration Save
    if ( isset($_POST['submit']) && check_admin_referer('cgr_sheets_options') ) {
        
        if (isset($_POST['spreadsheet_id'])) {
            update_option('cgr_google_spreadsheet_id', sanitize_text_field($_POST['spreadsheet_id']));
        }
        
        if (isset($_POST['client_id'])) {
            update_option('cgr_google_client_id', sanitize_text_field($_POST['client_id']));
        }
        
        if (isset($_POST['client_secret'])) {
            $posted_secret = trim(sanitize_text_field($_POST['client_secret']));
            // Avoid wiping the stored secret when the password field is left blank on save
            if ($posted_secret !== '') {
                update_option('cgr_google_client_secret', $posted_secret);
                update_option('cgr_sheets_client_secret', $posted_secret);
            }
        }
        
        if (isset($_POST['webhook_url'])) {
            $posted_webhook = sanitize_url($_POST['webhook_url']);
            update_option('cgr_google_sheets_webhook_url', $posted_webhook);
            update_option('cgr_sheets_webhook_url', $posted_webhook);
        }
        
        echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
    }

    $webhook_url = get_option('cgr_google_sheets_webhook_url', '');
    $client_id = get_option('cgr_google_client_id', '');
    $client_secret = get_option('cgr_google_client_secret', '');
    if (empty($client_secret)) {
        // Fallback so admins can see/update the value even if only the legacy option is set
        $client_secret = get_option('cgr_sheets_client_secret', '');
    }
    $spreadsheet_id = get_option('cgr_google_spreadsheet_id', '');
    
    // Sync Client Secret to the option name used by the Importer class
    if (!empty($client_secret)) {
        update_option('cgr_sheets_client_secret', $client_secret);
    }

    // Ensure webhook URL sync (legacy support)
    if (!empty($webhook_url) && empty(get_option('cgr_sheets_webhook_url'))) {
        update_option('cgr_sheets_webhook_url', $webhook_url);
    } elseif (empty($webhook_url) && !empty(get_option('cgr_sheets_webhook_url'))) {
        $webhook_url = get_option('cgr_sheets_webhook_url');
        update_option('cgr_google_sheets_webhook_url', $webhook_url);
    }
    ?>
    <div class="wrap">
        <h1>CGR Google Sheets Integration</h1>
        
        <div class="card" style="background: #e6fffa; border: 1px solid #38b2ac;">
            <h2>🔄 Two-Way Sync</h2>
            <p>Pull data from Google Sheets to update the website directories (Scientists, Earth Leaders, Team).</p>
            <form method="post">
                <input type="hidden" name="trigger_sync" value="1">
                <?php submit_button('Sync from Google Sheets Now', 'primary', 'sync_btn', false); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>Configuration</h2>
            <form method="post">
                <?php wp_nonce_field('cgr_sheets_options'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Google Spreadsheet ID</th>
                        <td>
                            <input type="text" name="spreadsheet_id" value="<?php echo esc_attr($spreadsheet_id); ?>" 
                                   class="regular-text" placeholder="e.g. 1AbCdEfG..."/>
                            <p class="description">Paste the ID from the spreadsheet URL.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Google Client ID</th>
                        <td>
                            <input type="text" name="client_id" value="<?php echo esc_attr($client_id); ?>" 
                                   class="regular-text" />
                            <p class="description">Google OAuth Client ID</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Google Client Secret</th>
                        <td>
                            <input type="password" name="client_secret" value="<?php echo esc_attr($client_secret); ?>" 
                                   class="regular-text" />
                            <p class="description">Google OAuth Client Secret</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Google Sheets Webhook URL</th>
                        <td>
                            <input type="url" name="webhook_url" value="<?php echo esc_attr($webhook_url); ?>" 
                                   class="regular-text" placeholder="https://script.google.com/macros/s/..."/>
                            <p class="description">
                                <strong>Current URL:</strong> <?php echo !empty($webhook_url) ? '<code>' . esc_html($webhook_url) . '</code>' : '<em>Not configured</em>'; ?><br>
                                <strong>Spreadsheet:</strong> 
                                <?php if ($spreadsheet_id): ?>
                                    <a href="https://docs.google.com/spreadsheets/d/<?php echo esc_attr($spreadsheet_id); ?>/edit" target="_blank">Open Spreadsheet</a>
                                <?php else: ?>
                                    <em>Set a Spreadsheet ID above</em>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>

        <div class="card">
            <h2>Credentials Status</h2>
            <?php 
            $validation = CGR_Google_Credentials::validate_credentials();
            $all_valid = $validation['all_valid'];
            ?>
            <div class="<?php echo $all_valid ? 'notice notice-success' : 'notice notice-warning'; ?>" style="padding: 10px;">
                <p><strong>Configuration Status:</strong> <?php echo $all_valid ? '✅ All credentials configured' : '⚠️ Setup incomplete'; ?></p>
                <ul>
                    <li>Client ID: <?php echo $validation['client_id_valid'] ? '✅ Valid' : '❌ Invalid'; ?></li>
                    <li>Client Secret: <?php echo $validation['client_secret_valid'] ? '✅ Valid' : '❌ Invalid'; ?></li>
                    <li>Webhook URL: <?php echo $validation['webhook_configured'] ? '✅ Configured' : '❌ Not set'; ?></li>
                    <li>Spreadsheet ID: <?php echo $validation['spreadsheet_id_valid'] ? '✅ Valid' : '❌ Not set'; ?></li>
                </ul>
            </div>
        </div>

        <div class="card">
                        <h2>Google Apps Script Setup (CGR)</h2>
            <ol>
                <li>Open <a href="https://script.google.com/" target="_blank">Google Apps Script</a></li>
                <li>Create a new project</li>
                                <li>Replace the default code with (update the Spreadsheet ID):</li>
            </ol>
            
            <textarea readonly style="width: 100%; height: 400px; font-family: monospace; background: #f8f9fa;">
/**
 * CGR Website – Google Sheets Integration (Unified)
 * Client ID: <?php echo esc_html($client_id); ?>
 * Spreadsheet ID: <?php echo esc_html($spreadsheet_id ?: 'YOUR_SPREADSHEET_ID'); ?>
 */

function doPost(e) {
    try {
        var spreadsheetId = '<?php echo esc_html($spreadsheet_id ?: 'YOUR_SPREADSHEET_ID'); ?>';
        var ss = SpreadsheetApp.openById(spreadsheetId);

        var data = JSON.parse(e.postData.contents);
        var sheetName = (data.sheet_name || data.registration_type || 'Registrations');
        var sheet = ss.getSheetByName(sheetName);
        if (!sheet) {
            sheet = ss.insertSheet(sheetName);
            addHeaders(sheet, sheetName);
        }

        if (sheet.getLastRow() === 0) {
            addHeaders(sheet, sheetName);
        }

        var row = buildRow(data, sheetName);
        sheet.appendRow(row);

        return ContentService.createTextOutput(JSON.stringify({
            success: true,
            result: 'success',
            sheet: sheetName,
            row: sheet.getLastRow()
        })).setMimeType(ContentService.MimeType.JSON);
    } catch (ex) {
        return ContentService.createTextOutput(JSON.stringify({
            success: false,
            result: 'error',
            message: ex.message
        })).setMimeType(ContentService.MimeType.JSON);
    }
}

function addHeaders(sheet, sheetName) {
    var base = ['Timestamp','Full Name','Email','Phone','City','Registration Type','Source','IP Address','User Agent','Status'];
    var extras = [];
    if (sheetName === 'Volunteers') {
        extras = ['Volunteer Role','Availability','Skills','Photo Consent'];
    } else if (sheetName === 'Participants') {
        extras = ['Age','Occupation'];
    } else if (sheetName === 'Contacts' || sheetName === 'Registrations') {
        extras = ['Message','Interest'];
    }
    var headers = base.concat(extras);
    sheet.appendRow(headers);
    var r = sheet.getRange(1,1,1,headers.length);
    r.setBackground('#1F4B2C');
    r.setFontColor('#ffffff');
    r.setFontWeight('bold');
}

function buildRow(data, sheetName) {
    var base = [
        data.timestamp || new Date(),
        data.name || data.full_name || '',
        data.email || '',
        data.phone || data.mobile || '',
        data.city || data.location || '',
        data.sheet_name || data.registration_type || 'Registrations',
        data.source || 'CGR Website',
        data.ip_address || '',
        data.user_agent || '',
        data.status || 'New'
    ];
    var extras = [];
    if (sheetName === 'Volunteers') {
        extras = [data.volunteer_role || '', data.availability || '', data.skills || '', data.photo_consent || ''];
    } else if (sheetName === 'Participants') {
        extras = [data.age || '', data.occupation || ''];
    } else if (sheetName === 'Contacts' || sheetName === 'Registrations') {
        extras = [data.message || '', data.interest || ''];
    }
    return base.concat(extras);
}

function testConnection() {
    var spreadsheetId = '<?php echo esc_html($spreadsheet_id ?: 'YOUR_SPREADSHEET_ID'); ?>';
    try {
        var ss = SpreadsheetApp.openById(spreadsheetId);
        Logger.log('✅ Connected: ' + ss.getName());
        return true;
    } catch (ex) {
        Logger.log('❌ Failed: ' + ex.message);
        return false;
    }
}
            </textarea>
            
            <ol start="4">
                <li>Click <strong>Deploy</strong> → <strong>New Deployment</strong></li>
                <li>Select <strong>Web app</strong> as the type</li>
                <li>Set "Execute as" to <strong>"Me"</strong></li>
                <li><strong>CRITICAL:</strong> Set "Who has access" to <strong>"Anyone"</strong> (this fixes 401 errors)</li>
                <li>Click <strong>Deploy</strong> and copy the <strong>Web app URL</strong></li>
                <li><strong>Important:</strong> Use the PRODUCTION URL (not /dev), it should end with /exec</li>
                <li>Paste the URL in the field above and save</li>
            </ol>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 15px;">
                <h4 style="color: #856404; margin-top: 0;">🔧 Fixing Connection Issues</h4>
                <p style="margin-bottom: 10px;"><strong>If you're getting "No webhook URL configured" errors:</strong></p>
                <ol style="margin: 0;">
                    <li><strong>Check URL Type:</strong> Make sure you're using the <strong>Web app URL</strong>, not the Library URL</li>
                    <li><strong>Web app URL should look like:</strong> <code>https://script.google.com/macros/s/[ID]/exec</code></li>
                    <li><strong>Library URL (wrong) looks like:</strong> <code>https://script.google.com/macros/library/d/[ID]/1</code></li>
                    <li>Go to your Google Apps Script project</li>
                    <li>Click <strong>Deploy</strong> → <strong>Manage Deployments</strong></li>
                    <li>Make sure you have a <strong>Web app</strong> deployment (not Library)</li>
                    <li>Set "Who has access" to <strong>"Anyone"</strong></li>
                    <li>Copy the <strong>Web app URL</strong> (ends with /exec)</li>
                </ol>
            </div>
        </div>

        <?php if (!empty($webhook_url)): ?>
        <!-- Registration Activity Dashboard -->
        <?php
        if (class_exists('CGR_Registration_Logger')) {
            $log_stats = CGR_Registration_Logger::get_log_stats();
            $recent_logs = CGR_Registration_Logger::get_recent_logs(10);
        } else {
            $log_stats = ['form_submissions' => 0, 'sync_successes' => 0, 'sync_failures' => 0, 'last_24_hours' => 0];
            $recent_logs = [];
        }
        ?>
        
        <div class="card" style="margin-bottom: 20px;">
            <h2>📊 Registration Activity Dashboard</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
                <div class="stat-card" style="background: #f0f8f0; padding: 15px; border-radius: 8px; text-align: center;">
                    <h3 style="color: #28a745; margin: 0; font-size: 1.5em;"><?php echo $log_stats['form_submissions']; ?></h3>
                    <p style="margin: 5px 0 0 0;">Form Submissions</p>
                </div>
                <div class="stat-card" style="background: #f0f4f8; padding: 15px; border-radius: 8px; text-align: center;">
                    <h3 style="color: #007cba; margin: 0; font-size: 1.5em;"><?php echo $log_stats['sync_successes']; ?></h3>
                    <p style="margin: 5px 0 0 0;">Successful Syncs</p>
                </div>
                <div class="stat-card" style="background: #fff8f0; padding: 15px; border-radius: 8px; text-align: center;">
                    <h3 style="color: #d63638; margin: 0; font-size: 1.5em;"><?php echo $log_stats['sync_failures']; ?></h3>
                    <p style="margin: 5px 0 0 0;">Sync Failures</p>
                </div>
                <div class="stat-card" style="background: #f8f0ff; padding: 15px; border-radius: 8px; text-align: center;">
                    <h3 style="color: #8b5cf6; margin: 0; font-size: 1.5em;"><?php echo $log_stats['last_24_hours']; ?></h3>
                    <p style="margin: 5px 0 0 0;">Last 24 Hours</p>
                </div>
            </div>
            
            <?php if (!empty($recent_logs)): ?>
            <h3>Recent Activity (Last <?php echo count($recent_logs); ?> events)</h3>
            <div style="max-height: 200px; overflow-y: auto; background: #f9f9f9; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; border: 1px solid #ddd;">
                <?php foreach (array_slice($recent_logs, 0, 5) as $log): ?>
                    <div style="margin-bottom: 5px; padding: 5px; <?php 
                        if (strpos($log, 'ERROR:') !== false || strpos($log, 'SYNC_FAILED:') !== false) {
                            echo 'background: #ffebee; border-left: 3px solid #d32f2f;';
                        } elseif (strpos($log, 'SUCCESS:') !== false || strpos($log, 'SYNC_SUCCESS:') !== false) {
                            echo 'background: #e8f5e8; border-left: 3px solid #388e3c;';
                        } elseif (strpos($log, 'FORM_SUBMISSION:') !== false) {
                            echo 'background: #e3f2fd; border-left: 3px solid #1976d2;';
                        } else {
                            echo 'background: white; border-left: 3px solid #ccc;';
                        }
                    ?> border-radius: 3px;">
                        <?php echo esc_html($log); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <p style="margin-top: 10px;">
                <button type="button" onclick="toggleFullLogs()" class="button">View All Logs</button>
                <a href="?page=cgr-sheets&clear_logs=1" class="button" onclick="return confirm('Clear all logs? This action cannot be undone.')">Clear Logs</a>
            </p>
            
            <div id="full-logs" style="display: none; margin-top: 15px;">
                <h4>All Recent Activity</h4>
                <div style="max-height: 400px; overflow-y: auto; background: #f9f9f9; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 11px; border: 1px solid #ddd;">
                    <?php foreach ($recent_logs as $log): ?>
                        <div style="margin-bottom: 2px; padding: 3px; <?php 
                            if (strpos($log, 'ERROR:') !== false || strpos($log, 'SYNC_FAILED:') !== false) {
                                echo 'background: #ffebee; border-left: 3px solid #d32f2f;';
                            } elseif (strpos($log, 'SUCCESS:') !== false || strpos($log, 'SYNC_SUCCESS:') !== false) {
                                echo 'background: #e8f5e8; border-left: 3px solid #388e3c;';
                            } elseif (strpos($log, 'FORM_SUBMISSION:') !== false) {
                                echo 'background: #e3f2fd; border-left: 3px solid #1976d2;';
                            } else {
                                echo 'background: white;';
                            }
                        ?>">
                            <?php echo esc_html($log); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <p style="text-align: center; padding: 20px; color: #666; font-style: italic;">
                📝 No activity logged yet. Registration logs will appear here once forms are submitted.
            </p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>🔗 Test Connection</h2>
            <p>Test if your Google Sheets integration is working:</p>
            
            <?php if (empty($webhook_url)): ?>
            <div style="padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; margin-bottom: 15px; color: #856404;">
                <strong>⚠️ Setup Required:</strong> You need to deploy the Google Apps Script first and set the webhook URL above.
            </div>
            <?php endif; ?>
            
            <button type="button" id="test-sheets-connection" class="button" <?php echo empty($webhook_url) ? 'disabled' : ''; ?>>
                Test Connection
            </button>
            <div id="connection-result" style="margin-top: 10px;"></div>
        </div>        <script>
        // Toggle full logs visibility
        function toggleFullLogs() {
            const fullLogs = document.getElementById('full-logs');
            const button = event.target;
            
            if (fullLogs.style.display === 'none') {
                fullLogs.style.display = 'block';
                button.textContent = 'Hide Logs';
            } else {
                fullLogs.style.display = 'none';
                button.textContent = 'View All Logs';
            }
        }
        
        document.getElementById('test-sheets-connection').addEventListener('click', function() {
            const button = this;
            const resultDiv = document.getElementById('connection-result');
            
            button.disabled = true;
            button.textContent = 'Testing...';
            resultDiv.innerHTML = '<div style="padding: 10px; background: #f0f8ff; border-radius: 5px;">🔄 Testing connection to Google Sheets...</div>';
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=test_cgr_sheets'
            })
            .then(response => response.json())
            .then(data => {
                button.disabled = false;
                button.textContent = 'Test Connection';
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div style="padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724; margin-top: 10px;">
                            <h4 style="margin: 0 0 10px 0; color: #155724;">✅ Connection Successful!</h4>
                            <p style="margin: 0;">Test data has been sent to your Google Sheets successfully.</p>
                            ${data.data && data.data.response_body ? '<p style="margin: 10px 0 0 0; font-size: 12px; opacity: 0.8;">Response: ' + data.data.response_body + '</p>' : ''}
                        </div>
                    `;
                } else {
                    const errorMessage = data.data ? (data.data.message || data.data) : 'Unknown error';
                    resultDiv.innerHTML = `
                        <div style="padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24; margin-top: 10px;">
                            <h4 style="margin: 0 0 10px 0; color: #721c24;">❌ Connection Failed</h4>
                            <p style="margin: 0;"><strong>Error:</strong> ${errorMessage}</p>
                            ${data.data && data.data.debug_info ? '<p style="margin: 10px 0 0 0; font-size: 12px; opacity: 0.8;"><strong>Debug:</strong> ' + data.data.debug_info + '</p>' : ''}
                            <div style="margin-top: 10px; padding: 10px; background: rgba(0,0,0,0.1); border-radius: 3px; font-size: 12px;">
                                <strong>Troubleshooting:</strong><br>
                                1. Make sure you've deployed the Google Apps Script<br>
                                2. Check that the webhook URL is correctly set<br>
                                3. Verify the script has proper permissions
                            </div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                button.disabled = false;
                button.textContent = 'Test Connection';
                resultDiv.innerHTML = `
                    <div style="padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24; margin-top: 10px;">
                        <h4 style="margin: 0 0 10px 0; color: #721c24;">❌ Network Error</h4>
                        <p style="margin: 0;"><strong>Error:</strong> ${error.message}</p>
                        <p style="margin: 10px 0 0 0; font-size: 12px;">Please check your internet connection and try again.</p>
                    </div>
                `;
            });
        });
        </script>
        <?php endif; ?>
    </div>
    <?php
}

// AJAX handler for testing connection
add_action('wp_ajax_test_cgr_sheets', 'handle_test_cgr_sheets');

function handle_test_cgr_sheets() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access']);
        return;
    }
    
    try {
        $result = CGR_Sheet_Sync::test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Exception occurred: ' . $e->getMessage(),
            'debug_info' => 'Check error logs for more details'
        ]);
    }
}

// Initialize Google Sheets configuration
add_action('init', function() {
    // Set default webhook URL if not set
    if (!get_option('cgr_google_sheets_webhook_url')) {
        add_option('cgr_google_sheets_webhook_url', '');
    }
});
