import {Tasks} from "../tasks";

export function validateInputTasks(tasks: string[]): void {
    tasks.forEach(task => {
        if (!Tasks.allAvailableTasks().includes(task)) {
            console.error(`Task ${task} is not available.`);
            process.exit(1);
        }
    });
}

/**
 * Filters issues based on included and excluded tasks
 * @param issues Array of issues to filter
 * @param includeTasks Array of task types to include (if empty, include all tasks unless excluded)
 * @param excludeTasks Array of task types to exclude (higher priority than includeTasks)
 * @returns Filtered array of issues
 */
export function filterIssuesByTask<T>(
    issues: T[], 
    includeTasks: string[] = [], 
    excludeTasks: string[] = [],
    getTaskType: (issue: T) => Tasks
): T[] {
    return issues.filter(issue => {
        const taskType = getTaskType(issue);
        
        // First check exclusions - if task should be excluded, return false
        if (excludeTasks && excludeTasks.length > 0 && excludeTasks.includes(taskType)) {
            return false;
        }
        
        // Then check inclusions - if includeTasks is specified and not empty
        if (includeTasks && includeTasks.length > 0) {
            return includeTasks.includes(taskType);
        }
        
        // If we get here, the task is neither excluded nor filtered by inclusion
        return true;
    });
}
