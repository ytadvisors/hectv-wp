<?php
/*
 * Plugin Name: HECTV Admin
 * Author: YT Advisors
 * Description: Main Site setup for HEC-TV
 * Text Domain: hectv
 * Version: 0.7
 * Author URI: ytadvisors.com
 * License: GPLv2
*/

date_default_timezone_set ( "America/Chicago" );

Inpsyde\Wonolog\bootstrap();
$admin = new \HECTV\HECTV_Admin();