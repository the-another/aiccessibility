import {getReport} from "../commands/report";
import {OpenAIService} from "../services/openAIService";

/**
 * Utility class for detecting accessibility issues that may not be caught by Pa11y
 */
export class IssueDetector {

    static async detectIssues(dom: Document, openAIService: OpenAIService, htmlPath: string): Promise<any[]> {
        const issues: any[] = [];

        const result = await getReport(htmlPath, openAIService)
        issues.push(...this.detectButtonIssues(dom));
        issues.push(...result.issues);

        return issues;
    }

    /**
     * Detects button accessibility issues
     * @param dom The JSDOM document to analyze
     * @returns Array of detected button issues
     */
    private static detectButtonIssues(dom: Document): any[] {
        const issues: any[] = [];

        // Find all buttons
        const buttons = dom.querySelectorAll('button, [role="button"]');

        buttons.forEach((button, index) => {
            // Check if button has accessible name (text content, aria-label, or aria-labelledby)
            const hasText = button.textContent ? button.textContent.trim().length > 0 : false;
            const hasAriaLabel = button.hasAttribute('aria-label') &&
            button.getAttribute('aria-label') ? button.getAttribute('aria-label')!.trim().length > 0 : false;
            const hasAriaLabelledBy = button.hasAttribute('aria-labelledby');

            if (!hasText && !hasAriaLabel && !hasAriaLabelledBy) {
                issues.push({
                    code: 'WCAG2AA.1_3_1.button-name',
                    message: 'Button does not have an accessible name',
                    type: 'error',
                    context: button.outerHTML,
                    selector: this.generateUniqueSelector(button),
                    runner: 'custom-detector'
                });
            }
        });

        return issues;
    }

    /**
     * Generates a unique CSS selector for an element
     * @param element The element to generate a selector for
     * @returns CSS selector string
     */
    private static generateUniqueSelector(element: Element): string {
        // Simple implementation - in a real-world scenario, you'd want a more robust solution
        if (element.id) {
            return `#${element.id}`;
        }

        // Try to generate a selector based on element type and classes
        const tagName = element.tagName.toLowerCase();
        const classNames = element.className ? `.${element.className.split(' ').join('.')}` : '';

        if (classNames) {
            return `${tagName}${classNames}`;
        }

        // Fallback: generate a selector using the element's position in the DOM
        let selector = tagName;
        let parent = element.parentElement;
        let nth = 1;

        if (parent) {
            const siblings = Array.from(parent.children).filter(child =>
                child.tagName.toLowerCase() === tagName
            );

            if (siblings.length > 1) {
                nth = siblings.indexOf(element) + 1;
                selector = `${tagName}:nth-of-type(${nth})`;
            }
        }

        return selector;
    }
}
