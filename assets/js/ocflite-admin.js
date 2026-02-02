document.addEventListener('DOMContentLoaded', function () {
  var container = document.querySelector('.ocflite-settings');
  if (!container) return;

  var transportSelect = container.querySelector('select[name="ocflite_transport"]');
  if (!transportSelect) return;

  var smtpBox = container.querySelector('.ocflite-box--smtp');
  var fileConfig = container.querySelector('.ocflite-file-config');

  var smtpUserInput = container.querySelector('input[name="ocflite_smtp_user"]');
  var fromEmailInput = container.querySelector('input[name="ocflite_from_email"]');

  // isSynced: From currently mirrors SMTP Username (during username edits).
  // lastUserValue: remembers previous SMTP username value to detect real user changes.
  var isSynced = false;
  var isProgrammatic = false;
  var lastUserValue = smtpUserInput ? (smtpUserInput.value || '') : '';

  function toggleSection(el, enabled) {
    if (!el) return;
    el.classList.toggle('ocflite-box--disabled', !enabled);
  }

  function refreshTransports() {
    var v = transportSelect.value;
    toggleSection(smtpBox, v === 'smtp');
    toggleSection(fileConfig, v === 'file');
  }

  function inSmtpMode() {
    return transportSelect.value === 'smtp';
  }

  function getVal(el) {
    return (el && el.value != null) ? el.value : '';
  }

  function isEmpty(val) {
    return (val || '').trim() === '';
  }

  function syncFromToUser() {
    if (!smtpUserInput || !fromEmailInput) return;
    if (!inSmtpMode()) return;

    isProgrammatic = true;
    fromEmailInput.value = getVal(smtpUserInput);
    isProgrammatic = false;

    isSynced = true;
  }

  // Only sync in response to an actual username edit event.
  // Never sync just because From became empty.
  function onUsernameUserAction() {
    if (!smtpUserInput || !fromEmailInput) return;
    if (!inSmtpMode()) return;

    var userVal = getVal(smtpUserInput);
    var fromVal = getVal(fromEmailInput);

    // Detect real change since last time we handled it.
    // (Prevents any weird re-trigger without user typing.)
    if (userVal === lastUserValue) return;
    lastUserValue = userVal;

    // Mirror ONLY if:
    // - From is empty (user hasn't set it), OR
    // - From is currently synced (continue mirroring)
    if (isEmpty(fromVal) || isSynced) {
      syncFromToUser();
    }
  }

  // Transport change: optional one-time fill when switching to SMTP,
  // but only if From is empty AND username is non-empty.
  transportSelect.addEventListener('change', function () {
    refreshTransports();

    if (!inSmtpMode()) {
      isSynced = false;
      return;
    }

    if (!smtpUserInput || !fromEmailInput) return;

    var fromVal = getVal(fromEmailInput);
    var userVal = getVal(smtpUserInput);

    // One-time fill on switching to SMTP (no "From empty => refill" loop).
    if (isEmpty(fromVal) && !isEmpty(userVal)) {
      syncFromToUser();
    } else {
      isSynced = false;
    }

    // Update lastUserValue baseline
    lastUserValue = userVal;
  });

  // Username typing/deleting/paste => mirror immediately (if allowed).
  if (smtpUserInput) {
    smtpUserInput.addEventListener('input', onUsernameUserAction);
    smtpUserInput.addEventListener('change', onUsernameUserAction);
    // (Optional) also on paste/cut explicitly, but input event usually covers it:
    smtpUserInput.addEventListener('paste', function () { setTimeout(onUsernameUserAction, 0); });
    smtpUserInput.addEventListener('cut', function () { setTimeout(onUsernameUserAction, 0); });
  }

  // User edits From => never fight them. No auto-refill when it becomes empty.
  // Sync will only happen again when they type in SMTP Username.
  if (fromEmailInput) {
    fromEmailInput.addEventListener('input', function () {
      if (isProgrammatic) return;
      isSynced = false;
    });
    fromEmailInput.addEventListener('change', function () {
      if (isProgrammatic) return;
      isSynced = false;
    });
  }

  // Init
  refreshTransports();

  // Optional initial one-time fill on load if already in SMTP mode,
  // From empty, username non-empty.
  if (inSmtpMode() && smtpUserInput && fromEmailInput) {
    var fromVal0 = getVal(fromEmailInput);
    var userVal0 = getVal(smtpUserInput);
    lastUserValue = userVal0;

    if (isEmpty(fromVal0) && !isEmpty(userVal0)) {
      syncFromToUser();
    } else {
      isSynced = false;
    }
  }
});
