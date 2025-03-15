<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Class AICU_Site_Context
 */
class AICU_Site_Context {
	/**
	 * Add hooks.
	 *
	 * @return void
	 */
	static function init(): void {
		add_filter( 'aicu/context', array( __CLASS__, 'add_context' ) );
		add_action( 'admin_init', array( __CLASS__, 'add_settings' ) );
	}

	/**
	 * Add site context to global context.
	 *
	 * @param array $context Global context.
	 * @return array
	 */
	static function add_context( array $context ): array {
		$site_context = static::get_context();
		if ( ! empty( $site_context ) ) {
			$context['custom_site_context'] = $site_context;
		}

		return $context;
	}

	/**
	 * Add settings.
	 *
	 * @return void
	 */
	static function add_settings(): void {
		add_settings_section(
			'aicu',
			__( 'AICU', 'aicu' ),
			array( __CLASS__, 'render_settings_section' ),
			'general'
		);

		add_settings_field(
			'aicu_site_context',
			'<label for="aicu_site_context">' . __( 'Site Context', 'aicu' ) . '</label>',
			array( __CLASS__, 'render_settings_field' ),
			'general',
			'aicu_site_context'
		);

		register_setting( 'general', 'aicu_site_context' );
	}

	/**
	 * Render settings section.
	 *
	 * @return void
	 */
	static function render_settings_section(): void {}

	/**
	 * Render settings field.
	 *
	 * @return void
	 */
	static function render_settings_field(): void {
		$site_context = static::get_context();
		?>
			<textarea name="aicu_site_context" id="aicu_site_context" class="large-text" rows="5"><?php
				echo esc_textarea( $site_context );
			?></textarea>
		<?php
	}

	/**
	 * Get site context.
	 *
	 * @return string|false
	 */
	static function get_context(): string|false {
		return get_option( 'aicu_site_context' );
	}
}