<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @if($link->og_title)
    <meta property="og:title" content="{{ $link->og_title }}">
    @endif
    @if($link->og_description)
    <meta property="og:description" content="{{ $link->og_description }}">
    @endif
    @if($link->og_image_url)
    <meta property="og:image" content="{{ $link->og_image_url }}">
    @endif
    @if($appleAppId)
    <meta name="apple-itunes-app" content="app-id={{ $appleAppId }}, app-argument={{ $deepLinkUri }}">
    @endif
    <title>{{ $link->og_title ?? 'Opening App...' }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
        .container { text-align: center; padding: 2rem; max-width: 360px; }
        .spinner { width: 40px; height: 40px; border: 3px solid #e0e0e0; border-top-color: #333; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 1rem auto; }
        @keyframes spin { to { transform: rotate(360deg); } }
        p { color: #666; margin-top: 1rem; }
        .open-btn { display: inline-block; margin-top: 1.5rem; padding: 0.75rem 2rem; background: #333; color: #fff; text-decoration: none; border-radius: 8px; font-size: 1rem; }
        .store-link { display: block; margin-top: 1rem; color: #999; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="spinner"></div>
    <p>Opening app&hellip;</p>
    <a id="open-btn" class="open-btn" href="{{ $deepLinkUri }}" style="display:none">Open in App</a>
    @if($platform === 'ios' && $appStoreUrl)
    <a class="store-link" href="{{ $appStoreUrl }}">Download on the App Store</a>
    @elseif($platform === 'android' && $playStoreUrl)
    <a class="store-link" href="{{ $playStoreUrl }}">Get it on Google Play</a>
    @elseif($fallbackUrl)
    <a class="store-link" href="{{ $fallbackUrl }}">Open website</a>
    @endif
</div>

<script>
(function () {
    var fingerprint  = {!! json_encode($fingerprint,     JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
    var deepLinkUri  = {!! json_encode($deepLinkUri,     JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
    var appStoreUrl  = {!! json_encode($appStoreUrl,     JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
    var playStoreUrl = {!! json_encode($playStoreUrl,    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
    var fallbackUrl  = {!! json_encode($fallbackUrl,     JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
    var platform     = {!! json_encode($platform,        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
    var androidPkg   = {!! json_encode($androidPackage,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};

    // Step 1: Send beacon to update fingerprint with screen dims
    if (navigator.sendBeacon) {
        var lang = navigator.language || '';
        var tz = Intl && Intl.DateTimeFormat ? Intl.DateTimeFormat().resolvedOptions().timeZone : '';
        var payload = JSON.stringify({ fp: fingerprint, w: screen.width, h: screen.height, lang: lang, tz: tz });
        navigator.sendBeacon('/api/internal/fingerprint-update', new Blob([payload], { type: 'application/json' }));
    }

    // Build the effective deep link URI.
    // For Android App Links (https://), convert to intent:// so Chrome can
    // open the app and fall back to the Play Store without navigating away.
    // For custom URI schemes or iOS, use the URI as-is.
    function buildEffectiveUri() {
        if (platform === 'android' && androidPkg) {
            try {
                var u = new URL(deepLinkUri);
                if (u.protocol === 'https:' || u.protocol === 'http:') {
                    var fallback = playStoreUrl ? encodeURIComponent(playStoreUrl) : '';
                    return 'intent://' + u.host + u.pathname + u.search
                        + '#Intent'
                        + ';scheme=' + u.protocol.replace(':', '')
                        + ';package=' + androidPkg
                        + (fallback ? ';S.browser_fallback_url=' + fallback : '')
                        + ';end';
                }
            } catch (e) {}
        }
        return deepLinkUri;
    }

    var effectiveUri = buildEffectiveUri();

    // Update the visible "Open in App" button href to the effective URI.
    // For iOS Universal Links this button is the primary mechanism — the OS
    // intercepts user taps on the <a> but ignores programmatic JS navigation.
    var btn = document.getElementById('open-btn');
    btn.setAttribute('href', effectiveUri);

    // Show the button after a short delay so the auto-attempt has a chance first.
    setTimeout(function () { btn.style.display = 'inline-block'; }, 1500);

    // Step 2: Auto-attempt the deep link via hidden anchor at 300ms.
    // Works for custom URI schemes and Android intent:// URLs.
    // iOS Universal Links require a real user tap (see button above).
    var deepLinkStart = null;
    setTimeout(function () {
        deepLinkStart = Date.now();
        var a = document.createElement('a');
        a.setAttribute('href', effectiveUri);
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }, 300);

    // Step 3: After 2500ms redirect to store unless the app opened.
    // Use elapsed-time detection: if JS was paused (app opened), the timer
    // fires late (>3500ms after deepLinkStart). visibilitychange is unreliable
    // on Android Chrome during URI scheme resolution.
    //
    // For Android intent:// the browser already handles the store fallback
    // natively, so we only need this for iOS and custom schemes.
    setTimeout(function () {
        var elapsed = deepLinkStart ? (Date.now() - deepLinkStart) : 0;
        if (elapsed > 3500) return; // JS was paused — app opened

        var storeUrl = null;
        if (platform === 'ios' && appStoreUrl) {
            storeUrl = appStoreUrl;
        } else if (platform === 'android' && !androidPkg && playStoreUrl) {
            // Only redirect manually if we couldn't build an intent:// URL
            storeUrl = playStoreUrl;
        } else if (platform !== 'android' && fallbackUrl) {
            storeUrl = fallbackUrl;
        }
        if (storeUrl) {
            window.location.replace(storeUrl);
        }
    }, 2500);
})();
</script>
</body>
</html>
