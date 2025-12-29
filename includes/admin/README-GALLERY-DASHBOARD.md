# Gallery Dashboard

## Overview
Custom WordPress admin interface for managing CGR Gallery items with an intuitive media uploader and visual preview system.

## Features

### 🎨 Backend Dashboard (Admin Area)
- **Location**: Galleries → Dashboard in WordPress admin
- **Visual Grid**: See all gallery items with thumbnail previews
- **Quick Edit**: Inline media management without full page edit
- **Media Uploader**: WordPress native media library integration
- **Drag & Drop**: Easy media selection and removal
- **Live Preview**: See thumbnails of selected media instantly

### ⚙️ Settings Panel
Configure default display options:
- **Animation**: Slide up (default), Fade & scale, Glow pulse
- **Layout**: Compact grid (default), Stacked rows
- **Accent Color**: Customizable brand color (#1f4f2e default)

### 📱 Frontend Display
- **URL**: `/gallery/` (virtual page)
- **REST API**: Automatically fetches from `/wp-json/cgr/v1/gallery`
- **Interactive**: Modal slideshow with autoplay
- **Responsive**: Mobile-optimized grid layout

## Usage

### Adding Gallery Items

#### Method 1: Gallery Dashboard (Recommended)
1. Go to **Galleries → Dashboard** in WordPress admin
2. Click **"Add New Gallery"** button
3. Fill in title and description
4. Click **"Quick Edit Media"** on any gallery card
5. Use **"Add Media"** button to select images
6. Click **"Save Changes"**

#### Method 2: Traditional Edit Screen
1. Go to **Galleries → Add New Gallery**
2. Enter title and use content editor for description
3. In **Gallery Media** meta box, paste comma-separated media IDs or URLs:
   ```
   123, 456, https://example.com/image.jpg
   ```
4. Click **Publish**

### Viewing Gallery
- Frontend page: `yoursite.com/gallery/`
- Uses animation and layout settings from dashboard
- Displays all published gallery items
- Click any image to open modal slideshow

## File Structure
```
includes/
  admin/
    gallery-dashboard.php      # Main dashboard logic
    gallery-dashboard.css      # Dashboard styles
    gallery-dashboard.js       # Media uploader & AJAX
pages/
  page-gallery.php            # Frontend gallery template
functions.php                  # REST API & CPT registration
```

## REST API Endpoint
- **URL**: `/wp-json/cgr/v1/gallery`
- **Method**: GET
- **Response**: JSON array of gallery items with metadata
- **Authentication**: Public (no auth required)

## Meta Fields
- `_cgr_gallery_assets`: Comma-separated list of attachment IDs or URLs

## Customization

### Change Default Animation
Edit `gallery-dashboard.php` line 88:
```php
<option value="slide" selected>Slide up</option>
```

### Change Accent Color
Edit `gallery-dashboard.php` line 103:
```php
<input type="color" ... value="#1f4f2e">
```

### Modify Grid Columns
Edit `gallery-dashboard.css` line 96:
```css
grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
```

## Browser Support
- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Android)

## Notes
- Gallery items must be published to appear on frontend
- Unpublished galleries visible in dashboard but not on `/gallery/` page
- Media can be attachment IDs (numeric) or external URLs
- External URLs won't have automatic thumbnail generation
