<?php
/**
 * Plugin Name: HRIS SQ (hrissq)
 * Description: Login NIP+HP, Dashboard Pegawai, Form Pelatihan dengan Google Sheets Integration + SSO ke Google Apps Script.
 * Version: 1.1.0
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
if (!defined('HRISSQ_VER')) define('HRISSQ_VER', '1.1.0');
if (!defined('HRISSQ_DIR')) define('HRISSQ_DIR', plugin_dir_path(__FILE__));
if (!defined('HRISSQ_URL')) define('HRISSQ_URL', plugin_dir_url(__FILE__));

// Slug halaman
if (!defined('HRISSQ_LOGIN_SLUG'))     define('HRISSQ_LOGIN_SLUG', 'masuk');
if (!defined('HRISSQ_DASHBOARD_SLUG')) define('HRISSQ_DASHBOARD_SLUG', 'dashboard');
if (!defined('HRISSQ_FORM_SLUG'))      define('HRISSQ_FORM_SLUG', 'pelatihan');

// StarSender (opsional – untuk "lupa password")
if (!defined('HRISSQ_SS_URL')) define('HRISSQ_SS_URL', 'https://starsender.online/api/sendText');
if (!defined('HRISSQ_SS_KEY')) define('HRISSQ_SS_KEY', '4a74d8ae-8d5d-4e95-8f14-9429409c9eda'); // ganti sesuai
if (!defined('HRISSQ_SS_HC'))  define('HRISSQ_SS_HC',  '6285175201627'); // ganti sesuai

/* =======================================================
 *  ** KONFIG SSO → Google Apps Script **
 *  EDIT BAGIAN INI
 * ======================================================= */
if (!defined('HRISSQ_GAS_EXEC_URL')) {
  // URL /exec dari Apps Script (deployment web app)
  define('HRISSQ_GAS_EXEC_URL', 'https://script.google.com/macros/s/AKfycbxReKFiKsW1BtDZufNNi4sCuazw5jjzUQ9iHDPylmm9ARuAudqsB6CmSI_2vNpng3uP/exec'); // TODO: GANTI
}
if (!defined('HRISSQ_SSO_SECRET')) {
  // Secret acak, simpan juga di Apps Script Script Properties: key = SSO_SHARED_SECRET
  define('HRISSQ_SSO_SECRET', '5e9b3c8b20d130fe64f9d41c427ba81ffc6696045b76a5e57e7ca1eea37c0cbd'); // TODO: GANTI
}

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

  // data ke JS
  wp_localize_script('hrissq', 'HRISSQ', [
    'ajax'          => admin_url('admin-ajax.php'),
    'nonce'         => wp_create_nonce('hrissq-nonce'),
    'loginSlug'     => HRISSQ_LOGIN_SLUG,
    'dashboardSlug' => HRISSQ_DASHBOARD_SLUG,
  ]);

  // shortcodes bawaan
  add_shortcode('hrissq_login',     ['HRISSQ\\View', 'login']);
  add_shortcode('hrissq_dashboard', ['HRISSQ\\View', 'dashboard']);
  add_shortcode('hrissq_form',      ['HRISSQ\\View', 'form']);

  // **Shortcode tombol redirect ke GAS (SSO)**
  add_shortcode('hrissq_form_button', function(){
    if (!is_user_logged_in() && !HRISSQ\Auth::current_user()) {
      $login = trailingslashit(home_url('/' . HRISSQ_LOGIN_SLUG));
      return '<a class="button" href="'.esc_url($login).'">Login untuk isi form</a>';
    }
    $href = admin_url('admin-post.php?action=hrissq_go');
    return '<a class="button button-primary" href="'.esc_url($href).'">Isi Form Pelatihan</a>';
  });
});

/* =======================================================
 *  AJAX endpoints
 * ======================================================= */
add_action('wp_ajax_nopriv_hrissq_login',    ['HRISSQ\\Api', 'login']);
add_action('wp_ajax_hrissq_logout',          ['HRISSQ\\Api', 'logout']);
add_action('wp_ajax_nopriv_hrissq_logout',   ['HRISSQ\\Api', 'logout']);
add_action('wp_ajax_hrissq_submit_training', ['HRISSQ\\Api', 'submit_training']);
add_action('wp_ajax_nopriv_hrissq_submit_training', function(){
  wp_send_json(['ok'=>false,'msg'=>'Unauthorized']);
});
add_action('wp_ajax_nopriv_hrissq_forgot',   ['HRISSQ\\Api','forgot_password']);

/* =======================================================
 *  Cron (jika pakai import)
 * ======================================================= */
add_action('hrissq_profiles_cron', function(){
  $url = \HRISSQ\Profiles::get_csv_url();
  if ($url) \HRISSQ\Profiles::import_from_csv($url);
});
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

/* =======================================================
 *  ======= SSO → Google Apps Script (HMAC token) =======
 * ======================================================= */

/** base64url helper */
function hrissq_base64url($bin) {
  return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

/** bikin token: base64url(JSON) . '.' . base64url(HMAC_SHA256(payload, secret)) */
function hrissq_make_token(array $claims) {
  $payload = hrissq_base64url(json_encode($claims, JSON_UNESCAPED_UNICODE));
  $sig     = hrissq_base64url(hash_hmac('sha256', $payload, HRISSQ_SSO_SECRET, true));
  return $payload . '.' . $sig;
}

/** ambil klaim user dari sistemmu (coba dari Auth kustom lalu fallback WP) */
function hrissq_current_claims() {
  $now = time();
  $claims = [
    'nip'     => '',
    'nama'    => '',
    'unit'    => '',
    'jabatan' => '',
    'iat'     => $now,
    'exp'     => $now + 300, // 5 menit
    'jti'     => wp_generate_uuid4(),
  ];

  // 1) coba pakai sistem Auth plugin (kalau ada)
  if (class_exists('HRISSQ\\Auth')) {
    $u = HRISSQ\Auth::current_user();
    if (is_array($u) && !empty($u)) {
      $claims['nip']     = (string)($u['nip']     ?? '');
      $claims['nama']    = (string)($u['nama']    ?? ($u['display_name'] ?? ''));
      $claims['unit']    = (string)($u['unit']    ?? '');
      $claims['jabatan'] = (string)($u['jabatan'] ?? '');
    }
  }

  // 2) fallback: WP user standar
  if (empty($claims['nama']) || empty($claims['nip'])) {
    if (is_user_logged_in()) {
      $wp = wp_get_current_user();
      if ($wp && $wp->ID) {
        $claims['nama'] = $claims['nama'] ?: trim($wp->display_name ?: ($wp->first_name . ' ' . $wp->last_name));
        $claims['nip']  = $claims['nip']  ?: (string)get_user_meta($wp->ID, 'nip', true);
        $claims['unit'] = $claims['unit'] ?: (string)(get_user_meta($wp->ID, 'unit', true) ?: get_user_meta($wp->ID, 'hr_unit', true));
        $claims['jabatan'] = $claims['jabatan'] ?: (string)(get_user_meta($wp->ID, 'jabatan', true) ?: get_user_meta($wp->ID, 'hr_jabatan', true));
      }
    }
  }

  return $claims;
}

/** bangun URL redirect ke GAS (pakai token) */
function hrissq_build_gas_url(array $claims) {
  $token = hrissq_make_token($claims);
  $url   = add_query_arg('token', rawurlencode($token), HRISSQ_GAS_EXEC_URL);

  // (opsional) fallback prefill GET untuk debug/manual (tidak dipakai jika token dipakai)
  $url   = add_query_arg([
    'prefill_nama'    => rawurlencode($claims['nama']),
    'prefill_nip'     => rawurlencode($claims['nip']),
    'prefill_unit'    => rawurlencode($claims['unit']),
    'prefill_jabatan' => rawurlencode($claims['jabatan']),
  ], $url);

  return $url;
}

/** handler admin-post.php?action=hrissq_go */
add_action('admin_post_hrissq_go', function(){
  // wajib login via sistemmu
  if (!HRISSQ\Auth::current_user() && !is_user_logged_in()) {
    $login = trailingslashit(home_url('/' . HRISSQ_LOGIN_SLUG));
    wp_safe_redirect($login);
    exit;
  }

  $claims = hrissq_current_claims();

  // validasi minimal
  if (empty($claims['nama']) || empty($claims['nip'])) {
    hrissq_log('SSO error: klaim tidak lengkap ' . json_encode($claims));
    wp_die('Data akun belum lengkap untuk SSO (butuh nama & NIP). Hubungi admin.');
  }

  $url = hrissq_build_gas_url($claims);
  hrissq_log('SSO redirect → ' . $url);

  wp_safe_redirect($url);
  exit;
});

/* =======================================================
 *  Selesai
 * ======================================================= */
