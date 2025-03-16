<?php
/**
 * Basic WordPress Hooks Test
 * 
 * This is a very simple test script that doesn't require any dependencies
 * to verify that WordPress hooks would work as expected.
 */

// Track logged messages
$loggedMessages = [];

// Track scheduled tasks
$scheduledTasks = [];

// ----- MOCK WORDPRESS FUNCTIONS -----

/**
 * Mock WordPress's add_action function
 */
function add_action($hook, $callback, $priority = 10, $args = 1) {
    echo "Action registered: $hook\n";
    return true;
}

/**
 * Mock WordPress's add_filter function
 */
function add_filter($hook, $callback, $priority = 10, $args = 1) {
    echo "Filter registered: $hook\n";
    return true;
}

/**
 * Mock WordPress's do_action function
 */
function do_action($hook) {
    echo "Action triggered: $hook\n";
    $args = func_get_args();
    array_shift($args); // Remove hook name
    
    // Get global variables
    global $hooks;
    
    // Call the function registered to this hook
    if (isset($hooks[$hook])) {
        call_user_func_array($hooks[$hook], $args);
    }
}

/**
 * Mock WordPress's apply_filters function
 */
function apply_filters($hook, $value) {
    echo "Filter applied: $hook\n";
    return $value;
}

/**
 * Mock WordPress's register_activation_hook function
 */
function register_activation_hook($file, $callback) {
    echo "Activation hook registered\n";
    return true;
}

/**
 * Mock WordPress's wp_attachment_is_image function
 */
function wp_attachment_is_image($attachment_id) {
    return in_array($attachment_id, [10, 11, 12]);
}

/**
 * Mock WordPress's get_post_meta function
 */
function get_post_meta($post_id, $meta_key, $single = false) {
    $meta = [
        10 => [
            '_wp_attachment_image_alt' => [''] // No alt text
        ],
        11 => [
            '_wp_attachment_image_alt' => ['Existing alt text'] // Has alt text
        ],
        12 => [
            '_wp_attachment_image_alt' => ['Generic image'] // Generic alt text
        ],
    ];
    
    if (isset($meta[$post_id]) && isset($meta[$post_id][$meta_key])) {
        return $single ? $meta[$post_id][$meta_key][0] : $meta[$post_id][$meta_key];
    }
    
    return $single ? '' : [];
}

/**
 * Mock WordPress's wp_get_attachment_url function
 */
function wp_get_attachment_url($attachment_id) {
    return "https://example.com/wp-content/uploads/image-$attachment_id.jpg";
}

/**
 * Mock WordPress's is_post_type_viewable function
 */
function is_post_type_viewable($post_type) {
    return true;
}

/**
 * Mock WordPress's wp_is_post_revision function
 */
function wp_is_post_revision($post_id) {
    return false;
}

/**
 * Mock WordPress's get_post_type function
 */
function get_post_type($post_id) {
    return 'post';
}

/**
 * Mock WordPress's get_post_status function
 */
function get_post_status($post_id) {
    return 'publish';
}

/**
 * Mock function to log messages
 */
function access_log($message) {
    global $loggedMessages;
    $loggedMessages[] = $message;
    echo "LOG: $message\n";
    return true;
}

/**
 * Mock Action Scheduler function
 */
function as_schedule_single_action($timestamp, $hook, $args = [], $group = '') {
    global $scheduledTasks;
    $scheduledTasks[] = [
        'timestamp' => $timestamp,
        'hook' => $hook,
        'args' => $args,
        'group' => $group
    ];
    echo "SCHEDULED: $hook\n";
    return 123; // Mock action ID
}

/**
 * Mock WP Cron function
 * This would instead be a call to queue the updates from CLI later.
 */
function wp_schedule_single_event($timestamp, $hook, $args = []) {
    global $scheduledTasks;
    $scheduledTasks[] = [
        'timestamp' => $timestamp,
        'hook' => $hook,
        'args' => $args,
        'type' => 'wp_cron'
    ];
    echo "SCHEDULED (WP): $hook\n";
    return true;
}

// ----- REGISTER HOOKS -----

// Register hooks for tracking
$hooks = [];

// Register a function to process image uploads
$hooks['add_attachment'] = function($attachment_id) {
    // Check if it's an image
    if (!wp_attachment_is_image($attachment_id)) {
        return;
    }
    
    // Get image details
    $image_url = wp_get_attachment_url($attachment_id);
    $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    
    // Log information
    access_log("IMAGE UPLOADED - ID: $attachment_id, URL: $image_url, Has Alt: " . (empty($alt_text) ? 'No' : 'Yes'));
    
    // If no alt text, schedule a task
    if (empty($alt_text)) {
        access_log("TASK QUEUED - Generate alt text for image ID: $attachment_id");
        
        // Schedule a task
        as_schedule_single_action(
            time() + 30,
            'accessibility_generate_alt_text',
            ['attachment_id' => $attachment_id]
        );
    }
};

// Define our plugin code
$plugin_code = <<<'EOT'
<?php
/**
 * Plugin Name: Simple Accessibility Hooks
 * Description: Logs image uploads and post saves for accessibility processing
 * Version: 0.1.0
 * Author: Your Name
 */

// Register hooks
add_action('add_attachment', 'access_process_image');
add_action('save_post', 'access_process_post', 10, 3);
add_action('transition_post_status', 'access_process_publish', 10, 3);
add_action('rest_insert_post', 'access_process_rest_api_post_save', 10, 3);
add_action('updated_postmeta', 'access_process_meta_update', 10, 4);
add_action('get_header', 'access_add_skip_to_content');

// Register activation hook
register_activation_hook(__FILE__, function() {
    access_log("PLUGIN ACTIVATED - Simple Accessibility Hooks");
});

/**
 * Hook: Process image after upload
 */
function access_process_image($attachment_id) {
    // Only process images
    if (!wp_attachment_is_image($attachment_id)) {
        return;
    }
    
    $image_url = wp_get_attachment_url($attachment_id);
    $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    
    access_log("IMAGE UPLOADED - ID: $attachment_id, URL: $image_url, Has Alt: " . (empty($alt_text) ? 'No' : 'Yes'));
    
    // If no alt text, queue for processing
    if (empty($alt_text)) {
        access_log("TASK QUEUED - Generate alt text for image ID: $attachment_id");
        
        // Schedule a task
        if (function_exists('as_schedule_single_action')) {
            as_schedule_single_action(
                time() + 30,
                'accessibility_generate_alt_text',
                ['attachment_id' => $attachment_id]
            );
        } else {
            wp_schedule_single_event(
                time() + 30,
                'accessibility_generate_alt_text',
                [['attachment_id' => $attachment_id]]
            );
        }
    }
}
EOT;

// ----- RUN TESTS -----

echo "\n===== TESTING WORDPRESS ACCESSIBILITY HOOKS =====\n\n";

// Evaluate our plugin (just to show the hooks being registered)
echo "Registering plugin hooks:\n";
eval(str_replace('<?php', '', $plugin_code));
echo "\n";

// Test the add_attachment hook
echo "Testing attachment hook (no alt text):\n";
do_action('add_attachment', 10);
echo "\n";

// Test the add_attachment hook with an image that has alt text
echo "Testing attachment hook (with alt text):\n";
do_action('add_attachment', 11);
echo "\n";

// Show results
echo "===== RESULTS =====\n\n";

// Show logged messages
echo "Logged Messages:\n";
foreach ($loggedMessages as $index => $message) {
    echo ($index + 1) . ". $message\n";
}
echo "\n";

// Show scheduled tasks
echo "Scheduled Tasks:\n";
foreach ($scheduledTasks as $index => $task) {
    echo ($index + 1) . ". Hook: {$task['hook']}, Args: " . json_encode($task['args']) . "\n";
}
echo "\n";

echo "===== TEST COMPLETE =====\n";