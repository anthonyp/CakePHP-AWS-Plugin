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
        $this->assertEqual(
            $results['SendMessageResponse']['SendMessageResult']['MessageId'],
            'abb862ce-46cf-444f-a88d-46afc622b3de'
        );
        $this->assertEqual(
            $results['SendMessageResponse']['SendMessageResult']['MD5OfMessageBody'],
            'fafb00f5732ab283681e124bf8747ed1'
        );
        
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
        $this->assertEqual(
            $results['SendMessageResponse']['SendMessageResult']['MessageId'],
            'abb862ce-46cf-444f-a88d-46afc622b3de'
        );
        $this->assertEqual(
            $results['SendMessageResponse']['SendMessageResult']['MD5OfMessageBody'],
            'fafb00f5732ab283681e124bf8747ed1'
        );
        
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
        $this->assertEqual(
            $results['SendMessageResponse']['SendMessageResult']['MessageId'],
            'abb862ce-46cf-444f-a88d-46afc622b3de'
        );
        $this->assertEqual(
            $results['SendMessageResponse']['SendMessageResult']['MD5OfMessageBody'],
            'fafb00f5732ab283681e124bf8747ed1'
        );
        
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
        
        $query = array(
            'conditions' => array(),
            'fields' => array(
                'AttributeNames' => array('All'),
                'MaxNumberOfMessages' => 1,
                'VisibilityTimeout' => 300,
                'WaitTimeSeconds' => 0
            )
        );
        $result = $this->SimpleQueueService->read($this->Model, $query);
        $this->assertFalse($result);
        
        $query = array(
            'conditions' => array('Model.id'=>'3'),
            'fields' => array(
                'AttributeNames' => array('All'),
                'VisibilityTimeout' => 300,
                'WaitTimeSeconds' => 0
            )
        );
        $response = '<?xml version="1.0"?>'
            . '<ReceiveMessageResponse xmlns="http://queue.amazonaws.com/doc/2012-11-05/">'
            . '<ReceiveMessageResult><Message><Body>This is a test message</Body>'
            . '<MD5OfBody>fafb00f5732ab283681e124bf8747ed1</MD5OfBody>'
            . '<ReceiptHandle>cOJv9qrD9XLVlpsfwYn3xZ2ie/9St+dEOFjH6lc95+J2AF6x7w8KwRA7//Lcom5YkZupq5bMm8cmrLll'
            . 'zyQfgsoP/x2gDs/rAbyf9N2MeNk+sp7sFVfYmSMMQ2eT99jVvuaVMhc92KFhSBPmmg2jqI5ewdqYJkBSTWcSfooJtzwHSl'
            . '+TGI0Konoxxw2g8fAiruUIh7zwHQBojqmQxp/CTG9J0xXEbu8faTvJR7KKMdPQMxoz22NLkLONeCZiloHljv5sY0QcLhkhH'
            . 'McJTTUslBF9ZpMvdlp7/mKV/UpBXEM6e9VJvZqVOA==</ReceiptHandle><Attribute><Name>SenderId</Name>'
            . '<Value>445741093162</Value></Attribute><Attribute><Name>ApproximateFirstReceiveTimestamp</Name>'
            . '<Value>1359022363823</Value></Attribute><Attribute><Name>ApproximateReceiveCount</Name>'
            . '<Value>1</Value></Attribute><Attribute><Name>SentTimestamp</Name><Value>1359022195958</Value>'
            . '</Attribute><MessageId>df1b56cc-6d3b-4836-923f-d596c08192f8</MessageId></Message><Message>'
            . '<Body>This is a test message</Body><MD5OfBody>fafb00f5732ab283681e124bf8747ed1</MD5OfBody>'
            . '<ReceiptHandle>cOJv9qrD9XLVlpsfwYn3xZ2ie/9St+dEOFjH6lc95+JYTf9iOKqR9xA7//Lcom5Y+vxu6mzD2TEmrL'
            . 'llzyQfgvfWX2E2kQSNAbyf9N2MeNk+sp7sFVfYmSMMQ2eT99jVvuaVMhc92KFhSBPmmg2jqI5ewdqYJkBSTWcSfooJtzwHSl'
            . '+TGI0Konoxxw2g8fAiruUIh7zwHQBojqmQxp/CTJyS7ArMhkzXPoVhW7X02GsGZiJYukIyoaWw1Y4ZPusXkrb0Et8oPziy'
            . 'PuzIWSyHP6iq8kYuSZcs/mKV/UpBXEM6e9VJvZqVOA==</ReceiptHandle><Attribute><Name>SenderId</Name>'
            . '<Value>445741093162</Value></Attribute><Attribute><Name>ApproximateFirstReceiveTimestamp</Name>'
            . '<Value>1359022363823</Value></Attribute><Attribute><Name>ApproximateReceiveCount</Name>'
            . '<Value>1</Value></Attribute><Attribute><Name>SentTimestamp</Name><Value>1359022345191</Value>'
            . '</Attribute><MessageId>4ee47821-781a-4954-bf38-3a6cc76b7dcb</MessageId></Message>'
            . '</ReceiveMessageResult><ResponseMetadata><RequestId>d6512f08-d0bf-50b2-a3d5-6673dbe49845</RequestId>'
            . '</ResponseMetadata></ReceiveMessageResponse>';
        $this->SimpleQueueService->Http->setReturnValueAt(1, 'get', $response);
        $result = $this->SimpleQueueService->read($this->Model, $query);
        $expected = Set::extract(
            $result,
            '/ReceiveMessageResponse/ReceiveMessageResult/Message/MD5OfBody'
        );
        $this->assertEqual($expected[0], 'fafb00f5732ab283681e124bf8747ed1');
        $this->assertEqual($expected[1], 'fafb00f5732ab283681e124bf8747ed1');
        
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
        
        $conditions = array(
            'Model.condition1' => 1,
            'Model.condition2' => 2
        );
        $this->expectError(__('Conditions are not supported', true));
        $this->SimpleQueueService->delete($this->Model, $conditions);
        
        $conditions = array();
        $this->expectError(__('ReceiptHandle is required', true));
        $this->SimpleQueueService->delete($this->Model, $conditions);
        
        $conditions = array('Model.id' => '9999');
        $response = '<?xml version="1.0"?><DeleteQueueResponse xmlns='
            . '"http://queue.amazonaws.com/doc/2012-11-05/">'
            . '<ResponseMetadata><RequestId>99c83824-5aea-5d2d-b89b-715f495bbf9b'
            . '</RequestId></ResponseMetadata></DeleteQueueResponse>';
        $this->SimpleQueueService->Http->setReturnValueAt(0, 'get', $response);
        $result = $this->SimpleQueueService->delete($this->Model, $conditions);
        $expected = array(
            'DeleteQueueResponse' => array(
                'xmlns' => 'http://queue.amazonaws.com/doc/2012-11-05/',
                'ResponseMetadata' => array(
                    'RequestId' => '99c83824-5aea-5d2d-b89b-715f495bbf9b'
                )
            )
        );
        $this->assertEqual($result, $expected);
        
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
        
        $method = 'ListQueues';
        $query = array(
            'Action' => 'ListQueues'
        );
        $response = '<ListQueuesResponse><ListQueuesResult>'
                    . '<QueueUrl>http://sqs.us-east-1.amazonaws.com/123456789012/testQueue</QueueUrl>'
                    . '</ListQueuesResult><ResponseMetadata>'
                    . '<RequestId>725275ae-0b9b-4762-b238-436d7c65a1ac</RequestId>'
                    . '</ResponseMetadata></ListQueuesResponse>';
        $this->SimpleQueueService->Http->setReturnValueAt(0, 'get', $response);
        $result = $this->SimpleQueueService->_request($query);
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
     * Test _signQuery
     *
     * @return void
     */
    public function testSignQuery() {
        
        // test a call that dont uses queue name
        
        $query = array(
            'Action' => 'CreateQueue',
            'QueueName' => 'Test',
            'Attributes' => array(
                'DelaySeconds' => 0,
                'MaximumMessageSize' => 65536,
                'MessageRetentionPeriod' => 345600,
                'ReceiveMessageWaitTimeSeconds' => 0,
                'VisibilityTimeout' => 30
            )
        );
        $result = $this->SimpleQueueService->_signQuery($query);
        $result = parse_url($result);
        parse_str($result['query'], $queryString);
        unset(
            $queryString['Timestamp'],
            $queryString['Signature']
        );
        $expected = array(
            'AWSAccessKeyId' => $this->SimpleQueueService->config['login'],
            'Action' => 'CreateQueue',
            'Attribute_1_Name' => 'DelaySeconds',
            'Attribute_1_Value' => 0,
            'Attribute_2_Name' => 'MaximumMessageSize',
            'Attribute_2_Value' => 65536,
            'Attribute_3_Name' => 'MessageRetentionPeriod',
            'Attribute_3_Value' => 345600,
            'Attribute_4_Name' => 'ReceiveMessageWaitTimeSeconds',
            'Attribute_4_Value' => 0,
            'Attribute_5_Name' => 'VisibilityTimeout',
            'Attribute_5_Value' => 30,
            'QueueName' => 'Test',
            'SignatureMethod' => 'HmacSHA256',
            'SignatureVersion' => 2,
            'Version' => $this->SimpleQueueService->api_version
        );
        $this->assertEqual($queryString, $expected);
        
        // test a call that uses queue name
        
        $query = array(
            'Action' => 'ReceiveMessage',
            'AttributeNames' => array('All'),
            'MaxNumberOfMessages' => 1,
            'VisibilityTimeout' => 300,
            'WaitTimeSeconds' => 0
        );
        $result = $this->SimpleQueueService->_signQuery($query, 'Test');
        $result = parse_url($result);
        parse_str($result['query'], $queryString);
        unset(
            $queryString['Timestamp'],
            $queryString['Signature']
        );
        $expected = array(
            'AWSAccessKeyId' => $this->SimpleQueueService->config['login'],
            'Action' => 'ReceiveMessage',
            'AttributeName_1' => 'All',
            'MaxNumberOfMessages' => '1',
            'SignatureMethod' => 'HmacSHA256',
            'SignatureVersion' => '2',
            'Version' => $this->SimpleQueueService->api_version,
            'VisibilityTimeout' => '300',
            'WaitTimeSeconds' => '0'
        );
        $this->assertEqual($queryString, $expected);
        
        $query = array(
            'Action' => 'ReceiveMessage',
            'AttributeNames' => array('All'),
            'MaxNumberOfMessages' => 1,
            'VisibilityTimeout' => 300,
            'WaitTimeSeconds' => 0
        );
        $this->expectError(__('Invalid request queue name is required', true));
        $this->SimpleQueueService->_signQuery($query);
        
    }
    
    /**
     * Test _parseQuery
     *
     * @return void
     */
    public function testParseQuery() {
        
        // BatchRequestEntries
        $query = array(
            'Action' => 'SendMessageBatch',
            'BatchRequestEntries' => array(
                array(
                    'Id' => '50ff2d3428351',
                    'MessageBody' => 'This is a test message',
                    'DelaySeconds' => 0
                ),
                array(
                    'Id' => '50ff2d3428368',
                    'MessageBody' => 'This is a test message',
                    'DelaySeconds' => 0
                ),
                array(
                    'Id' => '50ff2d3428374',
                    'MessageBody' => 'This is a test message',
                    'DelaySeconds' => 0
                ),
                'ignore this'
            )
        );
        $result = $this->SimpleQueueService->_parseQuery($query);
        $expected = array(
            'Action' => 'SendMessageBatch',
            $query['Action'] .'RequestEntry.1.Id' => '50ff2d3428351',
            $query['Action'] .'RequestEntry.1.MessageBody' => 'This is a test message',
            $query['Action'] .'RequestEntry.1.DelaySeconds' => 0,
            $query['Action'] .'RequestEntry.2.Id' => '50ff2d3428368',
            $query['Action'] .'RequestEntry.2.MessageBody' => 'This is a test message',
            $query['Action'] .'RequestEntry.2.DelaySeconds' => 0,
            $query['Action'] .'RequestEntry.3.Id' => '50ff2d3428374',
            $query['Action'] .'RequestEntry.3.MessageBody' => 'This is a test message',
            $query['Action'] .'RequestEntry.3.DelaySeconds' => 0
        );
        $this->assertEqual($result, $expected);
        
        // AttributeNames
        $query = array(
            'AttributeNames' => array('All'),
            'MaxNumberOfMessages' => 3,
            'VisibilityTimeout' => 120,
            'WaitTimeSeconds' => 0
        );
        $result = $this->SimpleQueueService->_parseQuery($query);
        $expected = array(
            'MaxNumberOfMessages' => 3,
            'VisibilityTimeout' => 120,
            'WaitTimeSeconds' => 0,
            'AttributeName.1' => 'All'
        );
        $this->assertEqual($result, $expected);
        
        // Attributes
        $query = array(
            'Attributes' => array(
                'DelaySeconds' => 300,
                'MaximumMessageSize' => 65536,
                'MessageRetentionPeriod' => 345600,
                'ReceiveMessageWaitTimeSeconds' => 0,
                'VisibilityTimeout' => 30
            )
        );
        $result = $this->SimpleQueueService->_parseQuery($query);
        $expected = array(
            'Attribute.1.Name' => 'DelaySeconds',
            'Attribute.1.Value' => 300,
            'Attribute.2.Name' => 'MaximumMessageSize',
            'Attribute.2.Value' => 65536,
            'Attribute.3.Name' => 'MessageRetentionPeriod',
            'Attribute.3.Value' => 345600,
            'Attribute.4.Name' => 'ReceiveMessageWaitTimeSeconds',
            'Attribute.4.Value' => 0,
            'Attribute.5.Name' => 'VisibilityTimeout',
            'Attribute.5.Value' => 30
        );
        $this->assertEqual($result, $expected);
        
    }
    
}

