import fs from 'fs';

export function writeHTMLToDisk(inputHtml: string): string {
    const path = require('node:path');
    let filePath = path.resolve(process.cwd(), 'index.html');
    fs.writeFileSync(filePath, inputHtml);
    return filePath;
}

export function loadPrompt(htmlWithError: string, identifiedProblem: string): string {
    let prompt = fs.readFileSync('src/prompt.txt', 'utf8');
    prompt = prompt.replace('{website-code}', htmlWithError);
    prompt = prompt.replace('{identified-problems}', identifiedProblem);

    return prompt;
}
