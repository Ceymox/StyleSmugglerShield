# StyleSmuggler Shield for Magento 2

**Ceymox_StyleSmugglerShield** is a free Magento 2 / Adobe Commerce security module that
blocks the **StyleSmuggler** GraphQL template-injection vulnerability — an unpatched
remote code execution (RCE) zero-day disclosed by [Sansec](https://sansec.io/research/stylesmuggler)
and actively exploited in the wild since September 4, 2026.

If you run Magento Open Source or Adobe Commerce **2.4.7, 2.4.8, or 2.4.9**, this module closes the attack path in minutes.

## The vulnerability

Attackers send a GraphQL request whose `styles` input value smuggles Magento template
directive syntax (`{{block ...}}`, `{{template ...}}`, `{{layout ...}}`) past existing
input validation. The payload sits dormant until Magento renders the **Payment
Transaction Failed Reminder** transactional email — a notification the malware
deliberately triggers — at which point the directive executes with full PHP code
execution on the server.

No CVE has been assigned. As of this writing, no official Adobe security patch has
been published — check [Adobe's Security Bulletins](https://helpx.adobe.com/security/products/magento.html)
for updates before assuming this is resolved upstream.

## What this module does

Two independent, non-breaking protection layers:

1. **GraphQL request guard** — inspects every GraphQL query and its variables (including
   HTML-entity and URL-encoded obfuscation) for Magento directive syntax and rejects the
   request with an HTTP 400 before it reaches a resolver or gets persisted.
2. **Transactional email sanitizer** — strips directive syntax out of every dynamic value
   passed to *any* outgoing transactional email (order, invoice, shipment, payment-failed
   reminder, etc.) before it reaches Magento's template rendering engine.

Both layers are logged to `var/log/style_smuggler_shield.log` (payload + IP) and are
individually toggleable from the admin panel.

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

## Compatibility

- Magento Open Source / Adobe Commerce 2.4.7 – 2.4.9
- PHP 8.1 – 8.3

## Disclaimer

This is a **virtual patch**, not an official Adobe security fix — there isn't one
published yet. If and when Adobe releases one, apply it and keep this module as
defense-in-depth. Given active exploitation in the wild, also scan for existing
backdoors (e.g. with Sansec's eComscan) and review admin users and cron jobs for
signs of prior compromise — this module does not do either of those.

## Credits

Vulnerability research and disclosure: [Sansec](https://sansec.io/research/stylesmuggler)

## License

Copyright © Ceymox. All rights reserved.
