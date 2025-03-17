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
		if ( ob_get_level() ) {
			ob_end_flush();
		}
	}

	/**
	 * Send HTML to CLI.
	 *
	 * @param string $orig_html HTML to send to CLI.
	 * @return string
	 */
	static function improve_html( string $orig_html ): string {
		$uniqid = uniqid();
		$temp_dir = get_temp_dir();
		$temp_file_base = "aicu-{$uniqid}";

		$filtered_html = apply_filters( 'aicu/improve_html/html', $orig_html );
		$context = apply_filters( 'aicu/improve_html/context', AICU_Content_Updater::get_context() );

		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$temp_html_file = "{$temp_dir}{$temp_file_base}.html";
		file_put_contents( $temp_html_file, $filtered_html );

		do_action( 'qm/debug', 'temp_file: ' . $temp_html_file );

		if ( ! file_exists( $temp_html_file ) ) {
			return $orig_html;
		}

		$cli_output = AICU_Content_Updater::call_cli(
			'get-report',
			array( $temp_html_file )
		);

		$prepared_temp_file = "{$temp_dir}{$temp_file_base}-prepared.html";
		$prepared_html = file_get_contents( $prepared_temp_file );

		$fixed_temp_file = "{$temp_dir}{$temp_file_base}-fixed.html";
		$fixed_html = file_get_contents( $fixed_temp_file );

		$report_file = "{$temp_dir}{$temp_file_base}-report.json";
		$report = json_decode( file_get_contents( $report_file ), true );

		unlink( $temp_html_file );
		unlink( $prepared_temp_file );
		unlink( $fixed_temp_file );
		unlink( $report_file );

		if ( ! $prepared_html || ! $fixed_html ) {
			return $orig_html;
		}

		if ( class_exists( 'QM' ) ) {
			foreach ( $report['issues'] as $issue ) {
				QM::error( <<<ALERT
				AIccessibility issue found
				Code: {$issue['code']}
				Message: {$issue['message']}
				Selector: {$issue['selector']}
				Context: {$issue['context']}
				ALERT );
			}

			if ( ! class_exists( 'WP_Text_Diff_Renderer_Table', false ) ) {
				require ABSPATH . WPINC . '/wp-diff.php';
			}

			$orig_lines = explode( "\n", normalize_whitespace( $prepared_html ) );
			$impr_lines = explode( "\n", normalize_whitespace( $fixed_html ) );

			$line_diff = new Text_Diff( $orig_lines, $impr_lines );

			foreach ( $line_diff->getDiff() as $l_diff ) {
				if ( $l_diff instanceof Text_Diff_Op_copy ) {
					continue;
				}

				$orig_line = $l_diff->orig ? trim( implode( '', $l_diff->orig ) ) : '';
				$impr_line = $l_diff->final ? trim( implode( '', $l_diff->final ) ) : '';

				$orig_nodes = preg_split( '/(<[^>]+>|\\n)/', $orig_line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
				$impr_nodes = preg_split( '/(<[^>]+>|\\n)/', $impr_line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

				$node_diff = new Text_Diff( $orig_nodes, $impr_nodes );

				foreach ( $node_diff->getDiff() as $n_diff ) {
					if ( $n_diff instanceof Text_Diff_Op_copy ) {
						continue;
					}

					$orig_node  = $n_diff->orig ? trim( implode( '', $n_diff->orig ) ) : '';
					$impr_node = $n_diff->final ? trim( implode( '', $n_diff->final ) ) : '';

					// If node comparison contains HTML tags, log only the affected nodes.
					if (
						str_contains( $orig_node, '<' ) ||
						str_contains( $impr_node, '<' )
					) {
						QM::debug( <<<ALERT
						Inaccessible html detected (node):
						Original: {$orig_node}
						Improved: {$impr_node}
						ALERT );
					} else {
						QM::debug( <<<ALERT
						Inaccessible html detected (line):
						Original: {$orig_line}
						Improved: {$impr_line}
						ALERT );
					}
				}
			}
		}

		return $fixed_html;
	}
}