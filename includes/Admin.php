<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Admin {
  public static function menu(){
    add_management_page(
      'HCIS.YSQ Settings',
      'HCIS.YSQ Settings',
      'manage_options',
      'hcisysq-settings',
      [__CLASS__,'render']
    );
  }

  public static function render(){
    if (!current_user_can('manage_options')) return;

    // handle POST
    $msg = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      check_admin_referer('hcisysq_settings');

      // Profil Pegawai CSV
      if (isset($_POST['save_profiles']) || isset($_POST['import_profiles'])) {
        $url = esc_url_raw($_POST['profiles_csv_url'] ?? '');
        Profiles::set_csv_url($url);

        if (isset($_POST['import_profiles']) && $url) {
          $res = Profiles::import_from_csv($url);
          $msg .= $res['ok']
            ? "<strong>Import Profil:</strong> inserted {$res['inserted']}, updated {$res['updated']}.<br>"
            : "<strong>Import Profil GAGAL:</strong> " . esc_html($res['msg']) . "<br>";
        }
      }

      // Users Google Sheet
      if (isset($_POST['save_users']) || isset($_POST['import_users'])) {
        $sheet_id = sanitize_text_field($_POST['users_sheet_id'] ?? '');
        $tab_name = sanitize_text_field($_POST['users_tab_name'] ?? 'User');
        Users::set_sheet_config($sheet_id, $tab_name);

        if (isset($_POST['import_users']) && $sheet_id) {
          $url = "https://docs.google.com/spreadsheets/d/{$sheet_id}/export?format=csv&gid=0";
          $res = Users::import_from_csv($url);
          $msg .= $res['ok']
            ? "<strong>Import Users:</strong> inserted {$res['inserted']}, updated {$res['updated']}.<br>"
            : "<strong>Import Users GAGAL:</strong> " . esc_html($res['msg']) . "<br>";
        }
      }

      // Training Sheet Config
      if (isset($_POST['save_training'])) {
        $sheet_id = sanitize_text_field($_POST['training_sheet_id'] ?? '');
        $tab_name = sanitize_text_field($_POST['training_tab_name'] ?? 'Data');
        $webapp_url = esc_url_raw($_POST['training_webapp_url'] ?? '');
        $drive_folder = sanitize_text_field($_POST['training_drive_folder_id'] ?? '');

        Trainings::set_sheet_config($sheet_id, $tab_name);
        Trainings::set_webapp_url($webapp_url);
        if ($drive_folder) {
          Trainings::set_drive_folder_id($drive_folder);
        } else {
          delete_option(Trainings::OPT_TRAINING_DRIVE_FOLDER_ID);
        }

        $msg .= "<strong>Training config saved.</strong><br>";
      }
    }

    $profiles_csv = esc_url(Profiles::get_csv_url());
    $users_sheet_id = esc_attr(Users::get_sheet_id());
    $users_tab_name = esc_attr(Users::get_tab_name());
    $training_sheet_id = esc_attr(Trainings::get_sheet_id());
    $training_tab_name = esc_attr(Trainings::get_tab_name());
    $training_drive_folder = esc_attr(Trainings::get_drive_folder_id());
    $training_webapp_url = esc_url(Trainings::get_webapp_url());
    ?>
    <div class="wrap">
      <h1>HCIS.YSQ • Settings & Import</h1>
      <?php if ($msg): ?>
        <div class="notice notice-info"><p><?= $msg ?></p></div>
      <?php endif; ?>

      <!-- PROFIL PEGAWAI (CSV) -->
      <h2>1. Profil Pegawai (CSV)</h2>
      <form method="post">
        <?php wp_nonce_field('hcisysq_settings'); ?>
        <table class="form-table">
          <tr>
            <th scope="row"><label for="profiles_csv_url">CSV URL</label></th>
            <td>
              <input type="url" id="profiles_csv_url" name="profiles_csv_url" class="regular-text code" style="width: 600px"
                     value="<?= $profiles_csv ?>" placeholder="https://docs.google.com/spreadsheets/d/e/…/pub?gid=…&single=true&output=csv">
              <p class="description">URL: <code>https://docs.google.com/spreadsheets/d/e/2PACX-1vTlR2VUOcQfXRjZN4fNC-o4CvPTgd-ZlReqj_pfEfYGr5A87Wh6K2zU16iexLnfIh5djkrXzmVlk1w-/pub?gid=0&single=true&output=csv</code></p>
            </td>
          </tr>
        </table>
        <p class="submit">
          <button type="submit" name="save_profiles" class="button button-primary">Simpan</button>
          <button type="submit" name="import_profiles" class="button">Import Sekarang</button>
        </p>
      </form>
      <hr>

      <!-- USERS (Google Sheet) -->
      <h2>2. Users (Google Sheet)</h2>
      <form method="post">
        <?php wp_nonce_field('hcisysq_settings'); ?>
        <table class="form-table">
          <tr>
            <th scope="row"><label for="users_sheet_id">Sheet ID</label></th>
            <td>
              <input type="text" id="users_sheet_id" name="users_sheet_id" class="regular-text" style="width: 600px"
                     value="<?= $users_sheet_id ?>" placeholder="14Uf7pjsFVURLmL5NWXlWhYvoILrwdiW11y3sVOLrLt4">
              <p class="description">Sheet ID: <code>14Uf7pjsFVURLmL5NWXlWhYvoILrwdiW11y3sVOLrLt4</code></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="users_tab_name">Tab Name</label></th>
            <td>
              <input type="text" id="users_tab_name" name="users_tab_name" class="regular-text"
                     value="<?= $users_tab_name ?>" placeholder="User">
            </td>
          </tr>
        </table>
        <p class="submit">
          <button type="submit" name="save_users" class="button button-primary">Simpan</button>
          <button type="submit" name="import_users" class="button">Import Sekarang</button>
        </p>
      </form>
      <hr>

      <!-- TRAINING (Google Sheet) -->
      <h2>3. Training Form → Google Sheet</h2>
      <form method="post">
        <?php wp_nonce_field('hcisysq_settings'); ?>
        <table class="form-table">
          <tr>
            <th scope="row"><label for="training_sheet_id">Sheet ID</label></th>
            <td>
              <input type="text" id="training_sheet_id" name="training_sheet_id" class="regular-text" style="width: 600px"
                     value="<?= $training_sheet_id ?>" placeholder="1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ">
              <p class="description">Sheet ID: <code>1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ</code></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="training_tab_name">Tab Name</label></th>
            <td>
              <input type="text" id="training_tab_name" name="training_tab_name" class="regular-text"
                     value="<?= $training_tab_name ?>" placeholder="Data">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="training_drive_folder_id">Drive Folder ID</label></th>
            <td>
              <input type="text" id="training_drive_folder_id" name="training_drive_folder_id" class="regular-text" style="width: 600px"
                     value="<?= $training_drive_folder ?>" placeholder="1Wpf6k5G21Zb4kAILYDL7jfCjyKZd55zp">
              <p class="description">File sertifikat akan disimpan di folder Google Drive ini (sub-folder otomatis: <code>&lt;NIP&gt;-&lt;Nama&gt;</code>).</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="training_webapp_url">Web App URL</label></th>
            <td>
              <input type="url" id="training_webapp_url" name="training_webapp_url" class="regular-text code" style="width: 600px"
                     value="<?= $training_webapp_url ?>" placeholder="https://script.google.com/macros/s/…/exec">
              <p class="description">Deploy Google Apps Script sebagai Web App, lalu paste URL-nya di sini.</p>
            </td>
          </tr>
        </table>
        <p class="submit">
          <button type="submit" name="save_training" class="button button-primary">Simpan</button>
        </p>
      </form>

      <hr>
      <p><strong>Tips:</strong> Import otomatis dijalankan harian via WP-Cron (Profil & Users).</p>
    </div>
    <?php
  }
}
