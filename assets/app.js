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
        .then(r => {
          if (!r.ok) throw new Error('Network response: ' + r.status);
          return r.json();
        })
        .catch(err => {
          console.error('AJAX error:', err);
          throw err;
        });
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
      })
        .then(r => {
          if (!r.ok) throw new Error('Network response: ' + r.status);
          return r.json();
        })
        .catch(err => {
          console.error('AJAX error:', err);
          throw err;
        });
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
      if (!nip || !pwv) { msg.textContent = 'Akun & Pasword wajib diisi.'; return; }

      console.log('Submitting login:', { nip, ajax_url: (window.HRISSQ && HRISSQ.ajax) });

      ajax('hrissq_login', { nip, pw: pwv })
        .then(res => {
          console.log('Login response:', res);
          if (!res || !res.ok) {
            msg.textContent = (res && res.msg) ? res.msg : 'Login gagal.';
            return;
          }
          // server sudah mengirim res.redirect → pakai itu
          const dashSlug = (window.HRISSQ && HRISSQ.dashboardSlug) ? HRISSQ.dashboardSlug : 'dashboard';
          const redirectUrl = res.redirect || ('/' + dashSlug.replace(/^\/+/, '') + '/');
          console.log('Redirecting to:', redirectUrl);
          window.location.href = redirectUrl;
        })
        .catch(err => {
          console.error('Login error:', err);
          msg.textContent = 'Error: ' + (err && err.message ? err.message : String(err));
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
        if (!nip) { fMsg.textContent = 'Akun wajib diisi.'; return; }
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
    const buttons = document.querySelectorAll('#hrissq-logout');
    if (!buttons.length) return;

    const redirectToLogin = () => {
      const slug = (window.HRISSQ && HRISSQ.loginSlug) ? HRISSQ.loginSlug.replace(/^\/+/, '') : 'masuk';
      window.location.href = '/' + slug.replace(/\/+$/, '') + '/';
    };

    buttons.forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        btn.disabled = true;
        const old = btn.textContent;
        btn.textContent = 'Keluar…';

        ajax('hrissq_logout', {})
          .then(redirectToLogin)
          .catch(redirectToLogin)
          .finally(() => {
            btn.textContent = old;
            btn.disabled = false;
          });
      });
    });
  }

  // --- DASHBOARD: sidebar toggle ---
  function bootSidebarToggle() {
    const layout = document.getElementById('hrissq-dashboard');
    const sidebar = document.getElementById('hrissq-sidebar');
    const toggle = document.getElementById('hrissq-sidebar-toggle');
    if (!layout || !sidebar || !toggle) return;

    const overlay = document.getElementById('hrissq-sidebar-overlay');
    const closeBtn = document.getElementById('hrissq-sidebar-close');
    const mq = window.matchMedia('(max-width: 960px)');

    function isMobile() {
      return mq.matches;
    }

    function setAria(open) {
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
      if (overlay) {
        const overlayVisible = isMobile() && open;
        overlay.setAttribute('aria-hidden', overlayVisible ? 'false' : 'true');
      }
    }

    function openMobile() {
      sidebar.classList.add('is-open');
      if (overlay) overlay.classList.add('is-visible');
      setAria(true);
    }

    function closeMobile() {
      sidebar.classList.remove('is-open');
      if (overlay) overlay.classList.remove('is-visible');
      setAria(false);
    }

    function toggleDesktop() {
      const collapsed = layout.classList.toggle('is-collapsed');
      setAria(!collapsed);
    }

    function handleChange() {
      if (isMobile()) {
        layout.classList.remove('is-collapsed');
        if (sidebar.classList.contains('is-open')) {
          setAria(true);
          if (overlay) overlay.classList.add('is-visible');
        } else {
          setAria(false);
          if (overlay) overlay.classList.remove('is-visible');
        }
      } else {
        sidebar.classList.remove('is-open');
        if (overlay) overlay.classList.remove('is-visible');
        const collapsed = layout.classList.contains('is-collapsed');
        setAria(!collapsed);
      }
    }

    toggle.addEventListener('click', function () {
      if (isMobile()) {
        if (sidebar.classList.contains('is-open')) {
          closeMobile();
        } else {
          openMobile();
        }
      } else {
        toggleDesktop();
      }
    });

    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        if (isMobile()) {
          closeMobile();
        } else {
          layout.classList.add('is-collapsed');
          setAria(false);
        }
      });
    }

    if (overlay) {
      overlay.addEventListener('click', closeMobile);
    }

    if (mq.addEventListener) {
      mq.addEventListener('change', handleChange);
    } else if (mq.addListener) {
      mq.addListener(handleChange);
    }

    handleChange();
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
        window.location.href = '/' + slug.replace(/\/+$/, '') + '/';
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

  // --- TRAINING FORM ---
  function bootTrainingForm() {
    const form = document.getElementById('hrissq-training-form');
    if (!form) return;

    form.addEventListener('submit', (e) => {
      e.preventDefault();

      const submitBtn = form.querySelector('button[type="submit"]');
      const msg = form.querySelector('.msg');
      if (!msg) {
        const msgEl = document.createElement('div');
        msgEl.className = 'msg';
        form.appendChild(msgEl);
      }
      const msgEl = form.querySelector('.msg');

      submitBtn.disabled = true;
      submitBtn.textContent = 'Menyimpan...';
      msgEl.className = 'msg';
      msgEl.textContent = '';

      const formData = new FormData(form);
      formData.append('action', 'hrissq_submit_training');
      formData.append('_nonce', (window.HRISSQ && HRISSQ.nonce) ? HRISSQ.nonce : '');

      const url = (window.HRISSQ && HRISSQ.ajax) ? HRISSQ.ajax : '/wp-admin/admin-ajax.php';

      fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
      })
        .then(r => r.json())
        .then(res => {
          if (res && res.ok) {
            msgEl.className = 'msg ok';
            msgEl.textContent = 'Data berhasil disimpan!';
            form.reset();
            setTimeout(() => {
              const dashSlug = (window.HRISSQ && HRISSQ.dashboardSlug) ? HRISSQ.dashboardSlug : 'dashboard';
              window.location.href = '/' + dashSlug.replace(/^\/+/, '') + '/';
            }, 1500);
          } else {
            msgEl.className = 'msg error';
            if (res && res.msg === 'Unauthorized') {
              msgEl.textContent = 'Sesi Anda berakhir. Silakan login kembali.';
              setTimeout(() => {
                const slug = (window.HRISSQ && HRISSQ.loginSlug) ? HRISSQ.loginSlug.replace(/^\/+/, '') : 'masuk';
                window.location.href = '/' + slug.replace(/\/+$/, '') + '/';
              }, 1200);
            } else {
              msgEl.textContent = (res && res.msg) ? res.msg : 'Gagal menyimpan data.';
            }
          }
        })
        .catch(err => {
          msgEl.className = 'msg error';
          msgEl.textContent = 'Error: ' + (err && err.message ? err.message : err);
        })
        .finally(() => {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Simpan';
        });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bootLogin();
    bootLogoutButton();
    bootSidebarToggle();
    bootIdleLogout();
    bootTrainingForm();
  });
})();
