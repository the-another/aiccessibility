<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Class AICU_Post_Context
 */
class AICU_Post_Context {
	/**
	 * Add hooks.
	 *
	 * @return void
	 */
	static function init(): void {
		add_filter( 'aicu/context', array( __CLASS__, 'add_context' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save_post_context' ) );
	}

	/**
	 * Add post context to global context.
	 *
	 * @param array $context Global context.
	 * @return array
	 */
	static function add_context( array $context ): array {
		if ( ! is_singular() ) {
			return $context;
		}

		$context['post_type'] = get_post_type();
		$context['post_title'] = get_the_title();
		$context['post_excerpt'] = get_the_excerpt();
		$context['post_content'] = get_the_content();
		$context['post_url'] = get_permalink();
		$context['post_author'] = get_the_author();
		$context['post_date'] = get_the_date();
		$context['post_modified'] = get_the_modified_date();

		$post_context = static::get_context();
		if ( ! empty( $post_context ) ) {
			$context['custom_post_context'] = $post_context;
		}

		return $context;
	}

	/**
	 * Add post context meta box.
	 *
	 * @return void
	 */
	static function add_meta_box(): void {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$post_types = wp_list_pluck( $post_types, 'name' );
		$post_types = apply_filters( 'aicu/custom_context/post_types', $post_types );

		add_meta_box(
			'aicu-post-context',
			__( 'AICU Post Context', 'aicu' ),
			array( __CLASS__, 'render_meta_box' ),
			$post_types,
			'side',
			'high'
		);
	}

	/**
	 * Render post context meta box.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	static function render_meta_box( WP_Post $post ): void {
		$post_context = static::get_context( $post );
		?>
			<p>
				<label for="aicu-post-context">
					<?php _e( 'AICU Post Context', 'aicu' ); ?>
				</label>
				<textarea id="aicu-post-context" name="aicu-post-context" rows="5" style="width: 100%;"><?php
					echo esc_textarea( $post_context );
				?>
				</textarea>
			</p>
		<?php
	}

	/**
	 * Save post context.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	static function save_post_context( int $post_id ): void {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['aicu-post-context'] ) ) {
			$post_context = sanitize_text_field( $_POST['aicu-post-context'] );
			update_post_meta( $post_id, '_aicu_post_context', $post_context );
		}
	}

	/**
	 * Get post context.
	 *
	 * @param WP_Post|int|null $post Post object or ID.
	 * @return string|false
	 */
	static function get_context( WP_Post|int|null $post = null ): string|false {
		$post = get_post( $post );
		return get_post_meta( $post->ID, '_aicu_post_context', true );
	}
}