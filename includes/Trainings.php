<?php
namespace HRISSQ;

if (!defined('ABSPATH')) exit;

class Trainings {

  const OPT_TRAINING_SHEET_ID = 'hrissq_training_sheet_id';
  const OPT_TRAINING_TAB_NAME = 'hrissq_training_tab_name';
  const OPT_TRAINING_DRIVE_FOLDER_ID = 'hrissq_training_drive_folder_id';
  const OPT_TRAINING_COLUMN_WIDTHS   = 'hrissq_training_column_widths';
  const OPT_TRAINING_HEADER_FONT     = 'hrissq_training_header_font_size';

  /** Simpan / Ambil config Google Sheet untuk training */
  public static function set_sheet_config($sheet_id, $tab_name = 'Data'){
    update_option(self::OPT_TRAINING_SHEET_ID, sanitize_text_field($sheet_id), false);
    update_option(self::OPT_TRAINING_TAB_NAME, sanitize_text_field($tab_name), false);
  }

  public static function get_sheet_id(){
    return get_option(self::OPT_TRAINING_SHEET_ID, '1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ');
  }

  public static function get_tab_name(){
    return get_option(self::OPT_TRAINING_TAB_NAME, 'Data');
  }

  public static function set_drive_folder_id($folder_id){
    update_option(self::OPT_TRAINING_DRIVE_FOLDER_ID, sanitize_text_field($folder_id), false);
  }

  public static function get_drive_folder_id(){
    return get_option(self::OPT_TRAINING_DRIVE_FOLDER_ID, '1Wpf6k5G21Zb4kAILYDL7jfCjyKZd55zp');
  }

  /**
   * Simpan konfigurasi lebar kolom dalam bentuk JSON string.
   *
   * @param string $input JSON object {"Nama":220, ...}
   * @param string|null $error Pesan error jika format tidak valid.
   * @return array|null Array hasil normalisasi atau null jika gagal.
   */
  public static function set_column_widths_from_input($input, &$error = null){
    $input = is_string($input) ? trim($input) : '';

    if ($input === '') {
      delete_option(self::OPT_TRAINING_COLUMN_WIDTHS);
      return [];
    }

    $decoded = json_decode($input, true);
    if (!is_array($decoded)) {
      $error = __('Format column widths harus JSON object. Contoh: {"Nama":220,"Jabatan":180}', 'hrissq');
      return null;
    }

    $normalized = self::normalize_column_widths($decoded);

    if (!empty($normalized)) {
      update_option(self::OPT_TRAINING_COLUMN_WIDTHS, wp_json_encode($normalized), false);
    } else {
      delete_option(self::OPT_TRAINING_COLUMN_WIDTHS);
    }

    return $normalized;
  }

  /** Ambil konfigurasi lebar kolom sebagai array [judul => width]. */
  public static function get_column_widths(){
    $raw = get_option(self::OPT_TRAINING_COLUMN_WIDTHS, '');

    if (is_array($raw)) {
      return self::normalize_column_widths($raw);
    }

    if (is_string($raw) && trim($raw) !== '') {
      $decoded = json_decode($raw, true);
      if (is_array($decoded)) {
        return self::normalize_column_widths($decoded);
      }
    }

    return [];
  }

  /** Ambil konfigurasi lebar kolom siap tampil (JSON pretty). */
  public static function get_column_widths_display(){
    $widths = self::get_column_widths();
    if (empty($widths)) return '';
    return wp_json_encode($widths, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  /** Simpan ukuran font header (number). */
  public static function set_header_font_size($size){
    $size = intval($size);
    if ($size > 0) {
      update_option(self::OPT_TRAINING_HEADER_FONT, $size, false);
      return $size;
    }

    delete_option(self::OPT_TRAINING_HEADER_FONT);
    return 0;
  }

  public static function get_header_font_size(){
    $size = intval(get_option(self::OPT_TRAINING_HEADER_FONT, 0));
    return ($size > 0) ? $size : 0;
  }

  /** Normalisasi array width menjadi [judul => int]. */
  private static function normalize_column_widths($data){
    if (!is_array($data)) return [];

    $normalized = [];

    foreach ($data as $title => $width) {
      if (is_array($width)) {
        // dukung format [{"title":"Nama","width":220}, ...]
        if (isset($width['title']) && isset($width['width'])) {
          $title = $width['title'];
          $width = $width['width'];
        } else {
          continue;
        }
      }

      $title = sanitize_text_field(is_string($title) ? $title : '');
      if ($title === '') continue;

      if (is_string($width) && is_numeric($width)) {
        $width = $width + 0; // cast numeric string
      }

      if (!is_int($width) && !is_float($width)) continue;

      $width = intval(round($width));
      if ($width <= 0) continue;

      $normalized[$title] = $width;
    }

    return $normalized;
  }

  /** Submit training data ke Google Sheet via Apps Script Web App */
  public static function submit_to_sheet($data){
    $sheet_id = self::get_sheet_id();
    if (!$sheet_id) return ['ok'=>false,'msg'=>'Sheet ID belum dikonfigurasi'];

    // Data yang akan dikirim:
    // nip, nama, jabatan, unit_kerja, nama_pelatihan, tahun_penyelenggaraan, pembiayaan, kategori, link_sertifikat, timestamp

    // Untuk mengirim ke Google Sheet, kita butuh:
    // 1. Apps Script Web App yang di-deploy sebagai "anyone can access"
    // 2. URL dari Web App tersebut disimpan di options

    $web_app_url = get_option('hrissq_training_webapp_url', '');
    if (!$web_app_url) {
      return ['ok'=>false,'msg'=>'Web App URL belum dikonfigurasi'];
    }

    $payload = [
      'sheetId'       => $sheet_id,
      'tabName'       => self::get_tab_name(),
      'driveFolderId' => self::get_drive_folder_id(),
      'entry'         => $data,
    ];

    $widths = self::get_column_widths();
    if (!empty($widths)) {
      $payload['columnWidths'] = $widths;
    }

    $headerFont = self::get_header_font_size();
    if ($headerFont > 0) {
      $payload['headerFontSize'] = $headerFont;
    }

    // Kirim data via POST
    $args = [
      'body'    => wp_json_encode($payload),
      'headers' => ['Content-Type' => 'application/json'],
      'timeout' => 30,
    ];

    $resp = wp_remote_post($web_app_url, $args);

    if (is_wp_error($resp)) {
      return ['ok'=>false,'msg'=>$resp->get_error_message()];
    }

    $code = wp_remote_retrieve_response_code($resp);
    $body = wp_remote_retrieve_body($resp);

    if ($code !== 200) {
      return ['ok'=>false,'msg'=>'HTTP '.$code.': '.$body];
    }

    $result = json_decode($body, true);
    return $result ? $result : ['ok'=>true];
  }

  /** Set Web App URL */
  public static function set_webapp_url($url){
    update_option('hrissq_training_webapp_url', esc_url_raw($url), false);
  }

  public static function get_webapp_url(){
    return get_option('hrissq_training_webapp_url', '');
  }

  /**
   * Generate signed web app link that carries encoded nip & nama payload.
   */
  public static function build_webapp_link($nip, $nama){
    $base = self::get_webapp_url();
    if (!$base) return '';

    $payload = wp_json_encode([
      'nip'  => strval($nip),
      'nama' => strval($nama),
    ]);

    if (!$payload) return $base;

    $encoded = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    $token   = hash_hmac('sha256', $encoded, wp_salt('hrissq-training-link'));

    $url = add_query_arg([
      'payload' => $encoded,
      'token'   => $token,
    ], $base);

    return esc_url_raw($url);
  }
}
