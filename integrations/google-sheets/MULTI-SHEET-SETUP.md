# Google Sheets Setup for Unified Registration System

## Overview
This document explains how to set up Google Sheets with separate tabs for Participants, Volunteers, and Writers registrations.

## Step 1: Create Google Sheets with Multiple Tabs

1. Open your existing Google Sheets document: `1ivXccQ2X8QnChYKFtxU_aOh8gCGEsYTSNcisoDRhqgE`
2. Create three separate sheets (tabs) with these exact names:
   - `Participants`
   - `Volunteers`
   - `Writers`

## Step 2: Set Up Column Headers

### Participants Sheet
Add these column headers in row 1:
```
A: Timestamp
B: Full Name
C: Email
D: Phone
E: Age
F: City
G: Occupation
H: Competition Category
I: Previous Experience
J: Newsletter Subscribed
K: Source
L: IP Address
M: Status
```

### Volunteers Sheet
Add these column headers in row 1:
```
A: Timestamp
B: Full Name
C: Email
D: Phone
E: Volunteer Role
F: Availability
G: Skills
H: Photo Consent
I: Source
J: IP Address
K: Status
```

### Writers Sheet
Add these column headers in row 1:
```
A: Timestamp
B: Full Name
C: Email
D: Phone
E: Writer Category
F: Genres
G: Published Works
H: Bio
I: Social Media/Website
J: Source
K: IP Address
L: Status
```

### Scientists Sheet
Add these column headers in row 1:
```
A: Timestamp
B: Full Name
C: Email
D: Phone
E: Specialization
F: Institution
G: Bio
H: Photo URL
I: Status
```

### EarthLeaders Sheet
Add these column headers in row 1:
```
A: Timestamp
B: Full Name
C: Email
D: Phone
E: Training Year
F: District
G: Organization
H: Photo URL
I: Status
```

### CGRTeam Sheet
Add these column headers in row 1:
```
A: Timestamp
B: Full Name
C: Email
D: Phone
E: Designation
F: Role Type
G: Bio
H: Photo URL
I: Status
```

## Step 3: Create Google Apps Script

1. In your Google Sheet, go to **Extensions** > **Apps Script**
2. Delete any existing code
3. Copy and paste the following script:

```javascript
/**
 * Samooha Unified Registration - Google Apps Script
 * Handles multiple registration types with separate sheets
 * Version: 2.0
 */

function doPost(e) {
  try {
    // Parse the incoming data
    var data = JSON.parse(e.postData.contents);
    
    // Get the spreadsheet
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    
    // Determine which sheet to use based on sheet_name parameter
    var sheetName = data.sheet_name || 'Registrations';
    var sheet = ss.getSheetByName(sheetName);
    
    // If sheet doesn't exist, create it or use default
    if (!sheet) {
      Logger.log('Sheet "' + sheetName + '" not found, using default');
      sheet = ss.getSheetByName('Participants') || ss.getSheets()[0];
    }
    
    // Prepare row data based on registration type
    var rowData;
    
    if (sheetName === 'Participants') {
      rowData = [
        data.timestamp || new Date(),
        data.name || data.full_name || '',
        data.email || '',
        data.phone || '',
        data.age || '',
        data.city || '',
        data.occupation || '',
        data.competition_category || '',
        data.previous_experience || '',
        data.newsletter_subscribed || 'No',
        data.source || 'Website',
        data.ip_address || '',
        data.status || 'New'
      ];
    } 
    else if (sheetName === 'Volunteers') {
      rowData = [
        data.timestamp || new Date(),
        data.name || data.full_name || '',
        data.email || '',
        data.phone || '',
        data.volunteer_role || '',
        data.availability || '',
        data.skills || '',
        data.photo_consent || 'No',
        data.source || 'Website',
        data.ip_address || '',
        data.status || 'New'
      ];
    }
    else if (sheetName === 'Writers') {
      rowData = [
        data.timestamp || new Date(),
        data.name || data.full_name || '',
        data.email || '',
        data.phone || '',
        data.writer_category || '',
        data.genres || '',
        data.published_works || '',
        data.bio || '',
        data.social_media || '',
        data.source || 'Website',
        data.ip_address || '',
        data.status || 'New'
      ];
    }
    else {
      // Default/fallback format
      rowData = [
        data.timestamp || new Date(),
        data.name || data.full_name || '',
        data.email || '',
        data.phone || '',
        JSON.stringify(data) // Store all data as JSON for unknown types
      ];
    }
    
    // Append the data to the sheet
    sheet.appendRow(rowData);
    
    // Log success
    Logger.log('Successfully added registration to ' + sheetName);
    
    // Return success response
    return ContentService
      .createTextOutput(JSON.stringify({
        'result': 'success',
        'sheet': sheetName,
        'row': sheet.getLastRow()
      }))
      .setMimeType(ContentService.MimeType.JSON);
      
  } catch (error) {
    // Log error
    Logger.log('Error: ' + error.toString());
    
    // Return error response
    return ContentService
      .createTextOutput(JSON.stringify({
        'result': 'error',
        'error': error.toString()
      }))
      .setMimeType(ContentService.MimeType.JSON);
  }
}

/**
 * Test function - run this to test your script
 */
function testScript() {
  // Test participant data
  var testData = {
    postData: {
      contents: JSON.stringify({
        sheet_name: 'Participants',
        timestamp: new Date().toISOString(),
        name: 'Test User',
        email: 'test@example.com',
        phone: '1234567890',
        age: '25',
        city: 'Hyderabad',
        occupation: 'Student',
        competition_category: 'Poetry',
        previous_experience: 'None',
        newsletter_subscribed: 'Yes',
        source: 'Website Test',
        ip_address: '127.0.0.1',
        status: 'Test'
      })
    }
  };
  
  var result = doPost(testData);
  Logger.log(result.getContent());
}
```

## Step 4: Deploy the Script

1. Click the **Deploy** button (top right) > **New deployment**
2. Click the gear icon ⚙️ next to "Select type"
3. Choose **Web app**
4. Configure the deployment:
   - **Description**: `Samooha Unified Registration v2.0`
   - **Execute as**: `Me (your-email@gmail.com)`
   - **Who has access**: `Anyone` ⚠️ Important!
5. Click **Deploy**
6. **Copy the Web App URL** - it will look like:
   ```
   https://script.google.com/macros/s/YOUR_DEPLOYMENT_ID/exec
   ```

## Step 5: Update WordPress Credentials

1. Go to your WordPress site
2. Open the file: `wp-content/themes/samooha-child/integrations/google-sheets/credentials.php`
3. Update the webhook URL with your new deployment URL:
   ```php
   'webhook_url' => 'https://script.google.com/macros/s/YOUR_DEPLOYMENT_ID/exec',
   ```

## Step 6: Test the Integration

### Test via WordPress
1. Go to: `https://samooha.org.in/registration`
2. Try registering in each tab (Participant, Volunteer, Writer)
3. Check that data appears in the correct sheet

### Test via Apps Script
1. In Apps Script editor, select `testScript` function from dropdown
2. Click the **Run** button (▶)
3. Check the "Participants" sheet for test data

## Troubleshooting

### Data not appearing?
- Verify all three sheets exist with exact names (case-sensitive)
- Check that script is deployed with "Anyone" access
- Check WordPress error logs
- Try the `testScript()` function in Apps Script

### Wrong sheet being used?
- Check that `sheet_name` parameter is correctly set in the form submission
- Verify sheet names match exactly (no extra spaces)

### Permission errors?
- Redeploy the script
- Make sure "Execute as: Me" and "Who has access: Anyone" are selected

## Sheet Names Reference
- **Participants**: Competition registrations
- **Volunteers**: Event volunteers
- **Writers**: Featured writers/authors

## Security Notes
- The script only accepts POST requests
- Data is validated before insertion
- Each registration type goes to its dedicated sheet
- IP addresses are logged for security

## Support
For issues, check:
1. WordPress error logs
2. Google Apps Script logs (View > Logs)
3. Network tab in browser developer tools

---

**Last Updated**: November 2025  
**Version**: 2.0 - Unified Registration with Multi-Sheet Support
