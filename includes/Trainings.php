<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Trainings {

  const OPT_TRAINING_SHEET_ID = 'hcisysq_training_sheet_id';
  const OPT_TRAINING_TAB_NAME = 'hcisysq_training_tab_name';
  const OPT_TRAINING_DRIVE_FOLDER_ID = 'hcisysq_training_drive_folder_id';

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

  /** Submit training data ke Google Sheet via Apps Script Web App */
  public static function submit_to_sheet($data){
    $sheet_id = self::get_sheet_id();
    if (!$sheet_id) return ['ok'=>false,'msg'=>'Sheet ID belum dikonfigurasi'];

    // Data yang akan dikirim:
    // nip, nama, jabatan, unit_kerja, nama_pelatihan, tahun_penyelenggaraan, pembiayaan, kategori, link_sertifikat, timestamp

    // Untuk mengirim ke Google Sheet, kita butuh:
    // 1. Apps Script Web App yang di-deploy sebagai "anyone can access"
    // 2. URL dari Web App tersebut disimpan di options

    $web_app_url = get_option('hcisysq_training_webapp_url', '');
    if (!$web_app_url) {
      return ['ok'=>false,'msg'=>'Web App URL belum dikonfigurasi'];
    }

    $payload = [
      'sheetId'       => $sheet_id,
      'tabName'       => self::get_tab_name(),
      'driveFolderId' => self::get_drive_folder_id(),
      'entry'         => $data,
    ];

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
    update_option('hcisysq_training_webapp_url', esc_url_raw($url), false);
  }

  public static function get_webapp_url(){
    return get_option('hcisysq_training_webapp_url', '');
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
    $token   = hash_hmac('sha256', $encoded, wp_salt('hcisysq-training-link'));

    $url = add_query_arg([
      'payload' => $encoded,
      'token'   => $token,
    ], $base);

    return esc_url_raw($url);
  }
}
