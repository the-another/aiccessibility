<?php
/**
 * Plugin Name: AIccessibility Content Updater
 * Description: AI-powered accessibility improvements for WordPress content.
 * Version: poc
 * Plugin URI: https://github.com/the-another/aiccessibility
 * Author: CloudFest Hackathon 2025 Project Team
 * Author URI: https://hackathon.cloudfest.com/project/aiccessiblity-content-updater/
 * Requires PHP: 8.3
 * Text Domain: aicu
 * Domain Path: /lang
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'AICU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once 'inc/class-aicu-content-updater.php';
AICU_Content_Updater::init();