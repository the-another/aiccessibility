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
	 * Send HTML to CLI.
	 *
	 * @param string $html HTML to send to CLI.
	 *
	 * @return string
	 */
	static function improve_html( string $html ): string {
		$filtered_html = apply_filters( 'aicu/improve_html/html', $html );
		$context = apply_filters( 'aicu/improve_html/context', AICU_Content_Updater::get_context() );

		$b64_improved_html = AICU_Content_Updater::call_cli(
			'improve-html',
			array(
				base64_encode( $filtered_html ),
			),
			array(
				'context' => json_encode( array_filter( $context ) ),
			)
		);

		if ( ! $b64_improved_html ) {
			return $html;
		}

		$improved_html = base64_decode( $b64_improved_html );

		if ( class_exists( 'QM' ) ) {
			if ( ! class_exists( 'WP_Text_Diff_Renderer_Table', false ) ) {
				require ABSPATH . WPINC . '/wp-diff.php';
			}

			$o = explode( "\n", normalize_whitespace( $html ) );
			$i = explode( "\n", normalize_whitespace( $improved_html ) );

			$text_diff = new Text_Diff( $o, $i );

			foreach ( $text_diff->getDiff() as $diff ) {
				if ( $diff instanceof Text_Diff_Op_copy ) {
					continue;
				}

				$diff_orig = $diff->orig ? trim( implode( '', $diff->orig ) ) : '';
				$diff_final = $diff->final ? trim( implode( '', $diff->final ) ) : '';

				QM::alert( <<<ALERT
				Inaccessible part detected:
				Original: {$diff_orig}
				Improved: {$diff_final}
				ALERT );
			}
		}

		return $improved_html;
	}
}