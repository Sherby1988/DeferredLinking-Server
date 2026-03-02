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
    <title>{{ $link->og_title ?? 'Opening App...' }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
        .container { text-align: center; padding: 2rem; }
        .spinner { width: 40px; height: 40px; border: 3px solid #e0e0e0; border-top-color: #333; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 1rem auto; }
        @keyframes spin { to { transform: rotate(360deg); } }
        p { color: #666; margin-top: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="spinner"></div>
    <p>Opening app&hellip;</p>
</div>

<script>
(function () {
    var fingerprint = {{ json_encode($fingerprint, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }};
    var deepLinkUri = {{ json_encode($deepLinkUri, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }};
    var appStoreUrl = {{ json_encode($appStoreUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }};
    var playStoreUrl = {{ json_encode($playStoreUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }};
    var fallbackUrl = {{ json_encode($fallbackUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }};
    var platform = {{ json_encode($platform, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }};

    // Step 1: Send beacon to update fingerprint with screen dims
    if (navigator.sendBeacon) {
        var lang = navigator.language || '';
        var tz = Intl && Intl.DateTimeFormat ? Intl.DateTimeFormat().resolvedOptions().timeZone : '';
        var payload = JSON.stringify({
            fp: fingerprint,
            w: screen.width,
            h: screen.height,
            lang: lang,
            tz: tz
        });
        navigator.sendBeacon('/api/internal/fingerprint-update', new Blob([payload], { type: 'application/json' }));
    }

    var appOpened = false;

    // Step 2: Attempt to open deep link after 300ms
    setTimeout(function () {
        window.location.href = deepLinkUri;
    }, 300);

    // Step 3: Listen for visibilitychange — if page goes hidden, app opened
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            appOpened = true;
        }
    });

    // Step 4: After 2500ms, redirect to store if still visible
    setTimeout(function () {
        if (!appOpened) {
            var storeUrl = null;
            if (platform === 'ios' && appStoreUrl) {
                storeUrl = appStoreUrl;
            } else if (platform === 'android' && playStoreUrl) {
                storeUrl = playStoreUrl;
            } else if (fallbackUrl) {
                storeUrl = fallbackUrl;
            }
            if (storeUrl) {
                window.location.replace(storeUrl);
            }
        }
    }, 2500);
})();
</script>
</body>
</html>
