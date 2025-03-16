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
		if ( ! is_admin() && apply_filters( 'aicu/improve_html', true ) ) {
			require_once 'class-aicu-output-manager.php';
			AICU_Output_Manager::init();
		}

		if ( apply_filters( 'aicu/generate_alt', true ) ) {
			require_once 'class-aicu-media-manager.php';
			AICU_Media_Manager::init();
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

	/**
	 * Get site context.
	 *
	 * @return array
	 */
	static function get_context(): array {
		$context = array();

		$context['site_url'] = get_site_url();
		$context['site_name'] = get_bloginfo( 'name' );
		$context['site_description'] = get_bloginfo( 'description' );
		$context['site_language'] = get_bloginfo( 'language' );

		if ( is_search() ) {
			$context['search_query'] = get_search_query();
		}

		$context = apply_filters( 'aicu/context', $context );

		return array_filter( $context );
	}

	/**
	 * Call CLI command.
	 *
	 * @param string $command
	 * @param array  $args
	 * @param array  $flags
	 *
	 * @return string|false
	 */
	static function call_cli( string $command, array $args = array(), array $flags = array() ): string|false {
		$data = var_export( array(
			'args' => $args,
			'flags' => $flags,
		), true );

		if ( 'improve-html' === $command ) {
			return base64_encode( $data );
		}

		return $data;
	}
}