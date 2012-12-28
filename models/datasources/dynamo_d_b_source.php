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
    
    public $description = 'Dynamodb DataSource';
    
    public $connected = false;
    
    public $connection = null;
    
    public $_config = array(
        'datasource' => '',
        'database' => '',
        'host' => '',
        'login' => '',
        'password' => ''
    );
    
    public function __construct($config = array(), $autoConnect = true) {
        $this->setConfig($config);
        parent::__construct($config);
        if ($autoConnect) {
            return $this->connect();
        }
    }
    
    public function setConfig($config = array()) {
        $this->_config = array_merge($this->_config, $config);
    }
    
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
    
    public function disconnect() {
        $this->connection = null;
        $this->connected = false;
        return true;
    }
    
    public function close() {
        $this->disconnect();
    }
    
    public function listSources() {
        return $this->connection->list_tables()->body->TableNames()->map_string();
    }
    
    // @working
    public function calculate(&$model) {
        return 'count';
    }
    
    // @working
    public function describe(&$model) {
        return $model->_schema;
    }
    
    // @working
    public function create($options = array()) {
        if (!$this->connected) {
            return false;
        }
    }
    
    public function read(&$model, $query = array()) {
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
            return $this->parseItem($model, $response);
        } else {
            $response = $this->connection->scan($params);
            return $this->parseItems($model, $response);
        }
    }
    
    // @working
    public function update($options = array()) {
        if (!$this->connected) {
            return false;
        }
        
    }
    
    public function delete(&$model, $conditions = null) {
        if (!$this->connected) {
            return false;
        }
        $options = array(
            'TableName' => $model->table,
            'Key' => array(
                'HashKeyElement' => array(
                    AmazonDynamoDB::TYPE_NUMBER => (string)$model->id
                )
            )
        );
        return $this->connection->delete_item($options);
    }
    
    public function query() {
        if (!$this->connected) {
            return false;
        }
        $args = func_get_args();
        // A workaround to delete call that is not working as expected 
        // for a reason that I don't know right now
        if ($args[0] == 'del') {
            return $this->delete($args[2], $args[1]);
        }
        if (is_string($args[0]) && (method_exists($this, $args[0]) || method_exists($this->connection, $args[0]))) {
            if (method_exists($this, $args[0])) {
                $class = &$this;
            } else {
                $class = &$this->connection;
            }
            debug($args[0]);
            $options = array();
            if (!empty($args[1][0])) {
                $options = $args[1][0];
            }
            return call_user_func(array($class, $args[0]), $options);
        }
        debug(__FUNCTION__);
        return $this->connection->query($args[0]);
    }
    
    public function parseItem(&$model, $response = null) {
        $data = $this->__toArray($response);
        $result = array();
        foreach($data['body']['Item'] as $key=>$item) {
            foreach($item as $value) {
                $result[0][$model->alias][$key] = $value;
            }
        }
        return $result;
    }
    
    public function parseItems(&$model, $response = null) {
        $data = $this->__toArray($response);
        $result = array();
        foreach($data['body']['Items'] as $key=>$item) {
            foreach($item as $field=>$value) {
                foreach($value as $k=>$v) {
                    $result[$key][$model->alias][$field] = $v;
                }
            }
        }
        return $result;
    }
    
    public function __toArray($data = array()) {
        return json_decode(json_encode((array)$data), 1);
    }
    
    // @working
    public function __setType($data = array()) {
        
    }
    // @working
    public function __getType($data = array()) {
        
    }
    
}