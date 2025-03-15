<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Class AICU_Archive_Context
 */
class AICU_Archive_Context {
	/**
	 * Add hooks.
	 *
	 * @return void
	 */
	static function init(): void {
		add_filter( 'aicu/context', array( __CLASS__, 'add_context' ) );

		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		$taxonomies = wp_list_pluck( $taxonomies, 'name' );
		$taxonomies = apply_filters( 'aicu/custom_context/archive/taxonomies', $taxonomies );

		foreach ( $taxonomies as $taxonomy ) {
			add_action( "{$taxonomy}_edit_form_fields", array( __CLASS__, 'add_term_meta_field' ) );
			add_action( "{$taxonomy}_add_form_fields", array( __CLASS__, 'add_term_meta_field' ) );
			add_action( "edited_{$taxonomy}", array( __CLASS__, 'save_term_context' ) );
			add_action( "created_{$taxonomy}", array( __CLASS__, 'save_term_context' ) );
		}
	}

	/**
	 * Add archive context to global context.
	 *
	 * @param array $context Global context.
	 * @return array
	 */
	static function add_context( array $context ): array {
		if ( ! is_archive() ) {
			return $context;
		}

		$context['archive_title'] = get_the_archive_title();
		$context['archive_description'] = get_the_archive_description();

		$term_context = static::get_context();
		if ( ! empty( $term_context ) ) {
			$context['custom_term_context'] = $term_context;
		}

		return $context;
	}

	/**
	 * Add term meta field.
	 *
	 * @param WP_Term $term Term object.
	 * @return void
	 */
	static function add_term_meta_field( $term ): void {
		$term_id = $term->term_id;
		$term_meta = get_term_meta( $term_id, 'aicu_term_context', true );
		?>
			<tr class="form-field">
				<th scope="row">
					<label for="aicu_term_context"><?php esc_html_e( 'AICU Term Context', 'aicu' ); ?></label>
				</th>
				<td>
					<textarea id="aicu_term_context" name="aicu_term_context"><?php
						echo esc_textarea( $term_meta );
					?></textarea>
					<p class="description"><?php esc_html_e( 'Add custom context for this term.', 'aicu' ); ?></p>
				</td>
			</tr>
		<?php
	}

	/**
	 * Save term context.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	static function save_term_context( $term_id ): void {
		if ( ! isset( $_POST['aicu_term_context'] ) ) {
			return;
		}

		$term_meta = sanitize_text_field( wp_unslash( $_POST['aicu_term_context'] ) );
		update_term_meta( $term_id, 'aicu_term_context', $term_meta );
	}

	/**
	 * Get term context.
	 *
	 * @param WP_Term|int|null $term Term object or term ID.
	 * @return string|false
	 */
	static function get_context( WP_Term|int|null $term = null ): string|false {
		if ( is_null( $term ) ) {
			$term = get_queried_object();
			if ( ! $term instanceof WP_Term ) {
				return false;
			}
		} elseif ( is_int( $term ) ) {
			$term = get_term( $term );
		}

		return get_term_meta( $term->term_id, 'aicu_term_context', true );
	}
}