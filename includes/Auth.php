<?php
namespace HRISSQ;

if (!defined('ABSPATH')) exit;

class Auth {

  // normalisasi no HP: keep digits only, leading 0 -> 62
  public static function norm_phone($s){
    $s = preg_replace('/\D+/', '', strval($s));
    if ($s === '') return '';
    if ($s[0] === '0') $s = '62' . substr($s, 1);
    return $s;
  }

  /** Ambil user by NIP dari wpw3_hrissq_users */
  public static function get_user_by_nip($nip){
    global $wpdb;
    $t = $wpdb->prefix . 'hrissq_users';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE nip = %s", $nip));
  }

  /** Set session (token di transient + cookie) */
  private static function set_session_for($user_id){
    $token = wp_generate_uuid4();
    set_transient('hrissq_sess_'.$token, intval($user_id), HOUR_IN_SECONDS);
    // path/domain dari wp-config sudah di-set, fallback ke '/'
    setcookie('hrissq_token', $token, time() + HOUR_IN_SECONDS, (defined('COOKIEPATH') ? COOKIEPATH : '/'), (defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : ''), is_ssl(), true);
    return $token;
  }

  public static function login($nip, $plain_pass){
    $nip = trim(strval($nip));
    $plain_pass = trim(strval($plain_pass));
    if ($nip === '' || $plain_pass === '') {
      return ['ok'=>false, 'msg'=>'Akun & Pasword wajib diisi'];
    }

    $u = self::get_user_by_nip($nip);
    if (!$u) return ['ok'=>false, 'msg'=>'Akun tidak ditemukan'];

    // 1) Jika password di DB ada dan terlihat hash -> verifikasi hash
    if (!empty($u->password)) {
      $hash = $u->password;
      $looksHashed = (strpos($hash, '$2y$') === 0 || strpos($hash, '$argon2') === 0);

      if ($looksHashed) {
        if (!password_verify($plain_pass, $hash)) {
          return ['ok'=>false, 'msg'=>'Password salah'];
        }
      } else {
        // password tersimpan plain-text -> bandingkan langsung (disarankan migrasi ke hash)
        if ($plain_pass !== $hash) {
          // fallback ke no_hp kalau ternyata password kolom tidak dipakai
          if (self::norm_phone($plain_pass) !== self::norm_phone($u->no_hp)) {
            return ['ok'=>false, 'msg'=>'Password salah'];
          }
        }
      }
    } else {
      // 2) Kolom password kosong -> default = nomor HP
      if (self::norm_phone($plain_pass) !== self::norm_phone($u->no_hp)) {
        return ['ok'=>false, 'msg'=>'Belum ada password. Gunakan nomor HP sebagai password awal.'];
      }
    }

    // sukses → set session
    self::set_session_for($u->id);

    return [
      'ok'   => true,
      'user' => [
        'id'      => intval($u->id),
        'nip'     => $u->nip,
        'nama'    => $u->nama,
        'jabatan' => $u->jabatan,
        'unit'    => $u->unit,
      ]
    ];
  }

  public static function logout(){
    if (!empty($_COOKIE['hrissq_token'])) {
      $token = sanitize_text_field($_COOKIE['hrissq_token']);
      delete_transient('hrissq_sess_' . $token);
      setcookie('hrissq_token', '', time() - 3600, (defined('COOKIEPATH') ? COOKIEPATH : '/'), (defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : ''), is_ssl(), true);
      setcookie('hrissq_token', '', time() - 3600, '/', '', is_ssl(), true);
      unset($_COOKIE['hrissq_token']);
    }
    return true;
  }

  public static function current_user(){
    if (empty($_COOKIE['hrissq_token'])) return null;
    $token  = sanitize_text_field($_COOKIE['hrissq_token']);
    $userId = get_transient('hrissq_sess_' . $token);
    if (!$userId) return null;

    global $wpdb;
    $t = $wpdb->prefix . 'hrissq_users';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d", $userId));
  }
}
