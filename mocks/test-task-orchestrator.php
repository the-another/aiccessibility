<?php

/**
 * Simple Task Orchestrator Test
 * We first expect a pa11y  report to be generated.
 * We expect the report to indicate buttons tasks, image tasks, and skip to content tasks.
 * 
 * Buttons task expect to send in an HTML blob with identified issues.
 * Button task return modified HTML blob with issues resolved.
 * 
 * Image Alt task expects to receive an image file or base 64 encoded image data.
 * Image Alt task returns alt text for that image.
 * 
 * Skip to content task expects to receive an HTML block with identified issues.
 * Skip to content task returns modified HTML block with issues resolved.
 * 
 * Order:
 * 1. Generate Pa11y report
 * // Assume all three tasks are called out as needed.
 * 2. Run Buttons task
 * 3. Run Image Alt task
 * 4. Run Skip to content task
 * 
 * We expect that all content tasks are applied against the HTML blob to a final modified blob with all changes.
 * We will use that final modified blob to force render that page with issues resolved.
 */

// Include the WordPress mocks
require_once 'mock-wordpress-core-functions.php';


// Mocked pally report with the three tasks.
$pa11y_report = [
    [
        "pageUrl"=> 'The tested URL',
        "documentTitle"=> 'Title of the page under test',
        "issues"=> [
            [
                "code"=> 'WCAG2AA.Principle1.Guideline1_1.1_1_1.H30.2',
                "context"=> '<img fetchpriority="high" decoding="async" width="1024" height="536" src="https://5ke.e2d.myftpupload.com/wp-content/uploads/2025/03/qtq80-xCGjSX-1024x536.jpeg" alt="" class="wp-image-60" srcset="https://5ke.e2d.myftpupload.com/wp-content/uploads/2025/03/qtq80-xCGjSX-1024x536.jpeg 1024w, https://5ke.e2d.myftpupload.com/wp-content/uploads/2025/03/qtq80-xCGjSX-300x157.jpeg 300w, https://5ke.e2d.myftpupload.com/wp-content/uploads/2025/03/qtq80-xCGjSX-768x402.jpeg 768w, https://5ke.e2d.myftpupload.com/wp-content/uploads/2025/03/qtq80-xCGjSX-1536x804.jpeg 1536w, https://5ke.e2d.myftpupload.com/wp-content/uploads/2025/03/qtq80-xCGjSX-2048x1073.jpeg 2048w" sizes="(max-width: 1024px) 100vw, 1024px" />',
                "message"=> 'Img element is the only content of the link, but is missing alt text. The alt text should describe the purpose of the link.',
                "selector"=> 'html > body > p=>nth-child(1) > a',
                "type"=> 'error',
                "typeCode"=> 1
            ]
        ]
    ],
    [
        "pageUrl"=> 'The tested URL',
        "documentTitle"=> 'Title of the page under test',
        "issues"=> [
            [
                "code"=> 'WCAG2AA.Principle1.Guideline1_1.1_1_1.H30.2',
                "context"=> '<a href="https=>//example.com/"><img src="example.jpg" alt=""/></a>',
                "message"=> 'Img element is the only content of the link, but is missing alt text. The alt text should describe the purpose of the link.',
                "selector"=> 'html > body > p=>nth-child(1) > a',
                "type"=> 'error',
                "typeCode"=> 1
            ]
        ]
    ],
    [
        "pageUrl"=> 'The tested URL',
        "documentTitle"=> 'Title of the page under test',
        "issues"=> [
            [
                "code"=> 'WCAG2AA.Principle1.Guideline1_1.1_1_1.H30.2',
                "context"=> '<a href="https=>//example.com/"><img src="example.jpg" alt=""/></a>',
                "message"=> 'Img element is the only content of the link, but is missing alt text. The alt text should describe the purpose of the link.',
                "selector"=> 'html > body > p=>nth-child(1) > a',
                "type"=> 'error',
                "typeCode"=> 1
            ]
        ]
    ],

];

// Orchestrate the tasks based on the reports.
// Assume all three tasks are called out as needed.
$generatedAlts = [];
foreach($pa11y_report as $report) {
    // Check issues code to determine the task.
    $taskType = $report['issues'][0]['code'];
    switch($taskType) {
        case 'WCAG2AA.Principle1.Guideline1_1.1_1_1.H30.2':
            // Run Image Alt task
            $altString = run_image_alt_task($report);
            $imageContext = $report['issues'][0]['context'];
            // Regex parse the image context and extract id in the 'wp-image-*' class.
            // <img.*?wp-image-(\d*).*?\>
            preg_match('/<img.*?wp-image-(\d*).*?\>/', $imageContext, $matches);
            $image_id = $matches[1] ?? null;

            if ($image_id) {
                $generatedAlts[$image_id] = $altString;
            }
            break;
    }
}


function run_image_alt_task($report) {
    // Simulate the response, respond with a generated alt text.
    return 'Five Cloudfest Hackathon 2024 badges are displayed on a table. Each badge features a different theme, indicated by icons and text: "Dream Team" with a heart and people, "Social Media" with a thumbs-up, "Future of the Web" with a lightbulb, "Pitch Perfect" with a microphone, and "Web Impact" with a globe. The badges are designed with a circular format and a metallic appearance.';
}



// Mocked HTML blob with identified issues.
// Import the HTML to edit it.
$html_blob = file_get_contents('../mocks/html/VerySimpleWithUploadedImage.html');


// Insert the ALT after the image class.
foreach($generatedAlts as $image_id => $alt) {
    // print_r("In Generated Alts \n");
    // print_r([$image_id => $alt]);

    replace_alt_for_specific_image($html_blob, $image_id, $alt);

}

function replace_alt_for_specific_image($html, $target_image_id, $new_alt_text) {
    // Replace occurrence of double quote with single quote from new alt text.
    $new_alt_text = str_replace('"', "'", $new_alt_text);

    // Find images with specific ID and empty alt (handles both quote types)
    $altAfterPattern = "/(<img.*?wp-image-{$target_image_id}{1}.*?alt=[\'\"]{2}.*?\/>)/i";
    
    // Find all matches
    preg_match_all($altAfterPattern, $html, $matchesBefore);
    
    // Process each match
    foreach ($matchesBefore[0] as $match) {
        // Handle both single and double quotes
        $modified = preg_replace('/(alt=)([\'"])([\'"])/', '$1$2' . $new_alt_text . '$2', $match);
        
        // Replace the original match with modified version
        $html = str_replace($match, $modified, $html);
    }

    // Find matches when alt is prior to the image class.
    $altBeforePattern = "/(<img.*?alt=[\'\"]{2}.*?wp-image-{$target_image_id}{1}.*?\/>)/i";
    preg_match_all($altBeforePattern, $html, $matchesAfter);

    // Process each match
    foreach ($matchesAfter[0] as $match) {
        // Handle both single and double quotes
        $modified = preg_replace('/(alt=)([\'"])([\'"])/', '$1$2' . $new_alt_text . '$2', $match);

        // Replace the original match with modified version
        $html = str_replace($match, $modified, $html);
    }
    
    return $html;
}

