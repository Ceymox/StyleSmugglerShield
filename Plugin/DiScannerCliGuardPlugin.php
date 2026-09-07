<?php
/**
 * Copyright © Ceymox. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ceymox\StyleSmugglerShield\Plugin;

use Ceymox\StyleSmugglerShield\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Setup\Module\Di\Code\Reader\ClassesScannerInterface;
use Magento\Setup\Module\Di\Code\Scanner\ScannerInterface;

/**
 * Restricts Magento's DI compiler scanners to CLI execution only.
 *
 * These classes exist to serve `bin/magento setup:di:compile` and read/include
 * arbitrary files with no execution-context check of their own, so they have
 * no legitimate reason to run during a web request.
 */
class DiScannerCliGuardPlugin
{
    /**
     * @param Config $config
     */
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * Block scanning outside of a CLI process.
     *
     * @param ScannerInterface $subject
     * @param callable $proceed
     * @param array $files
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCollectEntities(ScannerInterface $subject, callable $proceed, array $files): array
    {
        $this->guard();

        return $proceed($files);
    }

    /**
     * Block class listing outside of a CLI process.
     *
     * @param ClassesScannerInterface $subject
     * @param callable $proceed
     * @param string $path
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetList(ClassesScannerInterface $subject, callable $proceed, $path): array
    {
        $this->guard();

        return $proceed($path);
    }

    /**
     * Reject the call if it did not originate from a CLI process.
     *
     * @return void
     * @throws LocalizedException
     */
    private function guard(): void
    {
        if ($this->config->isDiScannerGuardEnabled() && PHP_SAPI !== 'cli') {
            throw new LocalizedException(__('This operation is only available from the command line.'));
        }
    }
}
