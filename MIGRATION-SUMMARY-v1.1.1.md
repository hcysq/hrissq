# Migration Summary - HCIS.YSQ v1.1.1

## Tanggal: 2025-10-04

## 1. Migrasi Nama (hrissq → hcis.ysq)

### File Utama
- ✅ `hrissq.php` → `hcis.ysq.php`

### Namespace
- ✅ `HRISSQ` → `HCISYSQ` (8 file includes)

### Constants
- ✅ `HRISSQ_*` → `HCISYSQ_*`
  - HCISYSQ_VER, HCISYSQ_DIR, HCISYSQ_URL
  - HCISYSQ_LOGIN_SLUG, HCISYSQ_DASHBOARD_SLUG, HCISYSQ_FORM_SLUG
  - HCISYSQ_SS_URL, HCISYSQ_SS_KEY, HCISYSQ_SS_HC
  - HCISYSQ_GAS_EXEC_URL, HCISYSQ_SSO_SECRET
  - HCISYSQ_LOG_FILE

### Database Tables
- ✅ `hrissq_users` → `hcisysq_users`
- ✅ `hrissq_profiles` → `hcisysq_profiles`
- ✅ `hrissq_trainings` → `hcisysq_trainings`

### Functions
- ✅ `hrissq_log()` → `hcisysq_log()`
- ✅ `hrissq_base64url()` → `hcisysq_base64url()`
- ✅ `hrissq_make_token()` → `hcisysq_make_token()`
- ✅ `hrissq_current_claims()` → `hcisysq_current_claims()`
- ✅ `hrissq_build_gas_url()` → `hcisysq_build_gas_url()`

### CSS/JS
- ✅ Semua class dan ID dari `hrissq-*` → `hcisysq-*`
- ✅ Asset handles: `hrissq` → `hcisysq`

### WordPress Options
- ✅ `hrissq_*` → `hcisysq_*`
  - hcisysq_users_sheet_id
  - hcisysq_profiles_csv_url
  - hcisysq_training_sheet_id
  - hcisysq_training_webapp_url
  - dll

### Dokumentasi
- ✅ README.md
- ✅ CHANGELOG.md
- ✅ PROJECT-STRUCTURE.md
- ✅ PERBAIKAN-SUMMARY.md
- ✅ QUICKSTART.md
- ✅ docs/SETUP-GOOGLE-SHEETS.md
- ✅ docs/migration-v1.0.2-to-v1.0.3.sql

---

## 2. Perbaikan Shortcode (v1.1.1)

### Masalah
Shortcode dengan titik `[hcis.ysq_login]` tidak valid di WordPress (hanya menerima `[A-Za-z0-9_-]`), sehingga ditampilkan sebagai teks literal.

### Solusi

#### A. Shortcode Baru (Valid)
```
[hcis_ysq_login]       - Login page
[hcis_ysq_dashboard]   - Dashboard page
[hcis_ysq_form]        - Training form
[hcis_ysq_form_button] - Form button
```

#### B. Alias Kompatibilitas Mundur
Semua shortcode lama tetap berfungsi:
```
[hrissq_login]         → berfungsi
[hrissq_dashboard]     → berfungsi
[hrissq_form]          → berfungsi
[hcisysq_login]        → berfungsi
[hcisysq_dashboard]    → berfungsi
[hcisysq_form]         → berfungsi
```

#### C. Auto-Conversion Filter
Plugin otomatis mengkonversi shortcode dengan titik tanpa mengubah database:
```php
add_filter('the_content', function ($content) {
  // [hcis.ysq_login] → [hcis_ysq_login] on-the-fly
}, 9);
```

#### D. Conditional Asset Loading
CSS/JS hanya dimuat saat shortcode digunakan:
```php
add_action('wp_enqueue_scripts', function () {
  // Check if shortcode exists in post content
  // Only enqueue if found
}, 10);
```

---

## Testing Checklist

### Shortcode Testing
- [ ] Halaman dengan `[hcis.ysq_login]` tampil form login (auto-converted)
- [ ] Halaman dengan `[hcis_ysq_login]` tampil form login
- [ ] Halaman dengan `[hrissq_login]` tampil form login (kompat lama)
- [ ] Halaman dengan `[hcisysq_login]` tampil form login

### Asset Loading
- [ ] CSS dan JS hanya dimuat di halaman dengan shortcode
- [ ] Console browser tidak ada error
- [ ] Network tab menunjukkan app.css dan app.js termuat

### Functionality
- [ ] Login berfungsi normal
- [ ] Dashboard menampilkan data pegawai
- [ ] Form pelatihan dapat disubmit
- [ ] Auto-logout setelah idle berfungsi

### Database
- [ ] Tabel `wp_hcisysq_users` ada dan terisi
- [ ] Tabel `wp_hcisysq_profiles` ada dan terisi
- [ ] Tabel `wp_hcisysq_trainings` ada dan dapat menyimpan data

### Compatibility
- [ ] Tidak ada PHP errors di error log
- [ ] Tidak ada JavaScript errors di console
- [ ] WP Admin dapat mengakses settings
- [ ] Import data dari Google Sheets berfungsi

---

## Catatan Penting

### Tidak Perlu Action
- ❌ **TIDAK perlu** mengubah konten halaman yang menggunakan `[hcis.ysq_login]`
- ❌ **TIDAK perlu** migrasi database manual
- ❌ **TIDAK perlu** hapus dan reinstall plugin

### Rekomendasi
- ✅ Update versi plugin ke 1.1.1
- ✅ Clear cache (browser & server)
- ✅ Test semua halaman dengan shortcode
- ✅ (Opsional) Edit halaman untuk ganti ke shortcode baru secara manual

### Rollback
Jika terjadi masalah:
1. Deaktivasi plugin
2. Ganti nama file plugin kembali ke `hrissq.php` (jika perlu)
3. Restore dari backup
4. Laporkan issue

---

## File Baru
- ✅ `SHORTCODE-MIGRATION.md` - Panduan migrasi shortcode
- ✅ `MIGRATION-SUMMARY-v1.1.1.md` - Dokumen ini

## Versi
- Previous: 1.1.0
- Current: 1.1.1
- Release Date: 2025-10-04

## Support
Untuk pertanyaan atau masalah, hubungi developer.
