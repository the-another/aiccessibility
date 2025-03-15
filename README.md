# AIccessibility

AI accessibility plugin for WordPress

## CLI Tool

The AIccessibility CLI (`aicu`) is a command-line tool designed to help improve web accessibility in WordPress sites. The CLI provides various features for automatically enhancing accessibility, with a focus on image alt text, standardized buttons, and skip-to-content implementations.

### Current Features

- **Alt Text Generation**: Automatically generate descriptive alt text for images using OpenAI models.

### Installation

```bash
# Navigate to the CLI directory
cd cli

# Install dependencies
npm install

# Build the CLI
npm run build

# Link the CLI globally (optional)
npm link
```

### Usage

#### Generate Alt Text for Images

```bash
# Using a local image file
aicu alt-text --file ./path/to/image.jpg --api-key your-openai-api-key

# Using base64 encoded image data
aicu alt-text --base64 data:image/jpeg;base64,/9j/4AAQ... --api-key your-openai-api-key

# Using a specific openai model
aicu alt-text --file ./path/to/image.jpg --api-key your-api-key --model openai-vl

# List available openai vision models
aicu alt-text --list-models --api-key your-api-key
```

#### Environment Variables

You can set the openai API key as an environment variable instead of passing it as a parameter:

```bash
export OPENAI_API_KEY=your-openai-api-key
aicu alt-text --file ./path/to/image.jpg
```

### Upcoming Features

- Button standardization for consistent UI and accessibility
- Skip-to-content navigation implementation
- Automated accessibility audits
- Additional AI models for various accessibility improvements

## Development

### Project Structure

```
cli/
├── src/
│   ├── commands/          # CLI commands
│   ├── services/          # API and service integrations
│   ├── utils/             # Utility functions
│   └── index.ts           # Main entry point
├── package.json
└── tsconfig.json
```

### Adding New Commands

The CLI is designed to be modular, making it easy to add new commands for additional accessibility features.

# Setting up WordPress locally
- [Install WP-ENV package globally using NPM.](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)
- From the project repository run `wp-env start` and it will use the local config file `.wp.env.json1 to establish a local WordPress site in a Docker container. 
- Access site at `http://localhost:8888/wp-admin/` with the login `admin` and `password` as username and password.