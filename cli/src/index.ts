#!/usr/bin/env node

import {Command} from 'commander';
// @ts-ignore
import chalk from 'chalk';
// @ts-ignore
import * as dotenv from 'dotenv';
import {generateAltTextCommand, getReportCommand, solveIssueCommand} from './commands';

// Load environment variables
dotenv.config();

// Create a single program instance
const program = new Command();

// Set up CLI info
program
    .name('aicu')
    .description('AIccessibility Content Updater CLI for WordPress accessibility improvements')
    .version('1.0.0');

// Register commands
generateAltTextCommand(program);
getReportCommand(program);
solveIssueCommand(program);

// Display help by default if no command is specified
if (process.argv.length <= 2) {
    program.help();
}

// Parse arguments
program.parse();
