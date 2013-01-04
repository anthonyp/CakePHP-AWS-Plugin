<?php
/**
 * DynamoDB DataSource Test File
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

/**
* Post Model for the test
*
* @package app
* @subpackage app.model.post
*/
App::import('Model', 'Post');

class Post extends AppModel {
    
    public $name = 'Post';
    
    public $useDbConfig = 'dynamodb_test';
    
    public $displayField = 'title';
    
    public $recursive = -1;
    
    public $validate = array(
        'title' => array(
            'notempty' => array(
                'rule' => array('notempty'),
                //'message' => 'Your custom message here',
                //'allowEmpty' => false,
                //'required' => false,
                //'last' => false, // Stop validation after this rule
                //'on' => 'create', // Limit validation to 'create' or 'update' operations
            ),
        ),
    );
    
    public $schema = array(
        'id' => array(
            'type' => 'string',
            'null' => true,
            'key' => 'primary',
            'length' => 32,
        ),
        'rev' => array(
            'type' => 'string',
            'null' => true,
            'length' => 34,
        ),
        'title' => array(
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ),
        'description' => array(
            'type' => 'text',
            'null' => true,
        )
    );
    
}

/**
 * DynamoDBTestCase
 *
 * @package       datasources
 * @subpackage    datasources.tests.cases.models.datasources
 */
class DynamoDBTestCase extends CakeTestCase {
    
    /**
     * Skip test Amazon DynamoDB API
     *
     * Set to false tests completes faster
     *
     * @var boolean
     */
    public $skipTestAmazonDynamodbAPI = true;
    
    /**
     * Skip test create_table
     *
     * This takes a lot of time
     *
     * @var boolean
     */
    public $skipTestCreateTable = true;
    
    /**
     * Skip test delete_table
     *
     * This takes a lot of time
     *
     * @var boolean
     */
    public $skipTestDeleteTable = true;
    
    /**
     * Skip test update_table
     *
     * This takes a lot of time
     *
     * @var boolean
     */
    public $skipTestUpdateTable = true;
    
    /**
     * Create test tables
     *
     * This takes a lot of time
     *
     * @var string
     */
    public $create_test_tables = false;
    
    /**
     * Create test data
     *
     * This takes a lot of time
     *
     * @var string
     */
    public $create_test_data = false;
    
    /**
     * DynamoDB Datasource object
     *
     * @var object
     */
    public $DynamoDB = null;
    
    /**
     * One day ago
     *
     * @var string
     */
    public $one_day_ago = null;
    
    /**
     * Seven days ago
     *
     * @var string
     */
    public $seven_days_ago = null;
    
    /**
     * Fourteen days ago
     *
     * @var string
     */
    public $fourteen_days_ago = null;
    
    /**
     * Twenty one days ago
     *
     * @var string
     */
    public $twenty_one_days_ago = null;
     
    /**
     * Configuration
     *
     * @var array
     */
    protected $config = array(
        'datasource' => '',
        'database' => null,
        'host' => '',
        'login' => '',
        'password' => '',
        'default_cache_config' => ''
    );
    
    /**
     * Created test tables
     *
     * @var boolean
     */
    public $created_test_tables = false;
    
    /**
     * Created test data
     *
     * @var boolean
     */
    public $created_test_data = false;
    
    /**
     * Start Test
     *
     * @return void
     */
    public function startTest() {
        
        // set variables for dates
        $this->one_day_ago = date('Y-m-d H:i:s', strtotime("-1 days"));
        $this->seven_days_ago = date('Y-m-d H:i:s', strtotime("-7 days"));
        $this->fourteen_days_ago = date('Y-m-d H:i:s', strtotime("-14 days"));
        $this->twenty_one_days_ago = date('Y-m-d H:i:s', strtotime("-21 days"));
        
        $config = new DATABASE_CONFIG();
        
        if (isset($config->dynamodb_test)) {
            $this->config = $config->dynamodb_test;
        }
        
        if (empty($this->Post)) {
            ConnectionManager::create('dynamodb_test', $this->config);
            $this->Post = ClassRegistry::init('Post');
        }
        
        if (empty($this->DynamoDB)) {
            $this->DynamoDB = new DynamoDBSource($this->config);
            $this->DynamoDB =& ConnectionManager::getDataSource($this->Post->useDbConfig);
        }
        
        if (!$this->created_test_tables && $this->create_test_tables) {
            $this->assertTrue($this->_removeTestTables());
            $this->assertTrue($this->_createTestTables());
            $this->created_test_tables = true;
        }
        
        if (!$this->created_test_data && $this->create_test_data) {
            $this->assertTrue($this->_createTestData());
            $this->created_test_data = true;
        }
        
    }
    
    /**
     * Test connection
     *
     * @return void
     */
    public function testConnection() {
        
        // test connected
        $this->assertTrue($this->DynamoDB->connected);
        
        // test close 
        $this->assertTrue($this->DynamoDB->close());
        
        // test connect
        $this->assertTrue($this->DynamoDB->connect());
        $this->assertTrue($this->DynamoDB->connect());
        
        // test disconnect
        $disconnect = $this->DynamoDB->disconnect();
        $this->assertTrue($disconnect);
        
        // test connect passing the region
        $this->assertTrue($this->DynamoDB->connect($this->DynamoDB->_config['host']));
        
    }
    
    /**
     * Test calculate
     *
     */
    public function testCalculate() {
        
        $this->assertTrue($this->DynamoDB->calculate());
        
     }
    
    /**
     * Test describe
     *
     */
    public function testDescribe() {
        
        $this->DynamoDB->connected = false;
        $this->assertFalse($this->DynamoDB->describe($this->Post));
        $this->DynamoDB->connected = true;
        
    }
    
    /**
     * Test create
     *
     */
    public function testCreate() {
        
        $this->DynamoDB->connected = false;
        $this->assertFalse($this->DynamoDB->create($this->Post, array(), array()));
        $this->DynamoDB->connected = true;
        
        $postId = uniqid();
        $postTitle = 'Post #'. $postId;
        $data = array(
            'Post' => array(
                'id' => $postId,
                'rev' => rand(1,9),
                'title' => $postTitle,
                'description' => 'Description for '. $postTitle
            )
        );
        $this->assertTrue($this->Post->save($data));
        
        $result = $this->Post->read(null, $postId);
        $this->assertEqual(
            $result['Post']['title'],
            $postTitle
        );
        
    }
    
    /**
     * Test read
     *
     */
    public function testRead() {
         
        $this->DynamoDB->connected = false;
        $this->assertFalse(
            $this->DynamoDB->read($this->Post, array())
        );
        $this->DynamoDB->connected = true;
        
        $postId = uniqid();
        $postTitle = 'Post #'. $postId;
        $data = array(
            'Post' => array(
                'id' => $postId,
                'rev' => rand(1,9),
                'title' => $postTitle,
                'description' => 'Description for '. $postTitle
            )
        );
        $this->assertTrue($this->Post->save($data));
        
        $data = array(
            'Post' => array(
                'id' => $postId,
                'title' => $postTitle .' (updated)',
            )
        );
        $this->assertTrue($this->Post->save($data));
        
        $result = $this->Post->read(null, $postId);
        $this->assertEqual(
            $result['Post']['title'],
            $postTitle .' (updated)'
        );
        
        $this->Post->id = $postId;
        $result = $this->DynamoDB->read($this->Post);
        $this->assertNotNull($result);
        
        $query = array('conditions'=>array('id'=>$postId));
        $result = $this->DynamoDB->read($this->Post, $query);
        $this->assertNotNull($result);
        
    }
    
    /**
     * Test update
     *
     */
    public function testUpdate() {
        
        $this->DynamoDB->connected = false;
        $this->assertFalse(
            $this->DynamoDB->update($this->Post, array(), array())
        );
        $this->DynamoDB->connected = true;
        
        $postId = uniqid();
        $postTitle = 'Post #'. $postId;
        $data = array(
            'Post' => array(
                'id' => $postId,
                'rev' => rand(1,9),
                'title' => $postTitle,
                'description' => 'Description for '. $postTitle
            )
        );
        $this->assertTrue($this->Post->save($data));
        
        $data = array(
            'Post' => array(
                'id' => $postId,
                'title' => $postTitle .' (updated)',
            )
        );
        $this->assertTrue($this->Post->save($data));
        
        $result = $this->Post->read(null, $postId);
        $this->assertEqual(
            $result['Post']['title'],
            $postTitle .' (updated)'
        );
        
    }
    
    /**
     * Test delete
     *
     */
    public function testDelete() {
        
        $this->DynamoDB->connected = false;
        $this->assertFalse($this->DynamoDB->delete($this->Post, array()));
        $this->DynamoDB->connected = true;
        
        $postId = uniqid();
        $postTitle = 'Post #'. rand();
        $data = array(
            'Post' => array(
                'id' => $postId,
                'rev' => rand(1,9),
                'title' => $postTitle,
                'description' => 'Description for '. $postTitle
            )
        );
        $this->assertTrue($this->Post->save($data));
        
        $this->assertTrue($this->Post->delete($postId));
        
    }
    
    // public function testFindFirst() {
    //     
    //     $result = $this->Post->find('first', array());
    //     
    // }
    // 
    // public function testFindCount() {
    //     
    //     $result = $this->Post->find('count', array());
    //     
    // }
    // 
    // public function testFindAll() {
    //     
    //     $result = $this->Post->find('all', array());
    //     
    // }
    // 
    // public function testFindList() {
    //     
    //     $result = $this->Post->find('list', array());
    //     
    // }
    // 
    // public function testFindAllBy() {
    //     
    //     $result = $this->Post->findAllByTitle('first');
    //     
    // }
    // 
    // public function testFindBy() {
    //     
    //     $result = $this->Post->findByTitle('first');
    //     
    // }
    // 
    // public function testFindFields() {
    //     
    // }
    // 
    // public function testFindLimit() {
    //     
    // }
    // 
    // public function testFindOrder() {
    //     
    // }
    // 
    // public function testFindConditions() {
    //     
    // }
    // 
    // public function testFindRecursive() {
    //     
    // }
    // 
    // public function testField() {
    //     
    // }
    
    /**
     * CakePHP ATOM operations
     *
     */
    // public function testSaveAll() {
    //     
    // }
    // 
    // public function testUpdateAll() {
    //     
    // }
    // 
    // public function testDeleteAll() {
    //     
    // }
    // 
    // public function testCounterCache() {
    //     
    // }
    
    /**
     * Test _getConditions
     *
     */
    public function testGetConditions() {
        
        $conditions = array();
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, array());
        
        $conditions = array('title ='=>'The super story');
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_EQUAL,
                'value' => 'The super story'
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.title ='=>'The super story');
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_EQUAL,
                'value' => 'The super story'
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.title !='=>'The super story');
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_NOT_EQUAL,
                'value' => 'The super story'
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.title <>'=>'The super story');
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_NOT_EQUAL,
                'value' => 'The super story'
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.rev >'=>1);
        $expected = array(
            'rev' => array(
                'operator' => AmazonDynamoDB::CONDITION_GREATER_THAN,
                'value' => 1
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.rev >='=>1);
        $expected = array(
            'rev' => array(
                'operator' => AmazonDynamoDB::CONDITION_GREATER_THAN_OR_EQUAL,
                'value' => 1
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.rev <'=>1);
        $expected = array(
            'rev' => array(
                'operator' => AmazonDynamoDB::CONDITION_LESS_THAN,
                'value' => 1
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.rev <='=>1);
        $expected = array(
            'rev' => array(
                'operator' => AmazonDynamoDB::CONDITION_LESS_THAN_OR_EQUAL,
                'value' => 1
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.title NULL'=>1);
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_NULL,
                'value' => 1
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.title NOT NULL'=>1);
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_NOT_NULL,
                'value' => 1
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.tags CONTAINS'=>array('ONE', 'TWO', 'THREE'));
        $expected = array(
            'tags' => array(
                'operator' => AmazonDynamoDB::CONDITION_CONTAINS,
                'value' => array('ONE', 'TWO', 'THREE')
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.tags DOESNT CONTAINS'=>array('ONE', 'TWO', 'THREE'));
        $expected = array(
            'tags' => array(
                'operator' => AmazonDynamoDB::CONDITION_DOESNT_CONTAIN,
                'value' => array('ONE', 'TWO', 'THREE')
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.tags IN'=>array('ONE', 'TWO', 'THREE'));
        $expected = array(
            'tags' => array(
                'operator' => AmazonDynamoDB::CONDITION_IN,
                'value' => array('ONE', 'TWO', 'THREE')
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.rev BETWEEN'=>array(1, 5));
        $expected = array(
            'rev' => array(
                'operator' => AmazonDynamoDB::CONDITION_BETWEEN,
                'value' => array(1, 5)
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.title BEGINS WITH'=>'The super');
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_BEGINS_WITH,
                'value' => 'The super'
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Post, $conditions);
        $this->assertEqual($result, $expected);
    }
    
    /**
     * Find conditions
     *
     */
    public function testFindEqual() {
        
    }
    
    public function testFindNotEqual() {
        
    }
    
    public function testFindLessThan() {
        
    }
    
    public function testFindLessThanOrEqualTo() {
        
    }
    
    public function testFindGreaterThan() {
        
    }
    
    public function testFindNull() {
        
    }
    
    public function testFindNotNull() {
        
    }
    
    public function testFindContain() {
        
    }
    
    public function testFindDoesntContain() {
        
    }
    
    public function testFindIn() {
        
    }
    
    public function testFindBetween() {
        
    }
    
    public function testFindBeginsWith() {
        
    }
    
    /**
     * Save with optional return option
     *
     */
    // public function testSaveWithActionAdd() {
    //     
    // }
    // 
    // public function testSaveWithActionDelete() {
    //     
    // }
    // 
    // public function testSaveWithActionPut() {
    //     
    // }
    
    // public function testReadReturnNone() {
    //     
    // }
    // 
    // public function testReadReturnAllOld() {
    //     
    // }
    // 
    // public function testReadReturnAllNew() {
    //     
    // }
    // 
    // public function testReadReturnUpdatedOld() {
    //     
    // }
    // 
    // public function testReadReturnUpdatedNew() {
    //     
    // }
    
    /**
     * Test query
     *
     */
    public function testQuery() {
        
        $this->DynamoDB->connected = false;
        $this->assertFalse($this->DynamoDB->query(array()));
        $this->DynamoDB->connected = true;
        
        $tableName = 'testReply';
        $options = array(
            'TableName' => $tableName, 
            'HashKeyValue' => array(AmazonDynamoDB::TYPE_STRING => 'Amazon DynamoDB#DynamoDB Thread 2'),
        );
        $response = $this->DynamoDB->query($options);
        $this->assertEqual($response->status, 200);
        $result = count($response->body->Items);
        $this->assertTrue($result>0);
        
        $options = array(
            'TableName' => 'testProductCatalog'
        );
        $response = $this->DynamoDB->query('describe_table', array($options));
        $this->assertEqual($response->status, 200);
        
    }
    
    /**
     * Test query 2
     *
     */
    public function testQuery2() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $options = array(
            'TableName' => 'testReply',
            'HashKeyValue' => array(
                AmazonDynamoDB::TYPE_STRING => 'Amazon DynamoDB#DynamoDB Thread 2'
            ),
            'AttributesToGet' => array( 'Subject', 'ReplyDateTime', 'PostedBy' ),
            'ConsistentRead' => true,
            'RangeKeyCondition' => array(
                'ComparisonOperator' => AmazonDynamoDB::CONDITION_LESS_THAN_OR_EQUAL,
                'AttributeValueList' => array(
                    array(AmazonDynamoDB::TYPE_STRING => $this->seven_days_ago)
                )
            )
        );
        $response = $this->DynamoDB->query($options);
        $this->assertEqual($response->status, 200);
        $result = count($response->body->Items);
        $this->assertTrue($result>0);
        
    }
    
    /**
     * Test query 3
     *
     */
    public function testQuery3() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $options = array(
            'TableName' => 'testReply',
            'Limit' => 2,
            'HashKeyValue' => array(
                AmazonDynamoDB::TYPE_STRING => 'Amazon DynamoDB#DynamoDB Thread 2',
            ),
            'RangeKeyCondition' => array(
                'ComparisonOperator' => AmazonDynamoDB::CONDITION_GREATER_THAN_OR_EQUAL,
                'AttributeValueList' => array(
                    array( AmazonDynamoDB::TYPE_STRING => $this->fourteen_days_ago )
                )
            )
        );
        $response = $this->DynamoDB->query($options);
        $this->assertEqual($response->status, 200);
        
        // Do we have more data? Fetch it!
        if (isset($response->body->LastEvaluatedKey)) {
            $options = array(
                'TableName' => 'testReply',
                'Limit' => 2,
                'ExclusiveStartKey' => $response->body->LastEvaluatedKey->to_array()->getArrayCopy(),
                'HashKeyValue' => array( AmazonDynamoDB::TYPE_STRING => 'Amazon DynamoDB#DynamoDB Thread 2' ),
                'RangeKeyCondition' => array(
                    'ComparisonOperator' => AmazonDynamoDB::CONDITION_GREATER_THAN_OR_EQUAL,
                    'AttributeValueList' => array(
                        array( AmazonDynamoDB::TYPE_STRING => $this->fourteen_days_ago )
                    )
                )
            );
            $response2 = $this->DynamoDB->query($options);
        }
        $this->assertEqual($response2->status, 200);
        $this->assertTrue(isset($response2->body->Items));
        
    }
    
    /**
     * Test scan
     *
     */
    public function testScan() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $options = array(
            'TableName' => 'testProductCatalog'
        );
        $response = $this->DynamoDB->query('scan', array($options));
        $this->assertEqual($response->status, 200);
        
        $result = count($response->body->Items);
        $this->assertTrue($result>0);
        
    }
    
    /**
     * Test scan 2
     *
     */
    public function testScan2() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $options = array(
            'TableName' => 'testProductCatalog'
        );
        $response = $this->DynamoDB->query('scan', array($options));
        $this->assertEqual($response->status, 200);
        
        $result = count($response->body->Items);
        $this->assertTrue($result>0);
        foreach ($response->body->Items as $item) {
            $this->assertNotNull((string) $item->Id->{AmazonDynamoDB::TYPE_NUMBER});
            $this->assertNotNull((string) $item->Title->{AmazonDynamoDB::TYPE_STRING});
        }
        
    }
    
    /**
     * Test scan 3
     *
     */
    public function testScan3() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $options = array(
            'TableName' => 'testProductCatalog', 
            'AttributesToGet' => array('Id'),
            'ScanFilter' => array(
                'Price' => array(
                    'ComparisonOperator' => AmazonDynamoDB::CONDITION_LESS_THAN,
                    'AttributeValueList' => array(
                        array( AmazonDynamoDB::TYPE_NUMBER => '0' )
                    )
                ),
            )
        );
        $response = $this->DynamoDB->query('scan', array($options));
        $this->assertEqual($response->status, 200);
        
        $result = count($response->body->Items);
        $this->assertTrue($result>0);
        
    }
    
    /**
     * Test scan 4
     *
     */
    public function testScan4() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $options = array(
            'TableName' => 'testProductCatalog',
            'Limit' => 2
        );
        $response = $this->DynamoDB->query('scan', array($options));
        $this->assertEqual($response->status, 200);
        
        // Do we have more data? Fetch it!
        if (isset($response->body->LastEvaluatedKey)) {
            $options = array(
                'TableName' => 'testProductCatalog',
                'Limit' => 2,
                'ExclusiveStartKey' => $response->body->LastEvaluatedKey->to_array()->getArrayCopy()
            );
            $response2 = $this->DynamoDB->query('scan', array($options));
            $this->assertEqual($response2->status, 200);
            
            $result = count($response2->body->Items);
            $this->assertTrue($result>0);
        }
        
    }
    
    /**
     * Test batch_get_item
     *
     */
    public function testBatchGetItem() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $options = array(
            'RequestItems' => array(
                'testForum' => array(
                    'Keys' => array(
                        array( // Key #2
                            'HashKeyElement'  => array(
                                AmazonDynamoDB::TYPE_STRING => 'Amazon DynamoDB'
                            )
                        )
                    )
                ),
                'testReply' => array(
                    'Keys' => array(
                        array( // Key #1
                            'HashKeyElement'  => array(
                                AmazonDynamoDB::TYPE_STRING => 'Amazon DynamoDB#DynamoDB Thread 2'
                            ),
                            'RangeKeyElement' => array(
                                AmazonDynamoDB::TYPE_STRING => $this->seven_days_ago
                            ),
                        ),
                        array( // Key #2
                            'HashKeyElement'  => array(
                                AmazonDynamoDB::TYPE_STRING => 'Amazon DynamoDB#DynamoDB Thread 2'
                            ),
                            'RangeKeyElement' => array(
                                AmazonDynamoDB::TYPE_STRING => $this->twenty_one_days_ago
                            ),
                        ),
                    )
                )
            )
        );
        $response = $this->DynamoDB->query('batch_get_item', array($options));
        $this->assertEqual($response->status, 200);
        $this->assertNotNull($response->body->Responses->testReply);
        $this->assertNotNull($response->body->Responses->testForum);
        
    }
    
    /**
     * Test batch_get_item with optional parameters
     *
     */
    public function testBatchGetItemWithOptionalParameters() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $options = array(
            'RequestItems' => array(
                'testForum' => array(
                'Keys' => array(
                    array( // Key #1
                        'HashKeyElement' => array(
                            AmazonDynamoDB::TYPE_STRING => 'Amazon S3'
                        )
                    ),
                    array( // Key #2
                        'HashKeyElement' => array(
                            AmazonDynamoDB::TYPE_STRING => 'Amazon DynamoDB'
                        )
                    )
                ),
                'AttributesToGet' => array('Threads')
                ),
            )
        );
        $response = $this->DynamoDB->query('batch_get_item', array($options));
        $this->assertEqual($response->status, 200);
        
        $result = count($response->body->Responses->testForum->Items);
        $this->assertTrue($result>0);
        
    }
    
    /**
     * Test batch_write_item
     *
     */
    public function testBatchWriteItem() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $table1 = 'testForum';
        $table2 = 'testThread';
        
        $options = array(
              'RequestItems' => array(
                  $table1 => array(
                      array(
                          'PutRequest' => array(
                              'Item' => $this->DynamoDB->connection->attributes(array(
                                  'Name' => 'S3 Forum',
                                  'Threads' => 0
                              ))
                          )
                      )
                  ),          
                   $table2 => array(
                      array(
                          'PutRequest' => array(
                              'Item' => $this->DynamoDB->connection->attributes(array(
                                  'ForumName' => 'S3 Forum',
                                  'Subject' => 'My sample question',
                                  'Message'=> 'Message Text.',
                                  'KeywordTags'=> array('S3', 'Bucket')
                              ))
                          )
                      ),
                      array(
                          'DeleteRequest' => array(
                              'Key' => $this->DynamoDB->connection->attributes(array(
                                  'HashKeyElement' =>'Some hash value',
                                  'RangeKeyElement' => 'Some range key'
                              ))
                          )
                      )
                   )
              )
        );
        $response = $this->DynamoDB->query('batch_write_item', array($options));
        $this->assertEqual($response->status, 200);
        $this->assertNotNull($response->body->Responses->$table1->ConsumedCapacityUnits);
        $this->assertNotNull($response->body->Responses->$table2->ConsumedCapacityUnits);
        
    }
    
    /**
     * Test delete_item
     *
     */
    public function testDeleteItem() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $tableName = 'testProductCatalog';
        $itemKey = (string)rand();
        $options = array(
            'TableName' => $tableName,
            'Item' => array(
                'Id' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                ),
                'Title' => array(
                    AmazonDynamoDB::TYPE_STRING => 'Book Title'
                ),
                'ISBN' => array(
                    AmazonDynamoDB::TYPE_STRING => '111-1111111111'
                ),
                'Price' => array(
                    AmazonDynamoDB::TYPE_NUMBER => '25'
                ),
                'Authors' => array(
                    AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Author1', 'Author2')
                )
            )
        );
        $response = $this->DynamoDB->query('put_item', array($options));
        $this->assertEqual($response->status, 200);
        
        $options = array(
            'TableName' => $tableName,
            'Key' => array(
                'HashKeyElement' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                )
            )
        );
        $response = $this->DynamoDB->query('delete_item', array($options));
        $this->assertEqual($response->status, 200);
        $this->assertNotNull($response->body->ConsumedCapacityUnits);
        
    }
    
    /**
     * Test delete_item with optional paramenters
     *
     */
    public function testDeleteItemWithOptionalParameters() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $tableName = 'testProductCatalog';
        $itemKey = (string)rand();
        $options = array(
            'TableName' => $tableName,
            'Item' => array(
                'Id' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                ),
                'Title' => array(
                    AmazonDynamoDB::TYPE_STRING => 'Book Title'
                ),
                'ISBN' => array(
                    AmazonDynamoDB::TYPE_STRING => '111-1111111111'
                ),
                'Price' => array(
                    AmazonDynamoDB::TYPE_NUMBER => '25'
                ),
                'Authors' => array(
                    AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Author1', 'Author2')
                ),
                'InPublication' => array(
                    AmazonDynamoDB::TYPE_NUMBER => '0'
                )
            )
        );
        $response = $this->DynamoDB->query('put_item', array($options));
        $this->assertEqual($response->status, 200);
        
        $options = array(
            'TableName' => $tableName,
            'Key' => array(
                'HashKeyElement' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                )
            ),
            'Expected' => array(
                'InPublication' => array(
                    'Value' => array(
                        AmazonDynamoDB::TYPE_NUMBER => '0'
                    )
                )
            ),
            'ReturnValues' => AmazonDynamoDB::RETURN_ALL_OLD
        );
        $response = $this->DynamoDB->query('delete_item', array($options));
        $this->assertEqual($response->status, 200);
        $this->assertEqual(
            $response->body->Attributes->Id->{AmazonDynamoDB::TYPE_NUMBER},
            $itemKey
        );
        
    }
    
    /**
     * Test get_item
     *
     */
    public function testGetItem() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $tableName = 'testProductCatalog';
        $itemKey = (string)rand();
        $options = array(
            'TableName' => $tableName,
            'Item' => array(
                'Id' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                ),
                'Title' => array(
                    AmazonDynamoDB::TYPE_STRING => 'Book Title'
                ),
                'ISBN' => array(
                    AmazonDynamoDB::TYPE_STRING => '111-1111111111'
                ),
                'Price' => array(
                    AmazonDynamoDB::TYPE_NUMBER => '25'
                ),
                'Authors' => array(
                    AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Author1', 'Author2')
                ),
                'InPublication' => array(
                    AmazonDynamoDB::TYPE_NUMBER => '0'
                )
            )
        );
        $response = $this->DynamoDB->query('put_item', array($options));
        $this->assertEqual($response->status, 200);
        
        $options = array(
            'TableName' => $tableName,
            'Key' => array(
                'HashKeyElement' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                ),
                'ConsistentRead' => 'true',
                'AttributesToGet' => array('Id', 'Authors')
            )
        );
        $response = $this->DynamoDB->query('get_item', array($options));
        $this->assertEqual($response->status, 200);
        $this->assertEqual(
            $response->body->Item->Id->{AmazonDynamoDB::TYPE_NUMBER},
            $itemKey
        );
        
    }
    
    /**
     * Test put_item
     *
     */
    public function testPutItem() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $tableName = 'testProductCatalog';
        $itemKey = (string)rand();
        $options = array(
            'TableName' => $tableName,
            'Item' => array(
                'Id' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                ),
                'Title' => array(
                    AmazonDynamoDB::TYPE_STRING => 'Book Title'
                ),
                'ISBN' => array(
                    AmazonDynamoDB::TYPE_STRING => '111-1111111111'
                ),
                'Price' => array(
                    AmazonDynamoDB::TYPE_NUMBER => '25'
                ),
                'Authors' => array(
                    AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Author1', 'Author2')
                ),
                'InPublication' => array(
                    AmazonDynamoDB::TYPE_NUMBER => '0'
                )
            )
        );
        $response = $this->DynamoDB->query('put_item', array($options));
        $this->assertEqual($response->status, 200);
        
        $options = array(
            'TableName' => $tableName,
            'Key' => array(
                'HashKeyElement' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                ),
                'ConsistentRead' => 'true',
                'AttributesToGet' => array('Id', 'Authors')
            )
        );
        $response = $this->DynamoDB->query('get_item', array($options));
        $this->assertEqual($response->status, 200);
        $this->assertEqual(
            $response->body->Item->Id->{AmazonDynamoDB::TYPE_NUMBER},
            $itemKey
        );
        
    }
    
    /**
     * Test put_item with optional parameters
     *
     */
    public function testPutItemWithOptionalParameters() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $tableName = 'testProductCatalog';
        $itemKey = (string)rand();
        $itemISBN = (string)'111-'.rand();
        $options = array(
            'TableName' => $tableName,
            'Item' => array(
                'Id' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                ),
                'Title' => array(
                    AmazonDynamoDB::TYPE_STRING => 'Book Title'
                ),
                'ISBN' => array(
                    AmazonDynamoDB::TYPE_STRING => $itemISBN
                ),
                'Price' => array(
                    AmazonDynamoDB::TYPE_NUMBER => '25'
                ),
                'Authors' => array(
                    AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Author1', 'Author2')
                ),
                'InPublication' => array(
                    AmazonDynamoDB::TYPE_NUMBER => '0'
                )
            )
        );
        $response = $this->DynamoDB->query('put_item', array($options));
        $this->assertEqual($response->status, 200);
        
        $newItemISBN = (string)'111-'.rand();
        $options = array(
            'TableName' => $tableName,
            'Item' => array(
                'Id' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                ),
                'Title' => array(
                    AmazonDynamoDB::TYPE_STRING => 'Book Title'
                ),
                'ISBN' => array(
                    AmazonDynamoDB::TYPE_STRING => $newItemISBN
                ),
                'Price' => array(
                    AmazonDynamoDB::TYPE_NUMBER => '25'
                ),
                'Authors' => array(
                    AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Author1', 'Author2')
                ),
                'InPublication' => array(
                    AmazonDynamoDB::TYPE_NUMBER => '0'
                )
            ),
            'Expected' => array(
                'ISBN' => array(
                    'Value' => array(
                        AmazonDynamoDB::TYPE_STRING => $itemISBN
                    )
                )
            ),
            'ReturnValues' => AmazonDynamoDB::RETURN_ALL_OLD
        );
        $response = $this->DynamoDB->query('put_item', array($options));
        $this->assertEqual($response->status, 200);
        $this->assertEqual(
            $response->body->Attributes->ISBN->{AmazonDynamoDB::TYPE_STRING},
            $itemISBN
        );
        
    }
    
    /**
     * Test update_item
     *
     */
    public function testUpdateItem() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $tableName = 'testProductCatalog';
        $itemKey = (string)rand();
        $itemPrice = '25';
        $options = array(
            'TableName' => $tableName,
            'Item' => array(
                'Id' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                ),
                'Title' => array(
                    AmazonDynamoDB::TYPE_STRING => 'Book Title'
                ),
                'ISBN' => array(
                    AmazonDynamoDB::TYPE_STRING => '111-1111111111'
                ),
                'Price' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemPrice
                ),
                'Authors' => array(
                    AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Author1', 'Author2')
                ),
                'InPublication' => array(
                    AmazonDynamoDB::TYPE_NUMBER => '0'
                )
            )
        );
        $response = $this->DynamoDB->query('put_item', array($options));
        $this->assertEqual($response->status, 200);
        
        $authors = array('Author YY', 'Author ZZ');
        $options = array(
            'TableName' => $tableName,
            'Key' => array(
                'HashKeyElement' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                )
            ),
            'AttributeUpdates' => array(
                'Authors' => array(
                    'Action' => AmazonDynamoDB::ACTION_PUT,
                    'Value' => array(
                        AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => $authors
                    )
                ),
                // Reduce the price. To add or subtract a value,
                // use ADD with a positive or negative number.
                'Price' => array(
                    'Action' => AmazonDynamoDB::ACTION_ADD,
                    'Value' => array(
                        AmazonDynamoDB::TYPE_NUMBER => '-1'
                    )
                ),
                'ISBN' => array(
                    'Action' => AmazonDynamoDB::ACTION_DELETE
                )
            )
        );
        $response = $this->DynamoDB->query('update_item', array($options));
        $this->assertEqual($response->status, 200);
        $this->assertNotNull($response->body->ConsumedCapacityUnits);
        
        $options = array(
            'TableName' => $tableName,
            'Key' => array(
                'HashKeyElement' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                ),
                'ConsistentRead' => 'true',
                'AttributesToGet' => array('Id', 'Authors')
            )
        );
        $response = $this->DynamoDB->query('get_item', array($options));
        $this->assertEqual($response->status, 200);
        
        // set Authors
        $authors2 = array_shift($response->body->Item->Authors->to_array()->getArrayCopy());
        $this->assertEqual($authors2, $authors);
        
        // descrease -1 Price
        $this->assertEqual(
            (string)$response->body->Item->Price->{AmazonDynamoDB::TYPE_NUMBER},
            (string)$itemPrice - 1
        );
        
        // removed ISBN
        $this->assertFalse(isset($response->body->Item->ISBN));
        
    }
    
    /**
     * Test update_item with optional parameters
     *
     */
    public function testUpdateItemWithOptionalParameters() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $tableName = 'testProductCatalog';
        $itemKey = (string)rand();
        $itemPrice = '25';
        $options = array(
            'TableName' => $tableName,
            'Item' => array(
                'Id' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                ),
                'Title' => array(
                    AmazonDynamoDB::TYPE_STRING => 'Book Title'
                ),
                'ISBN' => array(
                    AmazonDynamoDB::TYPE_STRING => '111-1111111111'
                ),
                'Price' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemPrice
                ),
                'Authors' => array(
                    AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Author1', 'Author2')
                ),
                'InPublication' => array(
                    AmazonDynamoDB::TYPE_NUMBER => '0'
                )
            )
        );
        $response = $this->DynamoDB->query('put_item', array($options));
        $this->assertEqual($response->status, 200);
        
        $newItemPrice = '30';
        $options = array(
            'TableName' => $tableName,
            'Key' => array(
                'HashKeyElement' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemKey
                )
            ),
            'Expected' => array(
                'Price' => array( 'Value' => array(
                    AmazonDynamoDB::TYPE_NUMBER => $itemPrice
                    )
                )
            ),
            'AttributeUpdates' => array(
                'Price' => array(
                    'Action' => AmazonDynamoDB::ACTION_PUT,
                    'Value' => array(
                        AmazonDynamoDB::TYPE_STRING => $newItemPrice
                    )
                )
            ),
            'ReturnValues' => AmazonDynamoDB::RETURN_ALL_NEW
        );
        $response = $this->DynamoDB->query('update_item', array($options));
        $this->assertEqual($response->status, 200);
        
        $this->assertEqual(
            $response->body->Attributes->Id->{AmazonDynamoDB::TYPE_NUMBER},
            $itemKey
        );
        
        $this->assertEqual(
            $response->body->Attributes->Price->{AmazonDynamoDB::TYPE_STRING},
            $newItemPrice
        );
        
    }
    
    /**
     * Test create_table
     *
     */
    public function testCreateTable() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $skip = $this->skipIf(
            $this->skipTestCreateTable,
            'Test for create_table is disabled'
        );
        if ($skip) {
            return;
        }
        
        $tableName = uniqid();
        $options = array(
            'TableName' => $tableName,
            'KeySchema' => array(
                'HashKeyElement' => array(
                    'AttributeName' => 'Id',
                    'AttributeType' => AmazonDynamoDB::TYPE_NUMBER
                )
            ),
            'ProvisionedThroughput' => array(
                'ReadCapacityUnits' => 10,
                'WriteCapacityUnits' => 5
            )
        );
        $response = $this->DynamoDB->query('create_table', array($options));
        $this->assertEqual($response->status, 200);
        do {
            sleep(1);
            $options = array('TableName' => $tableName);
            $response = $this->DynamoDB->query('describe_table', array($options));
        }
        while ((string)$response->body->Table->TableStatus !== 'ACTIVE');
        
        $response = $this->DynamoDB->query('list_tables');
        $result = $response->body->TableNames()->map_string();
        
        $this->assertTrue(in_array($tableName, $result));
        
    }
    
    /**
     * Test delete_table
     *
     */
    public function testDeleteTable() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $skip = $this->skipIf(
            $this->skipTestDeleteTable,
            'Test for delete_table is disabled'
        );
        if ($skip) {
            return;
        }
        
        $tableName = uniqid();
        $options = array(
            'TableName' => $tableName,
            'KeySchema' => array(
                'HashKeyElement' => array(
                    'AttributeName' => 'Id',
                    'AttributeType' => AmazonDynamoDB::TYPE_NUMBER
                )
            ),
            'ProvisionedThroughput' => array(
                'ReadCapacityUnits' => 10,
                'WriteCapacityUnits' => 5
            )
        );
        $response = $this->DynamoDB->query('create_table', array($options));
        $this->assertEqual($response->status, 200);
        do {
            sleep(1);
            $options = array('TableName' => $tableName);
            $response = $this->DynamoDB->query('describe_table', array($options));
        }
        while ((string)$response->body->Table->TableStatus !== 'ACTIVE');
        
        $options = array(
            'TableName' => $tableName
        );
        $response = $this->DynamoDB->query('delete_table', array($options));
        do {
            sleep(1);
            $response = $this->DynamoDB->query('describe_table', array($options));
        }
        while ((string)$response->body->Table->TableStatus == 'DELETING');
        
        $response = $this->DynamoDB->query('list_tables');
        $result = $response->body->TableNames()->map_string();
        
        $this->assertFalse(in_array($tableName, $result));
        
    }
    
    /**
     * Test describe_table
     *
     */
    public function testDescribeTable() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $tableName = 'testProductCatalog';
        $options = array(
            'TableName' => $tableName
        );
        $response = $this->DynamoDB->query('describe_table', array($options));
        $this->assertNotNull($response->body->Table->TableStatus);
        
    }
    
    /**
     * Test update_table
     *
     */
    public function testUpdateTable() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $skip = $this->skipIf(
            $this->skipTestUpdateTable,
            'Test for update_table is disabled'
        );
        if ($skip) {
            return;
        }
        
        $tableName = 'testProductCatalog';
        $readCapacityUnits = 2;
        $options = array(
            'TableName' => $tableName,
            'ProvisionedThroughput' => array(
                'ReadCapacityUnits' => $readCapacityUnits,
                'WriteCapacityUnits' => 5 
            )
        );
        do {
            sleep(1);
            $options = array('TableName' => $tableName);
            $response = $this->DynamoDB->query('describe_table', array($options));
        }
        while ((string)$response->body->Table->TableStatus == 'UPDATING');
        
        $this->assertEqual(
            (string)$response->body->Table->ProvisionedThroughput->ReadCapacityUnits,
            10
        );
        
        $readCapacityUnits = -2;
        $options = array(
            'TableName' => $tableName,
            'ProvisionedThroughput' => array(
                'ReadCapacityUnits' => $readCapacityUnits,
                'WriteCapacityUnits' => 5 
            )
        );
        $this->assertEqual($response->status, 200);
        do {
            sleep(1);
            $options = array('TableName' => $tableName);
            $response = $this->DynamoDB->query('describe_table', array($options));
        }
        while ((string)$response->body->Table->TableStatus == 'UPDATING');
        
    }
    
    /**
     * Test list_tables
     *
     */
    public function testListTables() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $response = $this->DynamoDB->query('list_tables');
        $result = is_array($response->body->TableNames()->map_string());
        $this->assertTrue($result);
        
    }
    
    /**
     * Test list_tables 2
     *
     */
    public function testListTables2() {
        
        $skip = $this->skipIf(
            $this->skipTestAmazonDynamodbAPI,
            __FUNCTION__ .', test Amazon DynamoDB API is disabled'
        );
        if ($skip) {
            return;
        }
        
        $tables = array();
        do {
            $response = $this->DynamoDB->query('list_tables', array(array(
                'Limit' => 2, 
                'ExclusiveStartTableName' => isset($response) ? (string) $response->body->LastEvaluatedTableName : null
            )));
            $tables = array_merge($tables, $response->body->TableNames()->map_string());
        }
        while ($response->body->LastEvaluatedTableName);
        
        $this->assertEqual($response->status, 200);
        
    }
    
    /**
     * Test _parseItem
     *
     */
    public function testParseItem() {
        
        $data = new stdClass();
        $this->assertFalse($this->DynamoDB->_parseItem($this->Post, $data));
        
        $response = array(
            'item1' => array('N' => 1),
            'item2' => array('N' => 2),
            'item3' => array('N' => 3)
        );
        $data = new stdClass();
        $data->body->Item = $response;
        $result = $this->DynamoDB->_parseItem($this->Post, $data);
        $expected = array(array($this->Post->alias => array(
            'item1' => 1,
            'item2' => 2,
            'item3' => 3
        )));
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test _parseItems
     *
     */
    public function testParseItems() {
        
        $data = new stdClass();
        $this->assertFalse($this->DynamoDB->_parseItems($this->Post, $data));
        
        $response = array(
            array('item1' => array('N' => 1)),
            array('item2' => array('N' => 2)),
            array('item3' => array('N' => 3))
        );
        $data = new stdClass();
        $data->body->Count = 3;
        $data->body->Items = $response;
        $result = $this->DynamoDB->_parseItems($this->Post, $data);
        $expected = array(
            array($this->Post->alias => array('item1' => 1)),
            array($this->Post->alias => array('item2' => 2)),
            array($this->Post->alias => array('item3' => 3))
        );
        $this->assertEqual($result, $expected);
        
        $response = array(
            'item1' => array('N' => 1)
        );
        $data = new stdClass();
        $data->body->Count = 1;
        $data->body->Items = $response;
        $result = $this->DynamoDB->_parseItems($this->Post, $data);
        $expected = array(
            array($this->Post->alias => array('item1' => 1))
        );
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test _castValue
     *
     */
    public function testCastValue() {
        
        $value = 1;
        $result = $this->DynamoDB->_castValue('S', $value);
        $this->assertTrue(is_string($result));
        
        $value = '1';
        $result = $this->DynamoDB->_castValue('N', $value);
        $this->assertTrue(is_numeric($result));
        
        $value = 1;
        $expected = (binary)$value;
        $result = $this->DynamoDB->_castValue('B', $value);
        $this->assertTrue($result === $expected);
        
        $expected = 1;
        $result = $this->DynamoDB->_castValue('X', $expected);
        $this->assertTrue($result === $expected);
        
    }
    
    /**
     * Test _parseTable
     *
     */
    public function testParseTable() {
        
        $data = new stdClass();
        $this->assertFalse($this->DynamoDB->_parseTable($this->Post, $data));
        
        $expected = array(
            'item1' => 1,
            'item2' => 2,
            'item3' => 3
        );
        
        $data = new stdClass();
        $data->body->Table = (object)$expected;
        $result = $this->DynamoDB->_parseTable($this->Post, $data);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test _toArray
     *
     */
    public function testToArray() {
        
        $expected = array(
            'item1' => array(
                'node1' => 1,
                'node2' => 2,
                'node3' => 3
            ),
            'item2' => array(
                'node4' => 4,
                'node5' => 5,
                'node6' => 6
            )
        );
        $data = new stdClass();
        $data->item1 = (object)$expected['item1'];
        $data->item2 = (object)$expected['item2'];
        $result = $this->DynamoDB->_toArray($data);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test _getPrimaryKeyValue
     *
     */
    public function testGetPrimaryKeyValue() {
        
        $this->assertFalse($this->DynamoDB->_getPrimaryKeyValue());
        
        $value = array('N'=>100);
        $expected = 100;
        $result = $this->DynamoDB->_getPrimaryKeyValue($value);
        $this->assertEqual($result, $expected);
        
        $value = 100;
        $expected = 100;
        $result = $this->DynamoDB->_getPrimaryKeyValue($value);
        $this->assertEqual($result, $expected);
        
    }
    
    public function testSetHashPrimaryKey() {
        
        $data = array(
            'id'    => 100,
            'rev'   => 2,
            'title' => 'The super story',
            'text'  => 'The super story is a test'
        );
        
        $this->Post->primaryKeySchema = array(
            'HashKeyElement' => array(
                'AttributeName' => 'id',
                'AttributeType' => 'S'
            )
        );
        $expected = array(
            'HashKeyElement' => array(
                AmazonDynamoDB::TYPE_STRING => (string)$data['id']
            )
        );
        $result = $this->DynamoDB->_setHashPrimaryKey($this->Post, $data);
        $this->assertEqual($result, $expected);
        
        $this->Post->primaryKeySchema = array(
            'HashKeyElement' => array(
                'AttributeName' => 'id',
                'AttributeType' => 'N'
            )
        );
        $expected = array(
            'HashKeyElement' => array(
                AmazonDynamoDB::TYPE_NUMBER => (string)$data['id']
            )
        );
        $result = $this->DynamoDB->_setHashPrimaryKey($this->Post, $data);
        $this->assertEqual($result, $expected);
        
        $this->Post->primaryKeySchema = array(
            'HashKeyElement' => array(
                'AttributeName' => 'id',
                'AttributeType' => 'B'
            )
        );
        $expected = array(
            'HashKeyElement' => array(
                AmazonDynamoDB::TYPE_BINARY => (string)$data['id']
            )
        );
        $result = $this->DynamoDB->_setHashPrimaryKey($this->Post, $data);
        $this->assertEqual($result, $expected);
        
    }
    
    public function testSetRangePrimaryKey() {
        
        $data = array(
            'id'    => 100,
            'rev'   => 2,
            'title' => 'The super story',
            'text'  => 'The super story is a test'
        );
        
        $this->Post->primaryKeySchema = array(
            'RangeKeyElement' => array(
                'AttributeName' => 'id',
                'AttributeType' => 'S'
            )
        );
        $expected = array(
            'RangeKeyElement' => array(
                AmazonDynamoDB::TYPE_STRING => (string)$data['id']
            )
        );
        $result = $this->DynamoDB->_setRangePrimaryKey($this->Post, $data);
        $this->assertEqual($result, $expected);
        
        $this->Post->primaryKeySchema = array(
            'RangeKeyElement' => array(
                'AttributeName' => 'id',
                'AttributeType' => 'N'
            )
        );
        $expected = array(
            'RangeKeyElement' => array(
                AmazonDynamoDB::TYPE_NUMBER => (string)$data['id']
            )
        );
        $result = $this->DynamoDB->_setRangePrimaryKey($this->Post, $data);
        $this->assertEqual($result, $expected);
        
        $this->Post->primaryKeySchema = array(
            'RangeKeyElement' => array(
                'AttributeName' => 'id',
                'AttributeType' => 'B'
            )
        );
        $expected = array(
            'RangeKeyElement' => array(
                AmazonDynamoDB::TYPE_BINARY => (string)$data['id']
            )
        );
        $result = $this->DynamoDB->_setRangePrimaryKey($this->Post, $data);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test _setStringPrimaryKeyValue
     *
     */
    public function testSetStringPrimaryKey() {
        
        $data = array(
            'id' => '550e8400-e29b-41d4-a716-446655440000',
        );
        $expected = array(AmazonDynamoDB::TYPE_STRING => $data['id']);
        $result = $this->DynamoDB->_setStringPrimaryKeyValue(
            $this->Post,
            'id',
            $data
        );
        $this->assertEqual($result, $expected);
        
        $data = array(
            'Post.id' => '550e8400-e29b-41d4-a716-446655440000',
        );
        $expected = array(AmazonDynamoDB::TYPE_STRING => $data['Post.id']);
        $result = $this->DynamoDB->_setStringPrimaryKeyValue(
            $this->Post,
            'id',
            $data
        );
        $this->assertEqual($result, $expected);
        
        $data = array(
            'rev'   => 2,
            'title' => 'The super story',
            'text'  => 'The super story is a test'
        );
        $result = $this->DynamoDB->_setStringPrimaryKeyValue($this->Post, 'id', $data);
        $this->assertNotNull($result[AmazonDynamoDB::TYPE_STRING]);
        
    }
    
    /**
     * Test _setNumberPrimaryKeyValue
     *
     */
    public function testSetNumberPrimaryKey() {
        
        $data = array(
            'id' => 100,
        );
        $expected = array(AmazonDynamoDB::TYPE_NUMBER => $data['id']);
        $result = $this->DynamoDB->_setNumberPrimaryKeyValue(
            $this->Post,
            'id',
            $data
        );
        $this->assertEqual($result, $expected);
        
        $data = array(
            'Post.id' => 100,
        );
        $expected = array(AmazonDynamoDB::TYPE_NUMBER => $data['Post.id']);
        $result = $this->DynamoDB->_setNumberPrimaryKeyValue(
            $this->Post,
            'id',
            $data
        );
        $this->assertEqual($result, $expected);
        
        $data = array(
            'rev'   => 2,
            'title' => 'The super story',
            'text'  => 'The super story is a test'
        );
        $result = $this->DynamoDB->_setNumberPrimaryKeyValue($this->Post, 'id', $data);
        $this->assertNotNull($result[AmazonDynamoDB::TYPE_NUMBER]);
        
    }
    
    /**
     * Test _setBinaryPrimaryKeyValue
     *
     */
    public function testSetBinaryPrimaryKey() {
        
        $data = array(
            'id' => 100,
        );
        $expected = array(AmazonDynamoDB::TYPE_BINARY => $data['id']);
        $result = $this->DynamoDB->_setBinaryPrimaryKeyValue(
            $this->Post,
            'id',
            $data
        );
        $this->assertEqual($result, $expected);
        
        $data = array(
            'Post.id' => 100,
        );
        $expected = array(AmazonDynamoDB::TYPE_BINARY => $data['Post.id']);
        $result = $this->DynamoDB->_setBinaryPrimaryKeyValue(
            $this->Post,
            'id',
            $data
        );
        $this->assertEqual($result, $expected);
        
        $data = array(
            'rev'   => 2,
            'title' => 'The super story',
            'text'  => 'The super story is a test'
        );
        $result = $this->DynamoDB->_setBinaryPrimaryKeyValue($this->Post, 'id', $data);
        $this->assertNotNull($result[AmazonDynamoDB::TYPE_BINARY]);
        
    }
    
    /**
     * Test _setAttributeUpdates
     *
     */
    public function testSetAttributeUpdates() {
        
        $data = array(
            'id'    => 100,
            'rev'   => 2,
            'title' => 'The super story',
            'text'  => 'The super story is a test'
        );
        $expected = array(
            'rev' => array(
                'Action' => 'PUT',
                'Value' => $this->DynamoDB->_setValueType($data['rev'])
            ),
            'title' => array(
                'Action' => 'PUT',
                'Value' => $this->DynamoDB->_setValueType($data['title'])
            ),
            'text' => array(
                'Action' => 'PUT',
                'Value' => $this->DynamoDB->_setValueType($data['text'])
            )
        );
        $result = $this->DynamoDB->_setAttributeUpdates($this->Post, $data);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test setValueTypes
     *
     */
    public function testSetValueTypes() {
        
        $data = array(
            'key1' => true,
            'key2' => 1,
            'key3' => 'amazon'
        );
        $expected = array(
            'key1' => array(AmazonDynamoDB::TYPE_STRING => (string)$data['key1']),
            'key2' => array(AmazonDynamoDB::TYPE_NUMBER => (string)$data['key2']),
            'key3' => array(AmazonDynamoDB::TYPE_STRING => (string)$data['key3'])
        );
        $result = $this->DynamoDB->_setValueTypes($data);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test _setValueType
     *
     */
    public function testSetValueType() {
        
        $value = true;
        $expected = array(AmazonDynamoDB::TYPE_STRING => (string)$value);
        $result = $this->DynamoDB->_setValueType($value);
        $this->assertEqual($result, $expected);
        
        $value = 1;
        $expected = array(AmazonDynamoDB::TYPE_NUMBER => (string)$value);
        $result = $this->DynamoDB->_setValueType($value);
        $this->assertEqual($result, $expected);
        
        $value = (double)1.23456789;
        $expected = array(AmazonDynamoDB::TYPE_NUMBER => (string)$value);
        $result = $this->DynamoDB->_setValueType($value);
        $this->assertEqual($result, $expected);
        
        $value = 'amazon';
        $expected = array(AmazonDynamoDB::TYPE_STRING => (string)$value);
        $result = $this->DynamoDB->_setValueType($value);
        $this->assertEqual($result, $expected);
        
        $value = array('one','two','three','four','five');
        $expected = array(AmazonDynamoDB::TYPE_STRING_SET => $value);
        $result = $this->DynamoDB->_setValueType($value);
        $this->assertEqual($result, $expected);
        
        $value = array(1,2,3,4,5);
        $expected = array(AmazonDynamoDB::TYPE_NUMBER_SET => $value);
        $result = $this->DynamoDB->_setValueType($value);
        $this->assertEqual($result, $expected);
        
        $value = new stdClass();
        $this->expectError('var type (object) not supported');
        $this->DynamoDB->_setValueType($value);
        
        $value = NULL;
        $expected = array(AmazonDynamoDB::TYPE_STRING => '');
        $result = $this->DynamoDB->_setValueType($value);
        $this->assertEqual($result, $expected);
        
        // test a resource type var
        $value = $handle = fopen(CACHE."file.txt", "w+");
        $this->expectError('var type not supported');
        $this->DynamoDB->_setValueType($value);
    }
    
    /**
     * Test _prepareLogQuery
     *
     */
    public function testPrepareLogQuery() {
        
        $this->DynamoDB->fullDebug = false;
        $this->assertFalse($this->DynamoDB->_prepareLogQuery($this->Post));
        
        $this->DynamoDB->fullDebug = true;
        $this->DynamoDB->_prepareLogQuery($this->Post);
        $this->assertNotNull($this->DynamoDB->_startTime);
        $this->assertNull($this->DynamoDB->took);
        $this->assertNull($this->DynamoDB->affected);
        $this->assertNull($this->DynamoDB->error);
        $this->assertNull($this->DynamoDB->numRows);
    }
    
    /**
     * Removes test tables
     *
     */
    public function _removeTestTables() {
        
        $tableName = 'testProductCatalog';
        $options = array(
            'TableName' => $tableName
        );
        $response = $this->DynamoDB->query('delete_table', array($options));
        do {
            sleep(1);
            $response = $this->DynamoDB->query('describe_table', array($options));
        }
        while ((string)$response->body->Table->TableStatus == 'DELETING');
        
        
        $tableName = 'testForum';
        $options = array(
            'TableName' => $tableName
        );
        $response = $this->DynamoDB->query('delete_table', array($options));
        do {
            sleep(1);
            $response = $this->DynamoDB->query('describe_table', array($options));
        }
        while ((string)$response->body->Table->TableStatus == 'DELETING');
        
        
        $tableName = 'testThread';
        $options = array(
            'TableName' => $tableName
        );
        $response = $this->DynamoDB->query('delete_table', array($options));
        do {
            sleep(1);
            $response = $this->DynamoDB->query('describe_table', array($options));
        }
        while ((string)$response->body->Table->TableStatus == 'DELETING');
        
        
        $options = array(
            'TableName' => 'testReply'
        );
        $response = $this->DynamoDB->query('delete_table', array($options));
        do {
            sleep(1);
            $response = $this->DynamoDB->query('describe_table', array($options));
        }
        while ((string)$response->body->Table->TableStatus == 'DELETING');
        
    }
    
    /**
     * Create test tables
     *
     * @todo add posts tables for tests
     */
    public function _createTestTables() {
        
        $tableName = 'testProductCatalog';
        $options = array(
            'TableName' => $tableName,
            'KeySchema' => array(
                'HashKeyElement' => array(
                    'AttributeName' => 'Id',
                    'AttributeType' => AmazonDynamoDB::TYPE_NUMBER
                )
            ),
            'ProvisionedThroughput' => array(
                'ReadCapacityUnits' => 10,
                'WriteCapacityUnits' => 5
            )
        );
        $response = $this->DynamoDB->query('create_table', array($options));
        $this->assertEqual($response->status, 200);
        // wait until table is active
        do {
            sleep(1);
            $options = array('TableName' => $tableName);
            $response = $this->DynamoDB->query('describe_table', array($options));
        }
        while ((string)$response->body->Table->TableStatus !== 'ACTIVE');
        
        
        $tableName = 'testForum';
        $options = array(
            'TableName' => $tableName,
            'KeySchema' => array(
                'HashKeyElement' => array(
                    'AttributeName' => 'Name',
                    'AttributeType' => AmazonDynamoDB::TYPE_STRING
                )
            ),
            'ProvisionedThroughput' => array(
                'ReadCapacityUnits' => 10,
                'WriteCapacityUnits' => 5
            )
        );
        $response = $this->DynamoDB->query('create_table', array($options));
        $this->assertEqual($response->status, 200);
        // wait until table is active
        do {
            sleep(1);
            $options = array('TableName' => $tableName);
            $response = $this->DynamoDB->query('describe_table', array($options));
        }
        while ((string)$response->body->Table->TableStatus !== 'ACTIVE');
         
         
        $tableName = 'testThread';
        $options = array(
            'TableName' => $tableName,
            'KeySchema' => array(
                'HashKeyElement' => array(
                    'AttributeName' => 'ForumName',
                    'AttributeType' => AmazonDynamoDB::TYPE_STRING
                ),
                'RangeKeyElement' => array(
                    'AttributeName' => 'Subject',
                    'AttributeType' => AmazonDynamoDB::TYPE_STRING
                )
            ),
            'ProvisionedThroughput' => array(
                'ReadCapacityUnits' => 10,
                'WriteCapacityUnits' => 5
            )
        );
        $response = $this->DynamoDB->query('create_table', array($options));
        $this->assertEqual($response->status, 200);
        // wait until table is active
        do {
            sleep(1);
            $options = array('TableName' => $tableName);
            $response = $this->DynamoDB->query('describe_table', array($options));
        }
        while ((string)$response->body->Table->TableStatus !== 'ACTIVE');
        
        
        $tableName = 'testReply';
        $options = array(
            'TableName' => $tableName,
            'KeySchema' => array(
                'HashKeyElement' => array(
                    'AttributeName' => 'Id',
                    'AttributeType' => AmazonDynamoDB::TYPE_STRING
                ),
                'RangeKeyElement' => array(
                    'AttributeName' => 'ReplyDateTime',
                    'AttributeType' => AmazonDynamoDB::TYPE_STRING
                )
            ),
            'ProvisionedThroughput' => array(
                'ReadCapacityUnits' => 10,
                'WriteCapacityUnits' => 5
            )
        );
        $response = $this->DynamoDB->query('create_table', array($options));
        $this->assertEqual($response->status, 200);
        // wait until table is active
        do {
            sleep(1);
            $options = array('TableName' => $tableName);
            $response = $this->DynamoDB->query('describe_table', array($options));
        }
        while ((string)$response->body->Table->TableStatus !== 'ACTIVE');
        
        return true;
        
    }
    
    /**
     * Create test data
     *
     */
    public function _createTestData() {
         
        $queue = new CFBatchRequest();
        $queue->use_credentials($this->DynamoDB->connection->credentials);
        
        $this->DynamoDB->connection->batch($queue)->put_item(array(
            'TableName' => 'testProductCatalog',
            'Item' => array(
                'Id'              => array( AmazonDynamoDB::TYPE_NUMBER           => '101'              ), // Hash Key
                'Title'           => array( AmazonDynamoDB::TYPE_STRING           => 'Book 101 Title'   ),
                'ISBN'            => array( AmazonDynamoDB::TYPE_STRING           => '111-1111111111'   ),
                'Authors'         => array( AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Author1')   ),
                'Price'           => array( AmazonDynamoDB::TYPE_NUMBER           => '2'                ),
                'Dimensions'      => array( AmazonDynamoDB::TYPE_STRING           => '8.5 x 11.0 x 0.5' ),
                'PageCount'       => array( AmazonDynamoDB::TYPE_NUMBER           => '500'              ),
                'InPublication'   => array( AmazonDynamoDB::TYPE_NUMBER           => '1'                ),
                'ProductCategory' => array( AmazonDynamoDB::TYPE_STRING           => 'Book'             )
            )
        ));
        
        $this->DynamoDB->connection->batch($queue)->put_item(array(
            'TableName' => 'testProductCatalog',
            'Item' => array(
                'Id'              => array( AmazonDynamoDB::TYPE_NUMBER           => '102'                       ), // Hash Key
                'Title'           => array( AmazonDynamoDB::TYPE_STRING           => 'Book 102 Title'            ),
                'ISBN'            => array( AmazonDynamoDB::TYPE_STRING           => '222-2222222222'            ),
                'Authors'         => array( AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Author1', 'Author2') ),
                'Price'           => array( AmazonDynamoDB::TYPE_NUMBER           => '20'                        ),
                'Dimensions'      => array( AmazonDynamoDB::TYPE_STRING           => '8.5 x 11.0 x 0.8'          ),
                'PageCount'       => array( AmazonDynamoDB::TYPE_NUMBER           => '600'                       ),
                'InPublication'   => array( AmazonDynamoDB::TYPE_NUMBER           => '1'                         ),
                'ProductCategory' => array( AmazonDynamoDB::TYPE_STRING           => 'Book'                      )
            )
        ));
        
        $this->DynamoDB->connection->batch($queue)->put_item(array(
            'TableName' => 'testProductCatalog',
            'Item' => array(
                'Id'              => array( AmazonDynamoDB::TYPE_NUMBER           => '103'                       ), // Hash Key
                'Title'           => array( AmazonDynamoDB::TYPE_STRING           => 'Book 103 Title'            ),
                'ISBN'            => array( AmazonDynamoDB::TYPE_STRING           => '333-3333333333'            ),
                'Authors'         => array( AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Author1', 'Author2') ),
                'Price'           => array( AmazonDynamoDB::TYPE_NUMBER           => '2000'                      ),
                'Dimensions'      => array( AmazonDynamoDB::TYPE_STRING           => '8.5 x 11.0 x 1.5'          ),
                'PageCount'       => array( AmazonDynamoDB::TYPE_NUMBER           => '600'                       ),
                'InPublication'   => array( AmazonDynamoDB::TYPE_NUMBER           => '0'                         ),
                'ProductCategory' => array( AmazonDynamoDB::TYPE_STRING           => 'Book'                      )
            )
        ));
        
        $this->DynamoDB->connection->batch($queue)->put_item(array(
            'TableName' => 'testProductCatalog',
            'Item' => array(
                'Id'              => array( AmazonDynamoDB::TYPE_NUMBER           => '201'                 ), // Hash Key
                'Title'           => array( AmazonDynamoDB::TYPE_STRING           => '18-Bike-201'         ),
                'Description'     => array( AmazonDynamoDB::TYPE_STRING           => '201 Description'     ),
                'BicycleType'     => array( AmazonDynamoDB::TYPE_STRING           => 'Road'                ),
                'Brand'           => array( AmazonDynamoDB::TYPE_STRING           => 'Mountain A'          ),
                'Price'           => array( AmazonDynamoDB::TYPE_NUMBER           => '100'                 ),
                'Gender'          => array( AmazonDynamoDB::TYPE_STRING           => 'M'                   ),
                'Color'           => array( AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Red', 'Black') ),
                'ProductCategory' => array( AmazonDynamoDB::TYPE_STRING           => 'Bicycle'             )
            )
        ));
        
        $this->DynamoDB->connection->batch($queue)->put_item(array(
            'TableName' => 'testProductCatalog',
            'Item' => array(
                'Id'              => array( AmazonDynamoDB::TYPE_NUMBER           => '202'                   ), // Hash Key
                'Title'           => array( AmazonDynamoDB::TYPE_STRING           => '21-Bike-202'           ),
                'Description'     => array( AmazonDynamoDB::TYPE_STRING           => '202 Description'       ),
                'BicycleType'     => array( AmazonDynamoDB::TYPE_STRING           => 'Road'                  ),
                'Brand'           => array( AmazonDynamoDB::TYPE_STRING           => 'Brand-Company A'       ),
                'Price'           => array( AmazonDynamoDB::TYPE_NUMBER           => '200'                   ),
                'Gender'          => array( AmazonDynamoDB::TYPE_STRING           => 'M'                     ),
                'Color'           => array( AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Green', 'Black') ),
                'ProductCategory' => array( AmazonDynamoDB::TYPE_STRING           => 'Bicycle'               )
            )
        ));
        
        $this->DynamoDB->connection->batch($queue)->put_item(array(
            'TableName' => 'testProductCatalog',
            'Item' => array(
                'Id'              => array( AmazonDynamoDB::TYPE_NUMBER           => '203'                          ), // Hash Key
                'Title'           => array( AmazonDynamoDB::TYPE_STRING           => '19-Bike-203'                  ),
                'Description'     => array( AmazonDynamoDB::TYPE_STRING           => '203 Description'              ),
                'BicycleType'     => array( AmazonDynamoDB::TYPE_STRING           => 'Road'                         ),
                'Brand'           => array( AmazonDynamoDB::TYPE_STRING           => 'Brand-Company B'              ),
                'Price'           => array( AmazonDynamoDB::TYPE_NUMBER           => '300'                          ),
                'Gender'          => array( AmazonDynamoDB::TYPE_STRING           => 'W'                            ),
                'Color'           => array( AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Red', 'Green', 'Black') ),
                'ProductCategory' => array( AmazonDynamoDB::TYPE_STRING           => 'Bicycle'                      )
            )
        ));
        
        $this->DynamoDB->connection->batch($queue)->put_item(array(
            'TableName' => 'testProductCatalog',
            'Item' => array(
                'Id'              => array( AmazonDynamoDB::TYPE_NUMBER           => '204'             ), // Hash Key
                'Title'           => array( AmazonDynamoDB::TYPE_STRING           => '18-Bike-204'     ),
                'Description'     => array( AmazonDynamoDB::TYPE_STRING           => '204 Description' ),
                'BicycleType'     => array( AmazonDynamoDB::TYPE_STRING           => 'Mountain'        ),
                'Brand'           => array( AmazonDynamoDB::TYPE_STRING           => 'Brand-Company B' ),
                'Price'           => array( AmazonDynamoDB::TYPE_NUMBER           => '400'             ),
                'Gender'          => array( AmazonDynamoDB::TYPE_STRING           => 'W'               ),
                'Color'           => array( AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Red')      ),
                'ProductCategory' => array( AmazonDynamoDB::TYPE_STRING           => 'Bicycle'         )
            )
        ));
        
        $this->DynamoDB->connection->batch($queue)->put_item(array(
            'TableName' => 'testProductCatalog',
            'Item' => array(
                'Id'              => array( AmazonDynamoDB::TYPE_NUMBER           => '205'                 ), // Hash Key
                'Title'           => array( AmazonDynamoDB::TYPE_STRING           => '20-Bike-205'         ),
                'Description'     => array( AmazonDynamoDB::TYPE_STRING           => '205 Description'     ),
                'BicycleType'     => array( AmazonDynamoDB::TYPE_STRING           => 'Hybrid'              ),
                'Brand'           => array( AmazonDynamoDB::TYPE_STRING           => 'Brand-Company C'     ),
                'Price'           => array( AmazonDynamoDB::TYPE_NUMBER           => '500'                 ),
                'Gender'          => array( AmazonDynamoDB::TYPE_STRING           => 'B'                   ),
                'Color'           => array( AmazonDynamoDB::TYPE_ARRAY_OF_STRINGS => array('Red', 'Black') ),
                'ProductCategory' => array( AmazonDynamoDB::TYPE_STRING           => 'Bicycle'             )
            )
        ));
        
        $this->DynamoDB->connection->batch($queue)->put_item(array(
            'TableName' => 'testForum',
            'Item' => array(
                'Name'     => array( AmazonDynamoDB::TYPE_STRING => 'Amazon DynamoDB'     ), // Hash Key
                'Category' => array( AmazonDynamoDB::TYPE_STRING => 'Amazon Web Services' ),
                'Threads'  => array( AmazonDynamoDB::TYPE_NUMBER => '0'                   ),
                'Messages' => array( AmazonDynamoDB::TYPE_NUMBER => '0'                   ),
                'Views'    => array( AmazonDynamoDB::TYPE_NUMBER => '1000'                ),
            )
        ));
        
        $this->DynamoDB->connection->batch($queue)->put_item(array(
            'TableName' => 'testForum',
            'Item' => array(
                'Name'     => array( AmazonDynamoDB::TYPE_STRING => 'Amazon S3'           ), // Hash Key
                'Category' => array( AmazonDynamoDB::TYPE_STRING => 'Amazon Web Services' ),
                'Threads'  => array( AmazonDynamoDB::TYPE_NUMBER => '0'                   )
            )
        ));
        
        $this->DynamoDB->connection->batch($queue)->put_item(array(
            'TableName' => 'testReply',
            'Item' => array(
                'Id'            => array( AmazonDynamoDB::TYPE_STRING => 'Amazon DynamoDB#DynamoDB Thread 1' ), // Hash Key
                'ReplyDateTime' => array( AmazonDynamoDB::TYPE_STRING => $this->fourteen_days_ago                 ), // Range Key
                'Message'       => array( AmazonDynamoDB::TYPE_STRING => 'DynamoDB Thread 1 Reply 2 text'    ),
                'PostedBy'      => array( AmazonDynamoDB::TYPE_STRING => 'User B'                            ),
            )
        ));
        $this->DynamoDB->connection->batch($queue)->put_item(array(
            'TableName' => 'testReply',
            'Item' => array(
                'Id'            => array( AmazonDynamoDB::TYPE_STRING => 'Amazon DynamoDB#DynamoDB Thread 2' ), // Hash Key
                'ReplyDateTime' => array( AmazonDynamoDB::TYPE_STRING => $this->twenty_one_days_ago                    ), // Range Key
                'Message'       => array( AmazonDynamoDB::TYPE_STRING  => 'DynamoDB Thread 2 Reply 3 text'   ),
                'PostedBy'      => array( AmazonDynamoDB::TYPE_STRING => 'User B'                            ),
            )
        ));
        
        $this->DynamoDB->connection->batch($queue)->put_item(array(
            'TableName' => 'testReply',
            'Item' => array(
                'Id'            => array( AmazonDynamoDB::TYPE_STRING => 'Amazon DynamoDB#DynamoDB Thread 2' ), // Hash Key
                'ReplyDateTime' => array( AmazonDynamoDB::TYPE_STRING => $this->seven_days_ago                    ), // Range Key
                'Message'       => array( AmazonDynamoDB::TYPE_STRING  => 'DynamoDB Thread 2 Reply 2 text'   ),
                'PostedBy'      => array( AmazonDynamoDB::TYPE_STRING => 'User A'                            ),
            )
        ));
        
        $this->DynamoDB->connection->batch($queue)->put_item(array(
            'TableName' => 'testReply',
            'Item' => array(
                'Id'            => array( AmazonDynamoDB::TYPE_STRING => 'Amazon DynamoDB#DynamoDB Thread 2' ), // Hash Key
                'ReplyDateTime' => array( AmazonDynamoDB::TYPE_STRING => $this->one_day_ago                       ), // Range Key
                'Message'       => array( AmazonDynamoDB::TYPE_STRING  => 'DynamoDB Thread 2 Reply 1 text'   ),
                'PostedBy'      => array( AmazonDynamoDB::TYPE_STRING => 'User A'                            ),
            )
        ));
        
        $responses = $this->DynamoDB->connection->batch($queue)->send();
        
        return $responses->areOK();
        
    }
    
    /**
     * End Test
     *
     */
    public function endTest() {
        unset($this->Post);
        unset($this->DynamoDB);
        ClassRegistry::flush();
    }
    
}