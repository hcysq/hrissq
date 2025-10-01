<?php
namespace HRISSQ;

if (!defined('ABSPATH')) exit;

class Installer {
  public static function activate(){
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $t_emp = $wpdb->prefix.'hrissq_employees';
    $t_tr  = $wpdb->prefix.'hrissq_trainings';
    $t_pf  = $wpdb->prefix.'hrissq_profiles';

    $sql1 = "CREATE TABLE IF NOT EXISTS $t_emp (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      nip VARCHAR(32) NOT NULL UNIQUE,
      nama VARCHAR(191) NOT NULL,
      jabatan VARCHAR(191) DEFAULT '',
      unit VARCHAR(191) DEFAULT '',
      hp VARCHAR(32) DEFAULT '',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset;";

    $sql2 = "CREATE TABLE IF NOT EXISTS $t_tr (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      employee_id BIGINT UNSIGNED NOT NULL,
      nama_pelatihan VARCHAR(255) NOT NULL,
      tahun INT NOT NULL,
      pembiayaan VARCHAR(32) NOT NULL,
      kategori VARCHAR(32) NOT NULL,
      file_url TEXT DEFAULT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX (employee_id),
      CONSTRAINT fk_emp FOREIGN KEY (employee_id) REFERENCES $t_emp(id) ON DELETE CASCADE
    ) $charset;";

    $sql3 = "CREATE TABLE IF NOT EXISTS $t_pf (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      nip VARCHAR(32) NOT NULL UNIQUE,
      nama VARCHAR(191) NOT NULL,
      unit VARCHAR(191) DEFAULT '',
      jabatan VARCHAR(191) DEFAULT '',
      tempat_lahir VARCHAR(191) DEFAULT '',
      tanggal_lahir VARCHAR(32) DEFAULT '',
      alamat_ktp TEXT,
      desa VARCHAR(191) DEFAULT '',
      kecamatan VARCHAR(191) DEFAULT '',
      kota VARCHAR(191) DEFAULT '',
      kode_pos VARCHAR(16) DEFAULT '',
      email VARCHAR(191) DEFAULT '',
      hp VARCHAR(64) DEFAULT '',
      tmt VARCHAR(64) DEFAULT '',
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset;";

    require_once(ABSPATH.'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);

    // Jadwalkan import harian (kalau belum)
    if (!wp_next_scheduled('hrissq_profiles_cron')) {
      wp_schedule_event(time() + 600, 'daily', 'hrissq_profiles_cron');
    }
  }
}
