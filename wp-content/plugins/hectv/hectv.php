<?php
/*
 * Plugin Name: HECTV Admin
 * Author: YT Advisors
 * Text Domain: hectv
*/

date_default_timezone_set ( "America/Chicago" );

require_once __DIR__ . '/vendor/autoload.php';

if (!class_exists('HECTV\HECTV_Admin')) {
    $autoloader = require_once(__DIR__ . '/autoloader.php');
    $autoloader('HECTV\\', __DIR__ . "/");
}

$admin = new \HECTV\HECTV_Admin();