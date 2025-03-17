#!/usr/bin/env php
<?php
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

 require_once 'mock-wordpress-core-functions.php';
 require_once 'test-image-callback.php';


// Call CLI tool with simulated data.
echo "Testing CLI tool integration...\n";
$cli_mock = new CLI_Mock();
$response = $cli_mock->send_callback(12, 'Generated alt text for image 12');