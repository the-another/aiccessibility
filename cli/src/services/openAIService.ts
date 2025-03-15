import OpenAI from 'openai';
import fs from 'fs';
import { promisify } from 'util';

const readFile = promisify(fs.readFile);

export interface OpenAIConfig {
  apiKey: string;
  model: string;
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
   */
  async generateAltText(imagePath: string, isBase64 = false): Promise<string> {
    try {
      let base64Image: string;
      
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

      // Use OpenAI SDK to call vision-enabled model
      const completion = await this.openai.chat.completions.create({
        model: this.model,
        messages: [
          {
            role: "system",
            content: "You are an assistant that generates descriptive alt text for images, focusing on accessibility."
          },
          {
            role: "user",
            content: [
              { 
                type: "text", 
                text: "Generate a concise and descriptive alt text for this image, focusing on accessibility for screen readers. Include key visual elements, context, and purpose." 
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
      });

      // Extract the generated alt text
      if (completion.choices && completion.choices.length > 0 && completion.choices[0].message.content) {
        return completion.choices[0].message.content.trim();
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
} 