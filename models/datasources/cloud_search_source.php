<?php
/**
 * CloudSearchSource DataSource File
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
App::import('Core', 'HttpSocket');

class CloudSearchSource extends DataSource {
    
    /**
     * The description of this data source
     *
     * @var string
     */
    public $description = 'CloudSearch DataSource';
    
    /**
     * Http object
     *
     * @var object
     */
    public $Http = null;
    
    /**
     * Base configuration
     *
     * @var array
     */
    public $config = array(
        'datasource' => '',
        'search_endpoint' => '',
        'document_endpoint' => '',
        'api_version' => ''
    );
    
    /**
     * Constructor
     *
     * @param array $config Configuration array.
     * @return boolean Success.
     * @since 0.1
     */
    public function __construct($config = array()) {
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
        if (!$this->Http) {
            return false;
        }
        //debug(__FUNCTION__);
        //debug(func_get_args());
        
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
        
        if (empty($data['id'])) {
            $id = uniqid();
        } else {
            $id = $data['id'];
            unset($data['id']);
        }
        
        if (empty($data['version'])) {
            $version = time();
        } else {
            $version = $data['version'];
            unset($data['version']);
        }
        
        $params[] = array(
            'type' => 'add',
            'id' => $id,
            'version' => $version,
            'lang' => 'en',
            'fields' => $data
        );
        $results = $this->document($params);
        
        if ($results['status'] == 'success') {
            return true;
        } else {
            return false;
        }
        
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
        
        if (!$this->Http) {
            return false;
        }
        //debug(__FUNCTION__);
        //debug(func_get_args());
        
        extract($query);
        
        $key = $model->alias .'.id';
        if (sizeof($conditions) == 1 && isset($conditions[$key])) {
            $conditions['bq'] = "docid:'{$conditions[$key]}'";
            unset($conditions[$key]);
        }
        
        if (empty($conditions['q']) && empty($conditions['bq'])) {
            trigger_error(__('Empty query string', true));
            return false;
        }
        
        if ($model->findQueryType == 'first') {
            $conditions['size'] = 1;
        }
        
        if (!empty($page) && $page > 1) {
            $conditions['start'] = $page;
        }
        
        $results = $this->search($conditions);
        
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
        if (!$this->Http) {
            return false;
        }
        //debug(__FUNCTION__);
        //debug(func_get_args());
        
        if ($fields !== null && $values !== null) {
            $data = array_combine($fields, $values);
        } else {
            $data = $model->data;
        }
        
        if (empty($data['id'])) {
            trigger_error(__('The document ID is required for updates', true));
            return false;
        }
        
        $id = $data['id'];
        unset($data['id']);
        
        if (empty($data['version'])) {
            $version = time();
        } else {
            $version = $data['version'];
            unset($data['version']);
        }
        
        $params[] = array(
            'type' => 'add',
            'id' => $id,
            'version' => $version,
            'lang' => 'en',
            'fields' => $data
        );
        $results = $this->document($params);
        
        if (!empty($results['status']) && $results['status'] == 'success') {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * The "D" in CRUD
     *
     * Delete a record from the database.
     *
     * @todo add support for $conditions
     * @param object $model Model object that the record is for.
     * @param mixed $conditions The conditions to use for deleting.
     * @param integer $version Document version number.
     * @return boolean Success.
     * @since 0.1
     */
    public function delete(&$model, $conditions = null, $version = null) {
        if (!$this->Http) {
            return false;
        }
        //debug(__FUNCTION__);
        //debug(func_get_args());
        
        if (sizeof($conditions) > 1) {
            trigger_error(__('Conditional delete are not supported yet...', true));
            return false;
        }
        
        if (sizeof($conditions) === 1 && empty($conditions[$model->alias.'.id'])) {
            trigger_error(__('Document ID is required for delete', true));
            return false;
        }
        
        if (empty($version)) {
            $version = time();
        }
        
        $params[] = array(
            'type' => 'delete',
            'id' => $conditions[$model->alias.'.id'],
            'version' => $version
        );
        
        $results = $this->document($params);
        
        if (!empty($results['status']) && $results['status'] == 'success') {
            return true;
        } else {
            return false;
        }
        
    }
    
    /**
     * Query with Amazon CloudSearch Document and Search APIs
     *
     * @link http://docs.aws.amazon.com/cloudsearch/latest/developerguide/DocSvcAPI.html
     * @link http://docs.aws.amazon.com/cloudsearch/latest/developerguide/SearchAPI.html
     * @param string $method Call document or search methods.
     * @param array $params Array of parameters for query.
     * @param object $model Model object that the record is for.
     * @return mixed Returns mixed value relative to Amazon DynamoDB SDK API.
     * @since 0.1
     */
    public function query($method = null, $params = array(), &$model = null) {
        if (is_string($method) && method_exists($this, $method)) {
            return call_user_func(array($this, $method), $params);
        } elseif (strstr($method, 'findBy')) {
            return $this->findBy($method, $params, $model);
        }
        trigger_error(__('Invalid method call: '.$method, true));
    }
    
    public function findBy($method = null, $params = array(), &$model = null) {
        debug(func_get_args());
        return true;
    }
    
    /**
     * Call CloudSearch Search API
     *
     * @param string $params Array of parameters for search api call.
     * @return void
     * @since 0.1
     */
    public function search($params = array(), $type = 'application/json') {
        if (sizeof($params) == 0) {
            trigger_error(__('Invalid search parameters', true));
        }
        $url = sprintf(
            'https://%s/%s/search',
            $this->config['search_endpoint'],
            $this->config['api_version']
        );
        
        $response = $this->Http->get(
            $url,
            $params,
            array('header' => array('Content-Type' => $type))
        );
        
        if (empty($response)) {
            return false;
        }
        
        if (is_string($response)) {
            $response = json_decode($response);
        }
        
        if (!is_object($response)) {
            return false;
        }
        
        // handle errors here
        
        return $this->_toArray($response->hits->hit);
    }
    
    /**
     * Call CloudSearch Document API
     *
     * @param string $params Array of parameters for document api call.
     * @return void
     * @since 0.1
     */
    public function document($params = array(), $type = 'application/json') {
        if (sizeof($params) == 0) {
            trigger_error(__('Invalid document parameters', true));
        }
        $url = sprintf(
            'https://%s/%s/documents/batch',
            $this->config['document_endpoint'],
            $this->config['api_version']
        );
        $params = json_encode($params);
        
        // Amazon keep respondig with this error message:
        // "Operations cannot be JSON arrays (near operation with index 1)"
        // this fix it, removing the initial json array wrap
        if (strpos($params, '[[') === 0) {
            $params = substr($params, 1, -1);
        }
        
        //debug($params);
        
        $response = $this->Http->post(
            $url,
            $params,
            array('header' => array('Content-Type' => $type))
        );
        
        if (empty($response)) {
            return false;
        }
        
        if (is_string($response)) {
            $response = json_decode($response);
        }
        
        if (!is_object($response)) {
            return false;
        }
        
        // handle errors here
        
        return $this->_toArray($response);
        
    }
    
    /**
     * Translate a conditions array to CloudSearch format
     *
     * @param object $model A Model object that the query is for.
     * @param array $conditions Array with conditions.
     * @return array Returns converted array to DynamoDB format of conditions.
     * @since 0.1
     */
    public function _conditions($conditions = array()) {
        
        if (empty($conditions)) {
            return array();
        }
        
        // foreach($conditions as $field=>$value) {
        //     unset($conditions[$field]);
        //     // does not support OR
        //     if ($field == 'OR') {
        //         continue;
        //     }
        //     $field = array_pop(explode('.', $field));
        //     if (strpos($field, ' ') === false) {
        //         $operator = '=';
        //     } else {
        //         list($field, $operator) = explode(' ', $field);
        //     }
        //     $operators = array('AND', 'OR', 'NOT');
        //     if (!in_array($operator, $operators)) {
        //         continue;
        //     }
        //     $conditions[$field] = array(
        //         'operator' => $operators[$operator],
        //         'value' => $value
        //     );
        // }
        debug($conditions);
        return $conditions;
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
    
}
