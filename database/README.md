# MTICS Database Files

This folder contains essential database setup and migration files for the MTICS Bin It to Win It system.

## 📁 Essential Files

### `schema.sql`
- **Purpose**: Main database structure and initial data
- **When to use**: Fresh database installation
- **Contains**: All table definitions, indexes, default admin account, sample data

### `complete_setup.sql`
- **Purpose**: Complete database setup including all features
- **When to use**: New deployment or full reset
- **Contains**: Database creation, all tables, initial data

### `event_attendance_tables.sql`
- **Purpose**: Event attendance system tables
- **When to use**: Adding attendance tracking to existing system
- **Contains**: event_attendance table, event_statistics view, attendance columns

### `migrate_attendance.php`
- **Purpose**: PHP migration script for attendance system
- **When to use**: Running migration via web interface
- **Contains**: Safe table creation with error handling

## 🚀 Quick Setup

### For New Installation:
```bash
mysql -u root -p < complete_setup.sql
```

### For Existing System (Adding Attendance):
```bash
mysql -u root -p < event_attendance_tables.sql
# OR run via web:
# http://your-domain.com/BinItToWinIt/database/migrate_attendance.php
```

## 📋 Database Structure

### Core Tables:
- `users` - Student accounts and token balances
- `transactions` - Token transaction history
- `recycling_activities` - ESP32 recycling data
- `rewards` - Available rewards for redemption
- `redemptions` - Reward redemption records
- `events` - Event management
- `event_attendance` - Event attendance tracking
- `api_keys` - ESP32 device authentication

### Views:
- `event_statistics` - Attendance analytics

## ⚠️ Important Notes

- Always backup database before running migrations
- Use `complete_setup.sql` for fresh installations
- Run `migrate_attendance.php` after adding attendance system
- Default admin: `mtics.official` / `mticstuptaguig`

## 🔧 Maintenance

- Database files are version-controlled
- Migration scripts are idempotent (safe to run multiple times)
- All scripts include proper error handling
- Follow MySQL best practices for performance
