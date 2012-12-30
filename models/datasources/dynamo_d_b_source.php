<?php
/**
 * DynamoDB DataSource File
 *
 * Copyright (c) 2012 Everton Yoshitani
 *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 *
 * PHP version 5.3
 * CakePHP version 1.3
 *
 * @package     aws
 * @subpackage  aws.models.datasources
 * @since       0.1
 * @copyright   2012 Everton Yoshitani <everton@notreve.com>
 * @license     http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link        http://github.com/anthonyp/CakePHP-AWS-Plugin
 */
 
class DynamodbSource extends DataSource {
    
    /**
     * The description of this data source
     *
     * @var string
     */
    public $description = 'DynamoDB DataSource';
    
    /**
     * Current connection state
     *
     * @var boolean
     */
    public $connected = false;
    
    /**
     * Database instance
     *
     * @var string
     */
    public $connection = null;
    
    /**
     * Base configuration
     *
     * array('datasource'=>'', 'database'=>'', 'host'=>'', 'login'=>'', 'password'=>'')
     *
     * @var array Configuration settings
     */
    public $_config = array(
        'datasource' => '',
        'database' => '',
        'host' => '',
        'login' => '',
        'password' => ''
    );
    
    /**
     * Constructor
     *
     * @param array $config Configuration array.
     * @param boolean $autoConnect Auto connect (default true).
     * @return boolean Success.
     * @since 0.1
     */
    public function __construct($config = array(), $autoConnect = true) {
        $this->setConfig($config);
        parent::__construct($config);
        $this->fullDebug = Configure::read() > 1;
        if ($autoConnect) {
            return $this->connect();
        }
        return true;
    }
    
    /**
     * Deconstructor, closes the current datasource
     *
     * @return boolean Success.
     * @since 0.1
     */
    public function __destruct() {
        if ($this->connected) {
            $this->disconnect();
        }
        return true;
    }
    
    /**
     * Set default configuration
     *
     * @param array $config Configuration settings.
     * @return boolean Success.
     * @author Everton Yoshitani <everton@notreve.com>
     * @since 0.1
     */
    public function setConfig($config = array()) {
        return $this->_config = array_merge($this->_config, $config);
    }
    
    /**
     * Connects to the database using options in the given configuration array
     *
     * @param string $region Region for connect to.
     * @return object Connection object.
     * @since 0.1
     */
    public function connect($region = null) {
        if ($this->connected === true) {
            return $this->connection;
        }
        if (!class_exists('AmazonDynamoDB', false)) {
            require_once(App::pluginPath('AWS') . 'vendors/s_d_k_class.php');
        }
        $options = array(
            'key' => $this->_config['login'],
            'secret' => $this->_config['password'],
            'default_cache_config' => $this->_config['default_cache_config']
        );
        if ($this->connection = new AmazonDynamoDB($options)) {
            if ($region) {
                $this->connection->set_region($region);
            } else {
                $this->connection->set_region($this->_config['host']);
            }
            $this->connected = true;
        }
        return $this->connection;
    }
    
    /**
     * Disconnect from the database
     *
     * Kills the connection and advises that the connection is closed.
     *
     * @return boolean Disconnected.
     * @since 0.1
     */
    public function disconnect() {
        $this->connection = null;
        $this->connected = false;
        return true;
    }
    
    /**
     * Disconnects from the database
     *
     * If DEBUG is turned on (equals to 2) displays the log of stored data.
     *
     * @return boolean Disconnected.
     * @since 0.1
     */
    public function close() {
        if (Configure::read('debug') > 1) {
            $this->showLog();
        }
        return $this->disconnect();
    }
    
    /**
     * List of databases
     *
     * @return array List of databases.
     * @since 0.1
     */
    public function listSources() {
        return $this->connection
                ->list_tables()
                ->body
                ->TableNames()
                ->map_string();
    }
    
    /**
     * Calculate
     *
     * No SQL statments used return always true.
     *
     * @return boolean True.
     * @since 0.1
     */
    public function calculate() {
        return true;
    }
    
    /**
     * Return an array of the fields in given table name
     *
     * @todo Support Hash and Range primary keys.
     * @param object $model Model object of the database table to inspect.
     * @return array Fields in table. Keys are name and type.
     * @since 0.1
     */
    public function describe(&$model) {
        $primaryKey = $this->_getPrimaryKey($model);
        if ($primaryKey['type'] == 'hashAndRange') {
            trigger_error('Hash and Range primary key not support');
        } else {
            $model->primaryKey = $primaryKey['keys']['hash'];
        }
        return $model->schema;
    }
    
    /**
     * The "C" in CRUD
     *
     * Creates new records in the database.
     *
     * @param object $model Model object that the record is for.
     * @param array $fields An array of field names to insert. If null, 
     *        $model->data will be used to generate field names.
     * @param array $values An array of values with keys matching the fields. 
     *        If null, $model->data will be used to generate values. 
     * @return boolean Success.
     * @since 0.1
     */
    public function create(&$model, $fields = null, $values = null) {
        if (!$this->connected) {
            return false;
        }
        if ($fields == null) {
            unset($fields, $values);
            $fields = array_keys($model->data);
            $values = array_values($model->data);
        }
        if ($fields !== null && $values !== null) {
            $data = array_combine($fields, $values);
        } else {
            $data = $Model->data;
        }
        $data = $this->_setVarTypes($data);
        if (empty($data[$model->primaryKey])) {
            $data[$model->primaryKey] = array_shift($this->_setPrimaryKey($model));
        }
        $options = array(
            'TableName' => $model->table,
            'Item' => $data
        );
        $result = $this->connection->put_item($options);
        if (!empty($result)) {
            $model->setInsertId($this->_getPrimaryKeyValue($data[$model->primaryKey]));
            $model->id = $this->_getPrimaryKeyValue($data[$model->primaryKey]);
            return true;
        }
        return false;
    }
    
    /**
     * The "R" in CRUD
     *
     * Reads record(s) from the database.
     *
     * @param object $model A Model object that the query is for.
     * @param array $query An array of queryData information containing 
     *        keys similar to Model::find().
     * @param integer $recursive Number of levels of association.
     * @return mixed Boolean false on error/failure.
     *         An array of results on success.
     * @since 0.1
     */
    public function read(&$model, $query = array(), $recursive = null) {
        if (!$this->connected) {
            return false;
        }
        $params = array(
            'TableName' => $model->table
        );
        if (!empty($query['conditions'])) {
            $key = $query['conditions'][$model->alias .'.'. $model->primaryKey];
            if (isset($key)) {
                $params['Key'] = array(
                    'HashKeyElement' => array(AmazonDynamoDB::TYPE_NUMBER => (string)$key)
                );
            } else {
                $params['Key'] = array(
                    'HashKeyElement' => array(AmazonDynamoDB::TYPE_NUMBER => (string)$model->id)
                );
            }
            $response = $this->connection->get_item($params);
            $results = $this->_parseItem($model, $response);
        } else {
            $response = $this->connection->scan($params);
            $results = $this->_parseItems($model, $response);
        }
        if ($model->findQueryType == 'count') {
            return array('0'=>array('0'=>array('count'=>count($results))));
        }
        return $results;
    }
    
    /**
     * The "U" in CRUD
     *
     * Update record from the database.
     *
     * @param object $model Model object that the record is for.
     * @param array $fields An array of field names to update. If null, 
     *        $model->data will be used to generate field names.
     * @param array $values An array of values with keys matching the fields. 
     *        If null, $model->data will be used to generate values.
     * @return boolean Success.
     * @since 0.1
     */
    public function update(&$model, $fields = null, $values = null) {
        if (!$this->connected) {
            return false;
        }
        if ($fields !== null && $values !== null) {
            $data = array_combine($fields, $values);
        } elseif($fields !== null && $conditions !== null) {
            trigger_error('updateAll not supported');
        } else{
            $data = $Model->data;
        }
        $options = array(
            'TableName' => $model->table,
            'Key' => $this->_setPrimaryKey($model, $data),
            'AttributeUpdates' => $this->_setAttributeUpdates($model, $data)
        );
        return $this->connection->update_item($options);
    }
    
    /**
     * The "D" in CRUD
     *
     * Delete a record from the database.
     *
     * @param object $model Model object that the record is for.
     * @param mixed $conditions The conditions to use for deleting.
     * @return boolean Success.
     * @since 0.1
     */
    public function delete(&$model, $conditions = null) {
        if (!$this->connected) {
            return false;
        }
        $options = array(
            'TableName' => $model->table,
            'Key' => $this->_setPrimaryKey($model, $conditions)
        );
        return $this->connection->delete_item($options);
    }
    
    /**
     * Wraps Amazon DynamoDB SDK API methods
     *
     * @link http://docs.aws.amazon.com/amazondynamodb/latest/developerguide/operationlist.html
     * @param mixed All params relative to Amazon DynamoDB SDK API.
     * @return mixed Returns mixed value relative to Amazon DynamoDB SDK API.
     * @since 0.1
     */
    public function query() {
        if (!$this->connected) {
            return false;
        }
        $args = func_get_args();
        if (is_string($args[0]) && method_exists($this->connection, $args[0])) {
            $options = array();
            if (!empty($args[1][0])) {
                $options = $args[1][0];
            }
            return call_user_func(array($this->connection, $args[0]), $options);
        }
        return $this->connection->query($args[0]);
    }
    
    /**
     * Parse the Item element from an API call response
     *
     * @param object $model Model object
     * @param object $response Response object
     * @return mixed
     * @since 0.1
     */
    public function _parseItem(&$model, $response = null) {
        $data = $this->_toArray($response);
        if (empty($data['body']['Item'])) {
            return false;
        }
        $result = array();
        foreach($data['body']['Item'] as $key=>$item) {
            foreach($item as $value) {
                $result[0][$model->alias][$key] = $value;
            }
        }
        return $result;
    }
    
    /**
     * Parse the Items element from an API call response
     *
     * @param object $model Model object
     * @param object $response Response object
     * @return void
     * @since 0.1
     */
    public function _parseItems(&$model, $response = null) {
        $data = $this->_toArray($response);
        if (empty($data['body']['Items'])) {
            return false;
        }
        $result = array();
        if ($data['body']['Count']>1) {
            foreach($data['body']['Items'] as $key=>$item) {
                foreach($item as $field=>$value) {
                    foreach($value as $k=>$v) {
                        $result[$key][$model->alias][$field] = $v;
                    }
                }
            }
        } else {
            foreach($data['body']['Items'] as $field=>$value) {
                foreach($value as $k=>$v) {
                    $result[0][$model->alias][$field] = $v;
                }
            }
        }
        return $result;
    }
    
    /**
     * Parse the Table element from an API call response
     *
     * @param object $model Model object
     * @param object $response Response object
     * @return void
     * @since 0.1
     */
    public function _parseTable(&$model, $response = null) {
        $data = $this->_toArray($response);
        if (empty($data['body']['Table'])) {
            return false;
        }
        return $data['body']['Table'];
    }
    
    /**
     * Converts to array an object
     *
     * @param object $data Data object.
     * @return mixed Converted array on Success. Boolean false on error.
     * @since 0.1
     */
    public function _toArray($data = null) {
        return json_decode(json_encode((array)$data), 1);
    }
    
    /**
     * Returns the primary key type and key names of table
     *
     * @param object $model Model object.
     * @return array Primary key array with type and keys.
     * @since 0.1
     */
    public function _getPrimaryKey(&$model = null) {
        if (!$this->connected) {
            return false;
        }
        $type = null;
        $keys = array();
        $options = array(
            'TableName' => $model->table
        );
        $response = $this->_parseTable(
            $model,
            $this->connection->describe_table($options)
        );
        $composite = array('HashKeyElement', 'RangeKeyElement');
        if (array_keys($response['KeySchema']) == $composite) {
            $type = 'hashAndRange';
            $keys = array(
                'hash' => $response['KeySchema']['HashKeyElement']['AttributeName'],
                'range' => $response['KeySchema']['RangeKeyElement']['AttributeName']
            );
        } else {
            $type = 'hash';
            $keys = array(
                'hash' => $response['KeySchema']['HashKeyElement']['AttributeName']
            );
        }
        return compact('type', 'keys');
    }
    
    /**
     * Returns the primary key value
     *
     * @param mixed $value Value data.
     * @return mixed Boolean false on error. String with value.
     * @since 0.1
     */
    public function _getPrimaryKeyValue($value = null) {
        if (!$value) {
            return false;
        }
        $keys = array_keys($value);
        return $value[$keys[0]];
    }
    
    /**
     * Returns the primary key array with type as key and value
     *
     * @param object $model Model object.
     * @param array $data Data array.
     * @return array Primary key array with type as key and value.
     * @since 0.1
     */
    public function _setPrimaryKey(&$model, $data = array()) {
        if (!$this->connected) {
            return false;
        }
        $primaryKey = array();
        $type = null;
        $options = array(
            'TableName' => $model->table
        );
        $response = $this->_parseTable(
            $model,
            $this->connection->describe_table($options)
        );
        
        if (!empty($response['KeySchema']['HashKeyElement'])) {
            $name = $response['KeySchema']['HashKeyElement']['AttributeName'];
            switch($response['KeySchema']['HashKeyElement']['AttributeType']) {
                case 'S':
                    $primaryKey['HashKeyElement'] = $this->_setStringPrimaryKey(
                        $model, $name, $data
                    );
                    break;
                case 'N':
                    $primaryKey['HashKeyElement'] = $this->_setNumberPrimaryKey(
                        $model, $name, $data
                    );
                    break;
                case 'B':
                    $primaryKey['HashKeyElement'] = $this->_setBinaryPrimaryKey(
                        $model, $name, $data
                    );
                    break;
            }
        }
        if (!empty($response['KeySchema']['RangeKeyElement'])) {
            $name = $response['KeySchema']['RangeKeyElement']['AttributeName'];
            switch($response['KeySchema']['RangeKeyElement']['AttributeType']) {
                case 'S':
                    $primaryKey['RangeKeyElement'] = $this->_setStringPrimaryKey(
                        $model, $name, $data
                    );
                    break;
                case 'N':
                    $primaryKey['RangeKeyElement'] = $this->_setNumberPrimaryKey(
                        $model, $name, $data
                    );
                    break;
                case 'B':
                    $primaryKey['RangeKeyElement'] = $this->_setBinaryPrimaryKey(
                        $model, $name, $data
                    );
                    break;
            }
        }
        return $primaryKey;
    }
    
    /**
     * Set a String primary key
     *
     * Set with the current data array or create.
     *
     * @param object $model Model object.
     * @param string $name Primary key name.
     * @param array $data Data array with key/values.
     * @return array An array with key/value.
     * @since 0.1
     */
    public function _setStringPrimaryKey(&$model, $name = null, $data = array()) {
        if (!empty($data[$name])) {
            return array(
                AmazonDynamoDB::TYPE_STRING => $data[$name]
            );
        } elseif (!empty($data[$model->alias.'.'.$name])) {
            return array(
                AmazonDynamoDB::TYPE_STRING => $data[$model->alias.'.'.$name]
            );
        } else {
            return array(
                AmazonDynamoDB::TYPE_STRING => String::uuid()
            );
        }
    }
    
    /**
     * Set a Number primary key
     *
     * Set with the current data array or create.
     *
     * @param object $model Model object.
     * @param string $name Primary key name.
     * @param array $data Data array with key/values.
     * @return array An array with key/value.
     * @since 0.1
     */
    public function _setNumberPrimaryKey(&$model, $name = null, $data = array()) {
        if (!empty($data[$name])) {
            return array(
                AmazonDynamoDB::TYPE_NUMBER => $data[$name]
            );
        } elseif (!empty($data[$model->alias.'.'.$name])) {
            return array(
                AmazonDynamoDB::TYPE_NUMBER => $data[$model->alias.'.'.$name]
            );
        } else {
            return array(
                AmazonDynamoDB::TYPE_NUMBER => (string)microtime(true)
            );
        }
    }
    
    /**
     * Set a Binary primary key
     *
     * Set with the current data array or create.
     *
     * @param object $model Model object.
     * @param string $name Primary key name.
     * @param array $data Data array with key/values.
     * @return array An array with key/value.
     * @since 0.1
     */
    public function _setBinaryPrimaryKey(&$model, $name = null, $data = array()) {
        if (empty($data[$name])) {
            return array(
                AmazonDynamoDB::TYPE_BINARY => pack($data[$key])
            );
        } elseif (!empty($data[$model->alias.'.'.$name])) {
            return array(
                AmazonDynamoDB::TYPE_BINARY => pack($data[$model->alias.'.'.$name])
            );
        } else {
            return array(
                AmazonDynamoDB::TYPE_BINARY => pack(String::uuid())
            );
        }
    }
    
    /**
     * Set attributes for update call
     *
     * @param object $model Model object.
     * @param array $data An array of key/values for update.
     * @return array An array of attributes.
     * @since 0.1
     */
    public function _setAttributeUpdates(&$model, $data) {
        if (!empty($data[$model->primaryKey])) {
            unset($data[$model->primaryKey]);
        }
        $attributes = array();
        foreach($data as $key=>$value) {
            $attributes[$key] = array(
                'Action' => AmazonDynamoDB::ACTION_PUT,
                'Value' => $this->_setVarType($value)
            );
        }
        return $attributes;
    }
    
    /**
     * Set values data types for an array of values
     *
     * @param array $data An array of values to set data types.
     * @return array An array of values with data types set.
     * @since 0.1
     */
    public function _setVarTypes($data = array()) {
        foreach($data as $key=>$value) {
            $data[$key] = $this->_setVarType($value);
        }
        return $data;
    }
    
    /**
     * Set value type accord to DynamoDB specification.
     *
     * @todo Support TYPE_BINARY_SET.
     * @param mixed $value Value as any datatype except object and resource.
     * @return array An array with key as the DynanoDB data type and value.
     * @since 0.1
     */
    public function _setVarType($value = null) {
        switch(gettype($value)) {
            case 'boolean':
                $value = array(AmazonDynamoDB::TYPE_STRING => (string)$value);
                break;
            case 'integer':
                $value = array(AmazonDynamoDB::TYPE_NUMBER => (string)$value);
                break;
            case 'double':
                $value = array(AmazonDynamoDB::TYPE_NUMBER => (string)$value);
                break;
            case 'string':
                $value = array(AmazonDynamoDB::TYPE_STRING => (string)$value);
                break;
            case 'array':
                $hasString = false;
                foreach($value as $v) {
                    if (is_string($v)) {
                        $hasString = true;
                    }
                }
                if ($hasString) {
                    $value = array(AmazonDynamoDB::TYPE_STRING_SET => $value);
                } else {
                    $value = array(AmazonDynamoDB::TYPE_NUMBER_SET => $value);
                }
                break;
            case 'object':
                trigger_error('var type (object) not supported');
                break;
            case 'resource':
                trigger_error('var type (resource) not supported');
                break;
            case 'NULL':
                $value = array(AmazonDynamoDB::TYPE_STRING => '');
                break;
            default:
                $value = array(AmazonDynamoDB::TYPE_STRING => '');
        }
        return $value;
    }
    
    /**
     * Prepare log query.
     *
     * @param object $model Model object.
     * @return boolean Success.
     * @since 0.1
     */
    public function _prepareLogQuery(&$model) {
        if (!$this->fullDebug) {
            return false;
        }
        $this->_startTime = microtime(true);
        $this->took = null;
        $this->affected = null;
        $this->error = null;
        $this->numRows = null;
        return true;
    }
    
}