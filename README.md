# HCIS.YSQ Plugin - WordPress

Plugin HRIS (Human Resource Information System) untuk sistem kepegawaian dengan integrasi Google Sheets.

## Fitur

1. **Autentikasi Pegawai**
   - Login menggunakan NIP + Password/No HP
   - Logout manual & auto-logout setelah idle 15 menit
   - Lupa password via WhatsApp (StarSender)

2. **Dashboard Pegawai**
   - Profil ringkas pegawai
   - Status data & pengumuman
   - Menu navigasi lengkap

3. **Form Pelatihan**
   - Input data pelatihan pegawai
   - Upload sertifikat (PDF, JPG, PNG)
   - Sinkronisasi otomatis ke Google Sheets

4. **Integrasi Google Sheets**
   - **Profil Pegawai**: Import dari CSV (auto-sync harian)
   - **Data Users**: Import dari Google Sheet (auto-sync harian)
   - **Form Pelatihan**: Submit data ke Google Sheet via Apps Script

## Instalasi

1. Upload folder plugin ke `wp-content/plugins/`
2. Aktifkan plugin melalui WordPress Admin
3. Buka **Tools → HCIS.YSQ Settings** untuk konfigurasi

## Konfigurasi Google Sheets

### 1. Profil Pegawai (CSV)

URL CSV yang sudah dikonfigurasi:
```
https://docs.google.com/spreadsheets/d/e/2PACX-1vTlR2VUOcQfXRjZN4fNC-o4CvPTgd-ZlReqj_pfEfYGr5A87Wh6K2zU16iexLnfIh5djkrXzmVlk1w-/pub?gid=0&single=true&output=csv
```

**Struktur kolom yang dibutuhkan:**
- Nomor (NIP)
- NAMA
- UNIT
- JABATAN
- TEMPAT LAHIR
- TANGGAL LAHIR (TTTT-BB-HH)
- ALAMAT KTP
- DESA/KELURAHAN
- KECAMATAN
- KOTA/KABUPATEN
- KODE POS
- EMAIL
- NO HP
- TMT

### 2. Data Users (Google Sheet)

**Sheet ID:** `14Uf7pjsFVURLmL5NWXlWhYvoILrwdiW11y3sVOLrLt4`
**Tab Name:** `User`

**Struktur kolom yang dibutuhkan:**
- NIP
- NAMA
- JABATAN
- UNIT
- NO HP
- PASSWORD (opsional, jika kosong akan menggunakan NO HP)

**Cara konfigurasi:**
1. Pastikan Google Sheet dapat diakses (Share → Anyone with link can view)
2. Masukkan Sheet ID dan Tab Name di HCIS.YSQ Settings
3. Klik "Import Sekarang" untuk sinkronisasi manual
4. Import otomatis akan berjalan setiap hari via WP-Cron

### 3. Form Pelatihan (Google Sheet)

**Sheet ID:** `1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ`
**Tab Name:** `Data`

**Setup Google Apps Script:**

1. Buka Google Sheet untuk data pelatihan
2. Klik **Extensions → Apps Script**
3. Copy-paste script dari file `docs/google-apps-script-training.js`
4. Deploy:
   - Klik **Deploy → New deployment**
   - Pilih **Web app**
   - Execute as: **Me**
   - Who has access: **Anyone**
   - Copy URL deployment
5. Paste URL ke **HCIS.YSQ Settings → Training → Web App URL**

**Struktur kolom yang akan dibuat otomatis:**
- Timestamp
- User ID
- NIP
- Nama
- Unit
- Jabatan
- Nama Pelatihan
- Tahun
- Pembiayaan
- Kategori
- File URL

## Flow Login-Logout

### Login
1. User mengakses halaman `/masuk`
2. Input NIP + Password (default: No HP format 62xxx)
3. Plugin memverifikasi ke tabel `hcisysq_users`
4. Jika berhasil, session dibuat dengan cookie `hcisysq_token` (expired 1 jam)
5. Redirect ke `/dashboard`

### Logout
1. **Manual**: Klik tombol "Keluar" di dropdown user menu
2. **Auto**: Setelah idle 15 menit, popup warning muncul (countdown 30 detik)
3. Session dihapus dari transient & cookie
4. Redirect ke `/masuk`

### Guards
- Halaman `/dashboard` dan `/pelatihan` hanya bisa diakses jika sudah login
- Halaman `/masuk` akan redirect ke `/dashboard` jika sudah login

## Database Tables

### `wp_hcisysq_users`
Tabel autentikasi user (di-sync dari Google Sheet)

### `wp_hcisysq_profiles`
Tabel profil pegawai lengkap (di-sync dari CSV)

### `wp_hcisysq_trainings`
Tabel rekam data pelatihan yang diinput pegawai

## Shortcodes

```
[hcisysq_login]     - Halaman login
[hcisysq_dashboard] - Dashboard pegawai
[hcisysq_form]      - Form input pelatihan
```

## Cron Jobs

Plugin menggunakan WP-Cron untuk sinkronisasi otomatis:

- `hcisysq_profiles_cron` - Import profil pegawai (daily)
- `hcisysq_users_cron` - Import data users (daily)

## StarSender Integration

Untuk fitur "Lupa Password", plugin mengirim pesan ke Admin HCM via WhatsApp menggunakan StarSender API.

**Konfigurasi** (di `hcisysq.php`):
```php
define('HCISYSQ_SS_URL', 'https://starsender.online/api/sendText');
define('HCISYSQ_SS_KEY', 'YOUR_API_KEY');
define('HCISYSQ_SS_HC',  '6285175201627'); // nomor admin HCM
```

## Changelog

### v1.0.2
- Fixed login-logout flow
- Added Google Sheets integration (Users, Profiles, Training)
- Added auto-logout after 15 minutes idle
- Improved form UI/UX
- Fixed table structure consistency (hcisysq_users vs hcisysq_employees)

## Support

Untuk pertanyaan atau bug report, hubungi developer.

## License

Proprietary - Internal use only
