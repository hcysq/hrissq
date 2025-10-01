<?php
namespace HRISSQ;

if (!defined('ABSPATH')) exit;

class Trainings {

  const OPT_TRAINING_SHEET_ID = 'hrissq_training_sheet_id';
  const OPT_TRAINING_TAB_NAME = 'hrissq_training_tab_name';

  /** Simpan / Ambil config Google Sheet untuk training */
  public static function set_sheet_config($sheet_id, $tab_name = 'Data'){
    update_option(self::OPT_TRAINING_SHEET_ID, sanitize_text_field($sheet_id), false);
    update_option(self::OPT_TRAINING_TAB_NAME, sanitize_text_field($tab_name), false);
  }

  public static function get_sheet_id(){
    return get_option(self::OPT_TRAINING_SHEET_ID, '');
  }

  public static function get_tab_name(){
    return get_option(self::OPT_TRAINING_TAB_NAME, 'Data');
  }

  /** Submit training data ke Google Sheet via Apps Script Web App */
  public static function submit_to_sheet($data){
    $sheet_id = self::get_sheet_id();
    if (!$sheet_id) return ['ok'=>false,'msg'=>'Sheet ID belum dikonfigurasi'];

    // Data yang akan dikirim:
    // user_id, nip, nama, unit, jabatan, nama_pelatihan, tahun, pembiayaan, kategori, file_url, timestamp

    // Untuk mengirim ke Google Sheet, kita butuh:
    // 1. Apps Script Web App yang di-deploy sebagai "anyone can access"
    // 2. URL dari Web App tersebut disimpan di options

    $web_app_url = get_option('hrissq_training_webapp_url', '');
    if (!$web_app_url) {
      return ['ok'=>false,'msg'=>'Web App URL belum dikonfigurasi'];
    }

    // Kirim data via POST
    $args = [
      'body'    => json_encode($data),
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
}
