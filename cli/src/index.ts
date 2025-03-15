#!/usr/bin/env node

import {Command} from 'commander';
// @ts-ignore
import chalk from 'chalk';
// @ts-ignore
import * as dotenv from 'dotenv';
import * as fs from "node:fs";
import pa11y from "pa11y";
import {generateAltTextCommand} from './commands';
import {Tasks} from "./tasks";

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
    .argument('<input-html>', 'Base64 encoded HTML file to parse.')
    .action((inputHtmlBase64, options) => {
        const inputHtml = atob(inputHtmlBase64);
        const threshold = parseInt(options.threshold);
        const includeTasks = options.includeTasks;
        const excludeTasks = options.excludeTasks;
        const context = JSON.parse(options.context);

        function writeHTMLToDisk(inputHtml: string): string {
            const path = require('node:path');
            let filePath = path.resolve(process.cwd(), 'index.html');
            fs.writeFileSync(filePath, inputHtml);
            return filePath;
        }

        function validateInputTasks(tasks: string[]): void {
            tasks.forEach(task => {
                if (!Tasks.allAvailableTasks().includes(task)) {
                    console.error(`Task ${task} is not available.`);
                    process.exit(1);
                }
            });
        }

        validateInputTasks(includeTasks)
        validateInputTasks(excludeTasks)
        const htmlPath = writeHTMLToDisk(inputHtml);

        pa11y(htmlPath).then((results: any) => {
            console.log(results)
        });

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
