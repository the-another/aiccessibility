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

// Do the rest...