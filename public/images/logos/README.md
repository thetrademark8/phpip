# Company Logos Directory

This directory is for storing company logos for each IP firm instance.

## Usage

1. Place your company logo file in this directory
2. Update your `.env` file with the path to your logo:
   ```
   COMPANY_LOGO=images/logos/your-company-logo.png
   ```

## Display Behavior

When a logo is configured:
- **Navbar**: The logo replaces the text-based app name, providing a cleaner branded appearance
- **Login page**: The logo is displayed above the login form
- **Page title**: The app name is still used in the browser tab title

When no logo is configured:
- **Navbar**: The app name (from `APP_NAME` in `.env`) is displayed as text
- **Login page**: No logo is shown

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

## Accessibility

The logo's `alt` attribute automatically uses the company name from your configuration, ensuring screen readers can properly identify your brand.