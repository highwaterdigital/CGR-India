<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWD_SS_Network_YouTube extends HWD_SS_Network_Base {
    public function post( array $data ) {
        return [
            'success' => false,
            'error' => 'YouTube community posts are not supported via a public API. This adapter is a placeholder.',
        ];
    }
}
