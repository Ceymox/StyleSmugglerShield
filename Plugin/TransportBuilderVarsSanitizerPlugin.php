<?php
/**
 * Copyright © Ceymox. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ceymox\StyleSmugglerShield\Plugin;

use Ceymox\StyleSmugglerShield\Model\Config;
use Ceymox\StyleSmugglerShield\Model\DirectiveScanner;
use Magento\Framework\Mail\Template\TransportBuilder;
use Psr\Log\LoggerInterface;

/**
 * Strips Magento template directive syntax out of outgoing email template variables.
 */
class TransportBuilderVarsSanitizerPlugin
{
    /**
     * @param Config $config
     * @param DirectiveScanner $scanner
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Config $config,
        private readonly DirectiveScanner $scanner,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Sanitize template variables before they are stored on the transport builder.
     *
     * @param TransportBuilder $subject
     * @param array $templateVars
     * @return array[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSetTemplateVars(TransportBuilder $subject, $templateVars): array
    {
        if (!$this->config->isEmailVariableSanitizerEnabled() || !is_array($templateVars)) {
            return [$templateVars];
        }

        $matches = $this->scanner->scanStructure($templateVars);
        if (!empty($matches)) {
            $this->logger->warning(
                'StyleSmugglerShield: stripped template directive syntax found in an email template variable',
                ['matched' => reset($matches)]
            );
        }

        return [$this->scanner->sanitizeStructure($templateVars)];
    }
}
