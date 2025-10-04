<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Users {

  const OPT_USERS_SHEET_ID = 'hcisysq_users_sheet_id';
  const OPT_USERS_TAB_NAME = 'hcisysq_users_tab_name';

  /** Simpan / Ambil config Google Sheet untuk users */
  public static function set_sheet_config($sheet_id, $tab_name = 'User'){
    update_option(self::OPT_USERS_SHEET_ID, sanitize_text_field($sheet_id), false);
    update_option(self::OPT_USERS_TAB_NAME, sanitize_text_field($tab_name), false);
  }

  public static function get_sheet_id(){
    return get_option(self::OPT_USERS_SHEET_ID, '');
  }

  public static function get_tab_name(){
    return get_option(self::OPT_USERS_TAB_NAME, 'User');
  }

  /** Build URL CSV dari Sheet ID + Tab Name */
  public static function get_csv_url(){
    $sid = self::get_sheet_id();
    if (!$sid) return '';

    // Dapatkan gid dari nama tab (API Sheet v4)
    // Untuk sederhana, asumsi tab "User" = gid 0, atau kita bisa query API
    // Tapi cara paling mudah: gunakan publish to web format dengan gid manual
    // Format: https://docs.google.com/spreadsheets/d/{SHEET_ID}/export?format=csv&gid={GID}

    // Alternatif: user harus publish to web dulu, lalu kasih URL lengkap
    // Untuk sekarang kita return format manual
    return "https://docs.google.com/spreadsheets/d/{$sid}/export?format=csv&gid=0";
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

  /** Import dari URL CSV (users) */
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
    $fh = fopen('php://temp','rw');
    fwrite($fh, $body);
    rewind($fh);

    $headers = fgetcsv($fh);
    if (!$headers) return ['ok'=>false,'msg'=>'Header CSV tidak terbaca'];
    $map = self::header_map($headers);

    // Label kolom: NIP, NAMA, JABATAN, UNIT, NO HP, PASSWORD (opsional)
    $required = ['nip','nama'];
    foreach ($required as $r) {
      if (!array_key_exists(strtolower($r), $map)) {
        return ['ok'=>false,'msg'=>"Kolom wajib '$r' tidak ditemukan di CSV"];
      }
    }

    global $wpdb;
    $t = $wpdb->prefix.'hcisysq_users';
    $inserted = 0; $updated = 0;

    while (($row = fgetcsv($fh)) !== false) {
      $nip   = self::col($row,$map,'nip');
      $nama  = self::col($row,$map,'nama');
      if (!$nip || !$nama) continue;

      $password_raw = self::col($row,$map,'password');
      $no_hp = self::col($row,$map,'no hp');

      // Jika password ada dan tidak terlihat hash, hash dulu
      $password_stored = '';
      if ($password_raw) {
        $looksHashed = (strpos($password_raw, '$2y$') === 0 || strpos($password_raw, '$argon2') === 0);
        $password_stored = $looksHashed ? $password_raw : password_hash($password_raw, PASSWORD_BCRYPT);
      }

      $data = [
        'nip'        => $nip,
        'nama'       => $nama,
        'jabatan'    => self::col($row,$map,'jabatan'),
        'unit'       => self::col($row,$map,'unit'),
        'no_hp'      => $no_hp,
        'password'   => $password_stored,
        'updated_at' => current_time('mysql')
      ];

      $res = $wpdb->replace($t, $data, ['%s','%s','%s','%s','%s','%s','%s']);
      if ($res === 1) $inserted++;
      elseif ($res === 2) $updated++;
    }
    fclose($fh);

    return ['ok'=>true,'inserted'=>$inserted,'updated'=>$updated];
  }
}
