# Dev Tools

This directory contains development and debugging scripts. These are NOT for production use.

## Scripts

### Role & Permission Scripts
- `seed_roles*.php` - Various role seeding scripts
- `migrate_to_spatie.php` - Migrate to Spatie permissions
- `assign_all_roles.php` - Assign roles to users

### Translation Scripts
- `add_*.php` - Add translations for various languages (Arabic, Urdu, Basque)
- `fix_languages.php`, `fix_arabic.php` - Language fixes

### Debug Scripts
- `debug_*.php` - Debug Flutter, JSON, users
- `check_*.php` - Check roles, tokens, users, etc.
- `simulate_flutter.php` - Simulate Flutter app requests

### Database Scripts
- `reset-password.php` - Reset user passwords
- `insert_new_translations.php` - Insert new translations

## Usage

Run with: `php scriptname.php` from backend directory.

Note: Most scripts assume they're run from the backend directory with access to the Laravel app.
