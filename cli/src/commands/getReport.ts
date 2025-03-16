import { Command } from 'commander';
import chalk from 'chalk';
import ora from 'ora';
import pa11y from 'pa11y';
import { JSDOM } from 'jsdom';
import { Tasks } from '../tasks';
import { validateInputTasks, filterIssuesByTask } from '../utils/validationUtils';
import { writeHTMLToDisk } from '../utils/fileUtils';
import { IssueDetector } from '../utils/issueDetector';

// Define an interface for our enhanced issue type
interface EnhancedIssue {
  code: string;
  type: string;
  typeCode?: number;
  message: string;
  context: string;
  selector: string;
  runner: string;
  runnerExtras?: any;
  task_type?: Tasks;
}

export function getReportCommand(program: Command): void {
  program
    .command('get-report')
    .description('Analyze HTML for accessibility issues and return a detailed report')
    .option('--threshold <number>', 'Threshold of failures that are tolerated for the number of errors on the parsed page', '0')
    .option('--include-tasks [tasks...]', 'List of tasks to run (all if none is selected/excluded)', Tasks.allAvailableTasks())
    .option('--exclude-tasks [tasks...]', 'List of tasks to exclude (none if none is selected/included)', [])
    .option('--context <string>', 'Context of webpage in json format', '{}')
    .argument('<input-html>', 'Base64 encoded HTML file to parse')
    .action(async (inputHtmlBase64, options) => {
      try {
        const inputHtml = atob(inputHtmlBase64);
        const threshold = parseInt(options.threshold);
        const includeTasks = options.includeTasks;
        const excludeTasks = options.excludeTasks;
        const context = JSON.parse(options.context);

        // Validate task parameters
        validateInputTasks(includeTasks);
        validateInputTasks(excludeTasks);
        
        const spinner = ora('Analyzing HTML for accessibility issues...').start();
        
        // Write HTML to disk for pa11y to process
        const htmlPath = writeHTMLToDisk(inputHtml);
        const dom = new JSDOM(inputHtml).window.document;
        
        try {
          // Run pa11y analysis
          let issues = (await pa11y(htmlPath)).issues as EnhancedIssue[];
          
          // Use IssueDetector to find additional issues
          const customIssues = IssueDetector.detectIssues(dom) as EnhancedIssue[];
          issues = [...issues, ...customIssues];
          
          // Categorize issues by task type
          const categorizedIssues = new Map<Tasks, EnhancedIssue[]>();
          
          // Initialize all categories with empty arrays
          Object.values(Tasks).forEach(taskType => {
            if (typeof taskType === 'string') {
              categorizedIssues.set(taskType as Tasks, []);
            }
          });
          
          // Classify each issue into a task category and add the task_type to the issue object
          issues.forEach(issue => {
            const taskType = Tasks.classifyIssue(issue);
            // Add task_type property to each issue
            issue.task_type = taskType;
            categorizedIssues.get(taskType)?.push(issue);
          });
          
          // Filter issues based on include/exclude tasks
          const filteredIssues = filterIssuesByTask(
            issues, 
            includeTasks, 
            excludeTasks, 
            (issue) => Tasks.classifyIssue(issue)
          );
          
          spinner.succeed('Analysis complete');
          
          // Prepare the final report
          const report = {
            totalIssues: issues.length,
            filteredIssues: filteredIssues.length,
            categorizedCounts: Object.fromEntries(
              [...categorizedIssues.entries()].map(([key, value]) => [key, value.length])
            ),
            issues: filteredIssues,
            context,
            htmlPath,
            passThreshold: filteredIssues.length <= threshold
          };
          
          // Output the report as JSON
          console.log(JSON.stringify(report, null, 2));
          
          // Exit with code based on threshold
          process.exit(report.passThreshold ? 0 : 1);
          
        } catch (error) {
          spinner.fail('Analysis failed');
          console.error(chalk.red(`Error: ${error instanceof Error ? error.message : String(error)}`));
          process.exit(1);
        }
      } catch (error) {
        console.error(chalk.red(`Unexpected error: ${error instanceof Error ? error.message : String(error)}`));
        process.exit(1);
      }
    });
} 