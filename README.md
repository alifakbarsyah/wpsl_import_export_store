Here’s a more concise and streamlined version of your README:

---

# WPSL Import Export Plugins

## Description
Easily manage your WP Store Locator (WPSL) store locations with this plugin, featuring CSV import/export functionality, including support for store categories.

## Installation
1. Upload the plugin to `/wp-content/plugins/` and activate it via WordPress.
2. Access import/export under **WPSL Stores → Import/Export**.

## Features
- Import/export store locations and categories via CSV.
- User-friendly interface with data consistency support.

## CSV Structure
Your CSV should have these columns **in order**:  
`Store Name, Address, Address2, City, State, Zip, Country, Latitude, Longitude, Phone, Fax, Email, Website`

## Important Notes
- **Remove the header row** before importing.
- Keep the column structure as specified.
- **Unable to export store categories yet**.
- Avoid commas in data to prevent errors (since export file use comma as data separator).
- Use decimal format for Latitude/Longitude (e.g., `51.794116`).
- Validate data and formatting before import.

## File Format Example
```csv
"Store Name","Street Address","","City","State","12345","Country","51.123456","5.123456","123-456-789","","email@example.com","www.example.com"
```

## Troubleshooting
- Verify CSV structure and ensure no commas in data.
- Check required fields are complete.
- Increase PHP memory limit for large imports if needed.

## Support
1. Confirm CSV format and plugin requirements.
2. Update WordPress and WPSL to the latest versions.
3. Contact your administrator or developer for assistance.

## Requirements
- WordPress 4.7+  
- WP Store Locator plugin  
- PHP 7.0+ (recommended)

--- 

This revision retains all key details while being more concise and easier to scan. Let me know if you'd like further tweaks!
