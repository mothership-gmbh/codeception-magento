<?php
/**
 * Based on the DbSettings Class from Magerun; removed the ability to connect to a database or to use this class as an array
 */

namespace Mothership\Codeception\Helper;

use InvalidArgumentException;
use SimpleXMLElement;

/**
 * Class DbSettings
 *
 * Database settings.
 *
 * The Magento database settings are stored in a SimpleXMLElement structure
 *
 * @package Mothership\Codeception
 */
class DbSettings
{
    /**
     * @var string|null known field members
     */
    private $tablePrefix, $host, $port, $unixSocket, $dbName, $username, $password;

    /**
     * @var array field array
     */
    private $config;

    /**
     * @param string $file path to app/etc/local.xml
     */
    public function __construct($file)
    {
        $this->setFile($file);
    }

    /**
     * @param string $file path to app/etc/local.xml
     *
     * @throws InvalidArgumentException if the file is invalid
     */
    public function setFile($file)
    {
        if (!is_readable($file)) {
            throw new InvalidArgumentException(
                sprintf('"app/etc/local.xml"-file %s is not readable', var_export($file, true))
            );
        }

        $saved = libxml_use_internal_errors(true);
        $config = simplexml_load_file($file);
        libxml_use_internal_errors($saved);

        if (false === $config) {
            throw new InvalidArgumentException(
                sprintf('Unable to open "app/etc/local.xml"-file %s and parse it as XML', var_export($file, true))
            );
        }

        $resources = $config->global->resources;
        if (!$resources) {
            throw new InvalidArgumentException('DB global resources was not found in "app/etc/local.xml"-file');
        }

        if (!$resources->default_setup->connection) {
            throw new InvalidArgumentException('DB settings (default_setup) was not found in "app/etc/local.xml"-file');
        }

        $this->parseResources($resources);
    }

    /**
     * helper method to parse config file segment related to the database settings
     *
     * @param SimpleXMLElement $resources
     */
    private function parseResources(SimpleXMLElement $resources)
    {
        // default values
        $config = array(
            'host'        => null,
            'port'        => null,
            'unix_socket' => null,
            'dbname'      => null,
            'username'    => null,
            'password'    => null,
        );

        $config = array_merge($config, (array) $resources->default_setup->connection);
        $config['prefix'] = (string) $resources->db->table_prefix;

        // known parameters: host, port, unix_socket, dbname, username, password, options, charset, persistent,
        //                   driver_options
        //                   (port is deprecated; removed in magento 2, use port in host setting <host>:<port>)

        unset($config['comment']);

        /* @see Varien_Db_Adapter_Pdo_Mysql::_connect */
        if (strpos($config['host'], '/') !== false) {
            $config['unix_socket'] = (string) $config['host'];
            $config['host'] = null;
            $config['port'] = null;
        } elseif (strpos($config['host'], ':') !== false) {
            list($config['host'], $config['port']) = explode(':', $config['host']);
            $config['unix_socket'] = null;
        }

        $this->config = $config;

        $this->tablePrefix = $config['prefix'];
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->unixSocket = $config['unix_socket'];
        $this->dbName = $config['dbname'];
        $this->username = $config['username'];
        $this->password = $config['password'];
    }

    /**
     * Get Mysql PDO DSN
     *
     * @return string
     */
    public function getDsn()
    {
        $dsn = 'mysql:';

        $named = array();

        // blacklisted in prev. DSN creation: username, password, options, charset, persistent, driver_options, dbname

        if (isset($this->unixSocket)) {
            $named['unix_socket'] = $this->unixSocket;
        } else {
            $named['host'] = $this->host;
            if (isset($this->port)) {
                $named['port'] = $this->port;
            }
        }

        $options = array();
        foreach ($named as $name => $value) {
            $options[$name] = "{$name}={$value}";
        }

        return $dsn . implode(';', $options);
    }

    /**
     * @return bool
     */
    public function isSocketConnect()
    {
        return isset($this->config['unix_socket']);
    }

    /**
     * @return string table prefix, null if not in the settings (no or empty prefix)
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * @return string hostname, null if there is no hostname setup (e.g. unix_socket)
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string port, null if not setup
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string unix socket, null if not in use
     */
    public function getUnixSocket()
    {
        return $this->unixSocket;
    }

    /**
     * content of previous $dbSettings field of the DatabaseHelper
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string of the database identifier, null if not in use
     */
    public function getDatabaseName()
    {
        return $this->dbName;
    }
}
