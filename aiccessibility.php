<?php
/**
 * Plugin Name: AIccessibility Content Updater
 * Description: AI-powered accessibility improvements for WordPress content.
 * Version: poc
 * Plugin URI: https://github.com/the-another/aiccessibility
 * Plugin Author: CloudFest Hackathon 2025 Project Team
 * Author URI: https://hackathon.cloudfest.com/project/aiccessiblity-content-updater/
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

require_once 'inc/class-aicu-content-updater.php';
AICU_Content_Updater::init();