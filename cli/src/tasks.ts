export enum Tasks {
    // Main accessibility task categories
    ALT_TEXT = 'ALT_TEXT',        // Missing or improper alternative text for images
    BUTTON = 'BUTTON',            // Button accessibility issues (labels, roles, etc.)
    SKIP_CONTENT = 'SKIP_CONTENT', // Skip navigation links
    SEMANTIC_STRUCTURE = 'SEMANTIC_STRUCTURE', // Proper HTML5 semantic elements usage
}

// WCAG guideline categories mapped to our simplified task categories
export interface WCAGMapping {
    wcagId: string;
    taskType: Tasks;
    description: string;
}

export namespace Tasks {
    export function allAvailableTasks(): string[] {
        return Object.values(Tasks).filter(value => typeof value === 'string');
    }

    // Map WCAG guidelines to our task types
    export function getWCAGMappings(): WCAGMapping[] {
        return [
            // 1.1 Text Alternatives
            { wcagId: '1.1.1', taskType: Tasks.ALT_TEXT, description: 'Non-text Content' },
            
            // 2.1 Keyboard Accessible
            { wcagId: '2.1.1', taskType: Tasks.BUTTON, description: 'Keyboard' },
            { wcagId: '2.1.2', taskType: Tasks.BUTTON, description: 'No Keyboard Trap' },
            
            // 2.4 Navigable
            { wcagId: '2.4.1', taskType: Tasks.SKIP_CONTENT, description: 'Bypass Blocks' },
            { wcagId: '2.4.3', taskType: Tasks.SKIP_CONTENT, description: 'Focus Order' },
            { wcagId: '2.4.4', taskType: Tasks.BUTTON, description: 'Link Purpose (In Context)' },
            { wcagId: '2.4.6', taskType: Tasks.SEMANTIC_STRUCTURE, description: 'Headings and Labels' },
            
            // 3.1 Readable
            { wcagId: '3.1.1', taskType: Tasks.SEMANTIC_STRUCTURE, description: 'Language of Page' },
            
            // 4.1 Compatible
            { wcagId: '4.1.1', taskType: Tasks.SEMANTIC_STRUCTURE, description: 'Parsing' },
            { wcagId: '4.1.2', taskType: Tasks.BUTTON, description: 'Name, Role, Value' },
        ];
    }

    // Classify Pa11y issue to our task type
    export function classifyIssue(issue: any): Tasks {
        const code = issue.code;
        
        // Extract WCAG reference from Pa11y issue code
        const wcagMatch = code.match(/WCAG2AA\.(Principle\d+\.Guideline\d+_\d+\.)?(\d+_\d+_\d+)/);
        
        if (wcagMatch) {
            const wcagId = wcagMatch[2].replace(/_/g, '.');
            
            // Look for mapping
            const mapping = getWCAGMappings().find(m => m.wcagId === wcagId);
            if (mapping) {
                return mapping.taskType;
            }
        }
        
        // Fallback classification based on issue message content
        const msg = issue.message.toLowerCase();
        
        if (msg.includes('alt') || msg.includes('image') || msg.includes('non-text')) {
            return Tasks.ALT_TEXT;
        } else if (msg.includes('button') || msg.includes('link') || msg.includes('control')) {
            return Tasks.BUTTON;
        } else if (msg.includes('skip') || msg.includes('bypass') || msg.includes('navigation')) {
            return Tasks.SKIP_CONTENT;
        }
        
        // Default to semantic structure for other issues
        return Tasks.SEMANTIC_STRUCTURE;
    }
}
