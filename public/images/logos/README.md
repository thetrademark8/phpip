# Company Logos Directory

This directory is for storing company logos for each IP firm instance.

## Usage

1. Place your company logo file in this directory
2. Update your `.env` file with the path to your logo:
   ```
   COMPANY_LOGO=images/logos/your-company-logo.png
   ```

## Supported Formats

- PNG (recommended for logos with transparency)
- JPG/JPEG
- SVG (recommended for scalability)

## Naming Convention

Use descriptive filenames like:
- `company-logo.png`
- `firm-name-logo.svg`
- `logo-light.png` (for light backgrounds)
- `logo-dark.png` (for dark backgrounds)

## Size Recommendations

- Maximum width: 200px for navbar display
- Maximum height: 60px for navbar display
- For login page: up to 300px width

The application will automatically scale the logo while maintaining aspect ratio.