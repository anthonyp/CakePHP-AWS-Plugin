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
    
    /**
     * Call CloudSearch Search API
     *
     * @param array $params Array of parameters for search api call.
     * @param string $type Request content type.
     * @return mixed Returns array with results. Or boolean false on error.
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
     * @param array $params Array of parameters for document api call.
     * @param string $type Request content type.
     * @return mixed Returns array with results. Or boolean false on error.
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
     * Find by field
     *
     * @param string $field Field name.
     * @param array $params Find conditions.
     * @param string $model Model object.
     * @return mixed Returns array with results. Or boolean false on error.
     * @author Yoshitani Everton
     */
    public function findBy($field = null, $params = array(), &$model = null) {
        trigger_error(__('findBy not supported', true));
    }
    
    /**
     * Translate a conditions array to CloudSearch format
     *
     * @param array $conditions Array with conditions.
     * @param string $type Interation type: recursive or query type.
     * @return array Returns converted array to DynamoDB format of conditions.
     * @since 0.1
     */
    public function _conditions($conditions = array(), $type = null) {
        
        $out = null;
        
        if (!$type && is_string($conditions)) {
            return array('q' => $this->_encloseQuotes($conditions));
        }
        
        if (!$type && $this->_isSingleConditionArray($conditions)) {
            return array('bq' => $this->_encloseQuotes($conditions[0]));
        }
        
        if (!$type) {
            $type = 'bq';
        }
        
        foreach($conditions as $field=>$value) {
            $field = array_pop(explode('.', $field));
            switch (true) {
                case $this->_isAnOperator($field, $value):
                    $out .= '('. $field;
                    $out .= $this->_conditions($value, 'recursive');
                    $out .= ')';
                    break;
                    
                case is_array($value) && $this->_isAnOperator(key($value), $value):
                    $out .= ' '. $this->_conditions($value, 'recursive');
                    break;
                    
                case $this->_isSingleConditionQueryOrBooleanQuery($field, $value):
                    $type = $field;
                    $out .= $this->_encloseQuotes($value);
                    break;
                    
                case (is_string($value) || is_integer($value)):
                    $value = $this->_encloseQuotes($value);
                    if ($type == 'recursive') {
                        if ($field == '0') {
                            $out .= ' '. $value;
                        } else {
                            $out .= " {$field}:'{$value}'";
                        }
                    } else {
                        $out .= "{$field}:{$value}";
                    }
                    break;
                    
                case $this->_isAnUintSearchRangeOfValues($value):
                    $out .= "{$field}:{$value[0]}..{$value[1]}";
                    break;
                    
                case $this->_isAnUintSearchOpenEndedValueAtStart($value):
                    $out .= "{$field}:..{$value[1]}";
                    break;
                    
                case $this->_isAnUintSearchOpenEndedValueAtEnd($value):
                    $out .= "{$field}:{$value[0]}..";
                    break;
                    
                default:
                    if (is_array($value)) {
                        $value = join('|', $value);
                        $value = $this->_encloseQuotes($value);
                    }
                    $out .= "{$field}:". $this->_encloseQuotes($value);
                    break;
            }
        }
        
        if ($type == 'recursive') {
            return $out;
        } else {
            return array($type=>$out);
        }
        
    }
    
    /**
     * Check if is a single condition array
     *
     * @param array $arr Array to check.
     * @return boolean Returns boolean.
     * @since 0.1
     */
    public function _isSingleConditionArray($arr = array()) {
        if (sizeof($arr) != 1) {
            return false;
        }
        if (isset($arr['q'])) {
            return false;
        }
        if (isset($arr['bq'])) {
            return false;
        }
        if ($this->_isAssociativeArray($arr)) {
            return false;
        }
        return true;
    }
    
    /**
     * Check if is a conditional or query type
     *
     * @param string $field Field to check.
     * @param mixed $value String or array value to check.
     * @return boolean Returns boolean.
     * @since 0.1
     */
    public function _isAnOperator($field = null, $value = null) {
        $operators = array('bq', 'q', 'and', 'or', 'not');
        if (!is_array($value)) {
            return false;
        }
        if (!in_array($field, $operators, true)) {
            return false;
        }
        return true;
    }
    
    /**
     * Check if is single condition query or boolean query
     *
     * @param string $field Field to check.
     * @param mixed $value String or array value to check.
     * @return boolean Returns boolean.
     * @since 0.1
     */
    public function _isSingleConditionQueryOrBooleanQuery($field = null, $value = null) {
        if ($field !== 'q' && $field !== 'bq') {
            return false;
        }
        if (is_array($value)) {
            return false;
        }
        return true;
    }
    
    /**
     * Check if is an uint search range or values
     *
     * @param mixed $value Value to check.
     * @return boolean Returns boolean.
     * @since 0.1
     */
    public function _isAnUintSearchRangeOfValues($value = null) {
        if (!is_array($value)) {
            return false;
        }
        if (sizeof($value) !== 2) {
            return false;
        }
        if (!is_integer($value[0])) {
            return false;
        }
        if (!is_integer($value[1])) {
            return false;
        }
        return true;
    }
    
    /**
     * Check if is an uint search open ended value at start
     *
     * @param string $value Value to check.
     * @return boolean Returns boolean.
     * @since 0.1
     */
    public function _isAnUintSearchOpenEndedValueAtStart($value = null) {
        if (!is_array($value)) {
            return false;
        }
        if (sizeof($value) !== 2) {
            return false;
        }
        if ($value[0] !== '..') {
            return false;
        }
        if (!is_integer($value[1])) {
            return false;
        }
        return true;
    }
    
    /**
     * Check if is an uint search open ended value at end
     *
     * @param string $value Value to check.
     * @return boolean Returns boolean.
     * @since 0.1
     */
    public function _isAnUintSearchOpenEndedValueAtEnd($value = null) {
        if (!is_array($value)) {
            return false;
        }
        if (sizeof($value) !== 2) {
            return false;
        }
        if (!is_integer($value[0])) {
            return false;
        }
        if ($value[1] !== '..') {
            return false;
        }
        return true;
    }
    
    /**
     * Enclose string into quotes
     *
     * @param mixed $string String or Array of strings to enclose.
     * @return mixed Returns string(s) enclosed by quotes.
     * @since 0.1
     */
    public function _encloseQuotes($string = null) {
        if (is_array($string)) {
            foreach($string as $k=>$v) {
                $string[$k] = $this->_encloseQuotes($v);
            }
            return $string;
        }
        $last = strlen($string)-1;
        if (strpos($string, '(') === 0) {
            return $string;
        }
        if (substr_count($string, '"') > 2) {
            return "'{$string}'";
        }
        if (strstr($string, "'") && (substr_count($string, "'") % 2) == 0) {
            return $string;
        }
        if (strpos($string, '"') === 0 && strrpos($string, '"') === $last) {
            return $string;
        }
        if (strpos($string, "'") === 0 && strrpos($string, "'") === $last) {
            return $string;
        }
        if (strstr($string, ' ')) {
            return "'{$string}'";
        }
        return $string;
    }
    
    /**
     * Check if is an associative array
     *
     * @param array $arr Array to check.
     * @return boolean Returns boolean.
     * @since 0.1
     */
    public function _isAssociativeArray($arr = array()) {
        if (!is_array($arr)) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
    
    /**
     * Parse query data parameters
     *
     * @param array $query Query data.
     * @return array Query data parsed.
     * @since 0.1
     */
    public function _query($query = array()) {
        
        foreach($query as $key=>$value) {
            if ($key == 'conditions') {
                continue;
            }
            
            // facet
            if ($key == 'facet' && is_array($value)) {
                $query['facet'] = join(',', $value);
            }
            
            // facet-FIELD-constraints
            if (preg_match('/^facet-(.+?)-constraints/', $key)) {
                if (is_array($value) && (sizeof($value) == 2) 
                    && is_integer($value[0]) && is_integer($value[1])) {
                        $query[$key] = $value[0] .'..'. $value[1];
                } else {
                    foreach($value as $k=>$v) {
                        $value[$k] = '\''. $v .'\'';
                    }
                    $query[$key] = join(',', $value);
                }
            }
            
            // return-fields
            if ($key == 'return-fields' && is_array($value)) {
                $query['return-fields'] = join(',', $value);
            }
        }
        
        return $query;
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
