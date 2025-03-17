import OpenAI from 'openai';
import fs from 'fs';
import {promisify} from 'util';
import {JSDOM} from "jsdom";

const readFile = promisify(fs.readFile);

export interface OpenAIConfig {
    apiKey: string;
    /**
     * Use undefined to use the default model
     */
    model: string | undefined;
}

const DEFAULT_MODEL = 'gpt-4o';

export class OpenAIService {
    private openai: OpenAI;
    private model: string;

    constructor(options: OpenAIConfig) {
        this.openai = new OpenAI({
            apiKey: options.apiKey
        });
        this.model = options.model || DEFAULT_MODEL;
    }

    /**
     * Generate alt text for an image using OpenAI's vision model
     * @param imagePath Path to the image file or base64 encoded image data
     * @param isBase64 Whether the image is provided as base64 data
     * @param pageContext Optional context about the page or a base64 encoded HTML
     * @param isHtmlBase64 Whether the pageContext is base64 encoded HTML that needs to be analyzed
     */
    async generateAltText(
        imagePath: string, 
        isBase64 = false, 
        pageContext?: string, 
        isHtmlBase64 = false
    ): Promise<{ altText: string; relevancy: number }> {
        try {
            let base64Image: string;
            let contextInfo: string | undefined = pageContext;

            // Process HTML if provided as base64 to extract context
            if (pageContext && isHtmlBase64) {
                try {
                    // Decode the base64 HTML
                    const buffer = Buffer.from(pageContext.includes('base64,') 
                        ? pageContext.split('base64,')[1] 
                        : pageContext, 'base64');
                    const htmlContent = buffer.toString('utf-8');
                    
                    // Extract the context from the HTML using LLM
                    contextInfo = await this.summarizePage(htmlContent);
                } catch (error) {
                    console.warn('Failed to extract context from HTML, proceeding without context', error);
                    contextInfo = undefined;
                }
            }

            if (isBase64) {
                // Handle base64 image - ensure it's properly formatted
                base64Image = imagePath.includes('base64,')
                    ? imagePath.split('base64,')[1]
                    : imagePath;
            } else {
                // Handle image file by reading and converting to base64
                const imageBuffer = await readFile(imagePath);
                base64Image = imageBuffer.toString('base64');
            }

            // Prepare base64 image with proper formatting if needed
            const imageUrl = `data:image/jpeg;base64,${base64Image}`;

            // Check if we have valid context info
            const hasContext = contextInfo !== undefined && contextInfo.trim().length > 0;

            // Create appropriate prompt based on whether context is available
            const promptContent = hasContext
                ? `Generate a concise and descriptive alt text for this image, focusing on accessibility for screen readers. Include key visual elements, context, and purpose. ADDITIONALLY, on a scale of 0 to 1, assess how relevant this image is to the following page context: "${contextInfo}". A score of 0 means completely irrelevant, while 1 means highly relevant. Format your response as JSON: {"altText": "your description here", "relevancy": 0.X, "reasoning": "brief explanation of relevancy score"}`
                : `Generate a concise and descriptive alt text for this image, focusing on accessibility for screen readers. Include key visual elements, context, and purpose. Since no page context was provided, set relevancy score to 0.5 as default. Format your response as JSON: {"altText": "your description here", "relevancy": 0.5, "reasoning": "No page context provided"}`;

            // Use OpenAI SDK to call vision-enabled model
            const completion = await this.openai.chat.completions.create({
                model: this.model,
                messages: [
                    {
                        role: "system",
                        content: "You are an assistant that generates descriptive alt text for images, focusing on accessibility. You also assess image relevancy to page content."
                    },
                    {
                        role: "user",
                        content: [
                            {
                                type: "text",
                                text: promptContent
                            },
                            {
                                type: "image_url",
                                image_url: {
                                    url: imageUrl
                                }
                            }
                        ]
                    }
                ],
                response_format: { type: "json_object" }
            });

            // Extract the generated alt text and relevancy
            if (completion.choices && completion.choices.length > 0 && completion.choices[0].message.content) {
                const response = JSON.parse(completion.choices[0].message.content.trim());
                
                if (!response.altText) {
                    throw new Error('No alt text generated from API response');
                }
                
                return {
                    altText: response.altText,
                    relevancy: response.relevancy || 0.5 // Default to 0.5 if not provided
                };
            }

            throw new Error('No alt text generated from API response');
        } catch (error) {
            if (error instanceof Error) {
                throw new Error(`OpenAI API error: ${error.message}`);
            }
            throw error;
        }
    }

    /**
     * Get available OpenAI models for vision tasks
     */
    async getAvailableModels(): Promise<string[]> {
        try {
            const response = await this.openai.models.list();

            if (response.data && Array.isArray(response.data)) {
                // Filter for vision-capable models
                return response.data
                    .map(model => model.id)
                    .filter(id => id.includes('gpt-4o') || id.includes('vision')); // Filter for likely vision models
            }

            return [DEFAULT_MODEL]; // Default fallback
        } catch (error) {
            console.error('Failed to fetch available models:', error);
            return [DEFAULT_MODEL]; // Default fallback
        }
    }

    /**
     * Send a chat prompt to the OpenAI API and return the response
     * @param prompt The chat prompt to send
     * @param model The OpenAI model to use
     * @param maxRetries Maximum number of retries for API calls
     */
    async sendChatPrompt(prompt: string, model = 'gpt-3.5-turbo-0125', maxRetries = 3): Promise<any> {
        let retries = 0;

        while (retries <= maxRetries) {
            try {
                const completion = await this.openai.chat.completions.create({
                    model: model,
                    messages: [
                        {
                            role: "user",
                            content: prompt
                        }
                    ],
                });

                // Extract the generated text
                if (completion.choices && completion.choices.length > 0 && completion.choices[0].message.content) {
                    const llmResponse = completion.choices[0].message.content.trim();

                    // Check if response contains the expected output format
                    if (!llmResponse.includes("<output>") || !llmResponse.includes("</output>")) {
                        if (retries < maxRetries) {
                            retries++;
                            continue;
                        } else {
                            throw new Error('Response missing proper <output> format after retries');
                        }
                    }

                    let jsonResponse = llmResponse.split("<output>")[1];
                    jsonResponse = jsonResponse.replace("</output>", "");
                    try {
                        return JSON.parse(jsonResponse);
                    } catch (e) {
                        if (retries < maxRetries) {
                            retries++;
                        } else {
                            throw new Error(`Failed to parse JSON response after ${maxRetries + 1} attempts`);
                        }
                    }
                } else {
                    if (retries < maxRetries) {
                        retries++;
                    } else {
                        throw new Error('No response generated from API after retries');
                    }
                }
            } catch (error) {
                if (retries < maxRetries) {
                    retries++;
                } else {
                    throw error;
                }
            }
        }

        throw new Error('Failed to get valid response after maximum retries');
    }

    async summarizePage(htmlInput: string): Promise<string> {
        const dom = new JSDOM(htmlInput);
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
        const bodyContent = document.body ? document.body.innerHTML : document.documentElement.outerHTML;

        // Send the cleaned HTML to the LLM (only the body content to further reduce size)
        const completion = await this.openai.chat.completions.create({
            model: "gpt-3.5-turbo",
            messages: [
                {
                    role: "user",
                    content: "Provide a summary of the page content in two sentences or less, focusing on the main topics and key points. Skip a preamble when outputing the summary. Content: " + bodyContent
                }
            ],
        });

        // Extract the generated text
        if (completion.choices && completion.choices.length > 0 && completion.choices[0].message.content) {
            return completion.choices[0].message.content.trim()
        } else {
            throw new Error('No response generated from API');
        }

    }
}
