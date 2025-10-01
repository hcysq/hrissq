/* HRISSQ front scripts */
(function () {
  // --- util AJAX ke admin-ajax.php ---
  function ajax(action, body = {}, withFile = false) {
    const url = (window.HRISSQ && HRISSQ.ajax) ? HRISSQ.ajax : '/wp-admin/admin-ajax.php';
    const nonce = (window.HRISSQ && HRISSQ.nonce) ? HRISSQ.nonce : '';

    if (withFile) {
      const fd = new FormData();
      fd.append('action', action);
      fd.append('_nonce', nonce);
      Object.keys(body).forEach(k => {
        if (k !== 'action' && k !== '_nonce') fd.append(k, body[k]);
      });
      return fetch(url, { method: 'POST', credentials: 'same-origin', body: fd })
        .then(r => r.json());
    } else {
      const fd = new URLSearchParams();
      fd.append('action', action);
      fd.append('_nonce', nonce);
      Object.keys(body).forEach(k => fd.append(k, body[k]));
      return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: fd.toString()
      }).then(r => r.json());
    }
  }

  // --- LOGIN PAGE ---
  function bootLogin() {
    const form = document.getElementById('hrissq-login-form');
    if (!form) return;

    // toggle eye
    const eye = document.getElementById('hrissq-eye');
    const pw = document.getElementById('hrissq-pw');
    if (eye && pw) {
      eye.addEventListener('click', () => {
        pw.type = pw.type === 'password' ? 'text' : 'password';
        eye.textContent = (pw.type === 'password') ? 'lihat' : 'sembunyikan';
        pw.focus();
      });
    }

    const msg = form.querySelector('.msg');
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      msg.className = 'msg';
      msg.textContent = 'Memeriksa…';

      const nip = (form.nip.value || '').trim();
      const pwv = (form.pw.value || '').trim();
      if (!nip || !pwv) { msg.textContent = 'NIP & Password wajib diisi.'; return; }

      ajax('hrissq_login', { nip, pw: pwv })
        .then(res => {
          if (!res || !res.ok) {
            msg.textContent = (res && res.msg) ? res.msg : 'Login gagal.';
            return;
          }
          // server sudah mengirim res.redirect → pakai itu
          window.location.href = res.redirect || '/dashboard';
        })
        .catch(err => {
          msg.textContent = 'Error: ' + (err && err.message ? err.message : err);
        });
    });

    // Forgot password modal
    const forgotBtn = document.getElementById('hrissq-forgot');
    const backdrop = document.getElementById('hrissq-modal');
    const cancelBtn = document.getElementById('hrissq-cancel');
    const sendBtn = document.getElementById('hrissq-send');
    const npInput = document.getElementById('hrissq-nip-forgot');
    const fMsg = document.getElementById('hrissq-forgot-msg');

    if (forgotBtn && backdrop) {
      forgotBtn.onclick = () => {
        backdrop.style.display = 'flex';
        if (npInput) npInput.value = (form.nip.value || '').trim();
        if (fMsg) { fMsg.className = 'modal-msg'; fMsg.textContent = ''; }
      };
      cancelBtn && (cancelBtn.onclick = () => { backdrop.style.display = 'none'; });
      sendBtn && (sendBtn.onclick = () => {
        const nip = (npInput.value || '').trim();
        if (!nip) { fMsg.textContent = 'NIP wajib diisi.'; return; }
        fMsg.textContent = 'Mengirim permintaan…';

        // NOTE: pastikan endpoint hrissq_forgot sudah ada di Api.php
        ajax('hrissq_forgot', { nip })
          .then(res => {
            if (res && res.ok) {
              fMsg.className = 'modal-msg ok';
              fMsg.textContent = 'Permintaan terkirim. Anda akan dihubungi Admin via WhatsApp.';
              setTimeout(() => { backdrop.style.display = 'none'; }, 1500);
            } else {
              fMsg.className = 'modal-msg';
              fMsg.textContent = 'Gagal mengirim permintaan. Coba lagi.';
            }
          })
          .catch(err => {
            fMsg.className = 'modal-msg';
            fMsg.textContent = 'Error: ' + (err && err.message ? err.message : err);
          });
      });
    }
  }

  // --- DASHBOARD: tombol Keluar ---
  function bootLogoutButton() {
    const btn = document.getElementById('hrissq-logout');
    if (!btn) return;
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      btn.disabled = true;
      const old = btn.textContent;
      btn.textContent = 'Keluar…';
      ajax('hrissq_logout', {})
        .finally(() => {
          const slug = (window.HRISSQ && HRISSQ.loginSlug) ? HRISSQ.loginSlug.replace(/^\/+/, '') : 'masuk';
          const to = '/' + slug + (window.location.pathname.endsWith('/') ? '' : '/');
          window.location.href = to;
        });
    });
  }

  // --- AUTO LOGOUT (Idle 15 menit, warning 30 detik) ---
  function bootIdleLogout() {
    const backdrop = document.getElementById('hrq-idle-backdrop');
    const stayBtn = document.getElementById('hrq-idle-stay');
    const exitBtn = document.getElementById('hrq-idle-exit');
    const countEl = document.getElementById('hrq-idle-count');
    if (!backdrop || !stayBtn || !exitBtn || !countEl) return; // hanya di dashboard

    const IDLE_MS = 15 * 60 * 1000; // 15 menit
    const WARN_MS = 30 * 1000;      // 30 detik
    let idleTimer = null;
    let warnTimer = null;
    let countdown = 30;

    function resetIdle() {
      if (idleTimer) clearTimeout(idleTimer);
      idleTimer = setTimeout(showWarning, IDLE_MS);
    }

    function showWarning() {
      countdown = 30;
      countEl.textContent = countdown;
      backdrop.style.display = 'flex';
      warnTimer = setInterval(() => {
        countdown--;
        countEl.textContent = countdown;
        if (countdown <= 0) {
          clearInterval(warnTimer);
          doLogout();
        }
      }, 1000);
    }

    function hideWarning() {
      backdrop.style.display = 'none';
      if (warnTimer) clearInterval(warnTimer);
      resetIdle();
    }

    function doLogout() {
      ajax('hrissq_logout', {}).finally(() => {
        const slug = (window.HRISSQ && HRISSQ.loginSlug) ? HRISSQ.loginSlug.replace(/^\/+/, '') : 'masuk';
        window.location.href = '/' + slug + '/';
      });
    }

    stayBtn.addEventListener('click', hideWarning);
    exitBtn.addEventListener('click', doLogout);

    // aktivitas yang mengulang timer
    ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach(ev => {
      window.addEventListener(ev, resetIdle, { passive: true });
    });

    resetIdle();
  }

  document.addEventListener('DOMContentLoaded', function () {
    bootLogin();
    bootLogoutButton();
    bootIdleLogout();
  });
})();
