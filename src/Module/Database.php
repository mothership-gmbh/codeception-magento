<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mothership\Codeception\Module;

use Codeception\Lib\ModuleContainer;
use Codeception\Module\Db;
use Mothership\Codeception\Helper\DbSettings;

class Database extends Db
{
    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        // This (obviously) NEEDS to happen before the parent construct
        $dbSettings = new DbSettings('app/etc/local.xml');

        $config = [
            'dsn'      => $dbSettings->getDsn(),
            'user'     => $dbSettings->getUsername(),
            'password' => $dbSettings->getPassword()
        ];

        parent::__construct($moduleContainer, $config);
    }
}
