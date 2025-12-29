# CGR Web Project - AI Coding Instructions

## Project Context
This is a custom WordPress Child Theme (`cgr-child`) for the Council for Green Revolution (CGR). It features a modular homepage architecture and custom integrations for Google Sheets and WhatsApp.

## Architecture & Patterns

### 1. Modular Homepage Structure
- **Entry Point**: `home-page.php` acts as the controller/orchestrator.
- **Components**: Content is broken down into independent files within the `sections/` directory (e.g., `section-hero.php`, `section-impact.php`).
- **Loading**: Sections are loaded using `locate_template()` to ensure child theme overrides work correctly.
- **Debugging**: `home-page.php` contains explicit `error_log` calls to trace section loading.

### 2. Integrations Layer
- **Location**: All external service logic resides in `integrations/`.
- **Loader**: `integrations/integrations-loader.php` is the single entry point for loading all integration files.
- **Google Sheets**: 
  - Logic in `integrations/google-sheets/`.
  - Key classes: `class-cgr-sheet-sync.php` (Sync logic), `registration-handler.php` (Form handling).
- **WhatsApp**: 
  - Logic in `integrations/whatsapp/`.
  - Key class: `class-cgr-whatsapp-api.php`.

### 3. Styling & Theming
- **CSS Variables**: Defined in `:root` within `style.css` (e.g., `--cgr-primary`, `--cgr-accent`). Always use these variables for colors to ensure consistency.
- **Structure**: Global styles are in `style.css`. Section-specific styles should ideally be kept near their markup or clearly commented in the main stylesheet.

## Development Workflow

### Debugging
- **Method**: Use `error_log('CGR DEBUG: message');` for server-side debugging.
- **Logs**: Check the standard WordPress `debug.log` file.
- **Example**: See `home-page.php` for the established logging pattern.

### Security & Best Practices
- **Direct Access**: All PHP files must start with the `ABSPATH` check:
  ```php
  if ( ! defined( 'ABSPATH' ) ) {
      exit;
  }
  ```
- **Constants**: Use `CGR_CHILD_DIR` (defined in `functions.php`) for file path references instead of `get_stylesheet_directory()`.
- **Naming**: Prefix all global functions and classes with `cgr_` or `CGR_` to avoid collisions.

## Key Files
- `functions.php`: Theme setup, constant definitions, and script enqueuing.
- `home-page.php`: Main template file for the homepage.
- `integrations/integrations-loader.php`: Central registry for custom modules.
- `style.css`: Main stylesheet and theme metadata.
