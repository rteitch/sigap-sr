export function readBootBranding() {
    try {
        return JSON.parse(document.body.dataset.branding || '{}');
    } catch {
        return {};
    }
}

export function applyBranding(branding) {
    if (!branding || typeof branding !== 'object') {
        return;
    }

    window.__SIGAP_BRANDING__ = branding;

    const root = document.documentElement;
    Object.entries(branding.palette || {}).forEach(([shade, value]) => {
        root.style.setProperty(`--color-brand-${shade}`, value);
    });

    if (branding.primary_color) {
        document.querySelector('meta[name="theme-color"]')?.setAttribute('content', branding.primary_color);
    }

    if (branding.favicon_url) {
        document.querySelectorAll('link[rel="icon"], link[rel="apple-touch-icon"]').forEach((link) => {
            link.setAttribute('href', branding.favicon_url);
        });
    }

    document.querySelector('meta[name="description"]')?.setAttribute(
        'content',
        `${branding.app_name || 'SIGAP-SR'} — ${branding.tagline || ''}. Data anak dilindungi dan akses dibatasi.`,
    );
}

export function appName(branding) {
    return branding?.app_name || window.__SIGAP_BRANDING__?.app_name || 'SIGAP-SR';
}
