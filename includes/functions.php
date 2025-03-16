<?php
/**
 * Functions
 */

declare(strict_types = 1);

if ( ! function_exists( 'trigger_deprecation' ) ) {
	/**
	 * Triggers a silenced deprecation notice.
	 *
	 * @param string $package The name of the Composer package that is triggering the deprecation.
	 * @param string $version The version of the package that introduced the deprecation.
	 * @param string $message The message of the deprecation.
	 * @param mixed  ...$args Values to insert in the message0 using printf() formatting.
	 */
	function trigger_deprecation( string $package, string $version, string $message, mixed ...$args ): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
		if ( function_exists( 'trigger_error' ) ) {
			$message = ( $package || $version ? "Since $package $version: " : '' ) . ( $args ? vsprintf( $message, $args ) : $message );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error( esc_html( $message ), E_USER_DEPRECATED );
		}
	}
}

/**
 * Converts a snake_case string to CamelCase.
 *
 * For example: hello_world to HelloWorld
 *
 * @param string $input snake_case string.
 *
 * @return string CamelCase string
 */
function to_camel_case( string $input ): string {
	return str_replace( ' ', '', ucwords( str_replace( '_', ' ', $input ) ) );
}

/**
 * Converts a CamelCase string to snake_case.
 *
 * For Example HelloWorld to hello_world
 *
 * @param string $input CamelCase String to Convert.
 *
 * @return string SnakeCase string
 */
function to_snake_case( string $input ): string {
	return strtolower( preg_replace( '/[A-Z]/', '_\\0', lcfirst( $input ) ) );
}
