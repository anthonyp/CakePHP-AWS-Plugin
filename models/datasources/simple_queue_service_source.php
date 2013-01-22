<?php
/**
 * Amazon Simple Queue Service Datasource File
 *
 * Copyright (c) 2013 Everton Yoshitani
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
 * @copyright   2013 Everton Yoshitani <everton@notreve.com>
 * @license     http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link        http://github.com/anthonyp/CakePHP-AWS-Plugin
*/

/**
 * Import XML, required library
 *
 */
App::import('Core', 'Xml');

/**
 * Import HttpSocket, required library
 *
 */
App::import('Core', 'HttpSocket');

/**
 * Amazon Simple Queue Service Datasource
 *
 * @package datasources
 * @subpackage datasources.models.datasources
 */
class SimpleQueueServiceSource extends DataSource {
    
    /**
     * The description of this data source
     *
     * @var string
     */
    public $description = 'Amazon Simple Queue Service DataSource';
    
    /**
     * Amazon Simple Queue Service API version
     *
     * @var string
     */
    public $api_version = '2012-11-05';
    
    /**
     * Base configuration
     *
     * @var array
     */
    public $config = array(
        'datasource' => '',
        'host' => '',
        'login' => '',
        'password' => ''
    );
    
    /**
     * Allowed API action accord to version 2012-11-05
     *
     * @var array
     */
    public $actions = array(
        'CreateQueue',
        'ListQueues',
        'DeleteQueue',
        'GetQueueAttributes',
        'SetQueueAttributes',
        'SendMessage',
        'ReceiveMessage',
        'DeleteMessage',
        'AddPermission',
        'RemovePermission',
        'ChangeMessageVisibility',
        'GetQueueUrl',
        'SendMessageBatch',
        'DeleteMessageBatch',
        'ChangeMessageVisibilityBatch'
    );
    
    /**
     * HttpSocket object
     *
     * @var HttpSocket
     */
    public $Http = null;
    
    /**
     * Constructor
     *
     * @param array $config Configuration array.
     * @return boolean Success.
     * @since 0.1
     */
    public function __construct($config) {
        parent::__construct($config);
        $this->setConfig($config);
        $this->setHttpSocket();
    }
    
    /**
     * Set default configuration
     *
     * @param array $config Configuration settings.
     * @return boolean Success.
     * @since 0.1
     */
    public function setConfig($config = array()) {
        $this->config = array_merge($this->config, $config);
    }
    
    /**
     * Initialise the HttpSocket object to post/get data
     *
     * @return object HttpSocket object.
     * @since 0.1
     */
    public function setHttpSocket() {
        if (!$this->Http) {
            $this->Http = new HttpSocket();
        }
        return $this->Http;
    }
    
    /**
     * List of queues
     *
     * @return array List of queues.
     * @since 0.1
     */
    public function listSources() {
        return false;
    }
    
    /**
     * Calculate
     *
     * No SQL statements used return always true.
     *
     * @return boolean True.
     * @since 0.1
     */
    public function calculate() {
        return true;
    }
    
    /**
     * Return an array of the fields in given queue name
     *
     * @param object $model Model object of the database table to inspect.
     * @return array Fields in table. Keys are name and type.
     * @since 0.1
     */
    public function describe(&$model) {
        return false;
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
        return false;
    }
    
    /**
     * The "R" in CRUD
     *
     * Reads record(s) from the database.
     *
     * @todo add support findQueryType 'list' and 'first'
     * @param object $model A Model object that the query is for.
     * @param array $query An array of queryData information containing 
     *        keys similar to Model::find().
     * @param integer $recursive Number of levels of association.
     * @return mixed Boolean false on error/failure.
     *         An array of results on success.
     * @since 0.1
     */
    public function read(&$model, $query = array(), $recursive = null) {
        return false;
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
        return false;
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
        return false;
    }
    
    /**
     * Query with Amazon Simple Queue Service API
     *
     * @param string $method Call API method.
     * @param array $params Array of parameters for query.
     * @param object $model Model object that the record is for.
     * @return mixed Returns Amazon Simple Queue Service API response.
     * @since 0.1
     */
    public function query($method = null, $params = array(), &$model = null) {
        
        if (!in_array($method, $this->actions)) {
            return trigger_error(sprintf(__('Invalid API action: %s', true), $method));
        }
        
        $query = array_merge(array('Action' => $method), $params);
        
        return $this->_request($query);
        
    }
    
    /**
     * Perform the request to Amazon Simple Queue Service
     *
     * @return mixed array of the resulting request or false if unable to contact server
     */
    public function _request($query = array()) {
        $request = $this->_signQuery($query);
        $response = $this->Http->get($request);
        $this->log($query);
        $this->log($request);
        $this->log($response);
        return Set::reverse(new Xml($response));
    }
    
    /**
     * Sign a query using sha256
     *
     * @link Grab from https://github.com/cakephp/datasources/
     * @return string request signed string.
     */
    private function _signQuery($query = array(), $timestamp = null) {
        
        $method = 'GET';
        $host = $this->config['host'];
        $uri = '/';
        
        if (empty($timestamp)) {
            $timestamp = gmdate("Y-m-d\TH:i:s\Z");
        }
        
        $params = array(
            'AWSAccessKeyId' => $this->config['login'],
            'Timestamp' => $timestamp,
            'SignatureMethod' => 'HmacSHA256',
            'SignatureVersion' => 2,
            'Version' => $this->api_version
        );
        
        $query = array_merge($params, $query);
        ksort($query);
        
        // create the canonicalized query
        $canonicalized_query = array();
        foreach ($query as $param=>$value) {
            $param = str_replace('%7E', '~', rawurlencode($param));
            $value = str_replace('%7E', '~', rawurlencode($value));
            $canonicalized_query[] = $param."=".$value;
        }
        $canonicalized_query = implode('&', $canonicalized_query);
        $string_to_sign = implode("\n", array($method, $host, $uri, $canonicalized_query));
        
        // calculate HMAC with SHA256 and base64-encoding
        $signature = base64_encode(hash_hmac("sha256", $string_to_sign, $this->config['password'], true));
        
        // encode the signature for the request
        $signature = str_replace('%7E', '~', rawurlencode($signature));
        
        // create request
        return sprintf('https://%s%s?%s&Signature=%s', $host, $uri, $canonicalized_query, $signature);
    }
    
}

