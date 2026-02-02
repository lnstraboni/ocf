<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Modern environment (WP ≥ 6.4)
// Load modern classes only

require_once __DIR__ . '/core/class-ocf-main-modern.php';

OCFLITE_Main_Modern::init();
