import {program} from 'commander';
import * as fs from "node:fs";
import {Tasks} from "./tasks";

program
    .description("Tool for detecting a11y issues in a web page and trying to provide a solution for some of them.")
    .option('--threshold <number>', 'Threshold of failures, that are tolerated for the numer of errors on the parsed page.', '0')
    .option('--include-tasks [tasks...]', 'List of tasks to run. (all if none is selected/excluded)', Tasks.allAvailableTasks())
    .option('--exclude-tasks [tasks...]', 'List of tasks to exclude. (none if none is selected/included)', [])
    .option('--context <string>', "Context of webpage in json format", "")
    .argument('<input-html>', 'Base64 encoded HTML file to parse.')

program.parse();

const options = program.opts();

const inputHtml = atob(program.args[0]);
const threshold = parseInt(options.threshold);
const includeTasks = options.includeTasks;
const excludeTasks = options.excludeTasks;
const context = JSON.parse(options.context)

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


console.log("Function was called with the following arguments:");
console.log("Threshold:", threshold);
console.log("Include tasks:", includeTasks);
console.log("Exclude tasks:", excludeTasks);
console.log("Input HTML:", inputHtml);
console.log("Context:", context);
console.log("HTML was written to disk at: ", htmlPath);
// Commander example: https://github.com/tj/commander.js?tab=readme-ov-file#installation
