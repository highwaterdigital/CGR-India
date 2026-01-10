# Templates Directory

This directory contains reusable PHP templates for various components of the CGR Child Theme.

## Templates

### earth-leaders-directory.php
The main template for displaying the Earth Leaders directory table with search, filter, and sort functionality.

**Used by:** `cgr_earth_leaders_directory_shortcode()` in `inc/shortcodes.php`

**Assets:**
- CSS: `assets/css/earth-leaders-directory.css`
- JavaScript: `assets/js/earth-leaders-directory.js`

**Features:**
- Statistics cards showing total leaders, districts, latest year, and visible count
- Real-time search functionality
- District filter dropdown
- Multi-criteria sorting (name, district, year)
- Responsive design with mobile card view
- Debounced search for performance
- Loading states and empty state handling
- Keyboard shortcuts (Ctrl/Cmd+K to focus search, Esc to clear)

**Meta Keys Used:**
- `district` - Leader's district
- `training_year` - Year of training
- `organization` - Organization name
- `email` - Contact email

## Architecture

Templates separate markup from logic, following WordPress best practices:

1. **PHP Template** - Contains HTML structure and WordPress queries
2. **CSS File** - Dedicated stylesheet with modern design system
3. **JS File** - Modular JavaScript for interactivity
4. **Shortcode** - Thin wrapper that enqueues assets and includes template

This separation improves:
- **Maintainability** - Easy to update styling or functionality independently
- **Performance** - Proper caching and minification possible
- **Debugging** - Clear separation of concerns
- **Reusability** - Templates can be included from multiple locations
