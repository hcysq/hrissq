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
        <h2>Hubungi Kami<br> Sekarang</h2>

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
    <div class="hrissq-dashboard">

      <!-- Sidebar -->
      <aside class="sidebar">
        <div class="logo"><span>SQ Pegawai</span></div>
        <nav>
          <a href="<?= esc_url(site_url('/'.HRISSQ_DASHBOARD_SLUG)) ?>">Dashboard</a>
          <a href="#">Profil</a>
          <a href="#">Slip Gaji</a>
          <a href="#">Rekap Absensi</a>
          <a href="#">Riwayat Kepegawaian</a>
          <a href="#">Cuti & Izin</a>
          <a href="#">Penilaian Kinerja</a>
          <a href="#">Tugas & Komunikasi</a>
          <a href="#">Administrasi Lain</a>
          <hr>
          <a href="#">Panduan</a>
          <a href="#">Support</a>
        </nav>
      </aside>

      <!-- Main -->
      <main class="content">
        <!-- Header -->
        <header class="topbar">
          <h2>Dashboard Pegawai</h2>
          <div class="user-menu">
            <span class="user-name"><?= esc_html($me->nama) ?></span>
            <div class="dropdown">
              <a href="#">Perbarui Profil</a>
              <a href="#">Ganti Password</a>
              <a href="#" id="hrissq-logout">Keluar</a>
            </div>
          </div>
        </header>

        <!-- Cards -->
        <section class="cards">
          <div class="card">
            <h3>Status Data</h3>
            <p>Butuh Pembaruan.<br>Lengkapi riwayat pelatihan Anda.</p>
            <a href="<?= esc_url(site_url('/'.HRISSQ_FORM_SLUG)) ?>">Isi Form Pelatihan →</a>
          </div>

          <div class="card">
            <h3>Unit & Jabatan</h3>
            <p>
              <?= esc_html($prof->unit ?? $me->unit ?? '-') ?><br>
              Jabatan: <?= esc_html($prof->jabatan ?? $me->jabatan ?? '-') ?>
            </p>
          </div>

          <div class="card">
            <h3>Profil Ringkas</h3>
            <?php if ($prof): ?>
              <p>
                <b><?= esc_html($prof->nama) ?></b><br>
                NIP: <?= esc_html($prof->nip) ?><br>
                TTL: <?= esc_html(($prof->tempat_lahir ?: '-').', '.($prof->tanggal_lahir ?: '-')) ?><br>
                HP: <?= esc_html($prof->hp ?: '-') ?> • Email: <?= esc_html($prof->email ?: '-') ?><br>
                Alamat KTP: <?= esc_html($prof->alamat_ktp ?: '-') ?>,
                <?= esc_html($prof->desa ?: '-') ?>, <?= esc_html($prof->kecamatan ?: '-') ?>,
                <?= esc_html($prof->kota ?: '-') ?> <?= esc_html($prof->kode_pos ?: '') ?><br>
                TMT: <?= esc_html($prof->tmt ?: '-') ?>
              </p>
            <?php else: ?>
              <p>Belum ada data profil untuk NIP ini. (Coba jalankan import CSV di Tools → HRISSQ Import)</p>
            <?php endif; ?>
          </div>

          <div class="card">
            <h3>Pengumuman</h3>
            <p>SPMB Dibuka.<br>Cek info terbaru di bawah.</p>
          </div>
        </section>

        <!-- News / Announcement -->
        <section class="news">
          <h3>Berita & Pengumuman</h3>
          <ul>
            <li><b>Pembaruan Data Pegawai</b> – Segera isi form profil terbaru.</li>
            <li><b>SPMB 2026/2027</b> – Pendaftaran telah dibuka.</li>
            <li><b>Agenda Internal</b> – Training Sabtu pekan ini.</li>
          </ul>
        </section>
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
