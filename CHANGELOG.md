# Changelog

## v1.0.3 (2025-10-01)

### Fixed
- **Login-Logout Flow**
  - Fixed session management dengan cookie yang lebih secure (httpOnly)
  - Fixed logout untuk menghapus session dan cookie dengan benar
  - Fixed redirect URL yang lebih dinamis menggunakan slug konfigurasi
  - Fixed auto-logout setelah idle 15 menit dengan warning 30 detik

### Added
- **Google Sheets Integration**
  - **Profil Pegawai**: Import dari CSV publik (auto-sync harian)
  - **Data Users**: Import dari Google Sheet dengan Sheet ID (auto-sync harian)
  - **Form Pelatihan**: Submit data ke Google Sheet via Apps Script Web App

- **New Classes**
  - `Users.php` - Handle import users dari Google Sheet
  - `Trainings.php` - Handle submit training ke Google Sheet

- **New Admin Page**
  - Unified settings page "HCIS.YSQ Settings" untuk semua konfigurasi
  - Section 1: Profil Pegawai (CSV URL)
  - Section 2: Users (Sheet ID + Tab Name)
  - Section 3: Training (Sheet ID + Tab Name + Web App URL)
  - Manual import button untuk setiap section

- **Cron Jobs**
  - `hcisysq_profiles_cron` - Auto-sync profil pegawai harian
  - `hcisysq_users_cron` - Auto-sync users harian

- **Documentation**
  - `README.md` - Dokumentasi lengkap plugin
  - `SETUP-GOOGLE-SHEETS.md` - Panduan setup Google Sheets
  - `google-apps-script-training.js` - Script untuk Apps Script

### Changed
- **Database Structure**
  - Renamed table `hcisysq_employees` → `hcisysq_users` untuk konsistensi
  - Updated foreign key di `hcisysq_trainings` dari `employee_id` → `user_id`
  - Added column `password` di `hcisysq_users`

- **Form UI**
  - Improved training form UI dengan styling yang lebih baik
  - Added validation messages
  - Added cancel button dengan link ke dashboard
  - Added file upload description

- **JavaScript**
  - Added `bootTrainingForm()` function untuk handle submit form
  - Fixed redirect URL menggunakan slug dari PHP config
  - Improved error handling

- **CSS**
  - Added `.hcisysq-form-wrap` dan `.training-form` styles
  - Added `.btn-light` style
  - Improved responsive design

### Technical
- Version bump to 1.0.3
- Updated plugin description
- Added more comprehensive logging
- Fixed security issues dengan proper sanitization dan escaping

---

## v1.0.2 (Previous)
- Initial version dengan fitur dasar:
  - Login NIP + Password/HP
  - Dashboard pegawai
  - Form pelatihan
  - MySQL storage
