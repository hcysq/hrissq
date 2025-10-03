<?php
namespace HRISSQ;

if (!defined('ABSPATH')) exit;

class Api {

  private static function check_nonce(){
    $nonce = $_POST['_nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'hrissq-nonce')) {
      wp_send_json(['ok'=>false,'msg'=>'Invalid nonce']);
    }
  }

public static function forgot_password(){
  // nonce
  if (!isset($_POST['_nonce']) || !wp_verify_nonce($_POST['_nonce'],'hrissq-nonce')) {
    wp_send_json(['ok'=>false,'msg'=>'Invalid request']);
  }

  $nip = sanitize_text_field($_POST['nip'] ?? '');
  if (!$nip) wp_send_json(['ok'=>false,'msg'=>'Akun wajib diisi']);

  // cari nama pegawai dari hrissq_users
  global $wpdb;
  $t = $wpdb->prefix.'hrissq_users';
  $emp = $wpdb->get_row($wpdb->prepare("SELECT nama FROM $t WHERE nip=%s", $nip));
  $nama = $emp ? $emp->nama : '(NIP tidak terdaftar)';

  // rakit pesan
  $message = "Permintaan reset pasword HRIS SQ\nAkun (NIP): {$nip}\nNama: {$nama}";

  // panggil StarSender (form-encoded + header apikey)
  $args = [
    'headers' => [ 'apikey' => HRISSQ_SS_KEY ],
    'body'    => [ 'tujuan' => HRISSQ_SS_HC, 'message' => $message ],
    'timeout' => 15,
  ];
  $res = wp_remote_post(HRISSQ_SS_URL, $args);

  if (is_wp_error($res)) {
    wp_send_json(['ok'=>false,'msg'=>'Gagal mengirim, coba lagi.']);
  }
  $code = wp_remote_retrieve_response_code($res);
  $ok   = ($code === 200);
  wp_send_json(['ok'=>$ok, 'status'=>$code]);
}


  /** POST: nip, pw */
  public static function login(){
    self::check_nonce();

    $nip = sanitize_text_field($_POST['nip'] ?? '');
    $pw  = sanitize_text_field($_POST['pw']  ?? '');

    hrissq_log("Login attempt: NIP={$nip}");

    $res = Auth::login($nip, $pw);

    if ($res['ok']) {
      $res['redirect'] = trailingslashit(site_url('/' . HRISSQ_DASHBOARD_SLUG));
      hrissq_log("Login success: NIP={$nip}, redirect={$res['redirect']}");
    } else {
      hrissq_log("Login failed: NIP={$nip}, msg={$res['msg']}");
    }

    wp_send_json($res);
  }

  public static function logout(){
    Auth::logout();
    wp_send_json(['ok'=>true]);
  }

  /** POST multipart: nama_pelatihan, tahun, pembiayaan, kategori, sertifikat (file) */
  public static function submit_training(){
    self::check_nonce();

    $me = Auth::current_user();
    if (!$me) wp_send_json(['ok'=>false,'msg'=>'Unauthorized']);

    // validasi sederhana
    $nama       = sanitize_text_field($_POST['nama_pelatihan'] ?? '');
    $tahun      = intval($_POST['tahun'] ?? 0);
    $pembiayaan = sanitize_text_field($_POST['pembiayaan'] ?? '');
    $kategori   = sanitize_text_field($_POST['kategori'] ?? '');

    if (!$nama || !$tahun || !$pembiayaan || !$kategori) {
      wp_send_json(['ok'=>false,'msg'=>'Lengkapi semua field.']);
    }

    // (opsional) upload file sertifikat
    $file_url = null;
    if (!empty($_FILES['sertifikat']['name'])) {
      require_once ABSPATH.'wp-admin/includes/file.php';
      $allowed = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png'
      ];
      $overrides = [
        'test_form' => false,
        'mimes'     => $allowed,
        'unique_filename_callback' => function($dir,$name,$ext){
          return 'sertif-'.wp_generate_password(8,false).$ext;
        }
      ];
      $upload = wp_handle_upload($_FILES['sertifikat'], $overrides);
      if (!empty($upload['error'])) {
        wp_send_json(['ok'=>false,'msg'=>'Upload gagal: '.$upload['error']]);
      }
      $file_url = $upload['url'];
    }

    global $wpdb;
    $t = $wpdb->prefix.'hrissq_trainings';
    $wpdb->insert($t, [
      'user_id'        => intval($me->id),
      'nama_pelatihan' => $nama,
      'tahun'          => $tahun,
      'pembiayaan'     => $pembiayaan,
      'kategori'       => $kategori,
      'file_url'       => $file_url
    ], ['%d','%s','%d','%s','%s','%s']);

    // Kirim data ke Google Sheet
    $sheet_data = [
      'nip'                     => $me->nip,
      'nama'                    => $me->nama,
      'jabatan'                 => $me->jabatan,
      'unit_kerja'              => $me->unit,
      'nama_pelatihan'          => $nama,
      'tahun_penyelenggaraan'   => $tahun,
      'pembiayaan'              => $pembiayaan,
      'kategori'                => $kategori,
      'link_sertifikat'         => $file_url,
      'timestamp'               => current_time('mysql')
    ];

    $sheet_result = Trainings::submit_to_sheet($sheet_data);

    if ($sheet_result && empty($sheet_result['ok'])) {
      wp_send_json(['ok'=>false,'msg'=>$sheet_result['msg'] ?? 'Gagal mengirim data ke Google Sheet.']);
    }

    wp_send_json(['ok'=>true]);
  }
}
