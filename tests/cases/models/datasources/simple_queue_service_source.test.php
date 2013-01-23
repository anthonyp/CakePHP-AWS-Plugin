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
     * Test listSources
     *
     * @return void
     */
    public function testListSources() {
        
        $this->SimpleQueueService->Http = false;
        $this->assertFalse($this->SimpleQueueService->listSources());
        $this->SimpleQueueService->Http = new MockHttpSocket();
        
        $response = '<?xml version="1.0"?><ListQueuesResponse xmlns="http://queue.amazonaws.com/doc/2012-11-05/">'
                    . '<ListQueuesResult>'
                    . '<QueueUrl>https://sqs.ap-northeast-1.amazonaws.com/445741222222/convertImages</QueueUrl>'
                    . '<QueueUrl>https://sqs.ap-northeast-1.amazonaws.com/445741222222/testQueue</QueueUrl>'
                    . '</ListQueuesResult>'
                    . '<ResponseMetadata>'
                    . '<RequestId>01c00bdd-5731-5125-806f-42a45fcd3393</RequestId>'
                    . '</ResponseMetadata>'
                    . '</ListQueuesResponse>';
        $this->SimpleQueueService->Http->setReturnValueAt(0, 'get', $response);
        $results = $this->SimpleQueueService->listSources();
        $expected = array('convertImages', 'testQueue');
        $this->assertEqual($results, $expected);
        
        $response = '<?xml version="1.0"?><Error><ErrorMessage>Some Error</ErrorMessage></Error>';
        $this->SimpleQueueService->Http->setReturnValueAt(0, 'get', $response);
        $this->assertFalse($this->SimpleQueueService->listSources());
        
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
        
        $this->SimpleQueueService->Http = false;
        $this->assertFalse($this->SimpleQueueService->create($this->Model));
        $this->SimpleQueueService->Http = new MockHttpSocket();
        
        $fields = array('MessageBody', 'DelaySeconds');
        $values = array('This is a test message', 0);
        $response = '<?xml version="1.0"?>'
                    . '<SendMessageResponse xmlns="http://queue.amazonaws.com/doc/2012-11-05/">'
                    . '<SendMessageResult>'
                    . '<MessageId>abb862ce-46cf-444f-a88d-46afc622b3de</MessageId>'
                    . '<MD5OfMessageBody>fafb00f5732ab283681e124bf8747ed1</MD5OfMessageBody>'
                    . '</SendMessageResult>'
                    . '<ResponseMetadata>'
                    . '<RequestId>18f74ec2-905c-5ecb-ac1a-07bb99b5b43d</RequestId>'
                    . '</ResponseMetadata>'
                    . '</SendMessageResponse>';
        $this->SimpleQueueService->Http->setReturnValueAt(0, 'get', $response);
        $results = $this->SimpleQueueService->create($this->Model, $fields, $values);
        $this->assertEqual($results['MessageId'], 'abb862ce-46cf-444f-a88d-46afc622b3de');
        $this->assertEqual($results['MD5OfMessageBody'], 'fafb00f5732ab283681e124bf8747ed1');
        
        $this->Model->data = array(
            'MessageBody' => 'This is a test message',
            'DelaySeconds' => 0
        );
        $response = '<?xml version="1.0"?>'
                    . '<SendMessageResponse xmlns="http://queue.amazonaws.com/doc/2012-11-05/">'
                    . '<SendMessageResult>'
                    . '<MessageId>abb862ce-46cf-444f-a88d-46afc622b3de</MessageId>'
                    . '<MD5OfMessageBody>fafb00f5732ab283681e124bf8747ed1</MD5OfMessageBody>'
                    . '</SendMessageResult>'
                    . '<ResponseMetadata>'
                    . '<RequestId>18f74ec2-905c-5ecb-ac1a-07bb99b5b43d</RequestId>'
                    . '</ResponseMetadata>'
                    . '</SendMessageResponse>';
        $this->SimpleQueueService->Http->setReturnValueAt(1, 'get', $response);
        $results = $this->SimpleQueueService->create($this->Model);
        $this->assertEqual($results['MessageId'], 'abb862ce-46cf-444f-a88d-46afc622b3de');
        $this->assertEqual($results['MD5OfMessageBody'], 'fafb00f5732ab283681e124bf8747ed1');
        
        $this->Model->data = array(
            'MessageBody' => 'This is a test message',
            'DelaySeconds' => 0
        );
        $response = '<?xml version="1.0"?>'
                    . '<SendMessageResponse xmlns="http://queue.amazonaws.com/doc/2012-11-05/">'
                    . '<SendMessageResult>'
                    . '<MessageId>abb862ce-46cf-444f-a88d-46afc622b3de</MessageId>'
                    . '<MD5OfMessageBody>fafb00f5732ab283681e124bf8747ed1</MD5OfMessageBody>'
                    . '</SendMessageResult>'
                    . '<ResponseMetadata>'
                    . '<RequestId>18f74ec2-905c-5ecb-ac1a-07bb99b5b43d</RequestId>'
                    . '</ResponseMetadata>'
                    . '</SendMessageResponse>';
        $this->SimpleQueueService->Http->setReturnValueAt(2, 'get', $response);
        $results = $this->SimpleQueueService->create($this->Model, 1);
        $this->assertEqual($results['MessageId'], 'abb862ce-46cf-444f-a88d-46afc622b3de');
        $this->assertEqual($results['MD5OfMessageBody'], 'fafb00f5732ab283681e124bf8747ed1');
        
        $this->Model->data = array(
            'MistypedMessageBody' => 'This is a test message',
            'DelaySeconds' => 0
        );
        $this->expectError(__('MessageBody field is required', true));
        $this->SimpleQueueService->create($this->Model, 1);
        
        $this->Model->data = array(
            'MessageBody' => 'This is a test message',
            'DelaySeconds' => 0
        );
        $response = '<?xml version="1.0"?><Error><ErrorMessage>Some Error</ErrorMessage></Error>';
        $this->SimpleQueueService->Http->setReturnValueAt(0, 'get', $response);
        $this->assertFalse($this->SimpleQueueService->create($this->Model, 1));
        
    }
    
    /**
     * Test read
     *
     * @return void
     */
    public function testRead() {
        
        $this->SimpleQueueService->Http = false;
        $this->assertFalse($this->SimpleQueueService->read($this->Model));
        $this->SimpleQueueService->Http = new MockHttpSocket();
        
        $params = array(
            'AttributeNames' => array('All'),
            'MaxNumberOfMessages' => 1,
            'VisibilityTimeout' => 300,
            'WaitTimeSeconds' => 0
        );
        //$result = $this->Model->read($params);
        //debug($result);
        
        $params = array(
            'AttributeNames' => array('All'),
            'MaxNumberOfMessages' => 1,
            'VisibilityTimeout' => 300,
            'WaitTimeSeconds' => 0
        );
        //$result = $this->SimpleQueueService->read($this->Model, $params);
        //debug($result);
        
        
    }
    
    /**
     * Test update
     *
     * @return void
     */
    public function testUpdate() {
        
        $this->assertFalse($this->SimpleQueueService->update($this->Model));
        
    }
    
    /**
     * Test delete
     *
     * @return void
     */
    public function testDelete() {
        
        $this->SimpleQueueService->Http = false;
        $this->assertFalse($this->SimpleQueueService->delete($this->Model));
        $this->SimpleQueueService->Http = new MockHttpSocket();
        
    }
    
    /**
     * Test query
     *
     * @return void
     */
    public function testQuery() {
        
        $this->SimpleQueueService->Http = false;
        $this->assertFalse($this->SimpleQueueService->query($this->Model));
        $this->SimpleQueueService->Http = new MockHttpSocket();
        
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

