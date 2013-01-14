<?php
/**
 * CloudSearch DataSource Test File
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
App::import('Datasource', 'AWS.CloudSearchSource');
App::import('Core', 'HttpSocket');
Mock::generate('HttpSocket');

class CloudSearchTestCase extends CakeTestCase {
    
    /**
     * CloudSearch object
     *
     * @var object
     */
    public $CloudSearch = null;
    
    /**
     * Model object
     *
     * @var object
     */
    public $Model = null;
    
    /**
     * Model configuration
     *
     * @var array
     */
    public $config = array(
        'datasource' => 'AWS.CloudSearchSource',
        'search_endpoint' => 'test.search_endpoint',
        'document_endpoint' => 'test.document_endpoint',
        'api_version' => '1'
    );
    
    /**
     * Test start
     *
     * @return void
     */
    public function startTest() {
        
        if (empty($this->CloudSearch)) {
            $this->CloudSearch = new CloudSearchSource($this->config);
            $this->CloudSearch->Http = new MockHttpSocket();
        }
        
    }
    
    /**
     * Test end
     *
     * @return void
     */
    public function endTest() {
        $this->CloudSearch = null;
        ClassRegistry::flush();
    }
    
    /**
     * Test setConfig
     *
     * @return void
     */
    public function testSetConfig() {
        
        $config = array(
            'datasource' => 'AWS.CloudSearchSource',
            'search_endpoint' => 'search-tests-px2qjztrvfmtcvmik3ohbdq6vy.us-east-1.cloudsearch.amazonaws.com',
            'document_endpoint' => 'doc-tests-px2qjztrvfmtcvmik3ohbdq6vy.us-east-1.cloudsearch.amazonaws.com',
            'api_version' => '2011-02-01'
        );
        
        $this->CloudSearch->setConfig($config);
        
        $this->assertEqual($this->CloudSearch->config, $config);
        
    }
    
    /**
     * Test setHttpSocket
     *
     * @return void
     */
    public function testSetHttpSocket() {
        
        // $result = $this->CloudSearch->setHttpSocket();
        // 
        // $this->assertTrue(is_object($result));
        
    }
    
    /**
     * Test create
     *
     * @return void
     */
    public function testCreate() {
        
    }
    
    /**
     * Test read
     *
     * @return void
     */
    public function testRead() {
        
    }
    
    /**
     * Test update
     *
     * @return void
     */
    public function testUpdate() {
        
    }
    
    /**
     * Test delete
     *
     * @return void
     */
    public function testDelete() {
        
    }
    
    /**
     * Test query
     *
     * @return void
     */
    public function testQuery() {
        
        $method = 'this_method_do_not_exists';
        
        $this->expectError(__('Invalid method call: '.$method, true));
        $this->CloudSearch->query($method, array());
        
        $params = array(
            'q' => 'Die Hard'
        );
        
        $expected = 'called_search';
        
        $this->CloudSearch->Http->setReturnValue('get', $expected);
        
        $result = $this->CloudSearch->query('search', $params);
        
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test search
     *
     * @return void
     */
    public function testSearch() {
        
        $this->expectError(__('Invalid search parameters', true));
        $this->CloudSearch->search();
        
        $url = sprintf(
            'https://%s/%s/search',
            $this->config['search_endpoint'],
            $this->config['api_version']
            
        );
        
        $params = array(
            'q' => 'Die Hard'
        );
        
        $this->CloudSearch->Http->expect('get', array($url, $params));
        $this->CloudSearch->search($params);
    }
    
    /**
     * Test document
     *
     * @return void
     */
    public function testDocument() {
        
        $this->expectError(__('Invalid document parameters', true));
        $this->CloudSearch->document();
        
        $url = sprintf(
            'https://%s/%s/documents/batch/',
            $this->config['document_endpoint'],
            $this->config['api_version']
        );
        
        $params = array(
            'q' => 'Die Hard'
        );
        
        $this->CloudSearch->Http->expect('post', array($url, json_encode($params)));
        $this->CloudSearch->search($params);
        
        
    }
    
    /**
     * Test conditions
     *
     * @return void
     */
    public function testConditions() {
        
    }
    
}