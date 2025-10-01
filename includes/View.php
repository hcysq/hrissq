<?php
namespace HRISSQ;

if (!defined('ABSPATH')) exit;

class View {

  /* ========== LOGIN PAGE ========== */
  public static function login(){
    wp_enqueue_style('hrissq');
    wp_enqueue_script('hrissq');
    ob_start(); ?>
    <div class="hrissq-auth-wrap">
      <div class="auth-card">
        <div class="auth-header">
          <h2>Masuk ke Akun Guru/Pegawai</h2>
        </div>

        <form id="hrissq-login-form" class="auth-form">
          <label>Akun <span class="req">*</span></label>
          <input type="text" name="nip" placeholder="Masukkan NIP" autocomplete="username" required>

          <label>Pasword <span class="req">*</span></label>
          <div class="pw-row">
            <input id="hrissq-pw" type="password" name="pw" placeholder="No HP (62812xxxxxxx)" autocomplete="current-password" required>
            <button type="button" id="hrissq-eye" class="eye">lihat</button>
          </div>

          <button type="submit" class="btn-primary">Masuk</button>

          <button type="button" id="hrissq-forgot" class="link-forgot">Lupa password?</button>
          <div class="msg" aria-live="polite"></div>
        </form>
      </div>
    </div>

    <!-- Modal Lupa Password -->
    <div id="hrissq-modal" class="modal-backdrop" style="display:none;">
      <div class="modal">
        <h3>Lupa Password</h3>
        <p>Masukkan NIP Anda. Kami akan mengirim permintaan ke Admin HCM.</p>
        <label>NIP</label>
        <input id="hrissq-nip-forgot" type="text" placeholder="2020xxxxxxxxxxxx">
        <div class="modal-actions">
          <button type="button" class="btn-light" id="hrissq-cancel">Batal</button>
          <button type="button" class="btn-primary" id="hrissq-send">Kirim</button>
        </div>
        <div id="hrissq-forgot-msg" class="modal-msg"></div>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  /* ========== DASHBOARD PAGE ========== */
  public static function dashboard(){
    $me = Auth::current_user();
    if (!$me) { wp_safe_redirect(site_url('/'.HRISSQ_LOGIN_SLUG)); exit; }

    $resolve = function(array $keys) use ($me){
      foreach ($keys as $key) {
        if (!isset($me->$key)) continue;
        $value = $me->$key;
        if (is_scalar($value)) {
          $value = trim((string)$value);
        } else {
          $value = '';
        }
        if ($value !== '') return $value;
      }
      return '';
    };

    $unit    = $resolve(['unit','unit_kerja','unitkerja','unitkerja_nama']);
    $jabatan = $resolve(['jabatan','posisi','position']);
    $hp      = $resolve(['no_hp','hp','telepon','phone']);
    $email   = $resolve(['email','mail']);
    $tempat  = $resolve(['tempat_lahir','tempatlahir','birth_place']);
    $tanggal = $resolve(['tanggal_lahir','tgl_lahir','birth_date']);
    $tmt     = $resolve(['tmt','tmt_mulai','tanggal_mulai']);

    $alamatUtama = $resolve(['alamat','alamat_ktp','alamat_domisili','alamatdomisili','alamat_rumah']);
    $alamatParts = array_filter([
      $alamatUtama,
      $resolve(['desa','kelurahan','desa_kelurahan']),
      $resolve(['kecamatan']),
      $resolve(['kota','kabupaten','kota_kabupaten']),
      $resolve(['kode_pos','kodepos'])
    ], function($val){ return $val !== ''; });
    $alamatFull = $alamatParts ? implode(', ', $alamatParts) : '';

    $profileRows = [
      ['label' => 'Nama', 'value' => isset($me->nama) ? trim((string)$me->nama) : ''],
      ['label' => 'NIP', 'value' => isset($me->nip) ? trim((string)$me->nip) : ''],
      ['label' => 'Tempat & Tanggal Lahir', 'value' => trim($tempat . ($tempat && $tanggal ? ', ' : '') . $tanggal)],
      ['label' => 'Alamat', 'value' => $alamatFull],
      ['label' => 'TMT', 'value' => $tmt],
    ];

    $contactLines = array_values(array_filter([
      $hp ? 'HP: '.$hp : '',
      $email ? 'Email: '.$email : ''
    ], function($val){ return $val !== ''; }));

    wp_enqueue_style('hrissq');
    wp_enqueue_script('hrissq');

    ob_start(); ?>
    <div class="hrissq-dashboard" id="hrissq-dashboard">

      <!-- Sidebar -->
      <aside class="hrissq-sidebar" id="hrissq-sidebar" aria-label="Navigasi utama">
        <div class="hrissq-sidebar-header">
          <span class="hrissq-sidebar-logo">SQ Pegawai</span>
          <button type="button" class="hrissq-icon-button hrissq-sidebar-close" id="hrissq-sidebar-close" aria-label="Tutup menu navigasi">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <nav class="hrissq-sidebar-nav">
          <a class="is-active" href="<?= esc_url(site_url('/'.HRISSQ_DASHBOARD_SLUG)) ?>">Dashboard</a>
          <a href="#">Profil</a>
          <a href="#">Slip Gaji</a>
          <a href="#">Rekap Absensi</a>
          <a href="#">Riwayat Kepegawaian</a>
          <a href="#">Cuti &amp; Izin</a>
          <a href="#">Penilaian Kinerja</a>
          <a href="#">Tugas &amp; Komunikasi</a>
          <a href="#">Administrasi Lain</a>
          <hr>
          <a href="#">Panduan</a>
          <a href="#">Support</a>
        </nav>
        <div class="hrissq-sidebar-meta">
          <span>Versi <?= esc_html(HRISSQ_VER) ?></span>
        </div>
      </aside>

      <div class="hrissq-sidebar-overlay" id="hrissq-sidebar-overlay" aria-hidden="true"></div>

      <!-- Main -->
      <main class="hrissq-main">
        <header class="hrissq-topbar">
          <div class="hrissq-topbar-left">
            <button type="button" class="hrissq-icon-button hrissq-menu-toggle" id="hrissq-sidebar-toggle" aria-label="Buka menu navigasi" aria-expanded="true">
              <span></span>
              <span></span>
              <span></span>
            </button>
            <div>
              <h1 class="hrissq-page-title">Dashboard Pegawai</h1>
              <p class="hrissq-page-subtitle">Ringkasan informasi dan tindakan penting untuk akun Anda.</p>
            </div>
          </div>
          <div class="hrissq-user">
            <div class="hrissq-user-meta">
              <span class="hrissq-user-name"><?= esc_html($me->nama) ?></span>
              <span class="hrissq-user-role">NIP: <?= esc_html($me->nip ?? '-') ?></span>
            </div>
            <button type="button" class="btn-light" id="hrissq-logout">Keluar</button>
          </div>
        </header>

        <div class="hrissq-main-body">
          <section class="hrissq-card-grid hrissq-card-grid--3">
            <article class="hrissq-card hrissq-card-highlight">
              <h3 class="hrissq-card-title">Status Data</h3>
              <p>Butuh pembaruan. Lengkapi riwayat pelatihan Anda untuk memastikan data tetap mutakhir.</p>
              <a class="hrissq-card-link" href="<?= esc_url(site_url('/'.HRISSQ_FORM_SLUG)) ?>">Isi Form Pelatihan</a>
            </article>

            <article class="hrissq-card">
              <h3 class="hrissq-card-title">Unit &amp; Jabatan</h3>
              <dl class="hrissq-meta-list">
                <div>
                  <dt>Unit</dt>
              <dd><?= esc_html($unit !== '' ? $unit : ($me->unit ?? '-')) ?></dd>
            </div>
            <div>
              <dt>Jabatan</dt>
              <dd><?= esc_html($jabatan !== '' ? $jabatan : ($me->jabatan ?? '-')) ?></dd>
            </div>
          </dl>
        </article>

        <article class="hrissq-card">
          <h3 class="hrissq-card-title">Kontak Utama</h3>
          <p>
            <?php if ($contactLines): ?>
              <?php foreach ($contactLines as $idx => $line): ?>
                <?= esc_html($line) ?><?php if ($idx < count($contactLines) - 1): ?><br><?php endif; ?>
              <?php endforeach; ?>
            <?php else: ?>
              <span>-</span>
            <?php endif; ?>
          </p>
        </article>
      </section>

      <section class="hrissq-card-grid hrissq-card-grid--2">
        <article class="hrissq-card">
          <h3 class="hrissq-card-title">Profil Ringkas</h3>
          <dl class="hrissq-meta-list">
            <?php foreach ($profileRows as $row): ?>
              <div>
                <dt><?= esc_html($row['label']) ?></dt>
                <dd><?= esc_html($row['value'] !== '' ? $row['value'] : '-') ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
        </article>

            <article class="hrissq-card">
              <h3 class="hrissq-card-title">Pengumuman</h3>
              <ul class="hrissq-bullet-list">
                <li><strong>Pembaruan Data Pegawai</strong> – Segera isi form profil terbaru.</li>
                <li><strong>SPMB 2026/2027</strong> – Pendaftaran telah dibuka.</li>
                <li><strong>Agenda Internal</strong> – Training Sabtu pekan ini.</li>
              </ul>
            </article>
          </section>
        </div>
      </main>
    </div>

    <!-- Modal Auto-Logout (Idle) -->
    <div id="hrq-idle-backdrop" class="modal-backdrop" style="display:none;">
      <div class="modal">
        <h3>Sesi Akan Berakhir</h3>
        <p>Anda tidak aktif cukup lama. Otomatis keluar dalam
          <b><span id="hrq-idle-count">30</span> detik</b>.
        </p>
        <div class="modal-actions">
          <button id="hrq-idle-stay" class="btn-light">Batalkan</button>
          <button id="hrq-idle-exit" class="btn-primary">Keluar Sekarang</button>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  /* ========== FORM PELATIHAN ========== */
  public static function form(){
    $me = Auth::current_user();
    if (!$me) { wp_safe_redirect(site_url('/'.HRISSQ_LOGIN_SLUG)); exit; }

    wp_enqueue_style('hrissq');
    wp_enqueue_script('hrissq');

    ob_start(); ?>
    <div class="hrissq-form-wrap">
      <h2>Form Riwayat Pelatihan</h2>
      <p>Lengkapi data pelatihan yang telah Anda ikuti.</p>

      <form id="hrissq-training-form" enctype="multipart/form-data" class="training-form">
        <div class="form-group">
          <label>Nama Pelatihan <span class="req">*</span></label>
          <input type="text" name="nama_pelatihan" placeholder="Contoh: Workshop Laravel" required>
        </div>

        <div class="form-group">
          <label>Tahun <span class="req">*</span></label>
          <input type="number" name="tahun" placeholder="2024" min="1990" max="2099" required>
        </div>

        <div class="form-group">
          <label>Pembiayaan <span class="req">*</span></label>
          <select name="pembiayaan" required>
            <option value="">Pilih Pembiayaan</option>
            <option value="mandiri">Mandiri</option>
            <option value="yayasan">Yayasan</option>
          </select>
        </div>

        <div class="form-group">
          <label>Kategori <span class="req">*</span></label>
          <select name="kategori" required>
            <option value="">Pilih Kategori</option>
            <option value="hard">Hard Skill</option>
            <option value="soft">Soft Skill</option>
          </select>
        </div>

        <div class="form-group">
          <label>Upload Sertifikat (opsional)</label>
          <input type="file" name="sertifikat" accept=".pdf,.jpg,.jpeg,.png">
          <small>Format: PDF, JPG, PNG (max 5MB)</small>
        </div>

        <button type="submit" class="btn-primary">Simpan</button>
        <a href="<?= esc_url(site_url('/'.HRISSQ_DASHBOARD_SLUG)) ?>" class="btn-light">Batal</a>
      </form>
    </div>
    <?php
    return ob_get_clean();
  }
}
