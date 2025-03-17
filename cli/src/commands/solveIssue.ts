import { Command } from 'commander';
import chalk from 'chalk';
import ora from 'ora';
import { JSDOM } from 'jsdom';
import { Tasks } from '../tasks';
import { loadPrompt } from '../utils/fileUtils';
import { OpenAIService } from '../services/openAIService';
import fs from "fs";

export function solveIssues(issueDataJsonPath: string, htmlContentPath: string, options: any) {
    try {

        // Parse inputs
        const issueData = JSON.parse(fs.readFileSync(issueDataJsonPath, 'utf-8'));
        const htmlContent = fs.readFileSync(htmlContentPath, 'utf-8');
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

            process.stdout.write(dom.serialize())
            const fixedHtmlPath = htmlContentPath.replace('.html', '-fixed.html');
            fs.writeFileSync(fixedHtmlPath, dom.serialize());
        } catch (error) {
            spinner.fail('Failed to generate solution');
            console.error(chalk.red(`Error: ${error instanceof Error ? error.message : String(error)}`));
            process.exit(1);
        }
    } catch (error) {
        console.error(chalk.red(`Unexpected error: ${error instanceof Error ? error.message : String(error)}`));
        process.exit(1);
    }
}

export function solveIssueCommand(program: Command): void {
  program
    .command('solve-issue')
    .description('Suggest fixes for identified accessibility issues using AI')
    .option('--issue-type <type>', 'Type of issue to solve (ALT_TEXT, BUTTON, SKIP_CONTENT, SEMANTIC_STRUCTURE)', 'ALT_TEXT')
    .option('--context <string>', 'Context of webpage in json format', '{}')
    .argument('<issue-data>', 'Path to the JSON containing the issue data to solve')
    .argument('<html-content>', 'Path to the HTML content file')
    .action(async (issueDataJsonPath, htmlContentPath, options) => {
        solveIssues(issueDataJsonPath, htmlContentPath, options);
    });
}
