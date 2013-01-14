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

App::import('Core', 'HttpSocket');
Mock::generate('HttpSocket');

App::import('Datasource', 'AWS.CloudSearchSource');
Mock::generatePartial(
    'CloudSearchSource',
    'CloudSearchSourceTestVersion',
    array('setHttpSocket')
);


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
        'datasource' => '',
        'search_endpoint' => '',
        'document_endpoint' => '',
        'api_version' => ''
    );
    
    /**
     * Test start
     *
     * @return void
     */
    public function startTest() {
        
    }
    
    /**
     * Test end
     *
     * @return void
     */
    public function endTest() {
        
    }
    
    /**
     * Test setConfig
     *
     * @return void
     */
    public function testSetConfig() {
        
    }
    
    /**
     * Test setHttpSocket
     *
     * @return void
     */
    public function testSetHttpSocket() {
        
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
        
    }
    
    /**
     * Test search
     *
     * @return void
     */
    public function testSearch() {
        
    }
    
    /**
     * Test document
     *
     * @return void
     */
    public function testDocument() {
        
    }
    
    /**
     * Test conditions
     *
     * @return void
     */
    public function testConditions() {
        
    }
    
}