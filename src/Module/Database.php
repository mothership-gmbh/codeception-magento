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

/**
 * Class Database
 *
 * The purpose of this module is to load the DB settings from Magento's local.xml and configure codeception's DB module
 * accordingly.
 * This is especially useful when Codeception Tests are run in different environments and the connection settings
 * shouldn't be hardcoded in the codeception config files.
 *
 * @package Mothership\Codeception\Module
 */
class Database extends Db
{
    /**
     * Database constructor.
     * @param ModuleContainer $moduleContainer
     * @param array           $config
     */
    public function __construct(ModuleContainer $moduleContainer, $config = [])
    {
        // This obviously NEEDS to happen before the parent constructor is called

        // 'config_location' acts as a full override
        if (array_key_exists('config_location', $config)) {
            $configLocation = $config['config_location'];
        } else {
            $configLocation = class_exists('\\Mage') ? \Mage::getBaseDir() . '/app/etc/local.xml' : 'app/etc/local.xml';
        }

        $configNode = array_key_exists('config_node', $config) ? $config['config_node'] : 'default_setup';
        $dbSettings = new DbSettings($configLocation, $configNode);

        $_config = [
            'dsn'      => $dbSettings->getDsn() . ';dbname=' . $dbSettings->getDatabaseName(),
            'user'     => $dbSettings->getUsername(),
            'password' => $dbSettings->getPassword()
        ];

        $this->debugSection('MS-DB-Middleware',
            sprintf("Trying to connect to '%s', user: '%s', db: '%s'. Full DSN: '%s'.",
                $dbSettings->getDsn(), $dbSettings->getUsername(), $dbSettings->getDatabaseName(), $config['dsn'])
        );

        parent::__construct($moduleContainer, $_config);
    }
}
