import fs from 'fs';
import path from 'path';

/**
 * Validates if a given path points to a valid image file
 * @param filePath Path to the image file
 * @returns True if valid, false otherwise
 */
export function isValidImageFile(filePath: string): boolean {
  try {
    // Check if file exists
    if (!fs.existsSync(filePath)) {
      return false;
    }

    // Check if it's a file (not a directory)
    const stats = fs.statSync(filePath);
    if (!stats.isFile()) {
      return false;
    }

    // Check file extension
    const validExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp'];
    const ext = path.extname(filePath).toLowerCase();
    
    return validExtensions.includes(ext);
  } catch (error) {
    return false;
  }
}

/**
 * Validates if a string is a valid base64 encoded image
 * @param base64String The base64 string to validate
 * @returns True if valid, false otherwise
 */
export function isValidBase64Image(base64String: string): boolean {
  // Check for base64 image pattern (data:image/xxx;base64,)
  if (!base64String.match(/^data:image\/(jpeg|jpg|png|gif|webp|bmp);base64,/)) {
    // If not a data URL, check if it's a plain base64 string
    try {
      const buffer = Buffer.from(base64String, 'base64');
      // Very basic validation - at least check if it decodes to something
      return buffer.length > 0;
    } catch (error) {
      return false;
    }
  }
  return true;
} 