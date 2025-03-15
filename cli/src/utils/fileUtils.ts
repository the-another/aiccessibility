import fs from "fs";

export function writeHTMLToDisk(inputHtml: string): string {
    const path = require('node:path');
    let filePath = path.resolve(process.cwd(), 'index.html');
    fs.writeFileSync(filePath, inputHtml);
    return filePath;
}
