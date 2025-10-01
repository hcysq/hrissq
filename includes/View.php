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
          <h2>Hubungi Kami Sekarang</h2>
          <p>Masuk dengan NIP dan password untuk mengakses dashboard pegawai.</p>
        </div>

        <form id="hrissq-login-form" class="auth-form">
          <label>NIP <span class="req">*</span></label>
          <input type="text" name="nip" placeholder="2020xxxxxxxxxxxx" autocomplete="username" required>

          <label>Password <span class="req">*</span></label>
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

    // ambil profil mirror (jika tersedia)
    $prof = null;
    if (class_exists('\\HRISSQ\\Profiles') && method_exists('\\HRISSQ\\Profiles','get_by_nip')) {
      $prof = \HRISSQ\Profiles::get_by_nip($me->nip);
    }

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
                  <dd><?= esc_html($prof->unit ?? $me->unit ?? '-') ?></dd>
                </div>
                <div>
                  <dt>Jabatan</dt>
                  <dd><?= esc_html($prof->jabatan ?? $me->jabatan ?? '-') ?></dd>
                </div>
              </dl>
            </article>

            <article class="hrissq-card">
              <h3 class="hrissq-card-title">Kontak Utama</h3>
              <p>
                HP: <?= esc_html($prof->hp ?? $me->hp ?? '-') ?><br>
                Email: <?= esc_html($prof->email ?? $me->email ?? '-') ?>
              </p>
            </article>
          </section>

          <section class="hrissq-card-grid hrissq-card-grid--2">
            <article class="hrissq-card">
              <h3 class="hrissq-card-title">Profil Ringkas</h3>
              <?php if ($prof): ?>
                <dl class="hrissq-meta-list">
                  <div>
                    <dt>Nama</dt>
                    <dd><?= esc_html($prof->nama) ?></dd>
                  </div>
                  <div>
                    <dt>NIP</dt>
                    <dd><?= esc_html($prof->nip) ?></dd>
                  </div>
                  <div>
                    <dt>Tempat &amp; Tanggal Lahir</dt>
                    <dd><?= esc_html(($prof->tempat_lahir ?: '-').', '.($prof->tanggal_lahir ?: '-')) ?></dd>
                  </div>
                  <div>
                    <dt>Alamat</dt>
                    <dd><?= esc_html($prof->alamat_ktp ?: '-') ?>, <?= esc_html($prof->desa ?: '-') ?>, <?= esc_html($prof->kecamatan ?: '-') ?>, <?= esc_html($prof->kota ?: '-') ?> <?= esc_html($prof->kode_pos ?: '') ?></dd>
                  </div>
                  <div>
                    <dt>TMT</dt>
                    <dd><?= esc_html($prof->tmt ?: '-') ?></dd>
                  </div>
                </dl>
              <?php else: ?>
                <p>Belum ada data profil untuk NIP ini. Silakan jalankan import CSV melalui Tools → HRISSQ Import.</p>
              <?php endif; ?>
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
