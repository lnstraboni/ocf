<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Legacy environment (WP < 6.4)

require_once __DIR__ . '/core/class-ocf-main-legacy.php';

OCFLITE_Main_Legacy::init();
