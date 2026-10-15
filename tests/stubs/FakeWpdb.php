<?php

class WCB2B_Fake_Wpdb {

    public $prefix   = 'wp_';
    public $posts    = 'wp_posts';
    public $postmeta = 'wp_postmeta';
    public $termmeta = 'wp_termmeta';
    public $usermeta = 'wp_usermeta';
    public $queries  = array();

    public function query( $sql ) {
        $this->queries[] = $sql;
        return 0;
    }
}
