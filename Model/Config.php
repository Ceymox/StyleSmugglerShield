<?php
/**
 * Copyright © Ceymox. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ceymox\StyleSmugglerShield\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    private const XML_PATH_ENABLED = 'security/style_smuggler_shield/enabled';
    private const XML_PATH_BLOCK_GRAPHQL = 'security/style_smuggler_shield/block_graphql_directives';
    private const XML_PATH_SANITIZE_EMAIL_VARS = 'security/style_smuggler_shield/sanitize_email_variables';
    private const XML_PATH_RESTRICT_DI_SCANNERS = 'security/style_smuggler_shield/restrict_di_scanners_to_cli';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    /**
     * Check whether the module is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED);
    }

    /**
     * Check whether the GraphQL request guard is enabled.
     *
     * @return bool
     */
    public function isGraphQlGuardEnabled(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(self::XML_PATH_BLOCK_GRAPHQL);
    }

    /**
     * Check whether the email template variable sanitizer is enabled.
     *
     * @return bool
     */
    public function isEmailVariableSanitizerEnabled(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(self::XML_PATH_SANITIZE_EMAIL_VARS);
    }

    /**
     * Check whether the DI compiler scanners are restricted to CLI execution.
     *
     * @return bool
     */
    public function isDiScannerGuardEnabled(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(self::XML_PATH_RESTRICT_DI_SCANNERS);
    }
}
