import { Command } from 'commander';
import chalk from 'chalk';
import ora from 'ora';
import { JSDOM } from 'jsdom';
import { Tasks } from '../tasks';
import { loadPrompt } from '../utils/fileUtils';
import { OpenAIService } from '../services/openAIService';
import fs from "fs";

export function solveIssueCommand(program: Command): void {
  program
    .command('solve-issue')
    .description('Suggest fixes for identified accessibility issues using AI')
    .option('--issue-type <type>', 'Type of issue to solve (ALT_TEXT, BUTTON, SKIP_CONTENT, SEMANTIC_STRUCTURE)', 'ALT_TEXT')
    .option('--context <string>', 'Context of webpage in json format', '{}')
    .argument('<issue-data>', 'JSON string containing the issue data to solve')
    .argument('<html-content>', 'Base64 encoded HTML content containing the issue')
    .action(async (issueDataJson, htmlContentBase64, options) => {
      try {

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

        const spinner = ora(`Solving ${issueType} issue...`).start();

        try {
          // Parse HTML to DOM
          const dom = new JSDOM(htmlContent);

         issueData.issues.map(async (issue: { selector: any; context: any; }) => {


           let querySelector = dom.window.document.querySelector(issue.selector);
           if (querySelector) {


             querySelector.outerHTML = decodeURIComponent(issue.context)

           }
          })

          spinner.succeed('Solution generated');

          console.log(dom.serialize())

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
