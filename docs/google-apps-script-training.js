/**
 * Google Apps Script untuk menerima data training dari WordPress
 *
 * Cara Deploy:
 * 1. Buka Google Sheet (1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ)
 * 2. Extensions → Apps Script
 * 3. Paste script ini
 * 4. Deploy → New deployment → Web app
 *    - Execute as: Me
 *    - Who has access: Anyone
 * 5. Copy URL dan paste ke HRISSQ Settings → Training → Web App URL
 */

function doPost(e) {
  try {
    // Parse request body
    const data = JSON.parse(e.postData.contents);

    // Buka Sheet berdasarkan ID dan Tab Name
    const SHEET_ID = '1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ';
    const TAB_NAME = 'Data';

    const ss = SpreadsheetApp.openById(SHEET_ID);
    const sheet = ss.getSheetByName(TAB_NAME);

    if (!sheet) {
      return ContentService.createTextOutput(JSON.stringify({
        ok: false,
        msg: 'Tab "' + TAB_NAME + '" tidak ditemukan'
      })).setMimeType(ContentService.MimeType.JSON);
    }

    // Cek header (baris pertama)
    const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];

    // Jika belum ada header, buat header
    if (!headers || headers.length === 0 || headers[0] === '') {
      sheet.appendRow([
        'Timestamp',
        'User ID',
        'NIP',
        'Nama',
        'Unit',
        'Jabatan',
        'Nama Pelatihan',
        'Tahun',
        'Pembiayaan',
        'Kategori',
        'File URL'
      ]);
    }

    // Tulis data
    sheet.appendRow([
      data.timestamp || new Date().toISOString(),
      data.user_id || '',
      data.nip || '',
      data.nama || '',
      data.unit || '',
      data.jabatan || '',
      data.nama_pelatihan || '',
      data.tahun || '',
      data.pembiayaan || '',
      data.kategori || '',
      data.file_url || ''
    ]);

    return ContentService.createTextOutput(JSON.stringify({
      ok: true,
      msg: 'Data berhasil disimpan'
    })).setMimeType(ContentService.MimeType.JSON);

  } catch (error) {
    return ContentService.createTextOutput(JSON.stringify({
      ok: false,
      msg: error.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

// Test function
function testPost() {
  const testData = {
    postData: {
      contents: JSON.stringify({
        user_id: 1,
        nip: '202012345678',
        nama: 'Test User',
        unit: 'IT',
        jabatan: 'Staff',
        nama_pelatihan: 'Web Development',
        tahun: 2024,
        pembiayaan: 'yayasan',
        kategori: 'hard',
        file_url: 'https://example.com/cert.pdf',
        timestamp: new Date().toISOString()
      })
    }
  };

  const result = doPost(testData);
  Logger.log(result.getContent());
}
