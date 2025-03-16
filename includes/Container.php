<?php
/**
 * Plugin Container class
 */

declare( strict_types = 1 );

namespace Another\Plugin\Aiccessibility;

/**
 * Class Container
 */
class Container {
	/**
	 * Container instance
	 */
	private static Container $instance;

	/**
	 * Registered services
	 *
	 * @var array<string, mixed> $services
	 */
	private array $services;

	/**
	 * Container constructor
	 *
	 * @param array<string, mixed> $services Services array.
	 */
	public function __construct( array $services = array() ) {
		$this->services = $services;
	}

	/**
	 * Register service
	 *
	 * @param string $name Service name.
	 * @param mixed  $service Service instance.
	 */
	public function register( string $name, mixed $service ): void {
		$this->services[ $name ] = $service;
	}

	/**
	 * Get service
	 *
	 * @param string $name Service name.
	 *
	 * @return mixed
	 */
	public function get( string $name ): mixed {
		return $this->services[ $name ];
	}

	/**
	 * Get container instance
	 *
	 * @return Container
	 */
	public static function instance(): Container {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
