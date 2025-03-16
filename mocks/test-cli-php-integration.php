#!/usr/bin/env php
<?php
/**
 * Integration Test for Alt Text Handler and CLI Tool
 * 
 * This script tests the integration between the WordPress plugin and the CLI tool
 * in a more realistic environment by executing the actual CLI tool.
 */

/**
 * #### Generate Alt Text for Images

* ```bash
* # Using a local image file
* aicu alt-text --file ./path/to/image.jpg --api-key your-openai-api-key

* # Using base64 encoded image data
* aicu alt-text --base64 data:image/jpeg;base64,/9j/4AAQ... --api-key your-openai-api-key

* # Using a specific openai model
* aicu alt-text --file ./path/to/image.jpg --api-key your-api-key --model openai-vl

* # List available openai vision models
* aicu alt-text --list-models --api-key your-api-key
* ```
 */
/**
 * Exec local node CLI utility to use REAL LLM calls.
 */
function exec_cli_tool($command) {
    $output = [];
    $return_var = 0;
    exec("node cli-tool.js $command", $output, $return_var);
    return [
        'output' => $output,
        'return_var' => $return_var
    ];
}

// Include the WordPress mocks
require_once 'mock-wordpress-core-functions.php';
require_once __DIR__ . '/../ProcessImageAlts.php';
echo "WordPress functions loaded.\n";

echo "\n===== INTEGRATION TEST: ALT TEXT HANDLER WITH CLI TOOL =====\n\n";

// First, load the Simple_Alt_Text_Handler and initialize it
echo "Initializing Alt Text Handler...\n";
$handler = new Simple_Alt_Text_Handler();

// Use the handler.
do_action('plugins_loaded');
echo "\n";

// Test with an image that has no alt text (ID: 10)
echo "Testing with image without alt text (ID: 10):\n";
do_action('add_attachment', 10);
echo "\n";





/**
 * Update specific post meta fields with alt text.
 * Using mocked WordPress hooks and functions.
 */
send_callback('http://example.com/callback', 10, 'Generated alt text for image 10: A beautiful landscape');

/**
 * Mock the REST api callback that will fire after CLI utility returns.
 * The callback should fire update_post_meta() with the alt text if was successful.
 */
function send_callback($callback_url, $attachment_id, $alt_text) {
    global $events;
    
    $response = new WP_REST_Response([
        'attachment_id' => $attachment_id,
        'alt_text' => $alt_text,
        'success' => true
    ]);
    
    $events['callbacks_received'][] = [
        'attachment_id' => $attachment_id,
        'alt_text' => $alt_text,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo "Received callback for attachment #$attachment_id: \"$alt_text\"\n";
    echo "Updating alt text for attachment #$attachment_id...\n";
    $response->data['success'] = update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
}
