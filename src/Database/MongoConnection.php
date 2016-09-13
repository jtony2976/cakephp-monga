<?php

namespace CakeMonga\Database;

use Cake\Core\Exception\Exception;
use League\Monga;

/**
 * A CakePHP Connection class for accessing MongoDB NoSQL Data stores
 * 
 * @author Wes King
 * @license MIT
 * @class MongoConnection
 */
class MongoConnection
{
    /**
     * The designated name of this collection class.  When adding your datasource in your app.php file, this is the key
     * that corresponds to this connection inside the 'Datasources' array.
     *
     * @var
     */
    protected $_name;

    /**
     * Holds the configuration for the current connection
     *
     * @var
     */
    public $_config;

    /**
     * Currently unused, boolean value that determines whether ot not we are currently logging queries.  Will be added
     * in later once support for MongoDB context is added for query logging.
     *
     * @var
     */
    protected $_logQueries;

    /**
     * Currently unused, will contain the MongoDB context object for logging queries that gets passed into the
     * MongoClient constructor inside of the Monga library.
     *
     * @var
     */
    protected $_logger;

    /**
     * Boolean that informs us whether we have built a connection in this class or not.
     *
     * @var bool
     */
    protected $_connected = false;

    /**
     * Holds our current Mongo connection for accessing databases and collections.
     *
     * @var null
     */
    protected $_mongo = null;

    /**
     * A list of allowed configuration options that gets passed into our connection.  The MongoClient library throws
     * exceptions if unrecognized keys are passed into the $opts config so we only pass in the following keys as config
     * using the $this->getMongoConfig() method.  Deprecated config options are not included.
     *
     * @var array
     */
    protected $_mongoConfigOpts = ['authMechanism', 'authSource', 'connect', 'connectTimeoutMS', 'db', 'fsync',
    'journal', 'gssapiServiceName', 'password', 'readPreference', 'readPreferenceTags', 'replicaSet',
    'secondaryAcceptableLatencyMS', 'socketTimeoutMS', 'ssl', 'username', 'w', 'wTimeoutMS'];

    /**
     * Currently unused.  The available context configuration closures used for logging queries.
     *
     * @var array
     */
    protected $_mongoContextOpts = ['log_cmd_insertable', 'log_cmd_delete', 'log_cmd_update', 'log_write_batch',
    'log_reply', 'log_getmore', 'log_killcursor'];

    /**
     * MongoConnection constructor.
     * @param $config
     */
    public function __construct($config = [])
    {
        $this->config($config);
    }

    /**
     * Connects to our MongoDB instance and returns the connection object.  If we have connected previously, returns the
     * old connection object that's already been established.
     *
     * @return Monga\Connection|null
     */
    public function connect()
    {
        if ($this->_mongo) {
            return $this->_mongo;
        }

        $this->_mongo = Monga::connection($this->dns(), $this->getMongoConfig());
        $this->_connected = true;
        return $this->_mongo;
    }

    /**
     * Returns whether the current object has established a connection to the MongoDB instance or not.
     *
     * @return bool
     */
    public function connected()
    {
        return $this->_connected;
    }

    /**
     * Returns the config name of this connection defined as the connection array key in the Datasources array in
     * our app.php file.
     *
     * @return string
     */
    public function configName()
    {
        if (empty($this->_config['name'])) {
            return '';
        }
        return $this->_config['name'];
    }

    /**
     * Gets and Sets our configuration array for our connection class.
     *
     * @param null $config
     * @return null
     */
    public function config($config = null)
    {
        if ($this->_config) {
            return $this->_config;
        }
        $this->_config = $config;
        return $this->_config;
    }

    /**
     * Gets or Sets the DNS string for our connection in our configuration array.  If no DNS string is provided, the
     * default localhost DNS is returned.
     *
     * @param null $dns
     * @return null|string
     */
    public function dns($dns = null)
    {
        if ($dns) {
            $this->_config['dns'] = $dns;
            return $dns;
        }

        if (isset($this->_config['dns'])) {
            return $this->_config['dns'];
        }

        return 'mongodb://localhost:27017';
    }

    /**
     * Helper method for returning an associative array with only the keys provided in the second argument.  Used for
     * building our Monga/MongoClient configuration without passing in unneeded keys that will throw errors.
     *
     * @param $array
     * @param array $includedKeys
     * @return array
     */
    protected function arrayInclude($array, Array $includedKeys)
    {
        $config = [];
        foreach($includedKeys as $key){
            if (isset($array[$key])) {
                $config[$key] = $array[$key];
            }
        }
        return $config;
    }

    /**
     * Wraps $this->arrayInclude(...) to provide our Monga/MongoClient configuration array.
     *
     * @return array
     */
    public function getMongoConfig()
    {
        return $this->arrayInclude($this->config(), $this->_mongoConfigOpts);
    }

    /**
     * Mock method included to satisfy CakePHP connection requirements.
     *
     * @param callable $transaction
     */
    public function transactional(callable $transaction)
    {

    }

    /**
     * Mock method included to satisfy CakePHP connection requirements.
     *
     * @param callable $operation
     */
    public function disableConstraints(callable $operation)
    {

    }

    /**
     * Currently unused.  Getter and Setter method for enabling or disabling query logging on the connection class.
     * @param null $enable
     * @return mixed
     */
    public function logQueries($enable = null)
    {
        if ($enable === null) {
            return $this->_logQueries;
        }
        $this->_logQueries = $enable;
    }

    /**
     * Currently unused.  Getter and Setter method for defining the Query Logger object on our connection class.  In a
     * future version, this logger object will provide the stream context for logging queries to the Monga/MongoClient
     * constructor's third argument.
     *
     * @param null $instance
     */
    public function logger($instance = null)
    {

    }

    public function getDefaultDatabase()
    {
        if (!isset($this->_config['database'])) {
            throw new Exception(sprintf('You have not configured a default database for Datasource %s yet.', $this->_config['name']));
        }
        $db = $this->_config['database'];
        return $this->connect()->database($db);
    }
}