<?php
/**
 * Loads all integration files.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

error_log('CGR DEBUG: Loading integrations-loader.php');

// Load Google Sheets Credentials
require_once CGR_CHILD_DIR . '/integrations/google-sheets/credentials.php';

// Load Google Sheet Sync Class
require_once CGR_CHILD_DIR . '/integrations/google-sheets/class-cgr-sheet-sync.php';

// Load Google Sheet Importer Class
require_once CGR_CHILD_DIR . '/integrations/google-sheets/class-cgr-sheet-importer.php';
if ( class_exists( 'CGR_Sheet_Importer' ) ) {
    error_log('CGR DEBUG: CGR_Sheet_Importer class loaded successfully.');
} else {
    error_log('CGR DEBUG: CGR_Sheet_Importer class FAILED to load.');
}

// Load Registration Logger
require_once CGR_CHILD_DIR . '/integrations/google-sheets/registration-logger.php';

// Load Registration Handler
require_once CGR_CHILD_DIR . '/integrations/google-sheets/registration-handler.php';

// Load Admin Sync Handler
require_once CGR_CHILD_DIR . '/integrations/google-sheets/admin-sync.php';

// Load Google Sheets Configuration (Admin Interface)
require_once CGR_CHILD_DIR . '/integrations/google-sheets/sheets-config.php';

// Load WhatsApp API Class
require_once CGR_CHILD_DIR . '/integrations/whatsapp/class-cgr-whatsapp-api.php';

// Note: Integration hooks are now loaded via inc/hooks.php