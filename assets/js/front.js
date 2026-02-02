/**
 * OneClick Form Lite — Front-end submission + theming + custom validation + reCAPTCHA v3
 */
(function () {
  "use strict";

  /* ---------- Minimal bootstrap for reCAPTCHA config (no UI impact) ---------- */
  // Do not hardcode any site key. Only use what PHP localized into OCFLITE.
  if (!window.OCFLITE) window.OCFLITE = {};
  if (!window.OCFLITE.recaptcha) {
    window.OCFLITE.recaptcha = {
      enabled: false, // disabled by default when not localized
      siteKey: "", // must be provided by PHP
      action: "contact_form", // keep aligned with server default
    };
  }

  /* ---------- Minimal CSS for tooltip (scoped) ---------- */
  function ensureUIStyles() {
    var css = [
      ".ocflite-form{",
      "  position:relative;",
      "  box-sizing:border-box;",
      "  width:min(640px,92vw);",
      "  margin:40px auto;",
      "  padding:20px;",
      "  border:1px solid transparent;",
      "  border-radius:6px;",
      "  background:transparent;",
      "  min-height:60vh;",
      "  display:block;",
      "  float:none;",
      "  align-self:center;",
      "}",
      ".ocflite-form .ocflite-row{",
      "  margin:0 0 14px;",
      "}",
      ".ocflite-form .ocflite-row--submit{",
      "  text-align:center;",
      "  margin-bottom:0;",
      "}",
      ".ocflite-form .ocflite-row--submit .wp-element-button,",
      ".ocflite-form .ocflite-row--submit button[type=submit]{",
      "  display:inline-block;",
      "  padding:.6em 1.1em;",
      "  border-radius:5px;",
      "  text-transform:uppercase;",
      "  transition:background-color .2s ease,color .2s ease,filter .2s ease;",
      "}",
      ".ocflite-form .ocflite-row--submit .wp-element-button:hover,",
      ".ocflite-form .ocflite-row--submit button[type=submit]:hover{",
      "  filter:brightness(1.25) saturate(1.2) contrast(1.1);",
      "  transition:all 300ms ease;",
      "}",
      ".ocflite-form .ocflite-consent{",
      "  font-size:.62em;",
      "  line-height:1.4;",
      "  display:block;",
      "}",
      ".ocflite-form .ocflite-fields{",
      "  transition:opacity 1s ease;",
      "}",
      ".ocflite-form.ocflite--sent .ocflite-fields{",
      "  opacity:0;",
      "  pointer-events:none;",
      "}",
      ".ocflite-form [data-ocflite-message]{",
      "  position:absolute;",
      "  inset:0;",
      "  white-space:pre-line;",
      "  display:flex;",
      "  align-items:center;",
      "  justify-content:center;",
      "  text-align:center;",
      "  padding:24px;",
      "  font-weight:700;",
      "  font-size:clamp(1.5rem,3.6vw,2.25rem);",
      "  line-height:1.25;",
      "  opacity:0;",
      "  transition:opacity .35s ease;",
      "  pointer-events:none;",
      "}",
      ".ocflite-form.ocflite--sent [data-ocflite-message]{",
      "  opacity:1;",
      "  pointer-events:auto;",
      "}",
      ".ocflite-tip{",
      "  position:absolute;",
      "  z-index:99999;",
      "  max-width:320px;",
      "  background:#fff !important;",
      "  color:#c00 !important;",
      "  padding:10px 10px;",
      "  border:1px solid #c00;",
      "  border-radius:5px;",
      "  font-size:12px;",
      "  line-height:1.35;",
      "  box-shadow:0 2px 10px rgba(0,0,0,.12);",
      "  text-align:left;",
      "  font-weight:600;",
      "}",
      ".ocflite-tip::after{",
      '  content:"";',
      "  position:absolute;",
      "  left:10px;",
      "  top:-8px;",
      "  border:8px solid transparent;",
      "  border-bottom:0;",
      "  border-top-color:#c00;",
      "}",
      ".ocflite-tip::before{",
      '  content:"";',
      "  position:absolute;",
      "  left:10px;",
      "  top:-6px;",
      "  border:8px solid transparent;",
      "  border-bottom:0;",
      "  border-top-color:#fff;",
      "}",
    ].join("\n");

    var style = document.getElementById("ocflite-ui");
    if (style) {
      if (style.firstChild) style.removeChild(style.firstChild);
      style.appendChild(document.createTextNode(css));
      return;
    }
    style = document.createElement("style");
    style.id = "ocflite-ui";
    style.type = "text/css";
    style.appendChild(document.createTextNode(css));
    document.head.appendChild(style);
  }

  /* ---------- Color helpers (unchanged) ---------- */
  function parseRGB(str) {
    if (!str) return null;
    var s = String(str).trim();
    var m = s
      .replace(/\s+/g, "")
      .match(/^rgba?\((\d+),(\d+),(\d+)(?:,([.\d]+))?\)$/i);
    if (m)
      return {
        r: +m[1],
        g: +m[2],
        b: +m[3],
        a: m[4] !== undefined ? +m[4] : 1,
      };
    var mh3 = s.match(/^#([0-9a-f]{3})$/i);
    if (mh3) {
      var d = mh3[1];
      return {
        r: parseInt(d[0] + d[0], 16),
        g: parseInt(d[1] + d[1], 16),
        b: parseInt(d[2] + d[2], 16),
        a: 1,
      };
    }
    var mh6 = s.match(/^#([0-9a-f]{6})$/i);
    if (mh6) {
      var d6 = mh6[1];
      return {
        r: parseInt(d6.slice(0, 2), 16),
        g: parseInt(d6.slice(2, 4), 16),
        b: parseInt(d6.slice(4, 6), 16),
        a: 1,
      };
    }
    return null;
  }
  function rgbCss(c) {
    return "rgb(" + c.r + ", " + c.g + ", " + c.b + ")";
  }
  function rgbToHsl(c) {
    var r = c.r / 255,
      g = c.g / 255,
      b = c.b / 255,
      max = Math.max(r, g, b),
      min = Math.min(r, g, b),
      h,
      s,
      l = (max + min) / 2;
    if (max === min) {
      h = s = 0;
    } else {
      var d = max - min;
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
      switch (max) {
        case r:
          h = (g - b) / d + (g < b ? 6 : 0);
          break;
        case g:
          h = (b - r) / d + 2;
          break;
        case b:
          h = (r - g) / d + 4;
          break;
      }
      h /= 6;
    }
    return { h: h, s: s, l: l };
  }
  function hslToRgb(hsl) {
    var h = hsl.h,
      s = hsl.s,
      l = hsl.l;
    function hue2rgb(p, q, t) {
      if (t < 0) t += 1;
      if (t > 1) t -= 1;
      if (t < 1 / 6) return p + (q - p) * 6 * t;
      if (t < 1 / 2) return q;
      if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
      return p;
    }
    var r, g, b;
    if (s === 0) {
      r = g = b = l;
    } else {
      var q = l < 0.5 ? l * (1 + s) : l + s - l * s,
        p = 2 * l - q;
      r = hue2rgb(p, q, h + 1 / 3);
      g = hue2rgb(p, q, h);
      b = hue2rgb(p, q, h - 1 / 3);
    }
    return {
      r: Math.round(r * 255),
      g: Math.round(g * 255),
      b: Math.round(b * 255),
    };
  }
  function relLum(c) {
    function t(x) {
      x /= 255;
      return x <= 0.03928 ? x / 12.92 : Math.pow((x + 0.055) / 1.055, 2.4);
    }
    return 0.2126 * t(c.r) + 0.7152 * t(c.g) + 0.0722 * t(c.b);
  }
  function contrastRatio(a, b) {
    var L1 = relLum(a),
      L2 = relLum(b),
      hi = Math.max(L1, L2),
      lo = Math.min(L1, L2);
    return (hi + 0.05) / (lo + 0.05);
  }
  function mixWithWhite(rgb, f) {
    return {
      r: Math.round(rgb.r * (1 - f) + 255 * f),
      g: Math.round(rgb.g * (1 - f) + 255 * f),
      b: Math.round(rgb.b * (1 - f) + 255 * f),
    };
  }
  function midpoint(a, b) {
    return {
      r: Math.round((a.r + b.r) / 2),
      g: Math.round((a.g + b.g) / 2),
      b: Math.round((a.b + b.b) / 2),
    };
  }
  function clamp(x, min, max) {
    return Math.max(min, Math.min(max, x));
  }
  function getEffectiveBgColor(el) {
    var node = el;
    while (node && node !== document.documentElement) {
      var cs = getComputedStyle(node),
        bg = cs && cs.backgroundColor;
      if (
        bg &&
        bg !== "transparent" &&
        bg !== "rgba(0, 0, 0, 0)" &&
        bg !== "rgba(0,0,0,0)"
      ) {
        var rgb = parseRGB(bg);
        if (rgb && rgb.a > 0) return rgb;
      }
      node = node.parentElement;
    }
    var bodyBg = getComputedStyle(document.body).backgroundColor;
    return parseRGB(bodyBg) || { r: 255, g: 255, b: 255, a: 1 };
  }
  function getSiteTextColor() {
    var c = parseRGB(getComputedStyle(document.body).color);
    if (c) return c;
    var de = parseRGB(getComputedStyle(document.documentElement).color);
    return de || { r: 17, g: 17, b: 17, a: 1 };
  }
  function labelColorForField(input) {
    var lab =
      input.closest("label") ||
      (input.parentElement && input.parentElement.querySelector("label")) ||
      null;
    var col = lab ? parseRGB(getComputedStyle(lab).color) : null;
    if (!col)
      col = parseRGB(
        getComputedStyle(input.closest("form") || document.body).color,
      ) || { r: 17, g: 17, b: 17 };
    return col;
  }
  function pastelFromLabel(rgb) {
    return mixWithWhite(rgb, 0.85);
  }
  function textColorFromPageHueAndFieldBg(fieldBgRGB) {
    var pageBg = getEffectiveBgColor(document.body);
    var pageHSL = rgbToHsl(pageBg);
    var fieldHSL = rgbToHsl(fieldBgRGB);
    var s = clamp(fieldHSL.s * 0.85 + 0.1, 0.12, 0.92);
    var goDark = fieldHSL.l > 0.5;
    var l = goDark ? 0.1 : 0.9;
    var target = 4.2;
    var rgb = hslToRgb({ h: pageHSL.h, s: s, l: l });
    var c = contrastRatio(rgb, fieldBgRGB);
    var tries = 24,
      step = 0.03;
    while (c < target && tries--) {
      l = clamp(goDark ? l - step : l + step, 0.0, 1.0);
      rgb = hslToRgb({ h: pageHSL.h, s: s, l: l });
      c = contrastRatio(rgb, fieldBgRGB);
      if (l === 0.0 || l === 1.0) break;
    }
    var relax = 0.12,
      stepRelax = 0.01,
      moved = 0;
    while (moved < relax) {
      var testL = clamp(goDark ? l + stepRelax : l - stepRelax, 0.0, 1.0);
      var testRgb = hslToRgb({ h: pageHSL.h, s: s, l: testL });
      if (contrastRatio(testRgb, fieldBgRGB) >= target) {
        l = testL;
        rgb = testRgb;
        moved += stepRelax;
      } else {
        break;
      }
    }
    return rgb;
  }
  function focusColor() {
    return midpoint(getSiteTextColor(), getEffectiveBgColor(document.body));
  }
  function borderFromBg(bg) {
    return {
      r: Math.max(0, Math.round(bg.r * 0.88)),
      g: Math.max(0, Math.round(bg.g * 0.88)),
      b: Math.max(0, Math.round(bg.b * 0.88)),
    };
  }

  /* ---------- Theme application ---------- */
  function styleFrame(form) {
    var pageBg = getEffectiveBgColor(form);

    // Derive a readable text color from the local background hue
    var textCol = textColorFromPageHueAndFieldBg(pageBg);
    form.style.color = rgbCss(textCol);

    // Keep existing subtle frame adaptation (border + shadow)
    if (relLum(pageBg) < 0.5) {
      form.style.borderColor = "rgba(255,255,255,.18)";
      form.style.boxShadow = "0 2px 12px rgba(0,0,0,.35)";
    } else {
      form.style.borderColor = "rgba(0,0,0,.12)";
      form.style.boxShadow = "0 2px 12px rgba(0,0,0,.06)";
    }
  }
  function styleFields(form) {
    var cls = form.dataset.ocfliteThemeId;
    if (!cls) {
      cls = "ocflite-theme-" + Math.random().toString(36).slice(2, 8);
      form.dataset.ocfliteThemeId = cls;
      form.classList.add(cls);
    }
    var ref = form.querySelector(
      "input:not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]), textarea, select",
    );
    var labelCol = ref ? labelColorForField(ref) : getSiteTextColor();
    var fieldBgGlobal = pastelFromLabel(labelCol);
    var textColGlobal = textColorFromPageHueAndFieldBg(fieldBgGlobal);
    var borderGlobal = borderFromBg(fieldBgGlobal);

    var inputs = form.querySelectorAll(
      "input:not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]), textarea, select",
    );
    inputs.forEach(function (el) {
      var labelColI = labelColorForField(el);
      var fieldBg = pastelFromLabel(labelColI);
      var textCol = textColorFromPageHueAndFieldBg(fieldBg);
      var border = borderFromBg(fieldBg);
      var placeholderCss =
        "rgba(" + textCol.r + "," + textCol.g + "," + textCol.b + ",0.65)";
      el.style.setProperty("background-color", rgbCss(fieldBg), "important");
      el.style.setProperty("color", rgbCss(textCol), "important");
      el.style.setProperty("caret-color", rgbCss(textCol), "important");
      el.style.setProperty("border-color", rgbCss(border), "important");
      el.style.setProperty("background-clip", "padding-box");

      var pid = el.dataset.ocflitePlId;
      if (!pid) {
        pid = "ocflite-pl-" + Math.random().toString(36).slice(2, 8);
        el.dataset.ocflitePlId = pid;
        el.classList.add(pid);
      }
      var sid = "ocflite-pl-style-" + pid;
      var prev = document.getElementById(sid);
      if (prev && prev.parentNode) prev.parentNode.removeChild(prev);
      var st = document.createElement("style");
      st.id = sid;
      st.type = "text/css";
      st.appendChild(
        document.createTextNode(
          "input." +
            pid +
            "::placeholder, textarea." +
            pid +
            "::placeholder{ color:" +
            placeholderCss +
            " !important; }",
        ),
      );
      document.head.appendChild(st);

      if (!el.dataset.ocflitePhBound) {
        var ph = el.getAttribute("placeholder");
        if (ph !== null && el.dataset.ocflitePh == null)
          el.dataset.ocflitePh = ph;
        el.addEventListener(
          "focus",
          function (e) {
            var t = e.currentTarget;
            if (t.getAttribute("placeholder") && t.dataset.ocflitePh == null) {
              t.dataset.ocflitePh = t.getAttribute("placeholder");
            }
            t.setAttribute("placeholder", "");
          },
          { passive: true },
        );
        el.addEventListener(
          "blur",
          function (e) {
            var t = e.currentTarget;
            if (!t.value && t.dataset.ocflitePh != null) {
              t.setAttribute("placeholder", t.dataset.ocflitePh);
            }
          },
          { passive: true },
        );
        el.dataset.ocflitePhBound = "1";
      }
    });

    var fcol = rgbCss(focusColor());
    var focusId = "ocflite-focus-" + cls;
    var old = document.getElementById(focusId);
    if (old && old.parentNode) old.parentNode.removeChild(old);
    var fstyle = document.createElement("style");
    fstyle.id = focusId;
    fstyle.type = "text/css";
    fstyle.appendChild(
      document.createTextNode(
        ".ocflite-form." +
          cls +
          " input:not([type=checkbox]):not([type=radio]):focus," +
          ".ocflite-form." +
          cls +
          " textarea:focus," +
          ".ocflite-form." +
          cls +
          " select:focus{outline:none!important;box-shadow:0 0 0 2px " +
          fcol +
          " !important;}" +
          ".ocflite-form." +
          cls +
          " input[type=checkbox]:focus,.ocflite-form." +
          cls +
          " input[type=checkbox]:focus-visible{outline:none!important;box-shadow:none!important;}" +
          ".ocflite-form." +
          cls +
          " input[type=checkbox]:not(:checked):focus{border-color:" +
          rgbCss(borderFromBg(fieldBgGlobal)) +
          " !important;}",
      ),
    );
    document.head.appendChild(fstyle);

    var btn = form.querySelector(
      ".ocflite-row--submit .wp-element-button, .ocflite-row--submit button[type=submit]",
    );
    var btnBg = btn ? parseRGB(getComputedStyle(btn).backgroundColor) : null;
    if (
      !btnBg ||
      getComputedStyle(btn).backgroundColor === "rgba(0, 0, 0, 0)"
    ) {
      var txtC = btn
        ? parseRGB(getComputedStyle(btn).color)
        : { r: 0, g: 122, b: 255 };
      btnBg = txtC || { r: 0, g: 122, b: 255 };
    }

    var cbId = "ocflite-cb-" + cls;
    var oldCb = document.getElementById(cbId);
    if (oldCb && oldCb.parentNode) oldCb.parentNode.removeChild(oldCb);
    var cbStyle = document.createElement("style");
    cbStyle.id = cbId;
    cbStyle.type = "text/css";
    var btnTextCol = btn
      ? parseRGB(getComputedStyle(btn).color)
      : { r: 255, g: 255, b: 255 };
    var checkSvg = encodeURIComponent(
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 10"><path fill="' +
        rgbCss(btnTextCol) +
        '" d="M4.5 7.5L1.5 4.7 0 6.2 4.5 10 12 2.6 10.5 1z"/></svg>',
    );
    var css =
      ".ocflite-form." +
      cls +
      " input[type=checkbox]{appearance:none;-webkit-appearance:none;width:16px;height:16px;display:inline-block;vertical-align:middle;border:1px solid " +
      rgbCss(borderGlobal) +
      " !important;background-color:" +
      rgbCss(fieldBgGlobal) +
      " !important;border-radius:3px;cursor:pointer;transition:filter .15s ease}" +
      ".ocflite-form." +
      cls +
      " input[type=checkbox]:hover{filter:brightness(0.98)}" +
      ".ocflite-form." +
      cls +
      " input[type=checkbox]:checked{background-color:" +
      rgbCss(btnBg) +
      " !important;border-color:" +
      rgbCss(btnBg) +
      ' !important;background-image:url("data:image/svg+xml;utf8,' +
      checkSvg +
      '");background-repeat:no-repeat;background-position:center;background-size:12px 12px}' +
      ".ocflite-form." +
      cls +
      " input[type=checkbox]:disabled{opacity:.6;cursor:not-allowed}";
    cbStyle.appendChild(document.createTextNode(css));
    document.head.appendChild(cbStyle);
  }

  function applyTheme(form) {
    styleFrame(form);
    styleFields(form);
  }
  function initAllForms() {
    document.querySelectorAll("form[data-ocflite]").forEach(applyTheme);
  }
  window.addEventListener("resize", initAllForms, { passive: true });
  document.addEventListener("visibilitychange", function () {
    if (!document.hidden) initAllForms();
  });
  if ("MutationObserver" in window) {
    var mo = new MutationObserver(initAllForms);
    mo.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ["class", "data-theme"],
    });
    mo.observe(document.body, {
      attributes: true,
      attributeFilter: ["class", "data-theme"],
    });
    mo.observe(document.body, { childList: true, subtree: true });
  }

  /* ---------- I18n helpers ---------- */
  function detectLocale() {
    var l =
      typeof window.OCFLITE !== "undefined" && OCFLITE.locale
        ? String(OCFLITE.locale).toLowerCase()
        : "en_us";
    if (l.indexOf("fr") === 0) return "fr";
    if (l.indexOf("es") === 0) return "es";
    if (
      l.indexOf("pt-br") === 0 ||
      l.indexOf("pt_br") === 0 ||
      l.indexOf("pt") === 0
    )
      return "pt-br";
    return "en";
  }
  function getFormMsg(form, key, fallback) {
    if (window.OCFLITE && OCFLITE.i18n) {
      if (key === "msg-required" && OCFLITE.i18n.required)
        return OCFLITE.i18n.required;
      if (key === "msg-email-invalid" && OCFLITE.i18n.emailInvalid)
        return OCFLITE.i18n.emailInvalid;
    }
    var v = form.getAttribute("data-" + key);
    if (v && v.trim()) return v.trim();
    return fallback;
  }
  function localizedRequired() {
    if (window.OCFLITE && OCFLITE.i18n && OCFLITE.i18n.required)
      return OCFLITE.i18n.required;
    var l = detectLocale();
    if (l === "fr") return "Veuillez compléter ce champ.";
    if (l === "es") return "Por favor complete este campo.";
    if (l === "pt-br") return "Preencha este campo.";
    return "Please fill out this field.";
  }
  function localizedEmail() {
    if (window.OCFLITE && OCFLITE.i18n && OCFLITE.i18n.emailInvalid)
      return OCFLITE.i18n.emailInvalid;
    var l = detectLocale();
    if (l === "fr") return "Veuillez saisir une adresse e-mail valide.";
    if (l === "es")
      return "Por favor introduce una dirección de correo válida.";
    if (l === "pt-br") return "Insira um endereço de e-mail válido.";
    return "Please enter a valid email address.";
  }

  /* ---------- reCAPTCHA helpers (fully self-contained) ---------- */
  function recaptchaEnabled() {
    return !!(
      window.OCFLITE &&
      OCFLITE.recaptcha &&
      OCFLITE.recaptcha.enabled &&
      OCFLITE.recaptcha.siteKey
    );
  }
  function loadRecaptcha(siteKey) {
    return new Promise(function (resolve, reject) {
      if (window.grecaptcha && typeof window.grecaptcha.ready === "function")
        return resolve();
      var s = document.createElement("script");
      s.src =
        "https://www.google.com/recaptcha/api.js?render=" +
        encodeURIComponent(siteKey);
      s.async = true;
      s.defer = true;
      s.onload = function () {
        resolve();
      };
      s.onerror = function () {
        reject(new Error("recaptcha load failed"));
      };
      document.head.appendChild(s);
    });
  }
  async function executeRecaptcha() {
    if (!recaptchaEnabled()) return null;
    var siteKey = OCFLITE.recaptcha.siteKey;
    var action = OCFLITE.recaptcha.action || "contact_form";
    try {
      await loadRecaptcha(siteKey);
      await new Promise(function (res) {
        grecaptcha.ready(res);
      });
      var token = await grecaptcha.execute(siteKey, { action: action });
      return token || null;
    } catch (_) {
      return null;
    }
  }

  /* ---------- Tooltip + validation ---------- */
  var activeTip = null,
    activeTarget = null;
  function hideTip() {
    if (activeTip && activeTip.parentNode)
      activeTip.parentNode.removeChild(activeTip);
    activeTip = null;
    activeTarget = null;
  }
  function positionTip() {
    if (!activeTip || !activeTarget) return;
    var r = activeTarget.getBoundingClientRect();
    var top = window.scrollY + r.bottom + 8;
    var left = window.scrollX + r.left;
    var maxTop =
      window.scrollY + window.innerHeight - (activeTip.offsetHeight + 8);
    if (top > maxTop) top = Math.max(window.scrollY + r.bottom + 4, maxTop);
    activeTip.style.top = Math.max(0, top) + "px";
    activeTip.style.left = left + "px";
  }
  function showTip(input, msg) {
    hideTip();
    if (!msg) return;
    var tip = document.createElement("div");
    tip.className = "ocflite-tip";
    tip.setAttribute("role", "alert");
    tip.textContent = msg;
    document.body.appendChild(tip);
    activeTip = tip;
    activeTarget = input;
    positionTip();
  }
  window.addEventListener("scroll", positionTip, { passive: true });
  window.addEventListener("resize", positionTip);
  document.addEventListener("click", function (e) {
    if (activeTip && activeTarget && !activeTarget.contains(e.target))
      hideTip();
  });

  function validateForm(form) {
    var requiredMsg = getFormMsg(form, "msg-required", localizedRequired());
    var emailMsg = getFormMsg(form, "msg-email-invalid", localizedEmail());
    var fields = form.querySelectorAll("input, textarea, select");
    for (var i = 0; i < fields.length; i++) {
      var el = fields[i];
      if (el.name === "ocflite_hp") continue;
      el.addEventListener("input", hideTip, { once: true });
      if (el.hasAttribute("required")) {
        var type = (el.getAttribute("type") || "").toLowerCase();
        var val = (el.value || "").trim();
        if (type === "checkbox" || type === "radio") {
          if (!el.checked) {
            var msg =
              el.name === "consent"
                ? window.OCFLITE &&
                  OCFLITE.i18n &&
                  OCFLITE.i18n.errors &&
                  OCFLITE.i18n.errors["consent_required"]
                  ? OCFLITE.i18n.errors["consent_required"]
                  : requiredMsg
                : requiredMsg;
            showTip(el, msg);
            el.focus();
            return false;
          }
        } else if (!val) {
          showTip(el, requiredMsg);
          el.focus();
          return false;
        }
      }
      if ((el.getAttribute("type") || "").toLowerCase() === "email") {
        var v = (el.value || "").trim();
        if (v && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v)) {
          showTip(el, emailMsg);
          el.focus();
          return false;
        }
      }
    }
    hideTip();
    return true;
  }

  /* ---------- Submit logic ---------- */
  function getEndpoint() {
    if (window.OCFLITE && OCFLITE.endpoint) return OCFLITE.endpoint;
    return "/wp-json/oneclick-form-lite/v1/submit";
  }
  function showMessage(form, html, type) {
    var box = form.querySelector("[data-ocflite-message]");
    if (box) {
      var txt = String(html || '')
        .replace(/<br\s*\/?>/gi, "\n")
        .replace(/<[^>]+>/g, "");
      box.textContent = txt;
      box.setAttribute("role", "status");
      box.setAttribute("aria-live", "polite");
      box.classList.remove("ocflite-msg--error", "ocflite-msg--success");
      box.classList.add(
        type === "error" ? "ocflite-msg--error" : "ocflite-msg--success",
      );
    } else if (html) {
      var txt = html.replace(/<[^>]+>/g, "");
      alert(txt);
    }
  }
  function tErr(code) {
    var map =
      window.OCFLITE && OCFLITE.i18n && OCFLITE.i18n.errors
        ? OCFLITE.i18n.errors
        : {};
    return map[code] || map["invalid_response"] || "An error occurred.";
  }
  function i18nSending() {
    if (window.OCFLITE && OCFLITE.i18n && OCFLITE.i18n.sending)
      return OCFLITE.i18n.sending;
    return "Sending";
  }
  function setSubmitting(form, isSubmitting) {
    var btns = form.querySelectorAll(
      'button[type="submit"],input[type="submit"]',
    );
    var sendingTxt = i18nSending();
    btns.forEach(function (btn) {
      if (isSubmitting) {
        btn.dataset._ocflitePrevText = btn.innerText || btn.value || "";
        btn.disabled = true;
        btn.setAttribute("aria-disabled", "true");
        if (btn.innerText) {
          btn.innerText = sendingTxt;
        }
        if (btn.value && !btn.innerText) {
          btn.value = sendingTxt;
        }
      } else {
        btn.disabled = false;
        btn.removeAttribute("aria-disabled");
        if (btn.dataset._ocflitePrevText) {
          if (btn.innerText) btn.innerText = btn.dataset._ocflitePrevText;
          if (btn.value && !btn.innerText)
            btn.value = btn.dataset._ocflitePrevText;
        }
      }
    });
    form.setAttribute("aria-busy", isSubmitting ? "true" : "false");
  }

  document.addEventListener("DOMContentLoaded", function () {
    initAllForms();
  });

  document.addEventListener("submit", function (e) {
    var form = e.target.closest("form[data-ocflite]");
    if (!form) return;
    e.preventDefault();
    applyTheme(form);
    if (!validateForm(form)) return;

    var consent = form.querySelector('input[name="consent"]');
    if (consent && !consent.checked) {
      showTip(consent, tErr("consent_required"));
      return;
    }

    var data = new FormData(form);

    // Ensure custom nonce exactly once (no REST header here).
    if (!data.has("ocflite_nonce") && window.OCFLITE && OCFLITE.nonce) {
      data.append("ocflite_nonce", OCFLITE.nonce);
    }

    var headers = {}; // Do not send X-WP-Nonce to avoid rest_cookie_invalid_nonce
    setSubmitting(form, true);

    // Generate token then POST
    executeRecaptcha()
      .then(function (token) {
        if (token) {
          data.append("g-recaptcha-response", token);
          data.append(
            "ocflite_action",
            OCFLITE && OCFLITE.recaptcha && OCFLITE.recaptcha.action
              ? OCFLITE.recaptcha.action
              : "contact_form",
          );
        }
        return fetch(getEndpoint(), {
          method: "POST",
          headers: headers,
          body: data,
        });
      })
      .then(function (res) {
        return res.json().catch(function () {
          return { ok: false, error: "invalid_response" };
        });
      })
      .then(function (json) {
        if (json && json.ok) {
          try {
            form.reset();
          } catch (_) {}
          hideTip();
          var localized = form.getAttribute("data-success-msg");
          var msg =
            localized && localized.trim()
              ? localized
              : window.OCFLITE && OCFLITE.i18n && OCFLITE.i18n.success
                ? OCFLITE.i18n.success
                : "Thank you! Your message has been sent.";
          msg = msg.replace(/([!.?])\s+/, "$1\n");
          showMessage(form, msg, "success");
          form.classList.add("ocflite--sent");
          form.dispatchEvent(
            new CustomEvent("ocflite:success", { bubbles: true, detail: json }),
          );
        } else {
          var code = json && json.error ? json.error : "invalid_response";
          hideTip();
          var btn = form.querySelector(
            '.ocflite-row--submit button, .ocflite-row--submit .wp-element-button, button[type="submit"], input[type="submit"]',
          );
          showTip(btn || form, tErr(code));
          form.dispatchEvent(
            new CustomEvent("ocflite:error", {
              bubbles: true,
              detail: json || { code: "invalid_response" },
            }),
          );
        }
      })
      .catch(function () {
        hideTip();
        var btn = form.querySelector(
          '.ocflite-row--submit button, .ocflite-row--submit .wp-element-button, button[type="submit"], input[type="submit"]',
        );
        showTip(btn || form, tErr("network_error"));
        form.dispatchEvent(
          new CustomEvent("ocflite:error", {
            bubbles: true,
            detail: { code: "network_error" },
          }),
        );
      })
      .finally(function () {
        setSubmitting(form, false);
      });
  });
})();
