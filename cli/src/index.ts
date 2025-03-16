#!/usr/bin/env node

import {Command} from 'commander';
// @ts-ignore
import chalk from 'chalk';
// @ts-ignore
import * as dotenv from 'dotenv';
import pa11y from "pa11y";
import {generateAltTextCommand} from './commands';
import {Tasks} from "./tasks";
import {validateInputTasks, filterIssuesByTask} from "./utils/validationUtils";
import {loadPrompt, writeHTMLToDisk} from "./utils/fileUtils";
import {JSDOM} from 'jsdom';
import {OpenAIService} from "./services/openAIService";

// Load environment variables
dotenv.config();

// Create a single program instance
const program = new Command();

// Set up CLI info
program
    .name('aicu')
    .description('AIccessibility Content Updater CLI for WordPress accessibility improvements')
    .version('1.0.0');

// Register alt text command
generateAltTextCommand(program);

// Register a11y detection command
program
    .command('detect-a11y')
    .description("Tool for detecting a11y issues in a web page and trying to provide a solution for some of them.")
    .option('--threshold <number>', 'Threshold of failures, that are tolerated for the numer of errors on the parsed page.', '0')
    .option('--include-tasks [tasks...]', 'List of tasks to run. (all if none is selected/excluded)', Tasks.allAvailableTasks())
    .option('--exclude-tasks [tasks...]', 'List of tasks to exclude. (none if none is selected/included)', [])
    .option('--context <string>', "Context of webpage in json format", "{}")
    .option('-k, --api-key <key>', 'OpenAI API key (can also be set via OPENAI_API_KEY env variable)')
    .argument('<input-html>', 'Base64 encoded HTML file to parse.')
    .action(async (inputHtmlBase64, options) => {
        const inputHtml = atob(inputHtmlBase64);
        const threshold = parseInt(options.threshold);
        const includeTasks = options.includeTasks;
        const excludeTasks = options.excludeTasks;
        const context = JSON.parse(options.context);

        const htmlPath = writeHTMLToDisk(inputHtml);
        const dom = new JSDOM(inputHtml).window.document
        
        const openAIService = new OpenAIService({
            apiKey: options.apiKey,
            model: options.model,
        });

        let issues = (await pa11y(htmlPath)).issues
        console.log("Pa11y result: ", JSON.stringify(issues))
        // only use the first 3 issues
        // TODO - remove this line
        issues = issues.slice(0, 3)

        // Categorize issues by task type
        const categorizedIssues = new Map<Tasks, any[]>();
        
        // Initialize all categories with empty arrays
        Object.values(Tasks).forEach(taskType => {
            if (typeof taskType === 'string') {
                categorizedIssues.set(taskType as Tasks, []);
            }
        });
        
        // Classify each issue into a task category
        issues.forEach(issue => {
            const taskType = Tasks.classifyIssue(issue);
            categorizedIssues.get(taskType)?.push(issue);
        });
        
        // Filter issues based on include/exclude tasks using the extracted function
        const filteredIssues = filterIssuesByTask(
            issues, 
            includeTasks, 
            excludeTasks, 
            (issue) => Tasks.classifyIssue(issue)
        );

        console.log("Categorized issues:");
        categorizedIssues.forEach((taskIssues, taskType) => {
            console.log(`${taskType}: ${taskIssues.length} issues`);
        });

        // Process issues based on their task type
        const aiFixes = filteredIssues.map(async (issue) => {
            const taskType = Tasks.classifyIssue(issue);
            const brokenHTML = dom.querySelector(issue.selector)!.parentElement!.outerHTML;
            
            // Use different solvers based on task type
            switch(taskType) {
                case Tasks.ALT_TEXT:
                    // Use alt text specific solution if implemented
                    // For now, fall back to generic prompt
                    const altTextPrompt = loadPrompt(brokenHTML, `${issue.message} [Task: ALT_TEXT]`);
                    return {
                        taskType,
                        issue,
                        fix: await openAIService.sendChatPrompt(altTextPrompt)
                    };
                
                case Tasks.BUTTON:
                    const buttonPrompt = loadPrompt(brokenHTML, `${issue.message} [Task: BUTTON]`);
                    return {
                        taskType,
                        issue,
                        fix: await openAIService.sendChatPrompt(buttonPrompt)
                    };
                
                case Tasks.SKIP_CONTENT:
                    const skipContentPrompt = loadPrompt(brokenHTML, `${issue.message} [Task: SKIP_CONTENT]`);
                    return {
                        taskType,
                        issue,
                        fix: await openAIService.sendChatPrompt(skipContentPrompt)
                    };
                
                default:
                    // Generic handler for other tasks
                    const prompt = loadPrompt(brokenHTML, issue.message);
                    return {
                        taskType,
                        issue,
                        fix: await openAIService.sendChatPrompt(prompt)
                    };
            }
        });

        const resolvedFixes = await Promise.all(aiFixes);

        console.log("Resolved fixes: ", JSON.stringify(resolvedFixes.map(fix => ({
            taskType: fix.taskType,
            message: fix.issue.message,
            fix: fix.fix
        }))));
        
        console.log("Function was called with the following arguments:");
        console.log("Threshold:", threshold);
        console.log("Include tasks:", includeTasks);
        console.log("Exclude tasks:", excludeTasks);
        console.log("HTML was written to disk at:", htmlPath);
        console.log("Context:", context);

        // TODO: Implement the actual a11y detection logic here
    });

// Display help by default if no command is specified
if (process.argv.length <= 2) {
    program.help();
}

// Parse arguments
program.parse();
