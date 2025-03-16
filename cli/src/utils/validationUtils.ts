import {Tasks} from "../tasks";

export function validateInputTasks(tasks: string[]): void {
    tasks.forEach(task => {
        if (!Tasks.allAvailableTasks().includes(task)) {
            console.error(`Task ${task} is not available.`);
            process.exit(1);
        }
    });
}
