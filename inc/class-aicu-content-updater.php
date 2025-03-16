<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Class AICU_Content_Updater
 */
class AICU_Content_Updater {
	/**
	 * Add hooks.
	 *
	 * @return void
	 */
	static function init(): void {
		if ( ! is_admin() ) {
			require_once 'class-aicu-output-manager.php';
			AICU_Output_Manager::init();
		}

		if ( apply_filters( 'aicu/custom_context/site', true ) ) {
			require_once 'class-aicu-site-context.php';
			AICU_Site_Context::init();
		}

		if ( apply_filters( 'aicu/custom_context/post', true ) ) {
			require_once 'class-aicu-post-context.php';
			AICU_Post_Context::init();
		}

		if ( apply_filters( 'aicu/custom_context/archive', true ) ) {
			require_once 'class-aicu-archive-context.php';
			AICU_Archive_Context::init();
		}

		do_action( 'aicu/init' );
	}
}