<?php
/**
 * Amazon Simple Queue Service DataSource Test File
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
App::import('Datasource', 'AWS.SimpleQueueServiceSource');
App::import('Core', 'HttpSocket');
Mock::generate('HttpSocket');

/**
 * SQS TestCase
 *
 * @package     datasources
 * @subpackage  datasources.tests.cases.models.datasources
 */
class SimpleQueueServiceTestCase extends CakeTestCase {
    
    /**
     * SQS object
     *
     * @var object
     */
    public $SimpleQueueService = null;
    
    /**
     * Model object
     *
     * @var object
     */
    public $Model = null;
    
    /**
     * HttpSocket object
     *
     * @var object
     */
    public $Http = null;
    
    /**
     * Model configuration
     *
     * @var array
     */
    public $config = array(
        'datasource' => 'AWS.SimpleQueueServiceSource',
        'host' => 'sqs.test.amazonaws.com',
        'login' => 'test.access.key',
        'password' => 'test.secret.key',
        'api_version' => '2012-11-05'
    );
    
    /**
     * Test start
     *
     * @return void
     */
    public function startTest() {
        
        if (empty($this->SimpleQueueService)) {
            $this->SimpleQueueService = new SimpleQueueServiceSource($this->config);
            $this->SimpleQueueService->Http = new MockHttpSocket();
        }
        
        if (!$this->Model) {
            $this->Model->alias = 'Model';
            $this->Model->findQueryType = null;
        }
        
    }
    
    /**
     * Test end
     *
     * @return void
     */
    public function endTest() {
        $this->SimpleQueueService = null;
        $this->Model = null;
        ClassRegistry::flush();
    }
    
    /**
     * Test setConfig
     *
     * @return void
     */
    public function testSetConfig() {
        
        $config = array(
            'datasource' => 'AWS.SimpleQueueServiceSource',
            'host' => 'sqs.test.config.amazonaws.com',
            'login' => 'test.access.key',
            'password' => 'test.secret.key',
            'api_version' => '2012-11-05'
        );
        $this->SimpleQueueService->setConfig($config);
        $this->assertEqual($this->SimpleQueueService->config, $config);
        
    }
    
    /**
     * Test setHttpSocket
     *
     * @return void
     */
    public function testSetHttpSocket() {
        
        $this->assertEqual(
            $this->SimpleQueueService->Http,
            $this->SimpleQueueService->setHttpSocket()
        );
        
    }
    
    /**
     * Test calculate
     *
     * @return void
     */
    public function testCalculate() {
        
        $this->assertTrue($this->SimpleQueueService->calculate());
        
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
     * Test find
     *
     * @return void
     */
    public function testFind() {
        
    }
    
    /**
     * Test findBy
     *
     * @return void
     */
    public function testFindBy() {
        
    }
    
    /**
     * Test saveAll
     *
     * @return void
     */
    public function testSaveAll() {
        
    }
    
    /**
     * Test updateAll
     *
     * @return void
     */
    public function testUpdateAll() {
        
    }
    
    /**
     * Test deleteAll
     *
     * @return void
     */
    public function testDeleteAll() {
        
    }
    
    /**
     * Test _request
     *
     * @return void
     */
    public function testRequest() {
        
    }
    
    /**
     * Test _signQuery
     *
     * @return void
     */
    public function testSignQuery() {
        
    }
    
}

