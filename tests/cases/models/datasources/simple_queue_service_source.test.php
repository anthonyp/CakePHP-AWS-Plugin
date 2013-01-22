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
        'account_id' => '20121105'
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
            $this->Model->queueName = 'testQueue';
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
            'account_id' => '20121105'
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
        
        $method = 'this_method_does_not_exist';
        $this->expectError(sprintf(__('Invalid API action: %s', true), $method));
        $this->SimpleQueueService->query($method, array(), $this->Model);
        
        $method = 'ListQueues';
        $params = array('QueueNamePrefix'=>'t');
        $response = '<ListQueuesResponse><ListQueuesResult>'
                    . '<QueueUrl>http://sqs.us-east-1.amazonaws.com/123456789012/testQueue</QueueUrl>'
                    . '</ListQueuesResult><ResponseMetadata>'
                    . '<RequestId>725275ae-0b9b-4762-b238-436d7c65a1ac</RequestId>'
                    . '</ResponseMetadata></ListQueuesResponse>';
        $this->SimpleQueueService->Http->setReturnValueAt(0, 'get', $response);
        $result = $this->SimpleQueueService->query($method, array($params), $this->Model);
        $expected = array(
            'ListQueuesResponse' => array(
                'ListQueuesResult' => array(
                    'QueueUrl' => 'http://sqs.us-east-1.amazonaws.com/123456789012/testQueue'
                ),
                'ResponseMetadata' => array(
                    'RequestId' => '725275ae-0b9b-4762-b238-436d7c65a1ac'
                )
            )
        );
        $this->assertEqual($result, $expected);
        
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

