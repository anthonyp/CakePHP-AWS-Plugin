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
 
class DynamoDBSource extends DataSource {
    
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
    }
    
    /**
     * Destructor, closes the current datasource
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
        return $this->disconnect();
    }
    
    /**
     * List of databases
     *
     * @return array List of databases.
     * @since 0.1
     */
    public function listSources() {
        if (!$this->connected) {
            return false;
        }
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
     * @param object $model Model object of the database table to inspect.
     * @return array Fields in table. Keys are name and type.
     * @since 0.1
     */
    public function describe(&$model) {
        if (!$this->connected) {
            return false;
        }
        if (empty($model->schema)) {
            trigger_error(__('Schema is not configured in the model.', true));
        }
        $options = array(
            'TableName' => $model->table
        );
        $response = $this->_parseTable(
            $model,
            $this->connection->describe_table($options)
        );
        $model->primaryKeySchema = $response['KeySchema'];
        if (array_keys($response['KeySchema']) == array('HashKeyElement', 'RangeKeyElement')) {
            $model->primaryKeyType = 'hashAndRange';
        } else {
            $model->primaryKeyType = 'hash';
        }
        $model->primaryKey = $response['KeySchema']['HashKeyElement']['AttributeName'];
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
            $data = $model->data;
        }
        $data = $this->_setValueTypes($data);
        if (empty($data[$model->primaryKey])) {
            $data[$model->primaryKey] = array_shift($this->_setHashPrimaryKey($model));
        }
        $options = array(
            'TableName' => $model->table,
            'Item' => $data
        );
        $result = $this->connection->put_item($options);
        if (!empty($result) && $result->status == 200) {
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
        $readType = $this->_getReadType($model, $query);
        switch($readType) {
            case 'get_item':
                $results = $this->_readWithGetItem($model, $query);
                break;
            case 'query':
                $results = $this->_readWithQuery($model, $query);
                break;
            case 'scan':
                $results = $this->_readWithScan($model, $query);
                break;
        }
        if ($readType == 'get_item') {
            $results = $this->_parseItem($model, $results);
        } else {
            $results = $this->_parseItems($model, $results);
        }
        if (empty($results)) {
            return false;
        }
        if ($model->findQueryType == 'count') {
            // this is bad!
            return array('0'=>array('0'=>array('count'=>count($results))));
        }
        if ($model->findQueryType == 'list') {
            
        }
        if ($model->findQueryType == 'first') {
            
        }
        return $results;
    }
    
    /**
     * The "U" in CRUD
     *
     * Update record from the database.
     *
     * @todo add support for $conditions.
     * @todo add support for updateAll.
     * @todo add support for update actions: ADD, PUT and DELETE. 
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
        } else {
            $data = $model->data;
        }
        $options = array(
            'TableName' => $model->table,
            'Key' => $this->_setHashPrimaryKey($model, $data),
            'AttributeUpdates' => $this->_setAttributeUpdates($model, $data)
        );
        return $this->connection->update_item($options);
    }
    
    /**
     * The "D" in CRUD
     *
     * Delete a record from the database.
     *
     * @todo add support for $conditions
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
            'Key' => $this->_setHashPrimaryKey($model, $conditions)
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
     * Returns the read type
     *
     * @param object $model A Model object that the query is for.
     * @param array $query An array of queryData information containing 
     *        keys similar to Model::find().
     * @return string Returns string with query type.
     * @since 0.1
     */
    public function _getReadType(&$model, $query = array()) {
        
        extract($query);
        
        // get_item?
        if (!empty($conditions) && count($conditions)==1) {
            if (!empty($conditions[$model->alias .'.'. $model->primaryKey])) {
                return 'get_item';
            }
            if (!empty($conditions[$model->primaryKey])) {
                return 'get_item';
            }
        }
        
        // is query? working on that one
        
        // scan!
        return 'scan';
        
    }
    
    /**
     * Pull a record from database with 'get_item' call
     *
     * @param object $model A Model object that the query is for.
     * @param array $query An array of queryData information containing 
     *        keys similar to Model::find().
     * @return object Returns DynamoDB results object.
     * @since 0.1
     */
    public function _readWithGetItem(&$model, $query = array()) {
        
        extract($query);
        
        if (empty($conditions[$model->alias .'.'. $model->primaryKey])) {
            $value = $this->_getPrimaryKeyValue($model->{$model->primaryKey});
        } else {
            $value = $conditions[$model->alias .'.'. $model->primaryKey];
        }
        $value = $this->_castValue(
            $model->primaryKeySchema['HashKeyElement']['AttributeType'],
            $value
        );
        $options = array(
            'TableName' => $model->table,
            'Key' => array('HashKeyElement'=>$this->_setValueType($value)),
        );
        
        return $this->connection->get_item($options);
        
    }
    
    /**
     * Pull record(s) from database with 'query' call
     *
     * @param object $model A Model object that the query is for.
     * @param array $query An array of queryData information containing 
     *        keys similar to Model::find().
     * @return object Returns DynamoDB results object.
     * @since 0.1
     */
    public function _readWithQuery(&$model, $query = array()) {
        
        extract($query);
        
        $options = array(
            'TableName' => $model->table
        );
        
        if (!empty($fields)) {
            $options['AttributesToGet'] = $fields;
        }
        
        if (!empty($limit)) {
            $options['Limit'] = $limit;
        }
        
        if (!empty($consistentRead)) {
            $options['ConsistentRead'] = $consistentRead;
        }
        
        if (!empty($count)) {
            $options['Count'] = $count;
        }
        
        $options['HashKeyValue'] = array();
        
        $options['RangeKeyCondition'] = array();
        
        if (!empty($scanIndexForward)) {
            $options['ScanIndexForward'] = $scanIndexForward;
        }
        
        if (!empty($exclusiveStartKey)) {
            $options['ExclusiveStartKey'] = $exclusiveStartKey;
        }
        
        $r = $this->connection->query($options);
        
        return $r;
    }
    
    /**
     * Pull record(s) from database with 'scan' call
     *
     * @param object $model A Model object that the query is for.
     * @param array $query An array of queryData information containing 
     *        keys similar to Model::find().
     * @return object Returns DynamoDB results object.
     * @since 0.1
     */
    public function _readWithScan(&$model, $query = array()) {
        
        extract($query);
        
        $options = array(
            'TableName' => $model->table
        );
        
        if (!empty($fields)) {
            $options['AttributesToGet'] = $fields;
        }
        
        if (!empty($limit)) {
            $options['Limit'] = $limit;
        }
        
        if (!empty($count)) {
            $options['Count'] = $count;
        }
        
        if (!empty($conditions)) {
            $conditions = $this->_getConditions($model, $conditions);
            if (!empty($conditions)) {
                $options['ScanFilter'] = array();
                foreach($conditions as $field=>$value) {
                    $options['ScanFilter'][$field]['ComparisonOperator'] = $value['operator'];
                    if (!empty($value['value'])) {
                        $options['ScanFilter'][$field]['AttributeValueList'] = $value['value'];
                    }
                }
            }
        }
        
        if (!empty($exclusiveStartKey)) {
            $options['ExclusiveStartKey'] = $exclusiveStartKey;
        }
        
        $response = $this->connection->scan($options);
        if ($response->status != 200) {
            if (!empty($response->body->message)) {
                $message = $response->body->message;
            } elseif (!empty($response->body->Message)) {
                $message = $response->body->Message;
            } else {
                $message = __('Unkown api error', true);
            }
            trigger_error($message);
        }
        //debug($response);
        return $response;
    }
    
    /**
     * Translate a conditions array to DynamoDB format
     *
     * @param object $model A Model object that the query is for.
     * @param array $conditions Array with conditions.
     * @return array Returns converted array to DynamoDB format of conditions.
     * @since 0.1
     */
    public function _getConditions(&$model, $conditions = array()) {
        if (empty($conditions)) {
            return array();
        }
        foreach($conditions as $field=>$value) {
            unset($conditions[$field]);
            // does not support OR
            if ($field == 'OR') {
                continue;
            }
            $field = array_pop(explode('.', $field));
            $field = str_replace('NOT NULL', 'NOT_NULL', $field);
            $field = str_replace('DOESNT CONTAINS', 'DOESNT_CONTAINS', $field);
            $field = str_replace('BEGINS WITH', 'BEGINS_WITH', $field);
            if (strpos($field, ' ') === false) {
                $operator = '=';
            } else {
                list($field, $operator) = explode(' ', $field);
            }
            $operators = array(
                '='                 => AmazonDynamoDB::CONDITION_EQUAL,
                '!='                => AmazonDynamoDB::CONDITION_NOT_EQUAL,
                '<>'                => AmazonDynamoDB::CONDITION_NOT_EQUAL,
                '>'                 => AmazonDynamoDB::CONDITION_GREATER_THAN,
                '>='                => AmazonDynamoDB::CONDITION_GREATER_THAN_OR_EQUAL,
                '<'                 => AmazonDynamoDB::CONDITION_LESS_THAN,
                '<='                => AmazonDynamoDB::CONDITION_LESS_THAN_OR_EQUAL,
                'NULL'              => AmazonDynamoDB::CONDITION_NULL,
                'NOT_NULL'          => AmazonDynamoDB::CONDITION_NOT_NULL,
                'CONTAINS'          => AmazonDynamoDB::CONDITION_CONTAINS,
                'DOESNT_CONTAINS'   => AmazonDynamoDB::CONDITION_DOESNT_CONTAIN,
                'BEGINS_WITH'       => AmazonDynamoDB::CONDITION_BEGINS_WITH,
                'IN'                => AmazonDynamoDB::CONDITION_IN,
                'BETWEEN'           => AmazonDynamoDB::CONDITION_BETWEEN
            );
            if (!in_array($operator, array_keys($operators))) {
                continue;
            }
            $conditions[$field] = array(
                'operator' => $operators[$operator],
                'value' => $value
            );
        }
        foreach($conditions as $field=>$properties) {
            if ($properties['operator'] == AmazonDynamoDB::CONDITION_NULL
                or $properties['operator'] == AmazonDynamoDB::CONDITION_NOT_NULL)
            {
                unset($conditions[$field]['value']);
            } elseif ($properties['operator'] == AmazonDynamoDB::CONDITION_CONTAINS
                or $properties['operator'] == AmazonDynamoDB::CONDITION_DOESNT_CONTAIN
                or $properties['operator'] == AmazonDynamoDB::CONDITION_IN
                or $properties['operator'] == AmazonDynamoDB::CONDITION_BETWEEN)
            {
                if (is_array($properties['value'])) {
                    $conditions[$field]['value'] = array();
                    foreach($properties['value'] as $value) {
                        $conditions[$field]['value'][] = $this->_setValueType($value);
                    }
                } else {
                    $conditions[$field]['value'] = array(
                        $this->_setValueType($properties['value'])
                    );
                }
            } else {
                $conditions[$field]['value'] = array(
                    $this->_setValueType($properties['value'])
                );
            }
        }
        return $conditions;
    }
    
    
    /**
     * Parse the Item element from an API call response
     *
     * @param object $model Model object
     * @param object $response Response object
     * @return mixed Returns false on error. Item element array on success.
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
     * @param object $model Model object.
     * @param object $response Response object.
     * @return mixed Returns false on error. Items element array on success.
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
     * @param object $model Model object.
     * @param object $response Response object.
     * @return mixed Returns false on error. Table element array on success.
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
     * Cast value
     *
     * @param string $type Type accord to Amazon DynamoDB spec.
     * @param string $value Value to cast.
     * @return mixed Return the value as string, integer or binary.
     * @since 0.1
     */
    public function _castValue($type, $value) {
        switch($type) {
            case 'S':
                $value = (string)$value;
                break;
            case 'N':
                $value = (int)$value;
                break;
            case 'B':
                $value = (binary)$value;
                break;
            default:
        }
        return $value;
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
        if (!is_array($value)) {
            return $value;
        }
        $keys = array_keys($value);
        return $value[$keys[0]];
    }
    
    /**
     * Returns the Hash Primary Key array
     *
     * @param object $model Model object.
     * @param array $data Data array.
     * @return array Primary key array with type as key and value.
     * @since 0.1
     */
    public function _setHashPrimaryKey(&$model, $data = array()) {
        $primaryKey = array();
        if (!empty($model->primaryKeySchema['HashKeyElement'])) {
            $name = $model->primaryKeySchema['HashKeyElement']['AttributeName'];
            switch($model->primaryKeySchema['HashKeyElement']['AttributeType']) {
                case 'S':
                    $primaryKey['HashKeyElement'] = $this->_setStringPrimaryKeyValue(
                        $model, $name, $data
                    );
                    break;
                case 'N':
                    $primaryKey['HashKeyElement'] = $this->_setNumberPrimaryKeyValue(
                        $model, $name, $data
                    );
                    break;
                case 'B':
                    $primaryKey['HashKeyElement'] = $this->_setBinaryPrimaryKeyValue(
                        $model, $name, $data
                    );
                    break;
            }
        }
        return $primaryKey;
    }
    
    /**
     * Returns the Range Primary Key array
     *
     * @param object $model Model object.
     * @param array $data Data array.
     * @return array Primary key array with type as key and value.
     * @since 0.1
     */
    public function _setRangePrimaryKey(&$model, $data = array()) {
        $primaryKey = array();
        if (!empty($model->primaryKeySchema['RangeKeyElement'])) {
            $name = $model->primaryKeySchema['RangeKeyElement']['AttributeName'];
            switch($model->primaryKeySchema['RangeKeyElement']['AttributeType']) {
                case 'S':
                    $primaryKey['RangeKeyElement'] = $this->_setStringPrimaryKeyValue(
                        $model, $name, $data
                    );
                    break;
                case 'N':
                    $primaryKey['RangeKeyElement'] = $this->_setNumberPrimaryKeyValue(
                        $model, $name, $data
                    );
                    break;
                case 'B':
                    $primaryKey['RangeKeyElement'] = $this->_setBinaryPrimaryKeyValue(
                        $model, $name, $data
                    );
                    break;
            }
        }
        return $primaryKey;
    }
    
    /**
     * Set a String primary key value
     *
     * Set with the current data array or create.
     *
     * @param object $model Model object.
     * @param string $name Primary key name.
     * @param array $data Data array with key/values.
     * @return array An array with key/value.
     * @since 0.1
     */
    public function _setStringPrimaryKeyValue(&$model, $name = null, $data = array()) {
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
     * Set a Number primary key value
     *
     * Set with the current data array or create.
     *
     * @param object $model Model object.
     * @param string $name Primary key name.
     * @param array $data Data array with key/values.
     * @return array An array with key/value.
     * @since 0.1
     */
    public function _setNumberPrimaryKeyValue(&$model, $name = null, $data = array()) {
        if (!empty($data[$name])) {
            return array(
                AmazonDynamoDB::TYPE_NUMBER => $data[$name]
            );
        } elseif (!empty($data[$model->alias.'.'.$name])) {
            return array(
                AmazonDynamoDB::TYPE_NUMBER => $data[$model->alias.'.'.$name]
            );
        } else {
            $number = str_replace('.', '', microtime(true));
            return array(
                AmazonDynamoDB::TYPE_NUMBER => (string)$number
            );
        }
    }
    
    /**
     * Set a Binary primary key value
     *
     * Set with the current data array or create.
     *
     * @param object $model Model object.
     * @param string $name Primary key name.
     * @param array $data Data array with key/values.
     * @return array An array with key/value.
     * @since 0.1
     */
    public function _setBinaryPrimaryKeyValue(&$model, $name = null, $data = array()) {
        if (!empty($data[$name])) {
            return array(
                AmazonDynamoDB::TYPE_BINARY => $data[$name]
            );
        } elseif (!empty($data[$model->alias.'.'.$name])) {
            return array(
                AmazonDynamoDB::TYPE_BINARY => $data[$model->alias.'.'.$name]
            );
        } else {
            return array(
                AmazonDynamoDB::TYPE_BINARY => String::uuid()
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
                'Value' => $this->_setValueType($value)
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
    public function _setValueTypes($data = array()) {
        foreach($data as $key=>$value) {
            $data[$key] = $this->_setValueType($value);
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
    public function _setValueType($value = null) {
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
                trigger_error(__('var type (object) not supported', true));
                break;
            case 'NULL':
                $value = array(AmazonDynamoDB::TYPE_STRING => '');
                break;
            default:
                trigger_error(__('var type not supported', true));
        }
        return $value;
    }
    
}