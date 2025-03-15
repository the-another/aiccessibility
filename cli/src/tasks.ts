export enum Tasks {
    // TODO: Add tasks here
    TODO = 'TODO',

}


export namespace Tasks {
    export function allAvailableTasks(): string[] {
        return Object.values(Tasks).filter(value => typeof value === 'string');
    }
}
