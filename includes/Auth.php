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

  /**
   * Set session (token disimpan di transient + cookie browser)
   * Kita simpan NIP karena tabel hrissq_users tidak memiliki kolom ID integer.
   */
  private static function set_session_for($nip){
    $token = wp_generate_uuid4();
    // simpan maks 12 jam supaya user tetap login lebih lama
    set_transient('hrissq_sess_'.$token, ['nip' => $nip], 12 * HOUR_IN_SECONDS);
    // path/domain dari wp-config sudah di-set, fallback ke '/'
    setcookie(
      'hrissq_token',
      $token,
      time() + (12 * HOUR_IN_SECONDS),
      (defined('COOKIEPATH') ? COOKIEPATH : '/'),
      (defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : ''),
      is_ssl(),
      true
    );
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

    $passOk = false;

    // 1) Jika kolom password terisi hash, tetap hormati hash tersebut.
    if (!empty($u->password)) {
      $hash = $u->password;
      $looksHashed = (strpos($hash, '$2y$') === 0 || strpos($hash, '$argon2') === 0);

      if ($looksHashed && password_verify($plain_pass, $hash)) {
        $passOk = true;
      } elseif (!$looksHashed && hash_equals(strval($hash), $plain_pass)) {
        $passOk = true;
      }
    }

    // 2) Validasi utama mengikuti instruksi terbaru: pasword = nomor HP.
    if (!$passOk) {
      $dbPhone  = self::norm_phone($u->no_hp ?? '');
      $inputPhone = self::norm_phone($plain_pass);

      if ($dbPhone !== '' && $inputPhone !== '' && hash_equals($dbPhone, $inputPhone)) {
        $passOk = true;
      } elseif (hash_equals(trim(strval($u->no_hp ?? '')), $plain_pass)) {
        $passOk = true;
      }
    }

    if (!$passOk) {
      return ['ok'=>false, 'msg'=>'Pasword salah. Gunakan nomor HP sebagai pasword.'];
    }

    // sukses → set session
    self::set_session_for($u->nip);

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
    $sess   = get_transient('hrissq_sess_' . $token);
    if (!$sess) return null;

    $nip = null;
    if (is_array($sess) && !empty($sess['nip'])) {
      $nip = $sess['nip'];
    } elseif (is_object($sess) && !empty($sess->nip)) {
      $nip = $sess->nip;
    } elseif (is_string($sess) && trim($sess) !== '') {
      // kompatibilitas lama ketika yang disimpan adalah ID numerik / string biasa
      $nip = $sess;
    }

    if (!$nip) return null;

    return self::get_user_by_nip($nip);
  }
}
