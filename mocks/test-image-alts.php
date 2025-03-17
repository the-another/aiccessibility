<?php
/**
 * Simple Alt Text Handler Test
 * 
 * A lightweight mock to test the alt text detection and application logic
 * without dealing with CLI commands or exec functions.
 */

// Track events for testing
$events = [
    'images_checked' => [],
    'cli_commands' => [],
    'alt_texts_applied' => []
];

// Mock WordPress attachment data
$attachments = [
    10 => ['url' => 'https://example.com/image-10.jpg', 'has_alt' => false],
    11 => ['url' => 'https://example.com/image-11.jpg', 'has_alt' => true, 'alt_text' => 'Existing alt text'],
    12 => ['url' => 'https://example.com/image-12.jpg', 'has_alt' => false]
];

require_once 'mock-wordpress-core-functions.php';

/**
 * Simple Alt Text Handler Class
 * This is a simplified version of the handler that doesn't use exec
 */
class Simple_Alt_Text_Handler {
    /**
     * Process a newly uploaded image
     */
    public function process_new_image($attachment_id) {
        global $events;
        
        // Only process images
        if (!wp_attachment_is_image($attachment_id)) {
            echo "Attachment #$attachment_id is not an image.\n";
            return;
        }
        
        // Check if it already has alt text
        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        
        // Record the check
        $events['images_checked'][] = [
            'attachment_id' => $attachment_id,
            'has_alt' => !empty($alt_text)
        ];
        
        echo "Checking image #$attachment_id - Has alt text: " . (!empty($alt_text) ? 'Yes' : 'No') . "\n";
        
        // If no alt text, queue for processing
        if (empty($alt_text)) {
            $this->send_to_cli($attachment_id);
        }
    }
    
    /**
     * Send image to CLI for processing
     */
    public function send_to_cli($attachment_id) {
        global $events;
        
        // Get image URL
        $image_url = wp_get_attachment_url($attachment_id);
        
        // Build the CLI command (we don't actually execute it)
        $command = "wp alt-text generate --url='$image_url' --id=$attachment_id";
        
        // Track the command
        $events['cli_commands'][] = [
            'attachment_id' => $attachment_id,
            'command' => $command
        ];
        
        echo "Sending to CLI: $command\n";
        
        // For testing purposes, we'll immediately simulate the CLI result
        $this->simulate_cli_result($attachment_id);
    }
    
    /**
     * Simulate CLI generating alt text and calling back
     */
    private function simulate_cli_result($attachment_id) {
        // Generate a sample alt text
        $alt_text = "Generated description for image #$attachment_id";
        
        echo "Simulating CLI process...\n";
        echo "Generated alt text: \"$alt_text\"\n";
        
        // Apply the alt text directly
        $result = update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
        
        if ($result) {
            echo "Successfully applied alt text to image #$attachment_id\n";
        } else {
            echo "Failed to apply alt text to image #$attachment_id\n";
        }
    }
}

// Run tests
echo "\n===== TESTING SIMPLE ALT TEXT HANDLER =====\n\n";

// Create an instance of the handler
$handler = new Simple_Alt_Text_Handler();

// Test with an image that has no alt text
echo "Testing with image #10 (no alt text):\n";
$handler->process_new_image(10);
echo "\n";

// Test with an image that already has alt text
echo "Testing with image #11 (has alt text):\n";
$handler->process_new_image(11);
echo "\n";

// Show the results
echo "===== TEST RESULTS =====\n\n";

// Show images checked
echo "Images Checked:\n";
foreach ($events['images_checked'] as $index => $check) {
    echo ($index + 1) . ". Image #{$check['attachment_id']} - Has alt text: " . 
         ($check['has_alt'] ? 'Yes' : 'No') . "\n";
}
echo "\n";

// Show CLI commands
echo "CLI Commands Generated:\n";
foreach ($events['cli_commands'] as $index => $cmd) {
    echo ($index + 1) . ". Image #{$cmd['attachment_id']} - Command: {$cmd['command']}\n";
}
echo "\n";

// Show alt texts applied
echo "Alt Texts Applied:\n";
foreach ($events['alt_texts_applied'] as $index => $applied) {
    echo ($index + 1) . ". Image #{$applied['attachment_id']} - Alt text: \"{$applied['alt_text']}\"\n";
}
echo "\n";

// Show final attachment state
echo "Final Attachment State:\n";
foreach ($attachments as $id => $attachment) {
    echo "Attachment #$id:\n";
    echo "  URL: {$attachment['url']}\n";
    echo "  Has alt text: " . ($attachment['has_alt'] ? 'Yes' : 'No') . "\n";
    if ($attachment['has_alt']) {
        echo "  Alt text: \"{$attachment['alt_text']}\"\n";
    }
}
echo "\n";

echo "===== TEST COMPLETE =====\n";