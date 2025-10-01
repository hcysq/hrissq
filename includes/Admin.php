<?php
namespace HRISSQ;

if (!defined('ABSPATH')) exit;

class Admin {
  public static function menu(){
    add_management_page(
      'HRISSQ Profiles Import',
      'HRISSQ Import',
      'manage_options',
      'hrissq-profiles',
      [__CLASS__,'render']
    );
  }

  public static function render(){
    if (!current_user_can('manage_options')) return;

    // handle POST
    $msg = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      check_admin_referer('hrissq_profiles');

      $url = esc_url_raw($_POST['csv_url'] ?? '');
      Profiles::set_csv_url($url);

      if (isset($_POST['do_import']) && $url) {
        $res = Profiles::import_from_csv($url);
        $msg = $res['ok']
          ? "Import OK: inserted {$res['inserted']}, updated {$res['updated']}."
          : "Import GAGAL: " . esc_html($res['msg']);
      }
    }

    $csv = esc_url(Profiles::get_csv_url());
    ?>
    <div class="wrap">
      <h1>HRISSQ • Import Profil dari Google Sheet (CSV)</h1>
      <?php if ($msg): ?>
        <div class="notice notice-info"><p><?= $msg ?></p></div>
      <?php endif; ?>
      <form method="post">
        <?php wp_nonce_field('hrissq_profiles'); ?>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="csv_url">CSV URL</label></th>
            <td>
              <input type="url" id="csv_url" name="csv_url" class="regular-text code" style="width: 600px"
                     value="<?= $csv ?>" placeholder="https://docs.google.com/spreadsheets/d/e/…/pub?gid=…&single=true&output=csv">
              <p class="description">Gunakan “File → Share → Publish to the Web → CSV” dari Google Sheets.</p>
            </td>
          </tr>
        </table>
        <p class="submit">
          <button type="submit" name="save" class="button button-primary">Simpan</button>
          <button type="submit" name="do_import" class="button">Import sekarang</button>
        </p>
      </form>
      <p>Tips: setelah stabil, kita jadwalkan import otomatis harian via WP-Cron.</p>
    </div>
    <?php
  }
}
