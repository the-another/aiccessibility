import { Command } from 'commander';
import chalk from 'chalk';
import ora from 'ora';
import fs from 'fs';
import { OpenAIService } from '../services/openAIService';
import { isValidImageFile, isValidBase64Image } from '../utils/imageUtils';

export function generateAltTextCommand(program: Command): void {
  program
    .command('alt-text')
    .description('Generate alt text for images using OpenAI API')
    .option('-f, --file <path>', 'Path to the image file')
    .option('-b, --base64 <string>', 'Base64 encoded image data')
    .option('-k, --api-key <key>', 'OpenAI API key (can also be set via OPENAI_API_KEY env variable)')
    .option('-m, --model <model>', 'OpenAI model to use (defaults to gpt-40)')
    .option('-l, --list-models', 'List available OpenAI vision models')
    .option('-c, --context <string>', 'Page context as text or JSON with context field')
    .option('-h, --html-base64 <string>', 'Base64 encoded HTML of the page for context extraction')
    .action(async (options) => {
      try {
        // Check for API key
        const apiKey = options.apiKey || process.env.OPENAI_API_KEY;
        if (!apiKey) {
          console.error(chalk.red('Error: OpenAI API key is required. Provide it with --api-key or set OPENAI_API_KEY environment variable.'));
          process.exit(1);
        }

        // Initialize OpenAI service
        const openAIService = new OpenAIService({
          apiKey,
          model: undefined,
        });

        // Handle list models option
        if (options.listModels) {
          const spinner = ora('Fetching available OpenAI models...').start();
          try {
            const models = await openAIService.getAvailableModels();
            spinner.succeed('Available models:');
            models.forEach(model => console.log(`  - ${model}`));
            return;
          } catch (error) {
            spinner.fail('Failed to fetch models');
            console.error(chalk.red(`Error: ${error instanceof Error ? error.message : String(error)}`));
            process.exit(1);
          }
        }

        // Check for image input
        if (options.file && options.base64) {
          console.error(chalk.red('Error: Please provide either a file or base64 data, not both.'));
          process.exit(1);
        }

        if (!options.file && !options.base64) {
          console.error(chalk.red('Error: Please provide either a file path (--file) or base64 data (--base64).'));
          process.exit(1);
        }

        // Check for context options - cannot provide both
        if (options.context && options.htmlBase64) {
          console.error(chalk.red('Error: Please provide either context or HTML base64 data, not both.'));
          process.exit(1);
        }

        // Process context if provided as JSON
        let pageContext: string | undefined;
        if (options.context) {
          try {
            // Check if the context is a JSON string with a context field
            const parsedContext = JSON.parse(options.context);
            if (parsedContext.context) {
              pageContext = parsedContext.context;
            } else {
              pageContext = options.context;
            }
          } catch (e) {
            // Not valid JSON, use as plain text
            pageContext = options.context;
          }
        } else if (options.htmlBase64) {
          pageContext = options.htmlBase64;
        }

        // Ensure pageContext is properly set when context is provided
        if (options.context && !pageContext) {
          console.error(chalk.red('Error: Failed to process context parameter.'));
          process.exit(1);
        }

        // Process file
        if (options.file) {
          if (!isValidImageFile(options.file)) {
            console.error(chalk.red(`Error: Invalid or non-existent image file: ${options.file}`));
            process.exit(1);
          }

          const spinner = ora(`Generating alt text for image: ${options.file}`).start();
          try {
            const result = await openAIService.generateAltText(
              options.file, 
              false, 
              pageContext, 
              !!options.htmlBase64
            );
            spinner.succeed('Alt text generated successfully');
            
            // Display alt text and relevancy
            console.log(chalk.green('\nAlt Text:'));
            console.log(chalk.white(result.altText));
            
            // Display relevancy with color coding based on score
            const relevancyColor = getRelevancyColor(result.relevancy);
            console.log(chalk.green('\nRelevancy Score:'));
            console.log(relevancyColor(`${result.relevancy.toFixed(2)} ${getRelevancyLabel(result.relevancy)}`));
            
            console.log('\nHTML usage:');
            console.log(chalk.cyan(`<img src="your-image-path" alt="${result.altText}" data-relevancy="${result.relevancy.toFixed(2)}" />`));
          } catch (error) {
            spinner.fail('Failed to generate alt text');
            console.error(chalk.red(`Error: ${error instanceof Error ? error.message : String(error)}`));
            process.exit(1);
          }
        }

        // Process base64
        if (options.base64) {
          if (!isValidBase64Image(options.base64)) {
            console.error(chalk.red('Error: Invalid base64 image data.'));
            process.exit(1);
          }

          const spinner = ora('Generating alt text for base64 image').start();
          try {
            const result = await openAIService.generateAltText(
              options.base64, 
              true, 
              pageContext, 
              !!options.htmlBase64
            );
            spinner.succeed('Alt text generated successfully');
            
            // Display alt text and relevancy
            console.log(chalk.green('\nAlt Text:'));
            console.log(chalk.white(result.altText));
            
            // Display relevancy with color coding based on score
            const relevancyColor = getRelevancyColor(result.relevancy);
            console.log(chalk.green('\nRelevancy Score:'));
            console.log(relevancyColor(`${result.relevancy.toFixed(2)} ${getRelevancyLabel(result.relevancy)}`));
            
            console.log('\nHTML usage:');
            console.log(chalk.cyan(`<img src="your-image-path" alt="${result.altText}" data-relevancy="${result.relevancy.toFixed(2)}" />`));
          } catch (error) {
            spinner.fail('Failed to generate alt text');
            console.error(chalk.red(`Error: ${error instanceof Error ? error.message : String(error)}`));
            process.exit(1);
          }
        }
      } catch (error) {
        console.error(chalk.red(`Unexpected error: ${error instanceof Error ? error.message : String(error)}`));
        process.exit(1);
      }
    });

  // Helper function to get color for relevancy score
  function getRelevancyColor(score: number): chalk.ChalkFunction {
    if (score >= 0.7) return chalk.green;
    if (score >= 0.4) return chalk.yellow;
    return chalk.red;
  }

  // Helper function to get label for relevancy score
  function getRelevancyLabel(score: number): string {
    if (score >= 0.7) return '(Highly relevant)';
    if (score >= 0.4) return '(Moderately relevant)';
    return '(Low relevance - consider removing)';
  }

  // Add example usage
  program.on('--help', () => {
    console.log('');
    console.log('Examples:');
    console.log('  $ aicu alt-text --file ./image.jpg --api-key your-api-key');
    console.log('  $ aicu alt-text --base64 data:image/jpeg;base64,/9j/4AAQ... --model gpt-4o');
    console.log('  $ aicu alt-text --file ./image.jpg --context "This is a blog post about horses and their care"');
    console.log('  $ aicu alt-text --file ./image.jpg --html-base64 base64EncodedHtmlContent');
    console.log('  $ aicu alt-text --list-models --api-key your-api-key');
  });
}
