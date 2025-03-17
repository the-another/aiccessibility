import {Command} from 'commander';
import chalk from 'chalk';
import ora from 'ora';
import {OpenAIService} from '../services/openAIService';
import {isValidBase64Image, isValidImageFile} from '../utils/imageUtils';

async function calculateRelevancy(options:any, openAIService: OpenAIService, altText: string) {
    let combindedContext = options.context
    if (options.page) {
        const context = await openAIService.summarizePage(atob(options.page))
        combindedContext += context;
    }
    return await openAIService.checkRelevancyOfAltText(altText, combindedContext);
}

export function generateAltTextCommand(program: Command): void {
  program
    .command('alt-text')
    .description('Generate alt text for images using OpenAI API')
    .option('-f, --file <string>', 'Path to the image file')
    .option('-b, --base64 <string>', 'Base64 encoded image data')
    .option('-k, --api-key <key>', 'OpenAI API key (can also be set via OPENAI_API_KEY env variable)')
    .option('-m, --model <model>', 'OpenAI model to use (defaults to gpt-40)')
    .option('-l, --list-models', 'List available OpenAI vision models')
    .option('-c, context <string>', 'Context of webpage in json format', '{}')
    .option('-p, --page <string>', 'Base64 encoded HTML content containing the issue')
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

        // Process file
        if (options.file) {
          if (!isValidImageFile(options.file)) {
            console.error(chalk.red(`Error: Invalid or non-existent image file: ${options.file}`));
            process.exit(1);
          }

          const spinner = ora(`Generating alt text for image: ${options.file}`).start();
          try {
            const altText = await openAIService.generateAltText(options.file, false);
            spinner.succeed('Alt text generated successfully');
            console.log(chalk.green('\nAlt Text:'));
            console.log(chalk.white(altText));
            console.log('\nHTML usage:');
            console.log(chalk.cyan(`<img src="your-image-path" alt="${altText}" />`));
              const relevancy = await calculateRelevancy(options, openAIService, altText);

              process.stdout.write(`{
                "altText": "${altText}",
                "relevancy": ${relevancy}
            }`);
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
            const altText = await openAIService.generateAltText(options.base64, true);
            spinner.succeed('Alt text generated successfully');
            console.log(chalk.green('\nAlt Text:'));
            console.log(chalk.white(altText));
            console.log('\nHTML usage:');
            console.log(chalk.cyan(`<img src="your-image-path" alt="${altText}" />`));

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

  // Add example usage
  program.on('--help', () => {
    console.log('');
    console.log('Examples:');
    console.log('  $ aicu alt-text --file ./image.jpg --api-key your-api-key');
    console.log('  $ aicu alt-text --base64 data:image/jpeg;base64,/9j/4AAQ... --model gpt-4o');
    console.log('  $ aicu alt-text --list-models --api-key your-api-key');
  });
}
