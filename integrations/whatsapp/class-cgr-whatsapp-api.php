<?php
/**
 * Handles sending WhatsApp messages via your chosen vendor (API).
 */
class CGR_WhatsApp_API {
    
    public static function send_message( $phone_number, $message ) {
        // -----------------------------------------------------------------
        // !! CRITICAL ACTION !!
        // -----------------------------------------------------------------
        // 1. Get your WhatsApp Vendor's API URL and Auth Token/API Key
        // 2. Paste them into the variables below.
        // 3. You MUST update the '$body' array to match your vendor's API.
        // -----------------------------------------------------------------
        $api_url = 'YOUR_WHATSAPP_VENDOR_API_URL_HERE';
        $auth_token = 'YOUR_VENDOR_AUTH_TOKEN_OR_API_KEY_HERE';

        if ( empty( $api_url ) || $api_url == 'YOUR_WHATSAPP_VENDOR_API_URL_HERE' ) {
            return; // Do nothing if not configured
        }
        
        // This body structure is a GUESS. You MUST change this
        // to match your specific WhatsApp API provider's documentation.
        $body = [
            'to' => $phone_number,
            'message' => $message
        ];

        // -----------------------------------------------------------------
        // !! CRITICAL STEP 4 !!
        // -----------------------------------------------------------------
        // Update the 'headers' array based on your API vendor's requirements.
        // Some use 'Authorization: Bearer token', others use custom headers.
        // Check your vendor's documentation for exact header format.
        // -----------------------------------------------------------------

        $response = wp_remote_post( $api_url, [
            'method'    => 'POST',
            'headers'   => [
                'Authorization' => 'Bearer ' . $auth_token, // Update format if needed
                'Content-Type'  => 'application/json'
            ],
            'body'      => json_encode( $body ),
            'sslverify' => false, // Use true on a live server with SSL
            'timeout'   => 30,
        ]);

        // -----------------------------------------------------------------
        // !! CRITICAL STEP 5 !!
        // -----------------------------------------------------------------
        // Check response and log results for debugging
        // -----------------------------------------------------------------
        if ( is_wp_error( $response ) ) {
            error_log( 'WhatsApp API Error: ' . $response->get_error_message() );
        } else {
            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );
            error_log( 'WhatsApp API Response Code: ' . $response_code );
            error_log( 'WhatsApp API Response Body: ' . $response_body );
        }
    }
}