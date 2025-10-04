# Project Structure - HCIS.YSQ Plugin

```
hcisysq/
├── hcisysq.php                      # Main plugin file
│
├── assets/
│   ├── app.css                     # Styles untuk login, dashboard, form
│   └── app.js                      # JavaScript untuk interaksi
│
├── includes/
│   ├── Admin.php                   # Admin settings page
│   ├── Api.php                     # AJAX endpoints (login, logout, submit)
│   ├── Auth.php                    # Authentication logic
│   ├── Installer.php               # Database setup & activation
│   ├── Profiles.php                # Import profil pegawai dari CSV
│   ├── Users.php                   # Import users dari Google Sheet
│   ├── Trainings.php               # Submit training ke Google Sheet
│   └── View.php                    # Shortcode views (login, dashboard, form)
│
├── docs/
│   ├── SETUP-GOOGLE-SHEETS.md      # Panduan setup Google Sheets
│   ├── google-apps-script-training.js  # Script untuk Apps Script
│   └── migration-v1.0.2-to-v1.0.3.sql  # SQL migration script
│
├── README.md                       # Dokumentasi utama
├── QUICKSTART.md                   # Quick start guide
├── CHANGELOG.md                    # History perubahan
└── PERBAIKAN-SUMMARY.md            # Summary perbaikan v1.0.3
```

## File Descriptions

### Root Files

- **hcisysq.php**: Main plugin file yang:
  - Define constants
  - Load semua includes
  - Register activation hooks
  - Register shortcodes
  - Register AJAX actions
  - Setup WP-Cron jobs

### Assets

- **app.css**: Berisi styles untuk:
  - Login page (`.hcisysq-auth-wrap`)
  - Dashboard layout (`.hcisysq-dashboard`)
  - Training form (`.hcisysq-form-wrap`)
  - Modal components
  - Responsive design

- **app.js**: Berisi JavaScript untuk:
  - Login form handling
  - Logout functionality
  - Auto-logout (idle 15 min)
  - Forgot password modal
  - Training form submit
  - AJAX helpers

### Includes

#### Admin.php
- Menu: `Tools → HCIS.YSQ Settings`
- 3 sections:
  1. Profil Pegawai (CSV URL)
  2. Users (Sheet ID + Tab)
  3. Training (Sheet ID + Tab + Web App URL)
- Manual import buttons

#### Api.php
- **login**: Handle login via AJAX
- **logout**: Handle logout via AJAX
- **forgot_password**: Send request ke Admin HCM via WhatsApp
- **submit_training**: Save to database + send to Google Sheet

#### Auth.php
- **login($nip, $password)**: Authenticate user
- **logout()**: Clear session & cookie
- **current_user()**: Get current logged-in user
- **norm_phone($phone)**: Normalize phone number
- **get_user_by_nip($nip)**: Get user by NIP

#### Installer.php
- Create 3 tables:
  - `hcisysq_users`: User data (auth)
  - `hcisysq_trainings`: Training records
  - `hcisysq_profiles`: Profile data (mirror dari CSV)
- Setup WP-Cron jobs

#### Profiles.php
- Import profil pegawai dari CSV publik
- Parse CSV dengan semua kolom profil
- Auto-sync harian via cron

#### Users.php (NEW)
- Import users dari Google Sheet
- Auto-hash password jika plain text
- Auto-sync harian via cron

#### Trainings.php (NEW)
- Submit training data ke Google Sheet
- Via Google Apps Script Web App
- Real-time (tidak pakai cron)

#### View.php
- **login()**: Render login form
- **dashboard()**: Render dashboard pegawai
- **form()**: Render training form

### Docs

#### SETUP-GOOGLE-SHEETS.md
- Panduan lengkap setup Google Sheets
- Step-by-step untuk semua 3 sheets
- Troubleshooting guide

#### google-apps-script-training.js
- Google Apps Script code
- Menerima POST request dari WordPress
- Write data ke sheet "Data"
- Auto-create header

#### migration-v1.0.2-to-v1.0.3.sql
- SQL script untuk migrasi manual
- Rename tables
- Alter columns
- Update foreign keys

### Documentation

#### README.md
- Overview plugin
- Feature list
- Installation guide
- Database structure
- Shortcodes
- Configuration

#### QUICKSTART.md
- Quick installation steps
- Configuration checklist
- Test procedures
- Default credentials

#### CHANGELOG.md
- Version history
- What's new in v1.0.3
- Fixed issues
- Added features

#### PERBAIKAN-SUMMARY.md
- Detailed summary of fixes
- What was changed and why
- File-by-file changes
- Testing checklist

## Database Tables

### wp_hcisysq_users
```sql
id, nip, nama, jabatan, unit, no_hp, password, created_at, updated_at
```
Primary: User authentication data

### wp_hcisysq_profiles
```sql
id, nip, nama, unit, jabatan, tempat_lahir, tanggal_lahir,
alamat_ktp, desa, kecamatan, kota, kode_pos, email, hp, tmt, updated_at
```
Mirror: Full profile data from CSV

### wp_hcisysq_trainings
```sql
id, user_id, nama_pelatihan, tahun, pembiayaan, kategori, file_url, created_at
```
Records: Training submissions

## Shortcodes

- `[hcisysq_login]` → Login page
- `[hcisysq_dashboard]` → Dashboard
- `[hcisysq_form]` → Training form

## AJAX Actions

- `hcisysq_login` (nopriv) → Login
- `hcisysq_logout` (priv) → Logout
- `hcisysq_forgot` (nopriv) → Forgot password
- `hcisysq_submit_training` (priv) → Submit training

## WP-Cron Jobs

- `hcisysq_profiles_cron` → Daily import profiles
- `hcisysq_users_cron` → Daily import users

## Constants

```php
HCISYSQ_VER           // Plugin version
HCISYSQ_DIR           // Plugin directory path
HCISYSQ_URL           // Plugin URL
HCISYSQ_LOGIN_SLUG    // Login page slug (masuk)
HCISYSQ_DASHBOARD_SLUG // Dashboard slug (dashboard)
HCISYSQ_FORM_SLUG     // Form slug (pelatihan)
HCISYSQ_SS_URL        // StarSender API URL
HCISYSQ_SS_KEY        // StarSender API key
HCISYSQ_SS_HC         // HCM phone number
HCISYSQ_LOG_FILE      // Log file path
```

## Flow Diagrams

### Login Flow
```
User accesses /masuk
  → Enter NIP + Password
  → AJAX to hcisysq_login
  → Auth::login() validates
  → Set session (cookie + transient)
  → Redirect to /dashboard
```

### Logout Flow
```
User clicks "Keluar"
  → AJAX to hcisysq_logout
  → Auth::logout() clears session
  → Redirect to /masuk

OR

User idle 15 minutes
  → Warning modal (30s countdown)
  → Auto-logout
  → Redirect to /masuk
```

### Import Flow
```
WP-Cron (daily)
  → hcisysq_profiles_cron
    → Fetch CSV
    → Parse rows
    → REPLACE into hcisysq_profiles

  → hcisysq_users_cron
    → Fetch Sheet via export?format=csv
    → Parse rows
    → Hash passwords if needed
    → REPLACE into hcisysq_users
```

### Training Submit Flow
```
User fills form
  → AJAX to hcisysq_submit_training
  → Validate + Upload file
  → INSERT into hcisysq_trainings
  → POST to Google Apps Script
  → Apps Script appends to Sheet
  → Return success
```

## Version History

- **v1.0.3** (2025-10-01): Google Sheets integration, fixed login-logout
- **v1.0.2**: Initial version with basic features
