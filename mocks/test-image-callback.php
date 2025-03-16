<?php
/**
 * Alt Text Callback and Application Test
 * 
 * Tests the REST callback functionality and alt text application process
 * without dealing with exec or CLI complexities.
 */

require_once 'mock-wordpress-core-functions.php';


// Track events for testing
$events = [
    'callbacks_received' => [],
    'alt_texts_applied' => []
];

// Mock WordPress attachment data
$attachments = [
    10 => ['url' => 'https://example.com/image-10.jpg', 'has_alt' => false],
    11 => ['url' => 'https://example.com/image-11.jpg', 'has_alt' => true, 'alt_text' => 'Existing alt text'],
    12 => ['url' => 'https://example.com/image-12.jpg', 'has_alt' => false]
];

/**
 * Alt Text Handler - Focused on REST callback and application
 */
class Alt_Text_Handler {
    /**
     * Apply alt text via REST callback
     * 
     * @param object $request The request object with params
     * @return WP_REST_Response The response
     */
    public function apply_alt_text($request) {
        global $events;
        
        // Get parameters from request
        $params = $request->params;
        $attachment_id = isset($params['attachment_id']) ? intval($params['attachment_id']) : 0;
        $alt_text = isset($params['alt_text']) ? $params['alt_text'] : '';
        
        // Record the callback
        $events['callbacks_received'][] = [
            'attachment_id' => $attachment_id,
            'alt_text' => $alt_text,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo "Received callback for attachment #$attachment_id\n";
        
        // Validate input
        if (!$attachment_id || empty($alt_text)) {
            echo "ERROR: Missing required parameters\n";
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Missing required parameters'
            ], 400);
        }
        
        // Update the attachment meta
        $result = update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
        
        if ($result) {
            echo "Successfully applied alt text\n";
            return new WP_REST_Response([
                'success' => true,
                'message' => 'Alt text applied successfully'
            ], 200);
        } else {
            echo "Failed to apply alt text\n";
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Failed to update alt text'
            ], 500);
        }
    }
    
    /**
     * Check if an attachment has alt text
     * 
     * @param int $attachment_id The attachment ID
     * @return array The check result
     */
    public function check_alt_text($attachment_id) {
        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        
        return [
            'attachment_id' => $attachment_id,
            'has_alt_text' => !empty($alt_text),
            'alt_text' => $alt_text
        ];
    }
}

/**
 * CLI Mock - Simulates the CLI tool calling back with alt text
 */
class CLI_Mock {
    /**
     * Send a callback to the handler with generated alt text
     * 
     * @param int $attachment_id The attachment ID
     * @param string $alt_text The alt text to send
     * @return object The response
     */
    public function send_callback($attachment_id, $alt_text) {
        // Create a mock request
        $request = new stdClass();
        $request->params = [
            'attachment_id' => $attachment_id,
            'alt_text' => $alt_text
        ];
        
        // Create an instance of the handler
        $handler = new Alt_Text_Handler();
        
        // Call the apply_alt_text method
        echo "CLI sending callback for attachment #$attachment_id\n";
        $response = $handler->apply_alt_text($request);
        
        return $response;
    }
    
    /**
     * Generate alt text for an attachment
     * 
     * @param int $attachment_id The attachment ID
     * @return string The generated alt text
     */
    public function generate_alt_text($attachment_id) {
        // These would be more realistic based on image content in a real system
        $descriptions = [
            10 => "A mountain landscape with snow-capped peaks reflecting in a clear blue lake",
            12 => "A close-up of a yellow and red tulip with morning dew drops on the petals",
            // Default for other IDs
            "An image related to the content of the page"
        ];
        
        $alt_text = isset($descriptions[$attachment_id]) 
            ? $descriptions[$attachment_id] 
            : $descriptions[array_key_last($descriptions)];
            
        echo "CLI generated alt text for #$attachment_id: \"$alt_text\"\n";
        return $alt_text;
    }
}

// Run tests
echo "\n===== TESTING ALT TEXT CALLBACK AND APPLICATION =====\n\n";

// Create instances of our classes
$handler = new Alt_Text_Handler();
$cli = new CLI_Mock();

// Test 1: Process an image without alt text
echo "Test 1: Processing image #10 (starts with no alt text)\n";
echo "Initial state: " . json_encode($handler->check_alt_text(10)) . "\n";

// Simulate CLI generating alt text and sending callback
$alt_text = $cli->generate_alt_text(10);
$response = $cli->send_callback(10, $alt_text);

// Check the result after applying alt text
echo "Response status: " . $response->status . "\n";
echo "Response data: " . json_encode($response->data) . "\n";
echo "Final state: " . json_encode($handler->check_alt_text(10)) . "\n\n";

// Test 2: Try to process an image that already has alt text
echo "Test 2: Processing image #11 (already has alt text)\n";
echo "Initial state: " . json_encode($handler->check_alt_text(11)) . "\n";

// Generate new alt text and try to apply it
$alt_text = $cli->generate_alt_text(11);
$response = $cli->send_callback(11, $alt_text);

// Check the result
echo "Response status: " . $response->status . "\n";
echo "Response data: " . json_encode($response->data) . "\n";
echo "Final state: " . json_encode($handler->check_alt_text(11)) . "\n\n";

// Test 3: Send a callback with missing parameters
echo "Test 3: Sending callback with missing parameters\n";
$request = new stdClass();
$request->params = []; // Empty params
$response = $handler->apply_alt_text($request);
echo "Response status: " . $response->status . "\n";
echo "Response data: " . json_encode($response->data) . "\n\n";

// Test 4: Send a callback with invalid attachment ID
echo "Test 4: Sending callback with invalid attachment ID\n";
$request = new stdClass();
$request->params = [
    'attachment_id' => 999, // Non-existent ID
    'alt_text' => 'This should not be applied'
];
$response = $handler->apply_alt_text($request);
echo "Response status: " . $response->status . "\n";
echo "Response data: " . json_encode($response->data) . "\n\n";

// Show summary of events
echo "===== TEST SUMMARY =====\n\n";

// Show callbacks received
echo "Callbacks Received:\n";
foreach ($events['callbacks_received'] as $index => $callback) {
    echo ($index + 1) . ". Attachment #{$callback['attachment_id']} - ";
    echo "Alt text: \"{$callback['alt_text']}\" - ";
    echo "Time: {$callback['timestamp']}\n";
}
echo "\n";

// Show alt texts applied
echo "Alt Texts Applied:\n";
foreach ($events['alt_texts_applied'] as $index => $applied) {
    echo ($index + 1) . ". Attachment #{$applied['attachment_id']} - ";
    echo "Alt text: \"{$applied['alt_text']}\" - ";
    echo "Time: {$applied['timestamp']}\n";
}
echo "\n";

print_r($events);

// Show final attachment state
echo "Final Attachment State:\n";
foreach ($attachments as $id => $attachment) {
    echo "Attachment #$id:\n";
    echo "  URL: {$attachment['url']}\n";
    echo "  Has alt text: " . ($attachment['has_alt'] ? 'Yes' : 'No') . "\n";
    if ($attachment['has_alt']) {
        echo "  Alt text: \"{$attachment['alt_text']}\"\n";
    }
    echo "\n";
}

echo "===== TEST COMPLETE =====\n";