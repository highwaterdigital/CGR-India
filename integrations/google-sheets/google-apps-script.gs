/**
 * CGR Website – Google Apps Script (Two-Way Sync)
 * 
 * FEATURES:
 * 1. doPost: Receives data from WordPress -> Saves to Sheets.
 * 2. doGet: Serves data from Sheets -> WordPress (for syncing back).
 * 3. Menu: "CGR Website" menu in Sheets to initialize sheets and Sync to Website.
 * 
 * DEPLOYMENT INSTRUCTIONS:
 * 1. Click "Deploy" > "New deployment".
 * 2. Select type: "Web app".
 * 3. Execute as: "Me".
 * 4. Who has access: "Anyone" (CRITICAL: Must be 'Anyone' to avoid HTML errors).
 * 5. Click "Deploy" and copy the URL.
 */

// CONFIGURATION
var WEBSITE_API_URL = 'https://cgrindia.org/wp-json/cgr/v1/sync'; // Update if domain changes
var SYNC_SECRET = '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'; // Copy from WP Admin > Settings > CGR Google Sheets

function onOpen() {
  SpreadsheetApp.getUi()
      .createMenu('CGR Website')
      .addItem('Initialize All Sheets', 'setupAllSheets')
      .addItem('Sync Data to Website', 'syncToWebsite')
      .addToUi();
}

function syncToWebsite() {
  var ui = SpreadsheetApp.getUi();
  
  try {
    var payload = {
      'secret': SYNC_SECRET
    };
    
    var options = {
      'method': 'post',
      'contentType': 'application/json',
      'payload': JSON.stringify(payload),
      'muteHttpExceptions': true // allow us to read the response body on errors
    };
    
    var response = UrlFetchApp.fetch(WEBSITE_API_URL, options);
    var status = response.getResponseCode();
    var body = response.getContentText();
    
    // Try to parse JSON, but handle plain text too
    var parsed = null;
    try { parsed = JSON.parse(body); } catch (err) {}
    
    if (status >= 200 && status < 300) {
      var result = parsed || {};
      var msg = 'Sync Complete!\n\n';
      if (result.scientists) msg += result.scientists + '\n';
      if (result.earth_leaders) msg += result.earth_leaders + '\n';
      if (result.cgr_team) msg += result.cgr_team + '\n';
      ui.alert(msg);
    } else {
      var errorMsg = 'Sync Failed (' + status + '): ' + body;
      ui.alert(errorMsg);
      Logger.log(errorMsg);
    }
    
  } catch (e) {
    ui.alert('Sync Failed: ' + e.toString());
    Logger.log('Sync Failed Exception: ' + e.toString());
  }
}

function setupAllSheets() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheets = ['Scientists', 'EarthLeaders', 'CGRTeam'];
  
  sheets.forEach(function(name) {
    var sheet = ss.getSheetByName(name);
    if (!sheet) {
      sheet = ss.insertSheet(name);
      addHeaders(sheet, name);
    } else if (sheet.getLastRow() === 0) {
      addHeaders(sheet, name);
    }
  });
  
  SpreadsheetApp.getUi().alert('Sheets initialized: ' + sheets.join(', '));
}

function doPost(e) {
  var lock = LockService.getScriptLock();
  lock.tryLock(10000);
  
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var data = JSON.parse(e.postData.contents);
    
    // Determine Sheet Name
    var sheetName = data.sheet_name || data.registration_type || 'Registrations';
    var sheet = ss.getSheetByName(sheetName);
    
    // Create Sheet if missing
    if (!sheet) {
      sheet = ss.insertSheet(sheetName);
      addHeaders(sheet, sheetName);
    }
    
    // Add headers if empty
    if (sheet.getLastRow() === 0) {
      addHeaders(sheet, sheetName);
    }
    
    // Check for duplicates (Update existing) or Append
    var emailIndex = getHeaderIndex(sheet, 'Email');
    var rowIndex = -1;
    
    // Try to find existing row by Email
    if (emailIndex > -1 && data.email) {
      var range = sheet.getDataRange();
      var values = range.getValues();
      for (var i = 1; i < values.length; i++) {
        if (values[i][emailIndex] == data.email) {
          rowIndex = i + 1; // 1-based row index
          break;
        }
      }
    }
    
    var rowData = buildRow(data, sheetName);
    
    if (rowIndex > 0) {
       // Update existing row (optional, currently appending to keep history)
       // sheet.getRange(rowIndex, 1, 1, rowData.length).setValues([rowData]);
       sheet.appendRow(rowData); 
    } else {
      sheet.appendRow(rowData);
    }
    
    return ContentService.createTextOutput(JSON.stringify({
      'success': true,
      'message': 'Data synced to Sheet: ' + sheetName,
      'row': sheet.getLastRow()
    })).setMimeType(ContentService.MimeType.JSON);
    
  } catch (error) {
    return ContentService.createTextOutput(JSON.stringify({
      'success': false,
      'message': 'Error: ' + error.toString()
    })).setMimeType(ContentService.MimeType.JSON);
    
  } finally {
    lock.releaseLock();
  }
}

function doGet(e) {
  try {
    var sheetName = e.parameter.sheet || 'Scientists'; 
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(sheetName);
    
    if (!sheet) {
      // If sheet missing, create it on the fly to avoid errors
      sheet = ss.insertSheet(sheetName);
      addHeaders(sheet, sheetName);
      
      return ContentService.createTextOutput(JSON.stringify({
        'success': true,
        'data': [] // Return empty data for new sheet
      })).setMimeType(ContentService.MimeType.JSON);
    }
    
    var rows = sheet.getDataRange().getValues();
    if (rows.length < 2) {
       return ContentService.createTextOutput(JSON.stringify({
        'success': true,
        'data': []
      })).setMimeType(ContentService.MimeType.JSON);
    }

    var headers = rows[0];
    var data = [];
    
    for (var i = 1; i < rows.length; i++) {
      var row = rows[i];
      var record = {};
      for (var j = 0; j < headers.length; j++) {
        record[headers[j]] = row[j];
      }
      data.push(record);
    }
    
    return ContentService.createTextOutput(JSON.stringify({
      'success': true,
      'data': data
    })).setMimeType(ContentService.MimeType.JSON);
    
  } catch (error) {
    return ContentService.createTextOutput(JSON.stringify({
      'success': false,
      'error': error.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

function addHeaders(sheet, sheetName) {
  var base = ['Timestamp','Full Name','Email','Phone','City','Status','Photo URL'];
  var extras = [];
  
  if (sheetName === 'Scientists') {
    extras = ['Specialization', 'Institution', 'Bio'];
  } else if (sheetName === 'EarthLeaders') {
    extras = ['Training Year', 'District', 'Organization'];
  } else if (sheetName === 'CGRTeam') {
    extras = ['Designation', 'Role Type', 'Bio'];
  } else {
    extras = ['Message','Interest','Source'];
  }
  
  var headers = base.concat(extras);
  sheet.appendRow(headers);
  sheet.getRange(1, 1, 1, headers.length).setBackground('#1F4B2C').setFontColor('white').setFontWeight('bold');
}

function buildRow(data, sheetName) {
  var ts = data.timestamp || new Date();
  var name = data.name || data.full_name || '';
  var email = data.email || '';
  var phone = data.phone || data.mobile || '';
  var city = data.city || data.location || '';
  var status = data.status || 'Active';
  var photo = data.photo_url || '';
  
  var base = [ts, name, email, phone, city, status, photo];
  var extras = [];
  
  if (sheetName === 'Scientists') {
    extras = [data.specialization || '', data.institution || '', data.bio || ''];
  } else if (sheetName === 'EarthLeaders') {
    extras = [data.training_year || '', data.district || '', data.organization || ''];
  } else if (sheetName === 'CGRTeam') {
    extras = [data.designation || '', data.role_type || '', data.bio || ''];
  } else {
    extras = [data.message || '', data.interest || '', data.source || 'Website'];
  }
  
  return base.concat(extras);
}

function getHeaderIndex(sheet, headerName) {
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  return headers.indexOf(headerName);
}
