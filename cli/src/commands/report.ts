import pa11y from "pa11y";
import {loadPromptButtonProblems, readHTMLFromDisk} from "../utils/fileUtils";
import {JSDOM} from "jsdom";
import {OpenAIService} from "../services/openAIService";

interface ResultIssueTest {
    code: string;
    context: string;
    message: string;
    selector: string;
    type: string;
    typeCode: number;
}

interface ResultsTest {
    documentTitle: string;
    pageUrl: string;
    issues: ResultIssueTest[];
}


export async function getReport(htmlPath: string, openAIService: OpenAIService) : Promise<ResultsTest> {

    let results: ResultsTest = (await pa11y(htmlPath))


    const htmlString = readHTMLFromDisk(htmlPath)


    const truncatedHtml= truncateHtml(htmlString)

    const dom = new JSDOM(truncatedHtml)

    const prompt = loadPromptButtonProblems(truncatedHtml);
    const response =  await openAIService.sendChatPrompt(prompt)

    return results


}

function truncateHtml(htmlString: string): string {


    const dom = new JSDOM(htmlString);
    const document = dom.window.document;

    // Remove visual elements (images, SVGs, pictures)
    document.querySelectorAll('img, svg, picture, video, audio, canvas, iframe').forEach(el => el.remove());

    // Remove style and script elements
    document.querySelectorAll('style, script, link[rel="stylesheet"]').forEach(el => el.remove());

    // Remove comments
    const removeComments = (node: Node) => {
        const childNodes = [...node.childNodes];
        childNodes.forEach(child => {
            if (child.nodeType === 8) { // Comment node
                child.remove();
            } else if (child.hasChildNodes()) {
                removeComments(child);
            }
        });
    };
    removeComments(document);

    // Remove non-essential page elements
    document.querySelectorAll('footer, header, nav, aside, [role="banner"], [role="navigation"]').forEach(el => el.remove());

    // Remove form elements
    document.querySelectorAll('form, input, button, textarea, select').forEach(el => el.remove());

    // Remove metadata
    document.querySelectorAll('meta, link[rel="icon"], link[rel="shortcut icon"]').forEach(el => el.remove());

    // Remove empty elements (with no text content)
    document.querySelectorAll('*').forEach(el => {
        if (!el.textContent?.trim() && !['html', 'head', 'body'].includes(el.tagName.toLowerCase())) {
            el.remove();
        }
    });

    // Remove all attributes except id and class (to preserve selectors)
    document.querySelectorAll('*').forEach(el => {
        const attrs = Array.from(el.attributes);
        attrs.forEach(attr => {
            if (attr.name !== 'id' && attr.name !== 'class') {
                el.removeAttribute(attr.name);
            }
        });
    });

    // Get the cleaned HTML content - only the body content
    return  document.body ? document.body.innerHTML : document.documentElement.outerHTML;

}