# Quick Start Guide - HCIS.YSQ Plugin

## Instalasi Cepat

### 1. Upload & Aktivasi
```bash
# Upload folder ke wp-content/plugins/
# Atau via WordPress Admin: Plugins → Add New → Upload Plugin

# Aktivasi plugin
wp plugin activate hcisysq
```

### 2. Konfigurasi Awal

Login ke WordPress Admin → **Tools → HCIS.YSQ Settings**

#### A. Profil Pegawai (CSV)
```
URL: https://docs.google.com/spreadsheets/d/e/2PACX-1vTlR2VUOcQfXRjZN4fNC-o4CvPTgd-ZlReqj_pfEfYGr5A87Wh6K2zU16iexLnfIh5djkrXzmVlk1w-/pub?gid=0&single=true&output=csv
```
- Paste URL
- Klik **Import Sekarang** untuk test

#### B. Data Users
```
Sheet ID: 14Uf7pjsFVURLmL5NWXlWhYvoILrwdiW11y3sVOLrLt4
Tab Name: User
```
- Paste Sheet ID dan Tab Name
- Klik **Import Sekarang** untuk test

#### C. Training Form
```
Sheet ID: 1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ
Tab Name: Data
Web App URL: [Paste URL dari Apps Script deployment]
```

### 3. Deploy Google Apps Script

1. Buka sheet training: https://docs.google.com/spreadsheets/d/1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ
2. **Extensions → Apps Script**
3. Copy-paste dari `docs/google-apps-script-training.js`
4. **Deploy → New deployment → Web app**
   - Execute as: Me
   - Who has access: Anyone
5. Copy URL → Paste ke HCIS.YSQ Settings

### 4. Buat Halaman WordPress

Buat 3 halaman baru dengan shortcode:

#### Halaman Login (slug: `masuk`)
```
[hcisysq_login]
```

#### Dashboard (slug: `dashboard`)
```
[hcisysq_dashboard]
```

#### Form Pelatihan (slug: `pelatihan`)
```
[hcisysq_form]
```

### 5. Test Login

1. Akses `https://yoursite.com/masuk`
2. Login dengan:
   - **NIP**: (ambil dari data users)
   - **Password**: (default = No HP format 62xxx)
3. Setelah login, akan redirect ke dashboard

### 6. Test Form Pelatihan

1. Dari dashboard, klik "Isi Form Pelatihan"
2. Isi semua field
3. Upload sertifikat (opsional)
4. Klik **Simpan**
5. Cek Google Sheet apakah data masuk

---

## Troubleshooting Cepat

### Login Gagal
- Cek data users sudah di-import (`Tools → HCIS.YSQ Settings → Import Users`)
- Pastikan NIP benar
- Password default = No HP (62xxx)

### Import Gagal
- Cek URL/Sheet ID benar
- Pastikan sheet publik/accessible
- Cek kolom wajib tersedia (NIP, NAMA)

### Form Tidak Masuk ke Sheet
- Cek Web App URL sudah benar
- Test function `testPost()` di Apps Script
- Cek permission Apps Script (Anyone)

### Auto-Sync Tidak Jalan
- Pastikan WP-Cron aktif
- Atau setup real cron:
  ```bash
  */15 * * * * curl -s https://yoursite.com/wp-cron.php
  ```

---

## Default Credentials

Setelah import users pertama kali:
- **NIP**: (lihat di Google Sheet users)
- **Password**: No HP dengan format 62xxx

Contoh:
- NIP: `202012345678`
- Password: `628123456789`

---

## Next Steps

1. Customize tema/style sesuai branding
2. Setup SSL untuk keamanan
3. Backup database secara berkala
4. Monitor log: `wp-content/hcisysq.log`

---

## Support

- Documentation: `README.md`
- Setup Guide: `docs/SETUP-GOOGLE-SHEETS.md`
- Changelog: `CHANGELOG.md`
- Log file: `wp-content/hcisysq.log`

Jika ada masalah, hubungi developer atau cek log error.
