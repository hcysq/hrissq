<?php
/**
 * Plugin Name: HRIS SQ (hrissq)
 * Description: Login NIP+HP, Dashboard Pegawai, Form Pelatihan dengan Google Sheets Integration.
 * Version: 1.0.3
 * Author: samijaya
 */

if (!defined('ABSPATH')) exit;

/* =======================================================
 *  Logger lokal (independen dari WP_DEBUG)
 * ======================================================= */
if (!defined('HRISSQ_LOG_FILE')) {
  define('HRISSQ_LOG_FILE', WP_CONTENT_DIR . '/hrissq.log');
}
if (!function_exists('hrissq_log')) {
  function hrissq_log($data) {
    $msg = '[HRISSQ ' . date('Y-m-d H:i:s') . '] ';
    $msg .= is_scalar($data) ? $data : print_r($data, true);
    $msg .= PHP_EOL;
    @error_log($msg, 3, HRISSQ_LOG_FILE); // tulis ke wp-content/hrissq.log
  }
}
// tangkap warning/notice
set_error_handler(function($errno, $errstr, $errfile, $errline){
  hrissq_log("PHP[$errno] $errstr @ $errfile:$errline");
  return false;
});
// tangkap fatal error
register_shutdown_function(function(){
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
    hrissq_log("FATAL {$e['message']} @ {$e['file']}:{$e['line']}");
  }
});
hrissq_log('hrissq plugin boot...');

/* =======================================================
 *  Konstanta plugin
 * ======================================================= */
if (!defined('HRISSQ_VER')) define('HRISSQ_VER', '1.0.3');
if (!defined('HRISSQ_DIR')) define('HRISSQ_DIR', plugin_dir_path(__FILE__));
if (!defined('HRISSQ_URL')) define('HRISSQ_URL', plugin_dir_url(__FILE__));

// Slug halaman (kalau mau ganti cukup ubah sini)
if (!defined('HRISSQ_LOGIN_SLUG'))     define('HRISSQ_LOGIN_SLUG', 'masuk');
if (!defined('HRISSQ_DASHBOARD_SLUG')) define('HRISSQ_DASHBOARD_SLUG', 'dashboard');
if (!defined('HRISSQ_FORM_SLUG'))      define('HRISSQ_FORM_SLUG', 'pelatihan');

// === StarSender config (for "Lupa Password") ===
if (!defined('HRISSQ_SS_URL')) define('HRISSQ_SS_URL', 'https://starsender.online/api/sendText');
if (!defined('HRISSQ_SS_KEY')) define('HRISSQ_SS_KEY', '4a74d8ae-8d5d-4e95-8f14-9429409c9eda'); // API key kamu
if (!defined('HRISSQ_SS_HC'))  define('HRISSQ_SS_HC',  '6285175201627'); // nomor HCM penerima WA

/* =======================================================
 *  Includes YANG ADA
 * ======================================================= */
require_once HRISSQ_DIR . 'includes/Installer.php';
require_once HRISSQ_DIR . 'includes/Auth.php';
require_once HRISSQ_DIR . 'includes/Api.php';
require_once HRISSQ_DIR . 'includes/View.php';
require_once HRISSQ_DIR . 'includes/Profiles.php';
require_once HRISSQ_DIR . 'includes/Users.php';
require_once HRISSQ_DIR . 'includes/Trainings.php';
require_once HRISSQ_DIR . 'includes/Admin.php';

/* =======================================================
 *  Activation (create tables)
 * ======================================================= */
register_activation_hook(__FILE__, ['HRISSQ\\Installer', 'activate']);

/* =======================================================
 *  Assets, Shortcodes
 * ======================================================= */
add_action('init', function () {
  // register assets
  wp_register_style('hrissq',  HRISSQ_URL . 'assets/app.css', [], HRISSQ_VER);
  wp_register_script('hrissq', HRISSQ_URL . 'assets/app.js', ['jquery'], HRISSQ_VER, true);

add_action('wp_ajax_nopriv_hrissq_forgot', ['HRISSQ\\Api','forgot_password']);


  // data ke JS
  wp_localize_script('hrissq', 'HRISSQ', [
    'ajax'          => admin_url('admin-ajax.php'),
    'nonce'         => wp_create_nonce('hrissq-nonce'),
    'loginSlug'     => HRISSQ_LOGIN_SLUG,
    'dashboardSlug' => HRISSQ_DASHBOARD_SLUG,
  ]);

  // shortcodes
  add_shortcode('hrissq_login',     ['HRISSQ\\View', 'login']);
  add_shortcode('hrissq_dashboard', ['HRISSQ\\View', 'dashboard']);
  add_shortcode('hrissq_form',      ['HRISSQ\\View', 'form']);
});

/* =======================================================
 *  AJAX endpoints
 * ======================================================= */
add_action('wp_ajax_nopriv_hrissq_login',    ['HRISSQ\\Api', 'login']);
add_action('wp_ajax_hrissq_logout',          ['HRISSQ\\Api', 'logout']);
add_action('wp_ajax_nopriv_hrissq_logout',   ['HRISSQ\\Api', 'logout']);
add_action('wp_ajax_hrissq_submit_training', ['HRISSQ\\Api', 'submit_training']);
// non-logged user dilarang submit
add_action('wp_ajax_nopriv_hrissq_submit_training', function(){
  wp_send_json(['ok'=>false,'msg'=>'Unauthorized']);
});
// Cron untuk import profil pegawai
add_action('hrissq_profiles_cron', function(){
  $url = \HRISSQ\Profiles::get_csv_url();
  if ($url) \HRISSQ\Profiles::import_from_csv($url);
});

// Cron untuk import users
add_action('hrissq_users_cron', function(){
  $sheet_id = \HRISSQ\Users::get_sheet_id();
  if ($sheet_id) {
    $url = "https://docs.google.com/spreadsheets/d/{$sheet_id}/export?format=csv&gid=0";
    \HRISSQ\Users::import_from_csv($url);
  }
});
add_action('admin_menu', ['HRISSQ\\Admin','menu']);

/* =======================================================
 *  Proteksi halaman (guard)
 * ======================================================= */
add_action('template_redirect', function () {
  // helper URL sesuai setting permalink
  $to = function ($slug) { return trailingslashit(home_url('/' . ltrim($slug, '/'))); };

  // kalau belum login dan buka dashboard/pelatihan → lempar ke /masuk
  if (is_page([HRISSQ_DASHBOARD_SLUG, HRISSQ_FORM_SLUG])) {
    if (!HRISSQ\Auth::current_user()) {
      hrissq_log('guard: not logged, redirect to /' . HRISSQ_LOGIN_SLUG);
      wp_safe_redirect($to(HRISSQ_LOGIN_SLUG));
      exit;
    }
  }
  // kalau sudah login dan buka /masuk → lempar ke /dashboard
  if (is_page(HRISSQ_LOGIN_SLUG) && HRISSQ\Auth::current_user()) {
    hrissq_log('guard: already logged, redirect to /' . HRISSQ_DASHBOARD_SLUG);
    wp_safe_redirect($to(HRISSQ_DASHBOARD_SLUG));
    exit;
  }
});
