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
    
    public $config = array();
    
    public function startTest() {
        $config = new DATABASE_CONFIG();
        if (isset($config->sqs_test)) {
            $this->config = $config->sqs_test;
        }
        ConnectionManager::create('sqs_test', $this->config);
        $this->Queue =& ClassRegistry::init('Queue');
    }
    
    public function endTest() {
        unset($this->Queue);
        ClassRegistry::flush();
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryCreateQueue.html
     *
     * @return void
     */
    public function testCreateQueue() {
        
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryListQueues.html
     *
     * @return void
     */
    public function testListQueues() {
        
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryDeleteQueue.html
     *
     * @return void
     */
    public function testDeleteQueue() {
        
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryGetQueueAttributes.html
     *
     * @return void
     */
    public function testGetQueueAttributes() {
        
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QuerySetQueueAttributes.html
     *
     * @return void
     */
    public function testSetQueueAttributes() {
        
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QuerySendMessage.html
     *
     * @return void
     */
    public function testSendMessage() {
        
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryReceiveMessage.html
     *
     * @return void
     */
    public function testReceiveMessage() {
        
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryDeleteMessage.html
     *
     * @return void
     */
    public function testDeleteMessage() {
        
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryAddPermission.html
     *
     * @return void
     */
    public function testAddPermission() {
        
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryRemovePermission.html
     *
     * @return void
     */
    public function testRemovePermission() {
        
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryChangeMessageVisibility.html
     *
     * @return void
     */
    public function testChangeMessageVisibility() {
        
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryGetQueueUrl.html
     *
     * @return void
     */
    public function testGetQueueUrl() {
        
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QuerySendMessageBatch.html
     *
     * @return void
     */
    public function testSendMessageBatch() {
        
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryDeleteMessageBatch.html
     *
     * @return void
     */
    public function testDeleteMessageBatch() {
        
    }
    
    /**
     * http://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/Query_QueryChangeMessageVisibilityBatch.html
     *
     * @return void
     */
    public function testChangeMessageVisibilityBatch() {
        
    }
    
}