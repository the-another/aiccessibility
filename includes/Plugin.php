<?php
/**
 * Main plugin file
 */

declare(strict_types=1);

namespace Another\Plugin\Aiccessibility;

use Another\Plugin\Another_Wishlist\Frontend\Enqueue;
use Another\Plugin\Another_Wishlist\Post_Types\Wishlist_Post_Type;

if (!\defined('WPINC')) {
    exit;
}

/**
 * Class Plugin
 */
final class Plugin
{

    public static self $instance;

    private string $version;
    private string $text_domain;
    private string $plugin_name;
    private string $plugin_file;
    private string $plugin_path;
    private string $plugin_url;

    private Container $container;
    private bool $initialized = false;

    /**
     * Plugin constructor.
     *
     * @param array $params Plugin params.
     */
    public function __construct(array $params = array())
    {
        $this->set_params($params);
        $this->container = new Container(
            array()
        );
    }

    /**
     * Set plugin params
     *
     * @param array $params Plugin params.
     */
    public function set_params(array $params = array()): void
    {
        foreach ($params as $param_name => $param_value) {
            if (property_exists($this, $param_name)) {
                $this->$param_name = $param_value;
            }
        }
    }

    /**
     * Get plugin version
     */
    public function version(): string
    {
        return $this->version;
    }

    /**
     * Get plugin text domain string
     */
    public function text_domain(): string
    {
        return $this->text_domain;
    }

    /**
     * Get plugin name
     */
    public function plugin_name(): string
    {
        return $this->plugin_name;
    }

    /**
     * Get plugin file path
     */
    public function plugin_file(): string
    {
        return $this->plugin_file;
    }

    /**
     * Get plugin directory path
     */
    public function plugin_path(): string
    {
        return $this->plugin_path;
    }

    /**
     * Get plugin directory URL
     */
    public function plugin_url(): string
    {
        return $this->plugin_url;
    }

    /**
     * Get plugin directory URL
     */
    public function initialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Get plugin container
     *
     * @return Container
     */
    public function container(): Container
    {
        return $this->container;
    }

    /**
     * Initialize plugin
     */
    public function init(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->init_global();

        if (is_admin()) {
            $this->init_admin();
        } else {
            $this->init_frontend();
        }

        $this->initialized = true;
    }

    /**
     * Global init hooks.
     *
     * @return void
     */
    public function init_global(): void
    {
        add_action(
            'init',
            array()
        );
    }

    /**
     * Initialize admin hooks.
     */
    public function init_admin(): void
    {
    }

    /**
     * Initialize frontend hooks.
     */
    public function init_frontend(): void
    {
        add_action(
            'wp_enqueue_scripts',
            array(
                $this->container->get(),
                'register',
            )
        );
    }

    /**
     * Get plugin instance
     *
     * @param array<string, mixed> $params Plugin constructor parameters.
     */
    public static function instance(array $params = array()): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self($params);
        }

        return self::$instance;
    }
}
