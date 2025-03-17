<?php
// Simplified WordPress functions
function wp_attachment_is_image($attachment_id) {
    global $attachments;
    return isset($attachments[$attachment_id]);
}

function get_post_meta($post_id, $meta_key, $single = false) {
    global $attachments;
    
    if ($meta_key === '_wp_attachment_image_alt' && isset($attachments[$post_id])) {
        return $attachments[$post_id]['has_alt'] ? $attachments[$post_id]['alt_text'] : '';
    }
    
    return '';
}

function do_action($hook) {
    global $events;
    
    if ($hook === 'add_attachment') {
        $attachment_id = func_get_arg(1);
        $events['images_checked'][] = [
            'attachment_id' => $attachment_id,
            'has_alt' => !empty(get_post_meta($attachment_id, '_wp_attachment_image_alt', true))
        ];
    }
}

function add_action($hook, $callback) {
    if ($hook === 'add_attachment') {
        $attachment_id = func_get_arg(1);
        $callback($attachment_id);
    }
}

function update_post_meta($post_id, $meta_key, $meta_value) {
    global $attachments, $events;
    
    if ($meta_key === '_wp_attachment_image_alt' && isset($attachments[$post_id])) {
        $attachments[$post_id]['has_alt'] = true;
        $attachments[$post_id]['alt_text'] = $meta_value;
        
        $events['alt_texts_applied'][] = [
            'attachment_id' => $post_id,
            'alt_text' => $meta_value
        ];
        
        echo "Updated alt text for attachment #$post_id: \"$meta_value\"\n";
        return true;
    }
    
    return false;
}

function wp_get_attachment_url($attachment_id) {
    global $attachments;
    
    if (isset($attachments[$attachment_id])) {
        return $attachments[$attachment_id]['url'];
    }
    
    return '';
}

/**
 * Mock the WP_REST_Response class.
 */

class WP_REST_Response {
    public $data;
    public $status;
    
    public function __construct($data, $status = 200) {
        $this->data = $data;
        $this->status = $status;
    }
}   

