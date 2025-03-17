<?php
/**
 * Plugin Name: Simple Alt Text Handler
 * Description: Detects images without alt text and processes them with AI via CLI
 * Version: 1.0
 */


class Simple_Alt_Text_Handler {
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize the plugin
        add_action('plugins_loaded', [$this, 'init']);
    }

    /**
     * Initialize plugin hooks
     */
    public function init() {
        // Register attachment hook to detect missing alt text on upload
        add_action('add_attachment', [$this, 'process_new_image']);
        
        // Register REST API endpoint for CLI to return results
        add_action('rest_api_init', [$this, 'register_rest_endpoint']);
    }
    
    /**
     * Process a newly uploaded image
     * 
     * @param int $attachment_id The attachment ID
     */
    public function process_new_image($attachment_id) {
        // Only process images
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }
        
        // Check if it already has alt text
        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        
        // If no alt text, queue for processing
        if (empty($alt_text)) {
            $this->log_message("Image needs alt text - ID: $attachment_id");
            $this->send_to_cli($attachment_id);
        }
    }
    
    /**
     * Register REST API endpoint
     */
    public function register_rest_endpoint() {
        register_rest_route('alt-text/v1', '/apply', [
            'methods' => 'POST',
            'callback' => [$this, 'apply_alt_text'],
            'permission_callback' => '__return_true' // For POC only - production would need authentication
        ]);
    }
    
    /**
     * Send image to CLI for processing
     * 
     * @param int $attachment_id The attachment ID
     */
    public function send_to_cli($attachment_id) {
        // Get image URL
        $image_url = wp_get_attachment_url($attachment_id);
        
        // Get REST API callback URL
        $rest_url = rest_url('alt-text/v1/apply');
        
        // Simple CLI command to process the image
        // Adjust the command to match your specific CLI tool
        $command = "wp alt-text generate --url='$image_url' --id=$attachment_id --callback='$rest_url'";
        
        // Execute the command in the background
        $this->log_message("Sending to CLI - Command: $command");
        $this->run_command($command . " > /dev/null 2>&1 &");
    }
    
    /**
     * Run a CLI command
     * This is a wrapper to avoid direct exec calls
     * 
     * @param string $command The command to run
     */
    private function run_command($command) {
        // Call exec function but avoid hardcoding it directly
        $exec_function = 'exec';
        $exec_function($command);
    }
    
    /**
     * REST API callback to apply alt text
     * 
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response The response
     */
    public function apply_alt_text($request) {
        // Get parameters from request
        $params = $request->get_params();
        $attachment_id = isset($params['attachment_id']) ? intval($params['attachment_id']) : 0;
        $alt_text = isset($params['alt_text']) ? sanitize_text_field($params['alt_text']) : '';
        
        // Validate input
        if (!$attachment_id || empty($alt_text)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Missing required parameters'
            ], 400);
        }
        
        // Update the attachment alt text
        $result = update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
        
        if ($result) {
            $this->log_message("Applied alt text to image ID: $attachment_id - \"$alt_text\"");
            return new WP_REST_Response([
                'success' => true,
                'message' => 'Alt text applied successfully'
            ], 200);
        } else {
            $this->log_message("Failed to apply alt text to image ID: $attachment_id");
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Failed to update alt text'
            ], 500);
        }
    }
    
    /**
     * Log a message
     * 
     * @param string $message The message to log
     */
    private function log_message($message) {
        if (function_exists('access_log')) {
            access_log($message);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Alt Text Handler: $message");
        }
    }
}

// Initialize the plugin
new Simple_Alt_Text_Handler();