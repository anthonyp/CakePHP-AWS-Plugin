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
     * DynamoDB Datasource object
     *
     * @var object
     */
    public $DynamoDB = null;
    
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
        
        // if (!$this->created_test_tables && !empty($this->config['create_test_tables'])) {
        //     $this->assertTrue($this->_removeTestTables());
        //     $this->assertTrue($this->_createTestTables());
        //     $this->created_test_tables = true;
        // }
        // 
        // if (!$this->created_test_data && !empty($this->config['create_test_data'])) {
        //     $this->assertTrue($this->_createTestData());
        //     $this->created_test_data = true;
        // }
        
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
      * Test create
      *
      */
     public function testCreate() {
        
        $fields = array(
            'id',
            'rev',
            'title',
            'description'
        );
         
        $post = 'Post #'. rand();
        $values = array(
            uniqid(),
            rand(1,9),
            $post,
            'Description for '. $post
        );
         
        $result = $this->DynamoDB->create($this->Post, $fields, $values);
        $this->assertTrue($result);
        
     }
     
     /**
      * Test read
      *
      */
     public function testRead() {
         
         $fields = array(
             'id',
             'rev',
             'title',
             'description'
         );
         
         $post = 'Post #'. rand();
         $values = array(
             uniqid(),
             rand(1,9),
             $post,
             'Description for '. $post
         );
         $data = array_combine($fields, $values);
         $result = $this->DynamoDB->create($this->Post, $fields, $values);
         
         $this->assertTrue($result);
         
         $id = $this->Post->getLastInsertId();
         
         $query = array(
            'conditions' => array(
                'Post.id' => $id
            )
         );
         $result = $this->DynamoDB->read($this->Post, $query);
         $this->assertEqual($data, $result[0]['Post']);
         
     }
     
     /**
      * Test update
      *
      */
     public function testUpdate() {
         
     }
     
     /**
      * Test delete
      *
      */
     public function testDelete() {
         
     }
     
     /**
      * Test query
      *
      */
     public function testQuery() {
         
         $tableName = 'Reply';
         $options = array(
            'TableName' => $tableName, 
            'HashKeyValue' => array(AmazonDynamoDB::TYPE_STRING => 'Amazon DynamoDB#DynamoDB Thread 2'),
         );
         $response = $this->DynamoDB->query($options);
         $this->assertTrue(is_object($response->body->Items));
         
     }
     
     public function testQuery2() {
         
     }
     
     public function testQuery3() {
         
     }
     
     public function testQuery4() {
         
     }
     
     public function testScan() {
         
     }
     
     public function testScan2() {
         
     }
     
     public function testScan3() {
         
     }
     
     public function testScan4() {
         
     }
     
     public function testBatchGetItem() {
         
     }
     
     public function testBatchGetItemWithOptionalParameters() {
         
     }
     
     public function testBatchWriteItem() {
         
     }
     
     public function testDeleteItem() {
         
     }
     
     public function testDeleteItemWithOptionalParameters() {
         
     }
     
     public function testGetItem() {
         
     }
     
     public function testPutItem() {
         
     }
     
     public function testPutItemWithOptionalParameters() {
         
     }
     
     public function testUpdateItem() {
         
     }
     
     public function testUpdateItemWithOptionalParameters() {
         
     }
     
     public function testCreateTable() {
         
     }
     
     public function testDeleteTable() {
         
     }
     
     public function testListTables() {
         
     }
     
     public function testListTables2() {
         
     }
     
     public function testParseItem() {
         
     }
     
     public function testParseItems() {
         
     }
     
     public function testParseTable() {
         
     }
     
     public function testGetPrimaryKey() {
         
     }
     
     public function testGetPrimaryKeyValue() {
         
     }
     
     public function testSetPrimaryKey() {
         
     }
     
     public function testSetStringPrimaryKey() {
         
     }
     
     public function testSetNumberPrimaryKey() {
         
     }
     
     public function testSetBinaryPrimaryKey() {
         
     }
     
     public function testSetAttributeUpdates() {
         
     }
     
     public function testSetVarTypes() {
         
     }
     
     public function testSetVarType() {
         
     }
     
     public function testPrepareLogQuery() {
         
     }
     
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
     * @return void
     */
    public function endTest() {
        unset($this->Post);
        unset($this->DynamoDB);
        ClassRegistry::flush();
    }
    
}