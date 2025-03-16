#!/usr/bin/env node

import {Command} from 'commander';
// @ts-ignore
import chalk from 'chalk';
// @ts-ignore
import * as dotenv from 'dotenv';
import pa11y from "pa11y";
import {generateAltTextCommand} from './commands';
import {Tasks} from "./tasks";
import {validateInputTasks} from "./utils/validationUtils";
import {loadPrompt, writeHTMLToDisk} from "./utils/fileUtils";
import {JSDOM} from 'jsdom';
import {OpenAIService} from "./services/openAIService";
import fs from "fs";

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


        validateInputTasks(includeTasks)
        validateInputTasks(excludeTasks)
        const htmlPath = writeHTMLToDisk(inputHtml);
        const dom = new JSDOM(inputHtml)
        const openAIService = new OpenAIService({
            apiKey: options.apiKey,
            model: options.model,
        });
        const pageSummary = await openAIService.summarizePage(inputHtml);

        let issues = (await pa11y(htmlPath)).issues
        console.log("Pa11y result: ", JSON.stringify(issues))
        // only use the first 3 issues
        // TODO - remove this line
        issues = issues.slice(0, 3)

        const aiFixes = issues.map(async (issue) => {
            const brokenHTML = dom.window.document.querySelector(issue.selector)!.parentElement!.outerHTML
            const prompt = loadPrompt(brokenHTML, issue.message)

            const result =  await openAIService.sendChatPrompt(prompt)

            dom.window.document.querySelector(issue.selector)!.parentElement!.outerHTML = result.fixedWebsiteCode
        })

        const resolvedFixes = await Promise.all(aiFixes)

        console.log("Resolved fixes: ", JSON.stringify(resolvedFixes))
        console.log("AI Fixes: ", aiFixes)
        console.log("Function was called with the following arguments:");
        console.log("Threshold:", threshold);
        console.log("Include tasks:", includeTasks);
        console.log("Exclude tasks:", excludeTasks);
        console.log("HTML was written to disk at:", htmlPath);
        console.log("Context:", context);
        console.log("Page summary:", pageSummary);

        fs.writeFileSync("output.html", dom.serialize())

        // TODO: Implement the actual a11y detection logic here
    });

// Display help by default if no command is specified
if (process.argv.length <= 2) {
    program.help();
}

// Parse arguments
program.parse();
