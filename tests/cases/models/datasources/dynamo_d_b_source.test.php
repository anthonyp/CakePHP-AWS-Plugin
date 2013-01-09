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

App::import('Vendor', 'AWS.s_d_k_class.php');
Mock::generate('AmazonDynamoDB');

App::import('Datasource', 'AWS.DynamoDBSource');
Mock::generatePartial(
    'DynamoDBSource',
    'DynamoDBSourceTestVersion',
    array('createConnection')
);

/**
 * DynamoDBTestCase
 *
 * @package       datasources
 * @subpackage    datasources.tests.cases.models.datasources
 */
class DynamoDBTestCase extends CakeTestCase {
    
    /**
     * Amazon DynamoDB API object
     *
     * @var object
     */
    public $AmazonDynamoDB = null;
    
    /**
     * DynamoDB Datasource object
     *
     * @var object
     */
    public $DynamoDB = null;
    
    /**
     * Model object
     *
     * @var object
     */
    public $Model1 = null;
    
    /**
     * Configuration
     *
     * @var array
     */
    protected $config = array(
        'datasource' => 'AWS.DynamoDBSource',
        'database' => null,
        'host' => 'dynamodb.ap-northeast-1.amazonaws.com',
        'login' => 'RJju8JKqFSYTbhVi0GQihqde',
        'password' => 'Wg1LhGnfoGvFdGuiqpMlqyLsjkGCA0PbXNpCemXxJp',
        'default_cache_config' => CACHE
    );
    
    /**
     * Start Test
     *
     * @return void
     */
    public function startTest() {
        
        if (empty($this->AmazonDynamoDB)) {
            $this->AmazonDynamoDB = new MockAmazonDynamoDB();
        }
        
        if (empty($this->DynamoDB)) {
            $this->DynamoDB = new DynamoDBSourceTestVersion();
            $this->DynamoDB->setReturnReference('createConnection', $this->AmazonDynamoDB);
            $this->DynamoDB->setConfig($this->config);
            $this->DynamoDB->connect();
        }
        
        if (empty($this->Model1)) {
            $this->Model1 = new stdClass();
            $this->Model1->alias = 'Model1';
            $this->Model1->primaryKey = 'id';
            $this->Model1->id = 'post-2013-01-01_1';
            $this->Model1->table = 'Model1';
            $this->Model1->findQueryType = 'all';
        }
        
    }
    
    /**
     * Test setConfig
     *
     * @return void
     */
    public function testSetConfig() {
        
        $config = array(
            'login' => '2hC8XzAgudMFJMf'
        );
        $results = $this->DynamoDB->setConfig($config);
        $this->assertEqual($results['login'], $config['login']);
        
    }
    
    /**
     * Test __construct
     *
     * @return void
     */
    public function testConstruct() {
        
        $this->assertTrue(new DynamoDBSource($this->config, false));
        
        $this->assertTrue(new DynamoDBSource($this->config));
        
    }
    
    /**
     * Test connection
     *
     * @return void
     */
    public function testConnect() {
        
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
        $this->assertTrue($this->DynamoDB->connect('test'));
        
    }
    
    /**
     * Test createConnection
     *
     * @return void
     */
    public function testCreateConnection() {
        
        // $this->DynamoDB = new DynamoDBSource($this->config);
        // $this->expectException('CFCredentials_Exception');
        // $this->DynamoDB->createConnection($this->config, 'AmazonDynamoDBThatDoNotExist');
        
    }
    
    /**
     * Test disconnect
     *
     * @return void
     */
    public function testDisconnect() {
        
        $this->assertTrue($this->DynamoDB->connection);
        $this->assertTrue($this->DynamoDB->connected);
        $this->assertTrue($this->DynamoDB->disconnect());
        $this->assertFalse($this->DynamoDB->connection);
        $this->assertFalse($this->DynamoDB->connected);
        
    }
    
    /**
     * Test close
     *
     * @return void
     */
    public function testClose() {
        
        $this->assertTrue($this->DynamoDB->close());
        $this->assertFalse($this->DynamoDB->connection);
        $this->assertFalse($this->DynamoDB->connected);
        
    }
    
    /**
     * Test listSources
     *
     * @return void
     */
    public function testListSources() {
        
        $this->DynamoDB->connected = false;
        $this->assertFalse($this->DynamoDB->listSources());
        $this->DynamoDB->connected = true;
        
        $response = (object) array(
            'body' => array(
                'TableNames' => array(
                    0 => 'Table1',
                    1 => 'Table2',
                    2 => 'Table3'
                )
            ),
            'status' => 200
        );
        
        $this->AmazonDynamoDB->setReturnValue('list_tables', $response);
        $results = $this->DynamoDB->listSources();
        $this->assertEqual($results, $response->body['TableNames']);
        
    }
    
    /**
     * Test calculate
     *
     * @return void
     */
    public function testCalculate() {
        
        $this->assertTrue($this->DynamoDB->calculate());
        
     }
    
    /**
     * Test describe
     *
     * @return void
     */
    public function testDescribe() {
        
        $this->DynamoDB->connected = false;
        $this->assertFalse($this->DynamoDB->describe($this->Model1));
        $this->DynamoDB->connected = true;
        
        $this->expectError(__('Schema is not configured in the model.', true));
        $this->DynamoDB->describe($this->Model1);
        
        $this->Model1->table = 'Model1';
        $this->Model1->schema = array(
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
            'reads' => array(
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
                'type' => 'string',
                'null' => true,
            ),
            'tags' => array(
                'type' => 'set',
                'null' => true,
            ),
            'category' => array(
                'type' => 'string',
                'null' => true,
            )
        );
        $response = (object)array(
            'body' => array(
                'Table' => array(
                    'CreationDateTime' => '1357332261.304',
                    'ItemCount' => '326',
                    'KeySchema' => array(
                        'HashKeyElement' => array(
                            'AttributeName' => 'id',
                            'AttributeType' => 'S',
                        ),
                    ),
                    'ProvisionedThroughput' => array(
                        'LastIncreaseDateTime' => '1357332337.472',
                        'NumberOfDecreasesToday' => '0',
                        'ReadCapacityUnits' => '100',
                        'WriteCapacityUnits' => '50',
                    ),
                    'TableName' => 'Model1',
                    'TableSizeBytes' => '28841',
                    'TableStatus' => 'ACTIVE',
                ),
            ),
            'status' => 200,
        );
        $this->AmazonDynamoDB->setReturnValueAt(1, 'describe_table', $response);
        $results = $this->DynamoDB->describe($this->Model1);
        $this->assertEqual($this->Model1->schema, $results);
        $this->assertEqual($this->Model1->primaryKeyType, 'hash');
        $this->assertEqual(
            $response->body['Table']['KeySchema'],
            $this->Model1->primaryKeySchema
        );
        $this->assertEqual(
            $response->body['Table']['KeySchema']['HashKeyElement']['AttributeName'],
            $this->Model1->primaryKey
        );
        
        $response = (object)array(
            'body' => array(
                'Table' => array(
                    'CreationDateTime' => '1357332261.304',
                    'ItemCount' => '326',
                    'KeySchema' => array(
                        'HashKeyElement' => array(
                            'AttributeName' => 'id',
                            'AttributeType' => 'S',
                        ),
                        'RangeKeyElement' => array(
                            'AttributeName' => 'title',
                            'AttributeType' => 'S'
                        )
                    ),
                    'ProvisionedThroughput' => array(
                        'LastIncreaseDateTime' => '1357332337.472',
                        'NumberOfDecreasesToday' => '0',
                        'ReadCapacityUnits' => '100',
                        'WriteCapacityUnits' => '50',
                    ),
                    'TableName' => 'Model1',
                    'TableSizeBytes' => '28841',
                    'TableStatus' => 'ACTIVE',
                ),
            ),
            'status' => 200,
        );
        $this->AmazonDynamoDB->setReturnValueAt(3, 'describe_table', $response);
        $results = $this->DynamoDB->describe($this->Model1);
        $this->assertEqual($this->Model1->schema, $results);
        $this->assertEqual($this->Model1->primaryKeyType, 'hashAndRange');
        $this->assertEqual(
            $response->body['Table']['KeySchema'],
            $this->Model1->primaryKeySchema
        );
        $this->assertEqual(
            $response->body['Table']['KeySchema']['HashKeyElement']['AttributeName'],
            $this->Model1->primaryKey
        );
        
    }
    
    /**
     * Test create
     *
     * @return void
     */
    public function testCreate() {
        
        $this->DynamoDB->connected = false;
        $this->assertFalse($this->DynamoDB->create($this->Model1));
        $this->DynamoDB->connected = true;
        
        $response = (object) array(
            'body' => array(
                'ConsumedCapacityUnits' => '1',
            ),
            'status' => 200,
        );
        
        $id = uniqid();
        $title = 'Post #'. $id;
        $data = array(
            'Model1' => array(
                'id' => $id,
                'rev' => rand(1,9),
                'title' => $title,
                'description' => 'Description for '. $title
            )
        );
        
        $fields = array_keys($data);
        $values = array_values($data);
        $this->AmazonDynamoDB->setReturnValueAt(0, 'put_item', $response);
        $this->assertTrue($this->DynamoDB->create($this->Model1, $fields, $values));
        
        $this->Model1->data = $data;
        $this->AmazonDynamoDB->setReturnValueAt(1, 'put_item', $response);
        $this->assertTrue($this->DynamoDB->create($this->Model1));
        
        $this->Model1->data = $data;
        $fields = array_keys($data);
        $values = null;
        $this->AmazonDynamoDB->setReturnValueAt(2, 'put_item', $response);
        $this->assertTrue($this->DynamoDB->create($this->Model1, $fields, $values));
        
        $response = (object) array(
            'body' => array(
                '__type' => 'com.amazonaws.dynamodb.v20111205#ResourceNotFoundException',
                'message' => 'Requested resource not found',
            ),
            'status' => 400,
        );
        
        $this->assertFalse($this->DynamoDB->create($this->Model1));
    }
    
    /**
     * Test read
     *
     * @return void
     */
    public function testRead() {
         
        $this->DynamoDB->connected = false;
        $this->assertFalse(
            $this->DynamoDB->read($this->Model1, array())
        );
        $this->DynamoDB->connected = true;
        
        $this->Model1->primaryKeyType = 'hash';
        $this->Model1->primaryKeySchema = array(
            'HashKeyElement' => array(
                'AttributeName' => 'id',
                'AttributeType' => 'S'
            )
        );
        $query = array(
            'conditions' => array(
                'id' => '1'
            )
        );
        $this->assertFalse($this->DynamoDB->read($this->Model1, $query));
        
        $this->Model1->primaryKeyType = 'hashAndRange';
        $this->Model1->primaryKeySchema = array(
            'HashKeyElement' => array(
                'AttributeName' => 'id',
                'AttributeType' => 'S'
            ),
            'RangeKeyElement' => array(
                'AttributeName' => 'title',
                'AttributeType' => 'S'
            )
        );
        $query = array(
            'conditions' => array(
                'Model1.id' => '1',
                'Model1.title' => 'The super story'
            )
        );
        $this->assertFalse($this->DynamoDB->read($this->Model1, $query));
        
        $this->Model1->primaryKeyType = 'hash';
        $query = array(
            'conditions' => array()
        );
        $this->assertFalse($this->DynamoDB->read($this->Model1, $query));
        
        $this->Model1->primaryKeyType = 'hash';
        $query = array(
            'conditions' => array('Model1.id'=>'post-2013-01-01_1')
        );
        $response = new stdClass();
        $response->body->Item = array(
            'item1' => array('N' => 1),
            'item2' => array('N' => 2),
            'item3' => array('N' => 3)
        );
        $this->AmazonDynamoDB->setReturnValue('get_item', $response);
        $results = $this->DynamoDB->read($this->Model1, $query);
        $expected = array(0 => array(
            'Model1' => array(
                'item1' => 1,
                'item2' => 2,
                'item3' => 3
            )
        ));
        $this->assertEqual($results, $expected);
        
        $this->Model1->findQueryType = 'count';
        $results = $this->DynamoDB->read($this->Model1, $query);
        $expected = array(0 => array(0 => array('count' => 1)));
        $this->assertEqual($results, $expected);
        
    }
    
    /**
     * Test update
     *
     * @return void
     */
    public function testUpdate() {
        
        $this->DynamoDB->connected = false;
        $this->assertFalse(
            $this->DynamoDB->update($this->Model1, array(), array())
        );
        $this->DynamoDB->connected = true;
        
        $response = (object) array(
            'body' => array(
                'ConsumedCapacityUnits' => '1',
            ),
            'status' => 200,
        );
        
        $id = uniqid();
        $title = 'Post #'. $id;
        $data = array(
            'Model1' => array(
                'id' => $id,
                'rev' => rand(1,9),
                'title' => $title,
                'description' => 'Description for '. $title
            )
        );
        
        $fields = array_keys($data);
        $values = array_values($data);
        $this->AmazonDynamoDB->setReturnValueAt(0, 'update_item', $response);
        $this->assertTrue($this->DynamoDB->update($this->Model1, $fields, $values));
        
        $this->Model1->data = $data;
        $this->AmazonDynamoDB->setReturnValueAt(1, 'update_item', $response);
        $this->assertTrue($this->DynamoDB->update($this->Model1));
        
    }
    
    /**
     * Test delete
     *
     * @return void
     */
    public function testDelete() {
        
        $this->DynamoDB->connected = false;
        $this->assertFalse($this->DynamoDB->delete($this->Model1, array()));
        $this->DynamoDB->connected = true;
        
        $this->AmazonDynamoDB->setReturnValueAt(0, 'delete_item', true);
        $this->assertTrue($this->DynamoDB->delete($this->Model1, array('post-2013-01-01_1')));
        
        $conditions = array(array('post-2013-01-01_1', 'The super story'));
        $this->AmazonDynamoDB->setReturnValueAt(1, 'delete_item', false);
        $this->assertFalse($this->DynamoDB->delete($this->Model1, $conditions));
        
    }
    
    /**
     * Test query
     *
     * @return void
     */
    public function testQuey() {
        
        $this->DynamoDB->connected = false;
        $this->assertFalse($this->DynamoDB->query($this->Model1, array()));
        $this->DynamoDB->connected = true;
        
        $options = array(
            'TableName' => 'Model1',
            'Key' => array(
                'HashKeyElement' => array(
                    AmazonDynamoDB::TYPE_STRING => 'post-2013-01-01_1'
                ),
                'ConsistentRead' => 'true',
                'AttributesToGet' => array('id', 'tags')
            )
        );
        $this->AmazonDynamoDB->setReturnValue('get_item', true);
        $this->assertTrue($this->DynamoDB->query('get_item', array($options)));
        
        $this->AmazonDynamoDB->setReturnValue('query', '12345');
        $this->assertEqual($this->DynamoDB->query(array($options)), '12345');
        
    }
    
    /**
     * Test _readType
     *
     * @return void
     */
    public function testGetReadType() {
        
        $this->Model1->primaryKeyType = 'hash';
        $query = array(
            'conditions' => array()
        );
        $this->assertEqual(
            $this->DynamoDB->_getReadType($this->Model1, $query),
            'scan'
        );
        
        $this->Model1->primaryKeyType = 'hashAndRange';
        $this->Model1->primaryKeySchema = array(
            'HashKeyElement' => array(
                'AttributeName' => 'id',
                'AttributeType' => 'S'
            ),
            'RangeKeyElement' => array(
                'AttributeName' => 'title',
                'AttributeType' => 'S'
            )
        );
        $query = array(
            'conditions' => array(
                'Model1.id' => '1',
                'Model1.title' => 'The super story'
            )
        );
        $this->assertEqual(
            $this->DynamoDB->_getReadType($this->Model1, $query),
            'query'
        );
        
        $this->Model1->primaryKeyType = 'hashAndRange';
        $this->Model1->primaryKeySchema = array(
            'HashKeyElement' => array(
                'AttributeName' => 'id',
                'AttributeType' => 'S'
            ),
            'RangeKeyElement' => array(
                'AttributeName' => 'title',
                'AttributeType' => 'S'
            )
        );
        $query = array(
            'conditions' => array(
                'Model1.id' => '1',
                'Model1.title <>' => 'The super story'
            )
        );
        $this->assertEqual(
            $this->DynamoDB->_getReadType($this->Model1, $query),
            'query'
        );
        
        $this->Model1->primaryKeyType = 'hash';
        $this->Model1->primaryKeySchema = array(
            'HashKeyElement' => array(
                'AttributeName' => 'id',
                'AttributeType' => 'S'
            )
        );
        $query = array(
            'conditions' => array(
                'Model1.id' => '1'
            )
        );
        $this->assertEqual(
            $this->DynamoDB->_getReadType($this->Model1, $query),
            'get_item'
        );
        
        $this->Model1->primaryKeyType = 'hash';
        $this->Model1->primaryKeySchema = array(
            'HashKeyElement' => array(
                'AttributeName' => 'id',
                'AttributeType' => 'S'
            )
        );
        $query = array(
            'conditions' => array(
                'id' => '1'
            )
        );
        $this->assertEqual(
            $this->DynamoDB->_getReadType($this->Model1, $query),
            'get_item'
        );
        
    }
    
    /**
     * Test _readWithGetItem
     *
     * @return void
     */
    public function testReadWithGetItem() {
        
        $this->Model1->primaryKeyType = 'hash';
        $this->Model1->primaryKeySchema = array(
            'HashKeyElement' => array(
                'AttributeName' => 'id',
                'AttributeType' => 'S'
            )
        );
        
        $query = array(
            'conditions' => array('Model1.id'=>'post-2013-01-01_1')
        );
        $this->AmazonDynamoDB->setReturnValue('get_item', true);
        $this->assertTrue($this->DynamoDB->_readWithGetItem($this->Model1, $query));
        
        $this->Model1->id = 'post-2013-01-01_2';
        
        $query = array(
            'conditions' => array()
        );
        $this->AmazonDynamoDB->setReturnValue('get_item', true);
        $this->assertTrue($this->DynamoDB->_readWithGetItem($this->Model1, $query));
        
    }
    
    /**
     * Test _readWithQuery
     *
     * @return void
     */
    public function testReadWithQuery() {
        
        $this->Model1->primaryKeyType = 'hashAndRange';
        $this->Model1->primaryKeySchema = array(
            'HashKeyElement' => array(
                'AttributeName' => 'id',
                'AttributeType' => 'S'
            ),
            'RangeKeyElement' => array(
                'AttributeName' => 'title',
                'AttributeType' => 'S'
            )
        );
        
        $query = array(
            'conditions' => array('Model1.id X'=>'post-2013-01-01_2')
        );
        
        $this->assertFalse($this->DynamoDB->_readWithQuery($this->Model1, $query));
        
        $query = array(
            'fields' => array('id', 'title'),
            'limit' => 2,
            'count' => true,
            'consistentRead' => true,
            'scanIndexForward' => true,
            'exclusiveStartKey' => 'post-2013-01-01_1',
            'conditions' => array(
                'Model1.id' => 'post-2013-01-01_2',
                'Model1.title <>' => 'The super story'
            )
        );
        $this->AmazonDynamoDB->setReturnValue('query', true);
        $this->assertTrue($this->DynamoDB->_readWithQuery($this->Model1, $query));
        
    }
    
    /**
     * Test _readWithScan
     *
     * @return void
     */
    public function testReadWithScan() {
        
        $query = array(
            'conditions' => array('id X'=>'post-2013-01-01_2')
        );
        
        $this->assertFalse($this->DynamoDB->_readWithScan($this->Model1, $query));
        
        $query = array(
            'fields' => array('id', 'title'),
            'limit' => 2,
            'count' => true,
            'exclusiveStartKey' => 'post-2013-01-01_1',
            'conditions' => array('Model1.id' => 'post-2013-01-01_2')
        );
        $this->AmazonDynamoDB->setReturnValue('scan', true);
        $this->assertTrue($this->DynamoDB->_readWithScan($this->Model1, $query));
        
    }
    
    /**
     * Test _getConditions
     *
     * @return void
     */
    public function testGetConditions() {
        
        $conditions = array();
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, array());
        
        $conditions = array('OR'=>array());
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, array());
        
        $conditions = array('title X'=>'The super story');
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, array());
        
        $conditions = array('title ='=>'The super story');
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_EQUAL,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_STRING => 'The super story')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.title'=>'The super story');
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_EQUAL,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_STRING => 'The super story')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.title ='=>'The super story');
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_EQUAL,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_STRING => 'The super story')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.title !='=>'The super story');
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_NOT_EQUAL,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_STRING => 'The super story')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.title <>'=>'The super story');
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_NOT_EQUAL,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_STRING => 'The super story')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.rev >'=>1);
        $expected = array(
            'rev' => array(
                'operator' => AmazonDynamoDB::CONDITION_GREATER_THAN,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_NUMBER => '1')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.rev >='=>1);
        $expected = array(
            'rev' => array(
                'operator' => AmazonDynamoDB::CONDITION_GREATER_THAN_OR_EQUAL,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_NUMBER => '1')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.rev <'=>1);
        $expected = array(
            'rev' => array(
                'operator' => AmazonDynamoDB::CONDITION_LESS_THAN,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_NUMBER => '1')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.rev <='=>1);
        $expected = array(
            'rev' => array(
                'operator' => AmazonDynamoDB::CONDITION_LESS_THAN_OR_EQUAL,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_NUMBER => '1')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.title NULL'=>1);
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_NULL
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.title NOT NULL'=>1);
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_NOT_NULL
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.tags CONTAINS'=>array('ONE', 'TWO', 'THREE'));
        $expected = array(
            'tags' => array(
                'operator' => AmazonDynamoDB::CONDITION_CONTAINS,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_STRING => 'ONE'),
                    array(AmazonDynamoDB::TYPE_STRING => 'TWO'),
                    array(AmazonDynamoDB::TYPE_STRING => 'THREE')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.tags CONTAINS'=>'ONE');
        $expected = array(
            'tags' => array(
                'operator' => AmazonDynamoDB::CONDITION_CONTAINS,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_STRING => 'ONE')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.tags DOESNT CONTAINS'=>array('ONE', 'TWO', 'THREE'));
        $expected = array(
            'tags' => array(
                'operator' => AmazonDynamoDB::CONDITION_DOESNT_CONTAIN,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_STRING => 'ONE'),
                    array(AmazonDynamoDB::TYPE_STRING => 'TWO'),
                    array(AmazonDynamoDB::TYPE_STRING => 'THREE')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.tags IN'=>array('ONE', 'TWO', 'THREE'));
        $expected = array(
            'tags' => array(
                'operator' => AmazonDynamoDB::CONDITION_IN,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_STRING => 'ONE'),
                    array(AmazonDynamoDB::TYPE_STRING => 'TWO'),
                    array(AmazonDynamoDB::TYPE_STRING => 'THREE')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.rev BETWEEN'=>array(1, 5));
        $expected = array(
            'rev' => array(
                'operator' => AmazonDynamoDB::CONDITION_BETWEEN,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_NUMBER => '1'),
                    array(AmazonDynamoDB::TYPE_NUMBER => '5')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
        
        $conditions = array('Post.title BEGINS WITH'=>'The super');
        $expected = array(
            'title' => array(
                'operator' => AmazonDynamoDB::CONDITION_BEGINS_WITH,
                'value' => array(
                    array(AmazonDynamoDB::TYPE_STRING => 'The super')
                )
            )
        );
        $result = $this->DynamoDB->_getConditions($this->Model1, $conditions);
        $this->assertEqual($result, $expected);
    }
    
    /**
     * Test _parseItem
     *
     * @return void
     */
    public function testParseItem() {
        
        $data = new stdClass();
        $this->assertFalse($this->DynamoDB->_parseItem($this->Model1, $data));
        
        $response = array(
            'item1' => array('N' => 1),
            'item2' => array('N' => 2),
            'item3' => array('N' => 3)
        );
        $data = new stdClass();
        $data->body->Item = $response;
        $result = $this->DynamoDB->_parseItem($this->Model1, $data);
        $expected = array(array($this->Model1->alias => array(
            'item1' => 1,
            'item2' => 2,
            'item3' => 3
        )));
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test _parseItems
     *
     * @return void
     */
    public function testParseItems() {
        
        $data = new stdClass();
        $this->assertFalse($this->DynamoDB->_parseItems($this->Model1, $data));
        
        $response = array(
            array('item1' => array('N' => 1)),
            array('item2' => array('N' => 2)),
            array('item3' => array('N' => 3))
        );
        $data = new stdClass();
        $data->body->Count = 3;
        $data->body->Items = $response;
        $result = $this->DynamoDB->_parseItems($this->Model1, $data);
        $expected = array(
            array($this->Model1->alias => array('item1' => 1)),
            array($this->Model1->alias => array('item2' => 2)),
            array($this->Model1->alias => array('item3' => 3))
        );
        $this->assertEqual($result, $expected);
        
        $response = array(
            'item1' => array('N' => 1)
        );
        $data = new stdClass();
        $data->body->Count = 1;
        $data->body->Items = $response;
        $result = $this->DynamoDB->_parseItems($this->Model1, $data);
        $expected = array(
            array($this->Model1->alias => array('item1' => 1))
        );
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test _parseTable
     *
     * @return void
     */
    public function testParseTable() {
        
        $data = new stdClass();
        $this->assertFalse($this->DynamoDB->_parseTable($this->Model1, $data));
        
        $expected = array(
            'item1' => 1,
            'item2' => 2,
            'item3' => 3
        );
        
        $data = new stdClass();
        $data->body->Table = (object)$expected;
        $result = $this->DynamoDB->_parseTable($this->Model1, $data);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test _parseTableNames
     *
     * @return void
     */
    public function testParseTableNames() {
        
        $response = (object) array(
            'body' => array(
                'TableNames' => array(
                    0 => 'Table1',
                    1 => 'Table2',
                    2 => 'Table3'
                )
            ),
            'status' => 200
        );
        
        $results = $this->DynamoDB->_parseTableNames($response);
        $this->assertEqual($results, $response->body['TableNames']);
        
        unset($response->body['TableNames']);
        $this->assertFalse($this->DynamoDB->_parseTableNames($response));
        
    }
    
    /**
     * Test _castValue
     *
     * @return void
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
     * Test _toArray
     *
     * @return void
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
     * @return void
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
    
    /**
     * Test _setHashPrimaryKey
     *
     * @return void
     */
    public function testSetHashPrimaryKey() {
        
        $data = array(
            'id'    => 100,
            'rev'   => 2,
            'title' => 'The super story',
            'text'  => 'The super story is a test'
        );
        
        $this->Model1->primaryKeySchema = array(
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
        $result = $this->DynamoDB->_setHashPrimaryKey($this->Model1, $data);
        $this->assertEqual($result, $expected);
        
        $this->Model1->primaryKeySchema = array(
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
        $result = $this->DynamoDB->_setHashPrimaryKey($this->Model1, $data);
        $this->assertEqual($result, $expected);
        
        $this->Model1->primaryKeySchema = array(
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
        $result = $this->DynamoDB->_setHashPrimaryKey($this->Model1, $data);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test _setRangePrimaryKey
     *
     * @return void
     */
    public function testSetRangePrimaryKey() {
        
        $data = array(
            'id'    => 100,
            'rev'   => 2,
            'title' => 'The super story',
            'text'  => 'The super story is a test'
        );
        
        $this->Model1->primaryKeySchema = array(
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
        $result = $this->DynamoDB->_setRangePrimaryKey($this->Model1, $data);
        $this->assertEqual($result, $expected);
        
        $this->Model1->primaryKeySchema = array(
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
        $result = $this->DynamoDB->_setRangePrimaryKey($this->Model1, $data);
        $this->assertEqual($result, $expected);
        
        $this->Model1->primaryKeySchema = array(
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
        $result = $this->DynamoDB->_setRangePrimaryKey($this->Model1, $data);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test _setStringPrimaryKeyValue
     *
     * @return void
     */
    public function testSetStringPrimaryKey() {
        
        $data = array(
            'id' => '550e8400-e29b-41d4-a716-446655440000',
        );
        $expected = array(AmazonDynamoDB::TYPE_STRING => $data['id']);
        $result = $this->DynamoDB->_setStringPrimaryKeyValue(
            $this->Model1,
            'id',
            $data
        );
        $this->assertEqual($result, $expected);
        
        $data = array(
            'Model1.id' => '550e8400-e29b-41d4-a716-446655440000',
        );
        $expected = array(AmazonDynamoDB::TYPE_STRING => $data['Model1.id']);
        $result = $this->DynamoDB->_setStringPrimaryKeyValue(
            $this->Model1,
            'id',
            $data
        );
        $this->assertEqual($result, $expected);
        
        $data = array(
            'rev'   => 2,
            'title' => 'The super story',
            'text'  => 'The super story is a test'
        );
        $result = $this->DynamoDB->_setStringPrimaryKeyValue($this->Model1, 'id', $data);
        $this->assertNotNull($result[AmazonDynamoDB::TYPE_STRING]);
        
    }
    
    /**
     * Test _setNumberPrimaryKeyValue
     *
     * @return void
     */
    public function testSetNumberPrimaryKey() {
        
        $data = array(
            'id' => 100,
        );
        $expected = array(AmazonDynamoDB::TYPE_NUMBER => $data['id']);
        $result = $this->DynamoDB->_setNumberPrimaryKeyValue(
            $this->Model1,
            'id',
            $data
        );
        $this->assertEqual($result, $expected);
        
        $data = array(
            'Model1.id' => 100,
        );
        $expected = array(AmazonDynamoDB::TYPE_NUMBER => $data['Model1.id']);
        $result = $this->DynamoDB->_setNumberPrimaryKeyValue(
            $this->Model1,
            'id',
            $data
        );
        $this->assertEqual($result, $expected);
        
        $data = array(
            'rev'   => 2,
            'title' => 'The super story',
            'text'  => 'The super story is a test'
        );
        $result = $this->DynamoDB->_setNumberPrimaryKeyValue($this->Model1, 'id', $data);
        $this->assertNotNull($result[AmazonDynamoDB::TYPE_NUMBER]);
        
    }
    
    /**
     * Test _setBinaryPrimaryKeyValue
     *
     * @return void
     */
    public function testSetBinaryPrimaryKey() {
        
        $data = array(
            'id' => 100,
        );
        $expected = array(AmazonDynamoDB::TYPE_BINARY => $data['id']);
        $result = $this->DynamoDB->_setBinaryPrimaryKeyValue(
            $this->Model1,
            'id',
            $data
        );
        $this->assertEqual($result, $expected);
        
        $data = array(
            'Model1.id' => 100,
        );
        $expected = array(AmazonDynamoDB::TYPE_BINARY => $data['Model1.id']);
        $result = $this->DynamoDB->_setBinaryPrimaryKeyValue(
            $this->Model1,
            'id',
            $data
        );
        $this->assertEqual($result, $expected);
        
        $data = array(
            'rev'   => 2,
            'title' => 'The super story',
            'text'  => 'The super story is a test'
        );
        $result = $this->DynamoDB->_setBinaryPrimaryKeyValue($this->Model1, 'id', $data);
        $this->assertNotNull($result[AmazonDynamoDB::TYPE_BINARY]);
        
    }
    
    /**
     * Test _setAttributeUpdates
     *
     * @return void
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
        $result = $this->DynamoDB->_setAttributeUpdates($this->Model1, $data);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test setValueTypes
     *
     * @return void
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
     * @return void
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
     * End Test
     *
     * @return void
     *
     */
    public function endTest() {
        
        unset($this->AmazonDynamoDB);
        unset($this->DynamoDB);
        unset($this->Model1);
        ClassRegistry::flush();
        
    }
    
}