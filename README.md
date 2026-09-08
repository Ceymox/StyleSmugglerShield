# StyleSmuggler Shield for Magento 2

**Ceymox_StyleSmugglerShield** is a free Magento 2 / Adobe Commerce security module that
blocks the **StyleSmuggler** GraphQL template-injection vulnerability, disclosed by
[Sansec](https://sansec.io/research/stylesmuggler) and actively exploited in the wild
since September 4, 2026.

Adobe released an official fix on September 7, 2026 —
**[CVE-2026-75650 / APSB26-146](https://experienceleague.adobe.com/en/docs/commerce-knowledge-base/kb/announcements/commerce-apsb26-146)**,
CVSS 10.0, patch identifier `VULN-39341`. Apply that patch first — it's the real fix.
This module remains useful as defense-in-depth and for stores that haven't applied
`VULN-39341` yet.

## The vulnerability

Attackers send a GraphQL request whose `styles` input value smuggles Magento template
directive syntax (`{{block ...}}`, `{{template ...}}`, `{{layout ...}}`) past existing
input validation. The payload sits dormant until Magento renders the **Payment
Transaction Failed Reminder** transactional email — a notification the malware
deliberately triggers — at which point the directive executes with full PHP code
execution on the server.

Adobe's fix is tracked as **CVE-2026-75650** ([APSB26-146](https://experienceleague.adobe.com/en/docs/commerce-knowledge-base/kb/announcements/commerce-apsb26-146)),
patch `VULN-39341`, released September 7, 2026.

## What this module does

Three independent, non-breaking protection layers:

1. **GraphQL request guard** — inspects every GraphQL query and its variables (including
   HTML-entity and URL-encoded obfuscation) for Magento directive syntax and rejects the
   request with an HTTP 400 before it reaches a resolver or gets persisted.
2. **Transactional email sanitizer** — strips directive syntax out of every dynamic value
   passed to *any* outgoing transactional email (order, invoice, shipment, payment-failed
   reminder, etc.) before it reaches Magento's template rendering engine.
3. **DI compiler scanner CLI guard** — blocks Magento's `setup:di:compile` scanner classes
   (`ArrayScanner`, `ClassesScanner`, `XmlInterceptorScanner`, and the rest of
   `ScannerInterface`) from running outside a CLI process. These classes `include`/
   `require_once` arbitrary file paths with no execution-context check of their own, and
   have no legitimate reason to run during a web request — closing this off is
   independent, root-cause hardening regardless of how a payload reaches this point.

All three layers are logged to `var/log/style_smuggler_shield.log` (payload + IP where
applicable) and are individually toggleable from the admin panel.

## Installation

### Composer (recommended)

Published on [Packagist](https://packagist.org/packages/ceymox/module-style-smuggler-shield):

```bash
composer require ceymox/module-style-smuggler-shield
php bin/magento module:enable Ceymox_StyleSmugglerShield
php bin/magento setup:upgrade
php bin/magento cache:flush
```

### Manual

```bash
cd <magento-root>
mkdir -p app/code/Ceymox
git clone git@github.com:Ceymox/StyleSmugglerShield.git app/code/Ceymox/StyleSmugglerShield
php bin/magento module:enable Ceymox_StyleSmugglerShield
php bin/magento setup:upgrade
php bin/magento cache:flush
```

## Configuration

**Admin Panel → Stores → Configuration → General → Security → StyleSmuggler Protection**

| Setting | Default |
|---|---|
| Enable Protection | Yes |
| Reject GraphQL Requests Containing Template Directive Syntax | Yes |
| Sanitize Transactional Email Template Variables | Yes |
| Restrict DI Compiler Scanners to CLI Only | Yes |

> In production deployment mode, run `bin/magento setup:di:compile` after
> installing or updating this module so the new plugins are baked into the
> generated interceptor classes.

## Compatibility

- Magento Open Source / Adobe Commerce 2.4.7 – 2.4.9
- PHP 8.1 – 8.3

## Disclaimer

This is a **virtual patch**, not a substitute for Adobe's official fix
(`VULN-39341` / CVE-2026-75650). Apply that patch — it corrects the vulnerable
source directly, which this module does not do. Keeping this module active
afterward as defense-in-depth is harmless either way.

Applying the patch does not clean a store that was already compromised during the
exploitation window (September 4–7, 2026). Given active exploitation in the wild,
also scan for existing backdoors (e.g. with Sansec's eComscan) and review admin
users and cron jobs for signs of prior compromise — this module does not do either
of those.

## Credits

Vulnerability research and disclosure: [Sansec](https://sansec.io/research/stylesmuggler)

## License

Copyright © Ceymox. All rights reserved.
