<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface HWD_SS_Network_Interface {
    public function post( array $data );
}
