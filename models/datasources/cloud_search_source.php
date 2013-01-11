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
App::import('Core', array('HttpSocket'));

class CloudSearchSource extends DataSource {

    /**
     * The description of this data source
     *
     * @var string
     */
    public $description = 'CloudSearch DataSource';
    
    public $Http = null;
    
    public $_config = array(
        'datasource' => '',
        'search_endpoint' => '',
        'document_endpoint' => ''
        'api_version' => ''
    );
    
    public function __construct($config = array()) {
        $this->config = array_merge($this->_config, $config());
        $this->Http = new HttpSocket();
    }
    
    public function create() {
        return false;
    }
    
    public function read() {
        return false;
    }
    
    public function update() {
        return false;
    }
    
    public function delete() {
        return false;
    }
    
    public function query($method = null, $params = array(), $model = null) {
        
    }
    
    public function search($params = array()) {
        if (sizeof($params) == 0) {
            trigger_error(__('Invalid search parameters', true));
        }
        $url = sprintf(
            'https://%s/%s/search',
            $this->config['search_endpoint'],
            $this->config['api_version']
        );
        return $this->Http->get($url, $params);
    }
    
    public function document($params = null) {
        if (sizeof($params) == 0) {
            trigger_error(__('Invalid document parameters', true));
        }
        $url = sprintf(
            'https://%s/%s/documents/batch/',
            $this->config['document_endpoint'],
            $this->config['api_version']
        );
        return $this->Http->post($url, json_encode($params));
    }
    
    // public function request($url = null, $method = 'get', $params = null) {
    //     if ($method == 'post') {
    //         $response = $this->Http->post($this->_request);
    //     } else {
    //         $response = $this->Http->get($this->_request);
    //     }
    //     return $response;
    // }
    
    
}
