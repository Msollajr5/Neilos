  </div><!-- /.page-content -->
</div><!-- /.main-content -->

</div><!-- /.portal-wrapper -->

<!-- Neilos System Modal: Account Information Found -->
<div class="modal-backdrop" id="neilosAccountInfoModal" style="z-index:99999">
  <div class="modal" style="max-width:520px;padding:0;overflow:hidden;border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);border:1px solid var(--border);background:var(--surface)">
    <!-- Header -->
    <div style="background:var(--accent-pale);padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:42px;height:42px;border-radius:12px;background:var(--accent-glow);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:1.25rem">
          🏢
        </div>
        <div>
          <h3 style="margin:0;font-size:1.1rem;font-weight:700;color:var(--text-primary)">Account Information Found</h3>
          <div style="font-size:0.85rem;color:var(--text-secondary)" id="accountInfoSubTitle">Neilos System Check</div>
        </div>
      </div>
      <button type="button" class="modal-close" id="accountInfoModalClose" style="background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer;line-height:1">&times;</button>
    </div>

    <!-- Body -->
    <div style="padding:24px">
      <div style="font-weight:700;font-size:1.15rem;color:var(--text-primary);margin-bottom:6px" id="accountInfoName">Account Name</div>
      <p style="font-size:0.88rem;color:var(--text-secondary);margin-bottom:18px" id="accountInfoDesc">The system found existing information for this account.</p>

      <div style="background:var(--surface-3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px 20px;margin-bottom:24px">
        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:12px">Available information:</div>
        <div style="display:flex;flex-direction:column;gap:8px;font-size:0.9rem;color:var(--text-primary)" id="accountInfoChecklist">
          <!-- Dynamically populated checklist -->
        </div>
      </div>

      <div style="display:flex;gap:12px;justify-content:flex-end">
        <button type="button" class="btn btn-secondary" id="accountInfoCancelBtn">Cancel</button>
        <button type="button" class="btn btn-primary" id="accountInfoLoadBtn"><?= svgIcon('check') ?> Load Existing Information</button>
      </div>
    </div>
  </div>
</div>

<!-- Neilos System Modal: Action Confirmation -->
<div class="modal-backdrop" id="neilosConfirmModal" style="z-index:999999">
  <div class="modal" style="max-width:460px;padding:0;overflow:hidden;border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);border:1px solid var(--border);background:var(--surface)">
    <!-- Header -->
    <div style="background:var(--accent-pale);padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(220,38,38,0.12);color:var(--danger);display:flex;align-items:center;justify-content:center;font-size:1.15rem" id="neilosConfirmIcon">
          ⚠️
        </div>
        <h3 style="margin:0;font-size:1.05rem;font-weight:700;color:var(--text-primary)" id="neilosConfirmTitle">Confirm Action</h3>
      </div>
      <button type="button" class="modal-close" id="neilosConfirmClose" style="background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer;line-height:1">&times;</button>
    </div>

    <!-- Body -->
    <div style="padding:20px 24px">
      <p style="font-size:0.92rem;color:var(--text-secondary);margin-bottom:20px;line-height:1.5" id="neilosConfirmMessage">Are you sure you want to proceed with this action?</p>

      <div style="display:flex;gap:12px;justify-content:flex-end">
        <button type="button" class="btn btn-secondary" id="neilosConfirmCancelBtn">Cancel</button>
        <button type="button" class="btn btn-danger" id="neilosConfirmProceedBtn">Proceed</button>
      </div>
    </div>
  </div>
</div>

<!-- Neilos System Modal: Alert / Notification -->
<div class="modal-backdrop" id="neilosAlertModal" style="z-index:999999">
  <div class="modal" style="max-width:440px;padding:0;overflow:hidden;border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);border:1px solid var(--border);background:var(--surface)">
    <div style="background:var(--accent-pale);padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(59,130,246,0.12);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:1.15rem" id="neilosAlertIcon">
          ℹ️
        </div>
        <h3 style="margin:0;font-size:1.05rem;font-weight:700;color:var(--text-primary)" id="neilosAlertTitle">Notification</h3>
      </div>
      <button type="button" class="modal-close" id="neilosAlertClose" style="background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer;line-height:1">&times;</button>
    </div>
    <div style="padding:20px 24px">
      <p style="font-size:0.92rem;color:var(--text-secondary);margin-bottom:20px;line-height:1.5" id="neilosAlertMessage">System message</p>
      <div style="display:flex;justify-content:flex-end">
        <button type="button" class="btn btn-primary" id="neilosAlertOkBtn">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- Neilos System Modal: Prompt Input -->
<div class="modal-backdrop" id="neilosPromptModal" style="z-index:999999">
  <div class="modal" style="max-width:460px;padding:0;overflow:hidden;border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);border:1px solid var(--border);background:var(--surface)">
    <div style="background:var(--accent-pale);padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(15,76,129,0.12);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:1.15rem">
          💬
        </div>
        <h3 style="margin:0;font-size:1.05rem;font-weight:700;color:var(--text-primary)" id="neilosPromptTitle">Input Required</h3>
      </div>
      <button type="button" class="modal-close" id="neilosPromptClose" style="background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer;line-height:1">&times;</button>
    </div>
    <div style="padding:20px 24px">
      <p style="font-size:0.92rem;color:var(--text-secondary);margin-bottom:12px;line-height:1.5" id="neilosPromptMessage">Please enter a value:</p>
      <input type="text" class="form-control" id="neilosPromptInput" style="margin-bottom:20px">
      <div style="display:flex;gap:12px;justify-content:flex-end">
        <button type="button" class="btn btn-secondary" id="neilosPromptCancelBtn">Cancel</button>
        <button type="button" class="btn btn-primary" id="neilosPromptSubmitBtn">Submit</button>
      </div>
    </div>
  </div>
</div>

<!-- Neilos System Modal: Global Document Viewer -->
<div class="modal-backdrop" id="neilosGlobalFileViewModal" style="z-index:999999">
  <div class="modal" style="max-width:800px;width:92%;padding:0;overflow:hidden;border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);border:1px solid var(--border);background:var(--surface)">
    <div style="background:var(--accent-pale);padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:40px;height:40px;border-radius:10px;background:rgba(15,76,129,0.12);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:1.25rem">
          📄
        </div>
        <div>
          <h3 style="margin:0;font-size:1.1rem;font-weight:700;color:var(--text-primary)" id="globalFileModalTitle">Document Preview</h3>
          <div style="font-size:0.8rem;color:var(--text-muted)" id="globalFileModalSubtitle">Neilos Network Document Viewer</div>
        </div>
      </div>
      <button type="button" class="modal-close" onclick="closeGlobalFileViewModal()" style="background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer;line-height:1">&times;</button>
    </div>

    <div style="padding:20px 24px;max-height:75vh;overflow-y:auto" id="globalFileModalBody">
      <!-- File preview / metadata container -->
    </div>

    <div style="padding:14px 24px;border-top:1px solid var(--border);background:var(--surface-2);display:flex;justify-content:space-between;align-items:center">
      <div class="text-muted font-sm">Click Download to save file directly to your computer.</div>
      <div style="display:flex;gap:10px;align-items:center">
        <a href="#" id="globalFileModalDownloadBtn" class="btn btn-primary btn-sm" style="text-decoration:none">
          <?= svgIcon('download', 14) ?> Download File
        </a>
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeGlobalFileViewModal()">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Neilos System Modal: Session Idle Warning -->
<div class="modal-backdrop" id="neilosIdleTimeoutModal" style="z-index:9999999;display:none;background:rgba(15,23,42,0.65);backdrop-filter:blur(4px)">
  <div class="modal" style="max-width:460px;padding:0;overflow:hidden;border-radius:var(--radius-lg);box-shadow:0 25px 50px -12px rgba(0,0,0,0.35);border:1px solid var(--border);background:var(--surface)">
    <div style="background:#fffbeb;padding:20px 24px;border-bottom:1px solid #fde68a;display:flex;align-items:center;gap:14px">
      <div style="width:42px;height:42px;border-radius:12px;background:#fef3c7;color:#d97706;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0">
        ⏳
      </div>
      <div>
        <h3 style="margin:0;font-size:1.1rem;font-weight:700;color:#92400e">Inactivity Notice</h3>
        <div style="font-size:0.82rem;color:#b45309">You have been idle for a while</div>
      </div>
    </div>
    <div style="padding:22px 24px">
      <p style="font-size:0.92rem;color:var(--text-secondary);line-height:1.55;margin-bottom:16px">
        For your security, your session will automatically terminate due to inactivity in:
      </p>
      <div style="text-align:center;padding:16px;background:var(--surface-2);border-radius:var(--radius-sm);margin-bottom:20px;border:1px dashed var(--border)">
        <span id="idleCountdownSeconds" style="font-size:2.2rem;font-weight:800;color:#d97706;letter-spacing:1px;font-variant-numeric:tabular-nums">60</span>
        <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);font-weight:600;margin-top:2px">seconds remaining</div>
      </div>
      <div style="display:flex;gap:12px;justify-content:flex-end">
        <a href="<?= APP_URL ?>/?page=logout" class="btn btn-secondary" style="text-decoration:none">Sign Out Now</a>
        <button type="button" class="btn btn-primary" id="stayLoggedInBtn" style="background:#0284c7;border-color:#0284c7">
          <?= svgIcon('check', 16) ?> Stay Logged In
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const IDLE_TIMEOUT_SECONDS = <?= defined('SESSION_IDLE_TIMEOUT') ? SESSION_IDLE_TIMEOUT : 900 ?>;
  const WARN_BEFORE_SECONDS = 60;
  const WARN_AT_SECONDS = Math.max(10, IDLE_TIMEOUT_SECONDS - WARN_BEFORE_SECONDS);
  const APP_PING_URL = '<?= APP_URL ?>/?page=session_ping';

  let idleSeconds = 0;
  let countdownInterval = null;
  let isModalVisible = false;
  let lastServerPingTime = Date.now();

  const modal = document.getElementById('neilosIdleTimeoutModal');
  const countdownEl = document.getElementById('idleCountdownSeconds');
  const stayLoggedInBtn = document.getElementById('stayLoggedInBtn');

  function showWarningModal() {
    if (isModalVisible) return;
    isModalVisible = true;
    modal.style.display = 'flex';
    let remaining = Math.max(0, IDLE_TIMEOUT_SECONDS - idleSeconds);
    countdownEl.textContent = remaining;

    if (countdownInterval) clearInterval(countdownInterval);
    countdownInterval = setInterval(() => {
      remaining--;
      if (remaining <= 0) {
        clearInterval(countdownInterval);
        window.location.href = '<?= APP_URL ?>/?page=login&reason=timeout';
      } else {
        countdownEl.textContent = remaining;
      }
    }, 1000);
  }

  function hideWarningModal() {
    if (!isModalVisible) return;
    isModalVisible = false;
    modal.style.display = 'none';
    if (countdownInterval) {
      clearInterval(countdownInterval);
      countdownInterval = null;
    }
  }

  function pingServer(keepAlive) {
    const url = APP_PING_URL + (keepAlive ? '&keep_alive=1' : '');
    fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(res => res.json())
      .then(data => {
        if (data && data.status === 'timeout') {
          window.location.href = '<?= APP_URL ?>/?page=login&reason=timeout';
        }
      })
      .catch(() => {});
  }

  function onUserActive() {
    if (isModalVisible) {
      return;
    }
    idleSeconds = 0;

    const now = Date.now();
    if (now - lastServerPingTime > 180000) {
      lastServerPingTime = now;
      pingServer(true);
    }
  }

  setInterval(() => {
    idleSeconds++;
    if (idleSeconds >= WARN_AT_SECONDS && !isModalVisible) {
      showWarningModal();
    }
    if (idleSeconds >= IDLE_TIMEOUT_SECONDS + 5) {
      window.location.href = '<?= APP_URL ?>/?page=login&reason=timeout';
    }
  }, 1000);

  ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach(evt => {
    window.addEventListener(evt, onUserActive, { passive: true });
  });

  if (stayLoggedInBtn) {
    stayLoggedInBtn.addEventListener('click', () => {
      idleSeconds = 0;
      lastServerPingTime = Date.now();
      hideWarningModal();
      pingServer(true);
    });
  }
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/docx-preview@0.3.3/dist/docx-preview.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/portal.js?v=<?= time() ?>"></script>
<?php if (!empty($extraJs)): ?>
<script src="<?= APP_URL ?>/assets/js/<?= e($extraJs) ?>.js?v=<?= time() ?>"></script>
<?php endif; ?>
</body>
</html>
