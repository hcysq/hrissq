# Setup Google Sheets Integration

## Ringkasan

Plugin HCISYSQ menggunakan 3 Google Sheets untuk berbagai keperluan:

1. **Profil Pegawai** (CSV) - Data profil lengkap pegawai
2. **Data Users** (Sheet) - Data autentikasi user
3. **Form Pelatihan** (Sheet) - Rekam data pelatihan yang diinput

## 1. Setup Profil Pegawai (CSV)

### Langkah-langkah:

1. Buka Google Sheet profil pegawai
2. Klik **File → Share → Publish to the web**
3. Pilih:
   - Sheet: (pilih sheet yang berisi data)
   - Format: **Comma-separated values (.csv)**
4. Klik **Publish**
5. Copy URL yang dihasilkan

### URL yang Sudah Dikonfigurasi:
```
https://docs.google.com/spreadsheets/d/e/2PACX-1vTlR2VUOcQfXRjZN4fNC-o4CvPTgd-ZlReqj_pfEfYGr5A87Wh6K2zU16iexLnfIh5djkrXzmVlk1w-/pub?gid=0&single=true&output=csv
```

### Struktur Kolom:
| Kolom | Deskripsi | Wajib |
|-------|-----------|-------|
| Nomor | NIP pegawai | ✓ |
| NAMA | Nama lengkap | ✓ |
| UNIT | Unit kerja | ✓ |
| JABATAN | Jabatan | ✓ |
| TEMPAT LAHIR | Tempat lahir | - |
| TANGGAL LAHIR (TTTT-BB-HH) | Format: YYYY-MM-DD | - |
| ALAMAT KTP | Alamat sesuai KTP | - |
| DESA/KELURAHAN | Desa/Kelurahan | - |
| KECAMATAN | Kecamatan | - |
| KOTA/KABUPATEN | Kota/Kabupaten | - |
| KODE POS | Kode pos | - |
| EMAIL | Email pegawai | - |
| NO HP | Nomor HP (62xxx) | - |
| TMT | Tanggal Mulai Tugas | - |

### Konfigurasi di WordPress:

1. Login ke WordPress Admin
2. Buka **Tools → HCIS.YSQ Settings**
3. Di section **"1. Profil Pegawai (CSV)"**:
   - Paste URL CSV
   - Klik **Simpan**
4. Klik **Import Sekarang** untuk test import

---

## 2. Setup Data Users (Google Sheet)

### Sheet ID yang Sudah Dikonfigurasi:
```
14Uf7pjsFVURLmL5NWXlWhYvoILrwdiW11y3sVOLrLt4
```

### Langkah-langkah:

1. Buka Google Sheet users
2. Pastikan sheet bisa diakses:
   - Klik **Share** (pojok kanan atas)
   - Change to **Anyone with the link**
   - Role: **Viewer**
3. Copy Sheet ID dari URL:
   ```
   https://docs.google.com/spreadsheets/d/[SHEET_ID]/edit
   ```

### Struktur Kolom di Tab "User":
| Kolom | Deskripsi | Wajib |
|-------|-----------|-------|
| NIP | NIP pegawai (unique) | ✓ |
| NAMA | Nama lengkap | ✓ |
| JABATAN | Jabatan | - |
| UNIT | Unit kerja | - |
| NO HP | Nomor HP format 62xxx | - |
| PASSWORD | Password (jika kosong = NO HP) | - |

### Catatan Penting:
- Jika kolom PASSWORD kosong, sistem akan menggunakan NO HP sebagai password default
- Jika PASSWORD diisi dengan plain text, sistem akan meng-hash otomatis saat import
- Password yang sudah di-hash tidak akan di-hash ulang

### Konfigurasi di WordPress:

1. Buka **Tools → HCIS.YSQ Settings**
2. Di section **"2. Users (Google Sheet)"**:
   - Sheet ID: `14Uf7pjsFVURLmL5NWXlWhYvoILrwdiW11y3sVOLrLt4`
   - Tab Name: `User`
   - Klik **Simpan**
3. Klik **Import Sekarang** untuk test import

---

## 3. Setup Form Pelatihan (Google Sheet + Apps Script)

### Sheet ID yang Sudah Dikonfigurasi:
```
1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ
```

### A. Persiapan Google Sheet:

1. Buka Google Sheet untuk data pelatihan
2. Buat tab bernama **"Data"** (atau sesuai kebutuhan)
3. Header akan dibuat otomatis oleh Apps Script

### B. Deploy Google Apps Script:

1. Di Google Sheet, klik **Extensions → Apps Script**
2. Hapus semua code default
3. Copy-paste script dari file `docs/google-apps-script-training.js`
4. Klik **Save** (icon disket)
5. Ubah SHEET_ID dan TAB_NAME jika perlu:
   ```javascript
   const SHEET_ID = '1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ';
   const TAB_NAME = 'Data';
   ```

### C. Deploy sebagai Web App:

1. Klik **Deploy → New deployment**
2. Settings:
   - Click icon ⚙️ → Select type: **Web app**
   - Description: `HCISYSQ Training Receiver`
   - Execute as: **Me** (email Anda)
   - Who has access: **Anyone**
3. Klik **Deploy**
4. Authorize:
   - Pilih akun Google Anda
   - Klik **Advanced** → **Go to [project name] (unsafe)**
   - Klik **Allow**
5. **Copy Web App URL** yang dihasilkan
   - Format: `https://script.google.com/macros/s/.../exec`

### D. Test Apps Script (Opsional):

1. Di Apps Script editor, pilih function **testPost**
2. Klik **Run**
3. Cek sheet "Data" apakah ada data test yang masuk

### E. Konfigurasi di WordPress:

1. Buka **Tools → HCIS.YSQ Settings**
2. Di section **"3. Training Form → Google Sheet"**:
   - Sheet ID: `1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ`
   - Tab Name: `Data`
   - Web App URL: (paste URL dari step C.5)
   - Klik **Simpan**

### F. Test Submit Form:

1. Login sebagai pegawai
2. Akses halaman Form Pelatihan
3. Isi semua field
4. Klik **Simpan**
5. Cek Google Sheet "Data" apakah data masuk

### Struktur Data yang Dikirim:
| Kolom | Deskripsi |
|-------|-----------|
| Nama | Nama pegawai (otomatis dari akun yang login) |
| Jabatan | Jabatan / posisi terakhir |
| Unit Kerja | Unit / divisi pegawai |
| Nama Pelatihan/Workshop/Seminar | Judul pelatihan yang diinput |
| Tahun Penyelenggaraan | Tahun berlangsungnya pelatihan |
| Pembiayaan | `mandiri` atau `yayasan` |
| Kategori | `hard` atau `soft` |
| Link Sertifikat/Bukti | Tautan file di Google Drive (atau URL asal jika upload gagal) |
| Timestamp | Waktu submit (format `Y-m-d H:i:s`) |

> **Catatan:** File sertifikat otomatis dipindahkan ke Google Drive folder `1Wpf6k5G21Zb4kAILYDL7jfCjyKZd55zp` dengan sub-folder per pegawai (`<NIP>-<Nama>`). Jika folder sudah ada maka file baru akan ditambahkan tanpa menimpa file lama.

---

## Auto-Sync Schedule

Plugin menggunakan WP-Cron untuk auto-sync harian:

- **Profil Pegawai**: Sync setiap hari (24 jam sekali)
- **Data Users**: Sync setiap hari (24 jam sekali)
- **Form Pelatihan**: Real-time (langsung saat submit)

### Manual Trigger:

Jika ingin trigger manual sync via WP-CLI:
```bash
wp cron event run hcisysq_profiles_cron
wp cron event run hcisysq_users_cron
```

---

## Troubleshooting

### Import CSV Gagal
- Pastikan URL CSV publik dan bisa diakses
- Cek kolom wajib sudah tersedia (NIP, NAMA, UNIT, JABATAN)
- Test akses URL CSV di browser

### Import Users Gagal
- Pastikan Sheet bisa diakses (Anyone with link)
- Cek Sheet ID benar
- Pastikan tab "User" ada
- Cek kolom NIP dan NAMA tidak kosong

### Form Training Tidak Terkirim ke Sheet
- Pastikan Web App URL benar
- Cek Apps Script sudah di-deploy dengan akses "Anyone"
- Test function `testPost()` di Apps Script
- Cek log error di wp-content/hcisysq.log

### WP-Cron Tidak Jalan
- Pastikan WP-Cron aktif (tidak disabled di wp-config.php)
- Atau setup real cron di server:
  ```bash
  */15 * * * * curl -s https://yoursite.com/wp-cron.php
  ```

---

## Security Notes

1. **CSV URL** - Publik, tidak masalah karena read-only
2. **Users Sheet** - Viewer access, aman untuk read-only
3. **Training Sheet** - Anyone can access via Web App, tapi protected by WordPress auth
4. **Password** - Auto-hash saat import, tidak pernah di-store plain text

---

## Support

Jika ada masalah, cek file log di:
```
wp-content/hcisysq.log
```

Atau hubungi developer.
