<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Class AICU_Output_Manager
 */
class AICU_Output_Manager {
	/**
	 * Add hooks.
	 *
	 * @return void
	 */
	static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'start_output_buffer' ) );
		add_action( 'shutdown', array( __CLASS__, 'end_output_buffer' ) );
	}

	/**
	 * Start output buffer.
	 *
	 * @return void
	 */
	static function start_output_buffer(): void {
		ob_start( array( __CLASS__, 'improve_html' ) );
	}

	/**
	 * End output buffer.
	 *
	 * @return void
	 */
	static function end_output_buffer(): void {
		ob_end_flush();
	}

	/**
	 * Send HTML to API.
	 *
	 * @param string $html HTML to send to API.
	 * @return string
	 */
	static function improve_html( string $html ): string {
		$html = apply_filters( 'aicu/html', $html );
		$b64_html = base64_encode( $html );

		$context = array();

		$context['site_url'] = get_site_url();
		$context['site_name'] = get_bloginfo( 'name' );
		$context['site_description'] = get_bloginfo( 'description' );
		$context['site_language'] = get_bloginfo( 'language' );

		if ( is_search() ) {
			$context['search_query'] = get_search_query();
		}

		$context = apply_filters( 'aicu/context', $context );

		$json_context = json_encode( array_filter( $context ) );

		// aicu improve-html --html=$b64_html --context=$json_context
		$cmd = 'aicu improve-html --html=' . escapeshellarg( $b64_html ) . ' --context=' . escapeshellarg( $json_context );
		$b64_improved_html = shell_exec( $cmd );

		return base64_decode( $b64_improved_html );
	}
}