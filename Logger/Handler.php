<?php
/**
 * Copyright © Ceymox. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ceymox\StyleSmugglerShield\Logger;

use Monolog\Logger as MonologLogger;
use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    /**
     * @var int
     */
    protected $loggerType = MonologLogger::WARNING;

    /**
     * @var string
     */
    protected $fileName = '/var/log/style_smuggler_shield.log';
}
