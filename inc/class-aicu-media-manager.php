<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Class AICU_Media_Manager
 */
class AICU_Media_Manager {
	/**
	 * Add hooks.
	 *
	 * @return void
	 */
	static function init(): void {
		add_action( 'restrict_manage_posts', array( __CLASS__, 'add_attachment_alt_filter' ) );
		add_filter( 'pre_get_posts', array( __CLASS__, 'filter_attachments_by_alt' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_submenu_page' ) );

		add_filter( 'manage_media_columns', array( __CLASS__, 'add_generate_alt_admin_column' ) );
		add_action( 'manage_media_custom_column', array( __CLASS__, 'render_generate_alt_admin_column' ), 10, 2 );

		add_filter( 'get_post_metadata', array( __CLASS__, 'get_post_metadata' ), 10, 4 );
		add_action( 'admin_footer', array( __CLASS__, 'focus_on_alt_field' ) );
	}

	static function add_attachment_alt_filter(): void {
		$screen = get_current_screen();

		if ( 'upload' !== $screen->id ) {
			return;
		}

		$value = filter_input( INPUT_GET, 'aicu_attachment_alt', FILTER_SANITIZE_STRING );

		$choices = array(
            '' => __( 'All Media', 'textdomain' ),
            'no-alt' => __( 'Media without Alt-Text', 'textdomain' ),
        );

		?>
            <label for="aicu_attachment_alt" class="screen-reader-text">
	            <?php esc_html_e( 'Filter by Alt-Text', 'textdomain' ); ?>
            </label>
			<select name="aicu_attachment_alt" id="aicu_attachment_alt">
				<?php foreach ( $choices as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		<?php
	}

	static function filter_attachments_by_alt( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$screen = get_current_screen();

		if ( 'upload' !== $screen->id ) {
			return;
		}

		$value = filter_input( INPUT_GET, 'aicu_attachment_alt', FILTER_SANITIZE_STRING );

		if ( ! $value ) {
			return;
		}

		if ( 'no-alt' === $value ) {
			$query->set( 'meta_query', array(
				'relation' => 'OR',
				array(
					'key' => '_wp_attachment_image_alt',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key' => '_wp_attachment_image_alt',
					'value' => '',
					'compare' => '=',
				),
			) );
		}
	}

	static function add_generate_alt_admin_column( array $columns ): array {
		$value = filter_input( INPUT_GET, 'aicu_attachment_alt', FILTER_SANITIZE_STRING );

		if ( 'no-alt' !== $value ) {
			return $columns;
		}

		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( 'title' === $key ) {
				$new_columns['generate_alt'] = __( 'AIccessibility Content Updater', 'textdomain' );
			}
		}

		return $new_columns;
	}

	static function render_generate_alt_admin_column( string $column_name, int $attachment_id ): void {
		if ( 'generate_alt' !== $column_name ) {
			return;
		}

		$edit_link = get_edit_post_link( $attachment_id );
		$edit_link = add_query_arg( 'aicu_generate_alt', '1', $edit_link );

		?>
			<a href="<?php echo esc_url( $edit_link ); ?>" class="button button-primary aicu-generate-alt">
				<?php esc_html_e( 'Generate Alt-Text', 'textdomain' ); ?>
			</a>
		<?php
	}

	static function add_submenu_page(): void {
		add_submenu_page(
			'upload.php',
			__( 'AIccessibility Content Updater', 'textdomain' ),
			__( 'AIccessibility Content Updater', 'textdomain' ),
			'upload_files',
			'upload.php?mode=list&aicu_attachment_alt=no-alt'
		);
	}

	static function get_post_metadata( $value, $object_id, $meta_key, $single ) {
		if (
		    ! empty( $value ) ||
			! is_admin() ||
			'_wp_attachment_image_alt' !== $meta_key ||
			! filter_input( INPUT_GET, 'aicu_generate_alt', FILTER_VALIDATE_BOOLEAN ) ||
			! current_user_can( 'edit_post', $object_id )
		) {
			return $value;
		}

		$alt_text = static::generate_alt( $object_id );

		if ( ! $alt_text ) {
			return $value;
		}

		return $single ? $alt_text : array( $alt_text );
	}

	static function focus_on_alt_field(): void {
		$screen = get_current_screen();

		if (
		    ! $screen instanceof WP_Screen ||
            'attachment' !== $screen->id ||
			! filter_input( INPUT_GET, 'aicu_generate_alt', FILTER_VALIDATE_BOOLEAN )
		) {
			return;
		}

		?>
			<script>
				jQuery( document ).ready( function() {
					jQuery( '.attachment-alt-text' ).get( 0 ).scrollIntoView();
					jQuery( '#attachment_alt' ).focus();
				} );
			</script>
		<?php
	}

	/**
	 * Generate alt text for an attachment.
	 *
	 * @param string|int|WP_Post $attachment Attachment Object, ID, URL, or path.
	 * @return string|false
	 */
	static function generate_alt( string|int|WP_Post $attachment ): string|false {
		$args = array();
		$context = array();

		if ( is_string( $attachment ) && ! is_numeric( $attachment ) ) {
			// If $attachment is the path to an image.
			if ( file_exists( $attachment ) ) {
				$args['source_path'] = $attachment;

				$attachment = attachment_url_to_postid( $attachment );
			}

			// If $attachment is the source URL of an attachment.
			if ( filter_var( $attachment, FILTER_VALIDATE_URL ) ) {
				$args['source_url'] = $attachment;

				$attachment = attachment_url_to_postid( $attachment );
			}

			// If $attachment is a base64-encoded image.
			if ( preg_match( '/^data:image\/([a-z]+);base64,/', $attachment ) ) {
				$args['base64_image'] = $attachment;

				$attachment = 0;
			}
		}

		if ( $attachment ) {
			$attachment = get_post( $attachment );

			if ( $attachment ) {
				$args['source_path'] = get_attached_file( $attachment->ID );

				$context['attachment_title'] = $attachment->post_title;
				$context['attachment_caption'] = $attachment->post_excerpt;
				$context['attachment_description'] = $attachment->post_content;
				if ( ! filter_input( INPUT_GET, 'aicu_generate_alt', FILTER_VALIDATE_BOOLEAN ) ) {
					$context['attachment_alt'] = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
				}
				$context['attachment_filename'] = wp_basename( get_attached_file( $attachment->ID ) );
				$context['attachment_mime_type'] = get_post_mime_type( $attachment->ID );
				$context['attachment_url'] = wp_get_attachment_url( $attachment->ID );
				$context['attachment_author'] = get_the_author_meta( 'display_name', $attachment->post_author );
				$context['attachment_date'] = get_the_date( '', $attachment );
				$context['attachment_modified'] = get_the_modified_date( '', $attachment );

				require_once 'class-aicu-post-context.php';
				$context['custom_attachment_context'] = AICU_Post_Context::get_context( $attachment );
			}
		}

		if ( empty( $args['base64_image'] ) && empty( $args['source_path'] ) ) {
			if ( empty( $args['source_url'] ) ) {
				return false;
			}

			$args['temp_source_path'] = download_url( $args['source_url'] );

			if ( is_wp_error( $args['temp_source_path'] ) ) {
				return false;
			}
		}

		unset( $args['source_url'] );

		$context = array_merge( AICU_Content_Updater::get_context(), $context );
		$context = apply_filters( 'aicu/generate_alt/context', $context );

		$alt_text = AICU_Content_Updater::call_cli(
			'generate-alt',
			array_values( $args ),
			array(
				'context' => json_encode( array_filter( $context ) ),
			)
		);

		if ( ! empty( $args['temp_source_path'] ) ) {
			unlink( $args['temp_source_path'] );
		}

		return $alt_text;
	}
}