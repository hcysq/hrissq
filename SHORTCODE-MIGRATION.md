# Panduan Migrasi Shortcode

## Masalah
Shortcode dengan titik (.) seperti `[hcis.ysq_login]` tidak valid di WordPress karena WordPress hanya menerima karakter `[A-Za-z0-9_-]` untuk nama shortcode. Akibatnya, shortcode tersebut ditampilkan sebagai teks literal dan tidak di-render.

## Solusi
Plugin versi 1.1.1 telah memperbaiki masalah ini dengan:

### 1. Shortcode Baru (Valid)
Gunakan shortcode dengan underscore:
- `[hcis_ysq_login]` - Halaman login
- `[hcis_ysq_dashboard]` - Dashboard pegawai
- `[hcis_ysq_form]` - Form pelatihan
- `[hcis_ysq_form_button]` - Tombol form

### 2. Kompatibilitas Mundur
Shortcode lama tetap berfungsi:
- `[hrissq_login]` → berfungsi
- `[hrissq_dashboard]` → berfungsi
- `[hrissq_form]` → berfungsi
- `[hcisysq_login]` → berfungsi
- `[hcisysq_dashboard]` → berfungsi
- `[hcisysq_form]` → berfungsi

### 3. Auto-Conversion
Plugin secara otomatis mengkonversi shortcode lama dengan titik:
- `[hcis.ysq_login]` → otomatis menjadi `[hcis_ysq_login]`
- `[hcis.ysq_dashboard]` → otomatis menjadi `[hcis_ysq_dashboard]`
- `[hcis.ysq_form]` → otomatis menjadi `[hcis_ysq_form]`

**PENTING:** Konversi dilakukan on-the-fly saat halaman di-render. Database tidak diubah, sehingga aman dan reversible.

## Migrasi

### Tidak Perlu Action
Jika halaman Anda menggunakan `[hcis.ysq_login]`, **tidak perlu melakukan apa-apa**. Plugin akan otomatis mengkonversinya saat render.

### Rekomendasi (Opsional)
Untuk best practice, disarankan untuk:
1. Edit halaman yang menggunakan shortcode lama
2. Ganti manual ke shortcode baru:
   - `[hcis.ysq_login]` → `[hcis_ysq_login]`
   - `[hcis.ysq_dashboard]` → `[hcis_ysq_dashboard]`
   - `[hcis.ysq_form]` → `[hcis_ysq_form]`

### Testing
1. Buka halaman yang sebelumnya menampilkan `[hcis.ysq_login]` sebagai teks
2. Refresh halaman (clear cache jika menggunakan caching plugin)
3. Form login seharusnya sudah tampil dengan benar
4. Periksa Console browser untuk memastikan CSS/JS termuat

## Optimisasi Asset Loading
Plugin sekarang hanya memuat CSS/JS ketika shortcode benar-benar digunakan di halaman. Ini meningkatkan performa karena asset tidak dimuat di semua halaman.

## Support
Jika mengalami masalah, pastikan:
- Plugin versi 1.1.1 atau lebih baru
- Cache sudah di-clear (browser & server)
- Tidak ada plugin caching yang menginterfer filter `the_content`
