<?php
/*
 * Plugin Name: Criminals Sitemap Provider
 * Plugin URI: https://myrotvorets.center/
 * Description: Sitemap Provider for Criminals CPT
 * Version: 1.0.0
 * Author: Myrotvorets
 * Author URI: https://myrotvorets.center/
 * License: MIT
 */

use Myrotvorets\WordPress\Sitemaps\Plugin;

if ( defined( 'ABSPATH' ) ) {
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	} elseif ( file_exists( ABSPATH . 'vendor/autoload.php' ) ) {
		require_once ABSPATH . 'vendor/autoload.php';
	}

	Plugin::instance();
}
