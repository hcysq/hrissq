<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Profiles {

  const OPT_CSV_URL = 'hcisysq_profiles_csv_url';

  /** Simpan / Ambil URL CSV publik */
  public static function set_csv_url($url){
    $url = esc_url_raw(trim($url));
    update_option(self::OPT_CSV_URL, $url, false);
  }
  public static function get_csv_url(){
    return get_option(self::OPT_CSV_URL, '');
  }

  /** Map header → index (case-insensitive) */
  private static function header_map($headers){
    $map = [];
    foreach ($headers as $i => $h) {
      $key = strtolower(trim($h));
      $map[$key] = $i;
    }
    return $map;
  }

  /** Helper ambil kolom aman */
  private static function col($row, $map, $label){
    $idx = $map[strtolower($label)] ?? null;
    if ($idx === null) return '';
    return isset($row[$idx]) ? trim((string)$row[$idx]) : '';
  }

  /** Import dari URL CSV (publish to web) */
  public static function import_from_csv($url){
    if (!$url) return ['ok'=>false,'msg'=>'CSV URL kosong'];

    // Ambil konten
    $resp = wp_remote_get($url, ['timeout'=>30]);
    if (is_wp_error($resp)) {
      return ['ok'=>false,'msg'=>$resp->get_error_message()];
    }
    $code = wp_remote_retrieve_response_code($resp);
    if ($code < 200 || $code >= 300) {
      return ['ok'=>false,'msg'=>'HTTP '.$code];
    }
    $body = wp_remote_retrieve_body($resp);
    if (!$body) return ['ok'=>false,'msg'=>'Body kosong'];

    // Parse CSV
    $lines = preg_split("/\r\n|\n|\r/", $body);
    if (count($lines) < 2) return ['ok'=>false,'msg'=>'CSV tidak berisi data'];

    // Pakai fgetcsv agar handling koma/quote aman
    $fh = fopen('php://temp','rw');
    fwrite($fh, $body);
    rewind($fh);

    $headers = fgetcsv($fh);
    if (!$headers) return ['ok'=>false,'msg'=>'Header CSV tidak terbaca'];
    $map = self::header_map($headers);

    // Label kolom yang kita pakai tahap awal:
    // "Nomor" (NIP), "NAMA", "UNIT", "JABATAN", 
    // "TEMPAT LAHIR", "TANGGAL LAHIR (TTTT-BB-HH)",
    // "ALAMAT KTP", "DESA/KELURAHAN", "KECAMATAN", "KOTA/KABUPATEN", "KODE POS",
    // "EMAIL", "NO HP", "TMT"
    $required = ['Nomor','NAMA','UNIT','JABATAN'];
    foreach ($required as $r) {
      if (!array_key_exists(strtolower($r), $map)) {
        return ['ok'=>false,'msg'=>"Kolom wajib '$r' tidak ditemukan di CSV"];
      }
    }

    global $wpdb;
    $t = $wpdb->prefix.'hcisysq_profiles';
    $inserted = 0; $updated = 0; $rownum = 1;

    while (($row = fgetcsv($fh)) !== false) {
      $rownum++;

      $nip   = self::col($row,$map,'Nomor');                // NIP
      $nama  = self::col($row,$map,'NAMA');
      if (!$nip || !$nama) continue; // skip baris invalid

      $data = [
        'nip'           => $nip,
        'nama'          => $nama,
        'unit'          => self::col($row,$map,'UNIT'),
        'jabatan'       => self::col($row,$map,'JABATAN'),
        'tempat_lahir'  => self::col($row,$map,'TEMPAT LAHIR'),
        'tanggal_lahir' => self::col($row,$map,'TANGGAL LAHIR (TTTT-BB-HH)'),
        'alamat_ktp'    => self::col($row,$map,'ALAMAT KTP'),
        'desa'          => self::col($row,$map,'DESA/KELURAHAN'),
        'kecamatan'     => self::col($row,$map,'KECAMATAN'),
        'kota'          => self::col($row,$map,'KOTA/KABUPATEN'),
        'kode_pos'      => self::col($row,$map,'KODE POS'),
        'email'         => self::col($row,$map,'EMAIL'),
        'hp'            => self::col($row,$map,'NO HP'),
        'tmt'           => self::col($row,$map,'TMT'),
        'updated_at'    => current_time('mysql')
      ];

      // REPLACE = insert jika belum ada (UNIQUE nip), update kalau sudah ada
      $res = $wpdb->replace($t, $data, [
        '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',
        '%s','%s','%s','%s','%s'
      ]);
      // $res = 1 (insert) atau 2 (update)
      if ($res === 1) $inserted++;
      elseif ($res === 2) $updated++;
    }
    fclose($fh);

    return ['ok'=>true,'inserted'=>$inserted,'updated'=>$updated];
  }

  /** Ambil profil ringkas berdasarkan NIP */
  public static function get_by_nip($nip){
    global $wpdb; $t = $wpdb->prefix.'hcisysq_profiles';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE nip=%s", $nip));
  }
}
