<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Stores the default settings for the ContentSecurityPolicy, if you
 * choose to use it. The values here will be read in and set as defaults
 * for the site. If needed, they can be overridden on a page-by-page basis.
 *
 * Suggested reference for explanations:
 *
 * @see https://www.html5rocks.com/en/tutorials/security/content-security-policy/
 */
class ContentSecurityPolicy extends BaseConfig
{
    // -------------------------------------------------------------------------
    // Broadbrush CSP management
    // -------------------------------------------------------------------------

    /**
     * Default CSP report context
     */
    /**
     * Start in Report-Only to beobachten, dann auf false setzen,
     * sobald alle benötigten Quellen (ggf. dynamisch) sauber erfasst sind.
     */
    public bool $reportOnly = false;

    /**
     * Specifies a URL where a browser will send reports
     * when a content security policy is violated.
     */
    /**
     * Optionaler Endpoint für CSP-Reports (kann über Nginx/Collector entgegengenommen werden)
     */
    public ?string $reportURI = null;

    /**
     * Instructs user agents to rewrite URL schemes, changing
     * HTTP to HTTPS. This directive is for websites with
     * large numbers of old URLs that need to be rewritten.
     */
    public bool $upgradeInsecureRequests = false;

    // -------------------------------------------------------------------------
    // Sources allowed
    // NOTE: once you set a policy to 'none', it cannot be further restricted
    // -------------------------------------------------------------------------

    /**
     * Will default to self if not overridden
     *
     * @var list<string>|string|null
     */
    /**
     * Strikte Basis: nur eigene Origin
     */
    public $defaultSrc = 'self';

    /**
     * Lists allowed scripts' URLs.
     *
     * @var list<string>|string
     */
    /**
     * Plugins werden als ES-Module von /plugins/* (gleiche Origin) geladen → 'self' reicht
     * Keine Inline-Skripte; falls notwendig, Nonces/Hashes verwenden.
     */
    public $scriptSrc = 'self';

    /**
     * Lists allowed stylesheets' URLs.
     *
     * @var list<string>|string
     */
    /**
     * Keine Inline-Styles, Styles ausschließlich lokal.
     */
    public $styleSrc = 'self';

    /**
     * Defines the origins from which images can be loaded.
     *
     * @var list<string>|string
     */
    /**
     * Erlaubt lokale Bilder + data: (z. B. eingebettete SVG/Icons). Externe Bild-Domains
     * können zur Laufzeit via service('csp')->addImageSrc('https://...') ergänzt werden.
     */
    public $imageSrc = ['self', 'data:'];

    /**
     * Restricts the URLs that can appear in a page's `<base>` element.
     *
     * Will default to self if not overridden
     *
     * @var list<string>|string|null
     */
    /**
     * Basis-URI auf eigene Origin beschränken
     */
    public $baseURI = 'self';

    /**
     * Lists the URLs for workers and embedded frame contents
     *
     * @var list<string>|string
     */
    public $childSrc = 'self';

    /**
     * Limits the origins that you can connect to (via XHR,
     * WebSockets, and EventSource).
     *
     * @var list<string>|string
     */
    /**
     * Externe APIs werden standardmäßig NICHT direkt erlaubt.
     * Zwei Wege für Plugins:
     *  - Server-Proxy unter /api/plugins/{id}/fetch → bleibt 'self'.
     *  - Oder Origins pro Response dynamisch ergänzen (Manifest/Permissions),
     *    z. B. service('csp')->addConnectSrc('https://api.example.com').
     */
    public $connectSrc = 'self';

    /**
     * Specifies the origins that can serve web fonts.
     *
     * @var list<string>|string
     */
    /**
     * Webfonts lokal (self); data: für eingebettete WOFF2-Fonts erlaubt
     */
    public $fontSrc = ['self', 'data:'];

    /**
     * Lists valid endpoints for submission from `<form>` tags.
     *
     * @var list<string>|string
     */
    public $formAction = 'self';

    /**
     * Specifies the sources that can embed the current page.
     * This directive applies to `<frame>`, `<iframe>`, `<embed>`,
     * and `<applet>` tags. This directive can't be used in
     * `<meta>` tags and applies only to non-HTML resources.
     *
     * @var list<string>|string|null
     */
    /**
     * Schutz vor Clickjacking – Seite darf nur von sich selbst gerahmt werden
     */
    public $frameAncestors = 'self';

    /**
     * The frame-src directive restricts the URLs which may
     * be loaded into nested browsing contexts.
     *
     * @var list<string>|string|null
     */
    /**
     * Iframe-Kacheln für externe Seiten benötigen ggf. dynamische Freigabe:
     * service('csp')->addFrameSrc('https://ziel.example');
     */
    public $frameSrc = 'self';

    /**
     * Restricts the origins allowed to deliver video and audio.
     *
     * @var list<string>|string|null
     */
    public $mediaSrc = 'self';

    /**
     * Allows control over Flash and other plugins.
     *
     * @var list<string>|string
     */
    /**
     * Plugins wie Flash/Java sind nicht erlaubt
     */
    public $objectSrc = 'none';

    /**
     * @var list<string>|string|null
     */
    public $manifestSrc = 'self';

    /**
     * Limits the kinds of plugins a page may invoke.
     *
     * @var list<string>|string|null
     */
    public $pluginTypes;

    /**
     * List of actions allowed.
     *
     * @var list<string>|string|null
     */
    public $sandbox;

    /**
     * Nonce tag for style
     */
    public string $styleNonceTag = '{csp-style-nonce}';

    /**
     * Nonce tag for script
     */
    public string $scriptNonceTag = '{csp-script-nonce}';

    /**
     * Replace nonce tag automatically
     */
    public bool $autoNonce = true;
}
