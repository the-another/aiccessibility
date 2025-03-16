import { Command } from 'commander';
import chalk from 'chalk';
import ora from 'ora';
import { JSDOM } from 'jsdom';
import { Tasks } from '../tasks';
import { loadPrompt } from '../utils/fileUtils';
import { OpenAIService } from '../services/openAIService';

export function solveIssueCommand(program: Command): void {
  program
    .command('solve-issue')
    .description('Suggest fixes for identified accessibility issues using AI')
    .option('-k, --api-key <key>', 'OpenAI API key (can also be set via OPENAI_API_KEY env variable)')
    .option('-m, --model <model>', 'OpenAI model to use')
    .option('--issue-type <type>', 'Type of issue to solve (ALT_TEXT, BUTTON, SKIP_CONTENT, SEMANTIC_STRUCTURE)', 'ALT_TEXT')
    .option('--context <string>', 'Context of webpage in json format', '{}')
    .argument('<issue-data>', 'JSON string containing the issue data to solve')
    .argument('<html-content>', 'Base64 encoded HTML content containing the issue')
    .action(async (issueDataJson, htmlContentBase64, options) => {
      try {
        // Check for API key
        const apiKey = options.apiKey || process.env.OPENAI_API_KEY;
        if (!apiKey) {
          console.error(chalk.red('Error: OpenAI API key is required. Provide it with --api-key or set OPENAI_API_KEY environment variable.'));
          process.exit(1);
        }

        // Parse inputs
        const issueData = JSON.parse(issueDataJson);
        const htmlContent = atob(htmlContentBase64);
        const context = JSON.parse(options.context);
        const issueType = options.issueType as Tasks;

        // Validate issue type
        if (!Object.values(Tasks).includes(issueType)) {
          console.error(chalk.red(`Error: Invalid issue type: ${issueType}. Must be one of: ${Tasks.allAvailableTasks().join(', ')}`));
          process.exit(1);
        }

        // Initialize OpenAI service
        const openAIService = new OpenAIService({
          apiKey,
          model: options.model,
        });

        const spinner = ora(`Solving ${issueType} issue...`).start();
        
        try {
          // Parse HTML to DOM
          const dom = new JSDOM(htmlContent).window.document;
          
          // Try to locate the element with the issue
          let elementWithIssue: Element | null = null;
          if (issueData.selector) {
            elementWithIssue = dom.querySelector(issueData.selector);
          }
          
          // If element not found, just use the full HTML
          const brokenHTML = elementWithIssue?.parentElement?.outerHTML || htmlContent;
          
          // Create issue context message
          const issueMessage = issueData.message || `Fix accessibility issue of type ${issueType}`;
          
          // Apply different solving strategies based on issue type
          let solution;
          
          switch (issueType) {
            case Tasks.ALT_TEXT:
              // Handle missing alt text for images
              if (elementWithIssue?.tagName === 'IMG') {
                const imgSrc = elementWithIssue.getAttribute('src') || '';
                // If it's an image with src, we can try to generate alt text
                if (imgSrc) {
                  solution = await openAIService.generateAltText(imgSrc, true);
                } else {
                  // If no src, use generic prompt
                  const altTextPrompt = loadPrompt(brokenHTML, `${issueMessage} [Task: ALT_TEXT]`);
                  solution = await openAIService.sendChatPrompt(altTextPrompt);
                }
              } else {
                // Generic approach for other ALT_TEXT issues
                const altTextPrompt = loadPrompt(brokenHTML, `${issueMessage} [Task: ALT_TEXT]`);
                solution = await openAIService.sendChatPrompt(altTextPrompt);
              }
              break;
              
            case Tasks.BUTTON:
              const buttonPrompt = loadPrompt(brokenHTML, `${issueMessage} [Task: BUTTON]`);
              solution = await openAIService.sendChatPrompt(buttonPrompt);
              break;
              
            case Tasks.SKIP_CONTENT:
              const skipContentPrompt = loadPrompt(brokenHTML, `${issueMessage} [Task: SKIP_CONTENT]`);
              solution = await openAIService.sendChatPrompt(skipContentPrompt);
              break;
              
            case Tasks.SEMANTIC_STRUCTURE:
              const semanticPrompt = loadPrompt(brokenHTML, `${issueMessage} [Task: SEMANTIC_STRUCTURE]`);
              solution = await openAIService.sendChatPrompt(semanticPrompt);
              break;
              
            default:
              // Generic handler for other tasks
              const prompt = loadPrompt(brokenHTML, issueMessage);
              solution = await openAIService.sendChatPrompt(prompt);
          }
          
          spinner.succeed('Solution generated');
          
          // Prepare response
          const response = {
            issueType,
            originalIssue: issueData,
            solution,
            context
          };
          
          // Output the solution as JSON
          console.log(JSON.stringify(response, null, 2));
          
        } catch (error) {
          spinner.fail('Failed to generate solution');
          console.error(chalk.red(`Error: ${error instanceof Error ? error.message : String(error)}`));
          process.exit(1);
        }
      } catch (error) {
        console.error(chalk.red(`Unexpected error: ${error instanceof Error ? error.message : String(error)}`));
        process.exit(1);
      }
    });
} 