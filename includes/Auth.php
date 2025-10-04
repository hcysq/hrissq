<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Auth {

  private static function determine_cookie_domain(){
    if (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) {
      return COOKIE_DOMAIN;
    }

    $host = parse_url(home_url(), PHP_URL_HOST);
    if (!$host) return '';

    $host = strtolower(trim($host));
    $host = trim($host, '.');
    if ($host === '') return '';

    $parts = explode('.', $host);
    if (count($parts) === 1) {
      return $host;
    }

    $suffix = implode('.', array_slice($parts, -2));
    $twoLevelTlds = apply_filters('hcisysq_two_level_tlds', [
      'co.id','or.id','ac.id','go.id','sch.id','net.id','web.id','my.id','biz.id','mil.id','ponpes.id'
    ]);

    if (in_array($suffix, $twoLevelTlds, true) && count($parts) >= 3) {
      $domainParts = array_slice($parts, -3);
    } else {
      $domainParts = array_slice($parts, -2);
    }

    $domain = '.' . implode('.', $domainParts);

    return apply_filters('hcisysq_cookie_domain', $domain, $host);
  }

  // normalisasi no HP: keep digits only, leading 0 -> 62
  public static function norm_phone($s){
    $s = preg_replace('/\D+/', '', strval($s));
    if ($s === '') return '';
    if ($s[0] === '0') $s = '62' . substr($s, 1);
    return $s;
  }

  /** Ambil user by NIP dari wpw3_hcisysq_users */
  public static function get_user_by_nip($nip){
    global $wpdb;
    $t = $wpdb->prefix . 'hcisysq_users';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE nip = %s", $nip));
  }

  /**
   * Set session (token disimpan di transient + cookie browser)
   * Kita simpan NIP karena tabel hcisysq_users tidak memiliki kolom ID integer.
   */
  private static function set_session_for($nip){
    $token = wp_generate_uuid4();
    // simpan maks 12 jam supaya user tetap login lebih lama
    set_transient('hcisysq_sess_'.$token, ['nip' => $nip], 12 * HOUR_IN_SECONDS);

    // Gunakan domain root (contoh: hcis.sabilulquran.or.id -> .sabilulquran.or.id) agar cookie bekerja di semua subdomain
    $domain = self::determine_cookie_domain();

    // Set cookie dengan SameSite=Lax untuk kompatibilitas lebih baik
    $options = [
      'expires'  => time() + (12 * HOUR_IN_SECONDS),
      'path'     => '/',
      'domain'   => $domain,
      'secure'   => is_ssl(),
      'httponly' => true,
      'samesite' => 'Lax'
    ];

    hcisysq_log("Setting cookie for NIP={$nip}, domain={$domain}, secure=" . (is_ssl() ? 'true' : 'false'));
    setcookie('hcisysq_token', $token, $options);
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
    if (!empty($_COOKIE['hcisysq_token'])) {
      $token = sanitize_text_field($_COOKIE['hcisysq_token']);
      delete_transient('hcisysq_sess_' . $token);

      // Hapus cookie dengan domain yang sama seperti saat set
      $domain = self::determine_cookie_domain();

      $options = [
        'expires'  => time() - 3600,
        'path'     => '/',
        'domain'   => $domain,
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax'
      ];

      setcookie('hcisysq_token', '', $options);
      unset($_COOKIE['hcisysq_token']);
    }
    return true;
  }

  public static function current_user(){
    if (empty($_COOKIE['hcisysq_token'])) return null;
    $token  = sanitize_text_field($_COOKIE['hcisysq_token']);
    $sess   = get_transient('hcisysq_sess_' . $token);
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
