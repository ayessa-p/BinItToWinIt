# Admin Panel Setup Guide

## Initial Setup

### 1. Import Database

For a fresh installation, import the complete setup SQL file:

```sql
-- In phpMyAdmin, select 'Import' and choose:
-- File: database/complete_setup.sql
```

This file will create the database `binittowinit` if it doesn't exist, create all necessary tables, and seed it with initial data and an admin account.

### 2. Default Admin Account

**Default Admin Credentials:**
- **Student ID/Email:** `mtics.official`
- **Password:** `mticstuptaguig`

### 3. Ensure Directory Permissions

Make sure the following directories exist and are writable by the web server:
- `uploads/`
- `uploads/avatars/`
- `uploads/events/`
- `uploads/attendance/`
- `uploads/printing/`

### 4. Access Admin Panel

1. Log in with an admin account
2. Navigate to: `http://localhost/BinItToWinIt/admin/index.php`
3. Or use the "Admin Panel" link in the navigation (if visible)

## Admin Features

### Token Management
- Adjust user token balances
- Add or subtract tokens
- View all user balances
- Track token transactions

### Events Management
- Create and edit events
- Set event dates and locations
- Publish/unpublish events

### Projects Management
- Manage organization projects
- Set project status (active/completed/archived)
- Feature projects on homepage

### News Management
- Create and edit news articles
- Publish/unpublish news
- Manage news content

### Users Management
- View all users
- Activate/deactivate accounts
- Promote users to admin
- View user details

### Rewards Management
- Create and edit rewards
- Set token costs
- Manage stock quantities
- Activate/deactivate rewards

### API Keys Management
- Generate API keys for ESP32 devices
- View device information
- Activate/deactivate API keys
- Track API key usage

### Redemptions Management
- View all reward redemptions
- Approve pending redemptions
- Mark redemptions as fulfilled
- Cancel redemptions

## Security Notes

- Only users with `is_admin = 1` can access the admin panel
- All admin actions require CSRF token verification
- Admin functions are protected by `require_admin()` function
- Regular users cannot access admin routes
