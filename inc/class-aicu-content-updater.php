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
		$is_debug = defined( 'WP_DEBUG' ) && WP_DEBUG &&
		            apply_filters( 'aicu/debug', true );

		if ( $is_debug ) {
			$log_data               = array();
			$log_data['start_time'] = microtime( true );
			$log_data['url'] = $_SERVER['REQUEST_URI'];
			$log_data['command']    = $command;
			$log_data['args']       = $args;
			$log_data['flags']      = $flags;
		}

		if ( empty( $args['-k'] ) ) {
			$open_ai_key = apply_filters(
				'aicu/config/open-ai-key',
				defined( 'AICU_OPEN_AI_KEY' ) ? AICU_OPEN_AI_KEY : '',
				$command,
				$args,
				$flags
			);

			if ( empty( $open_ai_key ) ) {
				do_action( 'qm/error', 'AICU Open AI Key is empty' );
				return false;
			}

			$args['-k'] = $open_ai_key;
		}

		$node_file = escapeshellarg( AICU_PLUGIN_DIR . 'cli/dist/index.js' );

		$node_args = array_map( 'escapeshellarg', $args );
		$node_args = implode( ' ', $node_args );

		$node_flags = array_map( function ( $key, $value ) {
			return $key . ' ' . escapeshellarg( $value );
		}, array_keys( $flags ), $flags );
		$node_flags = implode( ' ', $node_flags );

		$node_path = escapeshellarg( AICU_PLUGIN_DIR . 'cli/dist' );

		$node_command = "cd {$node_path} && node {$node_file} {$command} {$node_args} {$node_flags} 2>&1";

		exec( $node_command, $output, $result_code );

		$output_string = implode( "\n", $output );

		if ( 0 !== $result_code ) {
			do_action( 'qm/notice', <<<ERROR
			AICU CLI Call Error
			Command: {$node_command}
			Error: {$output_string}
			ERROR );

			return false;
		}

		if ( $is_debug ) {
			$log_data['time_needed'] = microtime( true ) - $log_data['start_time'];
			$log_data['output']     = $output;
			$log_data['result_code'] = $result_code;

			error_log( 'AICU CLI Call: ' . var_export( $log_data, true ) );
		}

		return $output_string;
	}
}