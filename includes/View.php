<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class View {

  /* ========== LOGIN PAGE ========== */
  public static function login(){
    wp_enqueue_style('hcisysq');
    wp_enqueue_script('hcisysq');
    ob_start(); ?>
    <div class="hcisysq-auth-wrap">
      <div class="auth-card">
        <div class="auth-header">
          <h2>Masuk ke Akun Guru/Pegawai</h2>
        </div>

        <form id="hcisysq-login-form" class="auth-form">
          <label for="hcisysq-nip">Akun <span class="req">*</span></label>
          <input id="hcisysq-nip" type="text" name="nip" placeholder="Masukkan NIP" autocomplete="username" required>

          <label for="hcisysq-pw">Pasword <span class="req">*</span></label>
          <div class="pw-row">
            <input id="hcisysq-pw" type="password" name="pw" placeholder="Gunakan No HP" autocomplete="current-password" required>
            <button type="button" id="hcisysq-eye" class="eye">lihat</button>
          </div>

          <button type="submit" class="btn-primary">Masuk</button>
          <button type="button" id="hcisysq-forgot" class="link-forgot">Lupa pasword?</button>
          <div class="msg" aria-live="polite"></div>
        </form>
      </div>
    <!-- Modal Lupa Password -->
    <div id="hcisysq-modal" class="modal-backdrop" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="hcisysq-forgot-title">
      <div class="modal">
        <button type="button" class="modal-close" id="hcisysq-close-modal" aria-label="Tutup">×</button>
        <h3 id="hcisysq-forgot-title">Lupa Pasword</h3>
        <p>Masukkan Akun (NIP) Anda. Kami akan mengirim permintaan ke Admin HCM.</p>
        <label>Akun (NIP)</label>
        <input id="hcisysq-nip-forgot" type="text" placeholder="Masukkan NIP">
        <div class="modal-actions">
          <button type="button" class="btn-light" id="hcisysq-cancel">Batal</button>
          <button type="button" class="btn-primary" id="hcisysq-send">Kirim</button>
        </div>
        <div id="hcisysq-forgot-msg" class="modal-msg" aria-live="polite"></div>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  /* ========== DASHBOARD PAGE ========== */
  public static function dashboard(){
    $me = Auth::current_user();
    if (!$me) { wp_safe_redirect(site_url('/'.HCISYSQ_LOGIN_SLUG)); exit; }
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
    // Format TMT -> dd mmmm yyyy
    $tmtFormatted = '';
    if ($tmt) {
      try {
        $dt = new \DateTimeImmutable($tmt);
        // Format sesuai lokal Indonesia
        $formatter = new \IntlDateFormatter(
          'id_ID',
          \IntlDateFormatter::LONG,
          \IntlDateFormatter::NONE,
          'Asia/Jakarta',
          \IntlDateFormatter::GREGORIAN,
          'd MMMM yyyy'
        );
        $tmtFormatted = $formatter->format($dt);
      } catch (\Exception $e) {
        $tmtFormatted = $tmt; // fallback kalau parsing gagal
      }
    }

    
 $nik     = $resolve(['nik','no_ktp','ktp','nik_ktp','no_ktp_kk']);

       // Hitung masa kerja dari TMT -> "X tahun Y bulan"
    $masaKerja = '';
    if ($tmt) {
      try {
        $d1 = new \DateTimeImmutable($tmt);
        $d2 = new \DateTimeImmutable('now');
        $diff = $d1->diff($d2);

        $y = (int)$diff->y;
        $m = (int)$diff->m;

        if ($y > 0 && $m > 0) {
          $masaKerja = $y.' tahun '.$m.' bulan';
        } elseif ($y > 0) {
          $masaKerja = $y.' tahun';
        } elseif ($m > 0) {
          $masaKerja = $m.' bulan';
        } else {
          $masaKerja = 'Kurang dari 1 bulan';
        }

      } catch (\Exception $e) {
        $masaKerja = '';
      }
    }

    $alamatUtama = $resolve(['alamat','alamat_ktp','alamat_domisili','alamatdomisili','alamat_rumah']);
    $alamatParts = array_filter([
      $alamatUtama,
      $resolve(['desa','kelurahan','desa_kelurahan']),
      $resolve(['kecamatan']),
      $resolve(['kota','kabupaten','kota_kabupaten']),
      $resolve(['kode_pos','kodepos'])
    ], function($val){ return $val !== ''; });
    $alamatFull = $alamatParts ? implode(', ', $alamatParts) : '';

       // Kartu kiri: Profil Ringkas
    $profilRingkasRows = [
      ['label' => 'Nama', 'value' => isset($me->nama) ? trim((string)$me->nama) : ''],
      ['label' => 'NIK',  'value' => $nik],
      ['label' => 'Tempat & Tanggal Lahir', 'value' => trim($tempat . ($tempat && $tanggal ? ', ' : '') . $tanggal)],
      ['label' => 'Alamat', 'value' => $alamatFull],
      ['label' => 'HP', 'value' => $hp],
      ['label' => 'Email', 'value' => $email],
    ];

    // Kartu kanan: Data Kepegawaian
    $kepegawaianRows = [
      ['label' => 'NIP',        'value' => isset($me->nip) ? trim((string)$me->nip) : ''],
      ['label' => 'Jabatan',    'value' => $jabatan !== '' ? $jabatan : ($me->jabatan ?? '')],
      ['label' => 'Unit Kerja', 'value' => $unit   !== '' ? $unit   : ($me->unit   ?? '')],
      ['label' => 'TMT',        'value' => $tmtFormatted],
      ['label' => 'Masa Kerja', 'value' => $masaKerja],
    ];

    wp_enqueue_style('hcisysq');
    wp_enqueue_script('hcisysq');

    $trainingFormBase = 'https://script.google.com/macros/s/AKfycbxReKFiKsW1BtDZufNNi4sCuazw5jjzUQ9iHDPylmm9ARuAudqsB6CmSI_2vNpng3uP/exec';
    $trainingLink = sprintf(
      '%s?nip=%s&nama=%s',
      $trainingFormBase,
      urlencode((string)($me->nip ?? '')),
      urlencode((string)($me->nama ?? ''))
    );

    ob_start(); ?>
    <div class="hcisysq-dashboard" id="hcisysq-dashboard">

      <!-- Sidebar -->
      <aside class="hcisysq-sidebar" id="hcisysq-sidebar" aria-label="Navigasi utama">
        <div class="hcisysq-sidebar-header">
          <span class="hcisysq-sidebar-logo">SQ Pegawai</span>
          <button type="button" class="hcisysq-icon-button hcisysq-sidebar-close" id="hcisysq-sidebar-close" aria-label="Tutup menu navigasi">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <nav class="hcisysq-sidebar-nav">
          <a class="is-active" href="<?= esc_url(site_url('/'.HCISYSQ_DASHBOARD_SLUG)) ?>">Dashboard</a>
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
        <div class="hcisysq-sidebar-meta">
          <span>Versi <?= esc_html(HCISYSQ_VER) ?></span>
        </div>
      </aside>

      <div class="hcisysq-sidebar-overlay" id="hcisysq-sidebar-overlay" aria-hidden="true"></div>

      <!-- Main -->
      <main class="hcisysq-main">
        <header class="hcisysq-topbar">
          <div class="hcisysq-topbar-left">
            <button type="button" class="hcisysq-icon-button hcisysq-menu-toggle" id="hcisysq-sidebar-toggle" aria-label="Buka menu navigasi" aria-expanded="true">
              <span></span>
              <span></span>
              <span></span>
            </button>
            <div>
              <h1 class="hcisysq-page-title">Dashboard Pegawai</h1>
              <p class="hcisysq-page-subtitle">Ringkasan informasi kepegawaian</p>
            </div>
          </div>
          <div class="hcisysq-user">
            <div class="hcisysq-user-meta">
              <span class="hcisysq-user-name"><?= esc_html($me->nama) ?></span>
              <span class="hcisysq-user-role">NIP: <?= esc_html($me->nip ?? '-') ?></span>
            </div>
            <button type="button" class="btn-light" id="hcisysq-logout">Keluar</button>
          </div>
        </header>

        <div class="hcisysq-main-body">
          <section class="hcisysq-card-grid hcisysq-card-grid--2">
            <!-- Kartu 1: Profil Ringkas -->
            <article class="hcisysq-card">
              <h3 class="hcisysq-card-title">Profil Ringkas</h3>
              <dl class="hcisysq-meta-list">
                <?php foreach ($profilRingkasRows as $row): ?>
                  <div>
                    <dt><?= esc_html($row['label']) ?></dt>
                    <dd><?= esc_html($row['value'] !== '' ? $row['value'] : '-') ?></dd>
                  </div>
                <?php endforeach; ?>
              </dl>
            </article>

            <!-- Kartu 2: Data Kepegawaian -->
            <article class="hcisysq-card">
              <h3 class="hcisysq-card-title">Data Kepegawaian</h3>
              <dl class="hcisysq-meta-list">
                <?php foreach ($kepegawaianRows as $row): ?>
                  <div>
                    <dt><?= esc_html($row['label']) ?></dt>
                    <dd><?= esc_html($row['value'] !== '' ? $row['value'] : '-') ?></dd>
                  </div>
                <?php endforeach; ?>
              </dl>
            </article>
          </section>


            <article class="hcisysq-card">
              <h3 class="hcisysq-card-title">Kontak Utama</h3>
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

            <article class="hcisysq-card">
              <h3 class="hcisysq-card-title">Profil Ringkas</h3>
              <dl class="hcisysq-meta-list">
                <?php foreach ($profileRows as $row): ?>
                  <div>
                    <dt><?= esc_html($row['label']) ?></dt>
                    <dd><?= esc_html($row['value'] !== '' ? $row['value'] : '-') ?></dd>
                  </div>
                <?php endforeach; ?>
              </dl>
            </article>
          </section>

          <section class="hcisysq-card-grid hcisysq-card-grid--1">
            <article class="hcisysq-card">
              <h3 class="hcisysq-card-title">Pengumuman</h3>
              <ul class="hcisysq-bullet-list">
                <li><strong>Pembaruan Data Pegawai</strong> – <a href="<?= esc_url($trainingLink) ?>" target="_blank" rel="noopener">Isi form pelatihan terbaru</a>.</li>
                <li><strong>SPMB 2026/2027</strong> – <a href="https://ppdb.sabilulquran.or.id" target="_blank" rel="noopener">Pendaftaran telah dibuka</a>.</li>
                <li><strong>Ikuti Sabilul Qur'an di Instagram</strong> – <a href="https://instagram.com/sabilulquran" target="_blank" rel="noopener">@sabilulquran</a>.</li>
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
    </div>
    <?php
    return ob_get_clean();
  }

  /* ========== FORM PELATIHAN ========== */
  public static function form(){
    $me = Auth::current_user();
    if (!$me) { wp_safe_redirect(site_url('/'.HCISYSQ_LOGIN_SLUG)); exit; }

    wp_enqueue_style('hcisysq');
    wp_enqueue_script('hcisysq');

    ob_start(); ?>
    <div id="hcisysq-app" class="hcisysq-app">
      <div class="hcisysq-form-wrap">
        <h2>Form Riwayat Pelatihan</h2>
        <p>Lengkapi data pelatihan yang telah Anda ikuti.</p>

        <form id="hcisysq-training-form" enctype="multipart/form-data" class="training-form">
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
          <a href="<?= esc_url(site_url('/'.HCISYSQ_DASHBOARD_SLUG)) ?>" class="btn-light">Batal</a>
        </form>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }
}
