<?php
/**
 * Queue Model Test File for Amazon Simple Queue Services Datasource
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
class Queue extends CakeTestModel {
    
    public $name = 'Queue';
    
    public $validate = array();
    
    public $useTable = false;
    
    public $queueName = 'testQueue';
    
    public $useDbConfig = 'sqs_test';
    
    public $_schema = array(
        'Body' => array(
            'type' => 'text',
            'null' => false
        ),
        'MD5OfBody' => array(
            'type' => 'text',
            'null' => false
        ),
        'MessageId' => array(
            'type' => 'string',
            'null' => false,
            'key' => 'primary',
            'length' => 255,
        ),
        'ReceiptHandle' => array(
            'type' => 'text',
            'null' => false
        ),
        'Attribute' => array(
            'type' => 'array',
            'null' => false
        )
    );
    
}

class QueueTestCase extends CakeTestCase {
    
    /**
     * Configuration array
     *
     * @var array
     */
    public $config = array();
    
    /**
     * Test start
     *
     * @return void
     */
    public function startTest() {
        $config = new DATABASE_CONFIG();
        if (isset($config->sqs_test)) {
            $this->config = $config->sqs_test;
        }
        ConnectionManager::create('sqs_test', $this->config);
        $this->Queue =& ClassRegistry::init('Queue');
    }
    
    /**
     * Test end
     *
     * @return void
     */
    public function endTest() {
        unset($this->Queue);
        ClassRegistry::flush();
    }
    
    /**
     * Test Message Actions: SendMessage, ReceiveMessage, ChangeMessageVisibility and DeleteMessage
     *
     * @return void
     */
    public function testMessageActions() {
        
        // SendMessage
        // http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QuerySendMessage.html
        $message = 'This is a test message';
        $params = array(
            'MessageBody' => $message,
            'DelaySeconds' => 0
        );
        $result = $this->Queue->SendMessage($params);
        $this->assertEqual(
            md5($message),
            $result['SendMessageResponse']['SendMessageResult']['MD5OfMessageBody']
        );
        
        // ReceiveMessage
        // http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryReceiveMessage.html
        $params = array(
            'AttributeNames' => array('All'),
            'MaxNumberOfMessages' => 1,
            'VisibilityTimeout' => 300,
            'WaitTimeSeconds' => 0
        );
        $message = $result = $this->Queue->ReceiveMessage($params);
        $this->assertFalse(
            empty($result['ReceiveMessageResponse']['ReceiveMessageResult']['Message']['Body'])
        );
        
        // ChangeMessageVisibility
        // http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryChangeMessageVisibility.html
        $timeout = rand(0, 600);
        $params = array(
            'ReceiptHandle' => $result['ReceiveMessageResponse']['ReceiveMessageResult']['Message']['ReceiptHandle'],
            'VisibilityTimeout' => $timeout
        );
        $result = $this->Queue->ChangeMessageVisibility($params);
        $this->assertFalse(
            empty($result['ChangeMessageVisibilityResponse']['ResponseMetadata']['RequestId'])
        );
        
        // DeleteMessage
        // http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryDeleteMessage.html
        $params = array(
            'ReceiptHandle' => $message['ReceiveMessageResponse']['ReceiveMessageResult']['Message']['ReceiptHandle']
        );
        $result = $this->Queue->DeleteMessage($params);
        $this->assertFalse(
            empty($result['DeleteMessageResponse']['ResponseMetadata']['RequestId'])
        );
        
    }
    
    /**
     * Test Batch Actions; SendMessageBatch, ChangeMessageVisibilityBatch and DeleteMessageBatch
     *
     * @return void
     */
    public function testBatchActions() {
        
        // SendMessageBatch
        // http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QuerySendMessageBatch.html
        $messageText = 'This is a test message';
        $params = array(
            'BatchRequestEntries' => array(
                array(
                    'Id' => uniqid(),
                    'MessageBody' => $messageText,
                    'DelaySeconds' => 0
                ),
                array(
                    'Id' => uniqid(),
                    'MessageBody' => $messageText,
                    'DelaySeconds' => 0
                ),
                array(
                    'Id' => uniqid(),
                    'MessageBody' => $messageText,
                    'DelaySeconds' => 0
                ),
            )
        );
        $result = $this->Queue->SendMessageBatch($params);
        if (empty($result['SendMessageBatchResponse']['SendMessageBatchResult']['SendMessageBatchResultEntry'])) {
            $this->fail('not results');
        } else {
            $messages = $result['SendMessageBatchResponse']['SendMessageBatchResult']['SendMessageBatchResultEntry'];
        }
        foreach($messages as $message) {
            $this->assertEqual(md5($messageText), $message['MD5OfMessageBody']);
        }
        
        // ChangeMessageVisibilityBatch
        // http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryChangeMessageVisibilityBatch.html
        $params = array(
            'AttributeNames' => array('All'),
            'MaxNumberOfMessages' => 3,
            'VisibilityTimeout' => 120,
            'WaitTimeSeconds' => 0
        );
        $result = $this->Queue->ReceiveMessage($params);
        if (!empty($result['ReceiveMessageResponse']['ReceiveMessageResult']['Message'])) {
            $messages = $result['ReceiveMessageResponse']['ReceiveMessageResult']['Message'];
        } else {
            $this->fail('Failed to receive messages');
        }
        $params = array();
        $i = 0;
        foreach($messages as $message) {
            $params['BatchRequestEntries'][] = array(
                'Id' => uniqid(),
                'ReceiptHandle' => $message['ReceiptHandle'],
                'VisibilityTimeout' => 45
            );
        }
        $result = $this->Queue->ChangeMessageVisibilityBatch($params);
        $resultIds = Set::extract($result, '/ChangeMessageVisibilityBatchResponse/ChangeMessageVisibilityBatchResult/ChangeMessageVisibilityBatchResultEntry/Id');
        $expectedIds = Set::extract($params, '/BatchRequestEntries/Id');
        $this->assertEqual($resultIds, $expectedIds);
        
        // DeleteMessageBatch
        // http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryDeleteMessageBatch.html
        $params = array();
        $i = 0;
        foreach($messages as $message) {
            $params['BatchRequestEntries'][] = array(
                'Id' => uniqid(),
                'ReceiptHandle' => $message['ReceiptHandle']
            );
        }
        $result = $this->Queue->DeleteMessageBatch($params);
        $resultIds = Set::extract($result, '/DeleteMessageBatchResponse/DeleteMessageBatchResult/DeleteMessageBatchResultEntry/Id');
        $expectedIds = Set::extract($params, '/BatchRequestEntries/Id');
        $this->assertEqual($resultIds, $expectedIds);
        
    }
    
    /**
     * Test Permission Actions; AddPermission and RemovePermission
     *
     * @return void
     */
    public function testAddPermissionAndRemovePermission() {
        
        // AddPermission
        // http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryAddPermission.html
        
        
        // RemovePermission
        // http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryRemovePermission.html
        
        
    }
    
    /**
     * Test GetQueueUrl
     *
     * @link http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryGetQueueUrl.html
     * @return void
     */
    public function testGetQueueUrl() {
        
        $params = array(
            'QueueName' => $this->Queue->queueName,
            'QueueOwnerAWSAccountId' => $this->config['account_id']
        );
        
        $result = $this->Queue->GetQueueUrl($params);
        
        $this->assertTrue(
            strstr($result['GetQueueUrlResponse']['GetQueueUrlResult']['QueueUrl'], $this->Queue->queueName)
        );
        
    }
    
    /**
     * Test CreateQueue and DeleteQueue
     * 
     * By default this tests are skipped, Amazon consider abusive create/delete many queues
     *
     * @return void
     */
    public function testCreateQueueAndDeleteQueue() {
        
        $this->skipIf(true, 'CreateQueue it is abusive create/delete many queues');
        
        // CreateQueue
        // http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryCreateQueue.html
        $queueName = uniqid();
        $queueName = "test_{$queueName}";
        $params = array(
            'QueueName' => $queueName,
            'Attributes' => array(
                'DelaySeconds' => 0,
                'MaximumMessageSize' => 65536,
                'MessageRetentionPeriod' => 345600,
                //'Policy' => rawurlencode($policy),
                'ReceiveMessageWaitTimeSeconds' => 0,
                'VisibilityTimeout' => 30
            )
        );
        $result = $this->Queue->CreateQueue($params);
        $this->assertTrue(
            strstr($result['CreateQueueResponse']['CreateQueueResult']['QueueUrl'], $queueName)
        );
        
        // DeleteQueue
        // http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryDeleteQueue.html
        $this->Queue->queueName = $queueName;
        $result = $this->Queue->DeleteQueue();
        $this->assertFalse(
            empty($result['DeleteQueueResponse']['ResponseMetadata']['RequestId'])
        );
        
    }
    
    /**
     * Test ListQueues
     *
     * @link http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryListQueues.html
     * @return void
     */
    public function testListQueues() {
        
        $params = array('QueueNamePrefix'=>'do_not_exists');
        $result = $this->Queue->ListQueues($params);
        $this->assertTrue(empty($result['ListQueuesResponse']['ListQueuesResult']));
        
        $params = array();
        $result = $this->Queue->ListQueues($params);
        $this->assertFalse(empty($result['ListQueuesResponse']['ListQueuesResult']['QueueUrl']));
        
    }
    
    /**
     * Test GetQueueAttributes
     *
     * @link http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryGetQueueAttributes.html
     * @return void
     */
    public function testGetQueueAttributes() {
        
        $attributes = array(
            'All',
            'ApproximateNumberOfMessages',
            'ApproximateNumberOfMessagesNotVisible',
            'VisibilityTimeout',
            'CreatedTimestamp',
            'LastModifiedTimestamp',
            'Policy',
            'MaximumMessageSize',
            'MessageRetentionPeriod',
            'QueueArn',
            'OldestMessageAge',
            'DelaySeconds',
            'ApproximateNumberOfMessagesDelayed'
        );
        
        $params = array('AttributeNames'=>array('All'));
        
        $result = $this->Queue->GetQueueAttributes($params);
        
        $results = Set::extract($result, '/GetQueueAttributesResponse/GetQueueAttributesResult/Attribute/Name');
        
        foreach($attributes as $attribute) {
            if ($attribute == 'All') {
                continue;
            }
            $this->assertTrue(in_array($attribute, $attributes));
        }
        
    }
    
    /**
     * Test SetQueueAttributes
     *
     * @link http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QuerySetQueueAttributes.html
     * @return void
     */
    public function testSetQueueAttributes() {
        
        $params = array(
            'Attributes' => array(
                'DelaySeconds' => 300,
                'MaximumMessageSize' => 65536,
                'MessageRetentionPeriod' => 345600,
                //'Policy' => '',
                'ReceiveMessageWaitTimeSeconds' => 0,
                'VisibilityTimeout' => 30
            )
        );
        
        $result = $this->Queue->SetQueueAttributes($params);
        
        $this->assertFalse(
            empty($result['SetQueueAttributesResponse']['ResponseMetadata']['RequestId'])
        );
        
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
     * Test Read
     *
     * @return void
     */
    public function testRead() {
        
        $params = array(
            'AttributeNames' => array('All'),
            'VisibilityTimeout' => 300,
            'WaitTimeSeconds' => 0
        );
        $result = $this->Queue->read($params, 2);
        debug($result);
        
    }
    
    /**
     * Test Save
     *
     * @return void
     */
    public function testSave() {
        
    }
    
    /**
     * Test delete
     *
     * @return void
     */
    public function testDelete() {
        
        $result = $this->Queue->read(null, 1);
        debug($result);
        
        $result = $this->Queue->delete($result['ReceiptHandle']);
        debug($result);
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
    
}