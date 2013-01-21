<?php
/**
 * CloudSearch DataSource Test File
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
App::import('Datasource', 'AWS.CloudSearchSource');
App::import('Core', 'HttpSocket');
Mock::generate('HttpSocket');

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
        'datasource' => 'AWS.CloudSearchSource',
        'search_endpoint' => 'test.search_endpoint',
        'document_endpoint' => 'test.document_endpoint',
        'api_version' => '1'
    );
    
    /**
     * Test start
     *
     * @return void
     */
    public function startTest() {
        
        if (empty($this->CloudSearch)) {
            $this->CloudSearch = new CloudSearchSource($this->config);
            $this->CloudSearch->Http = new MockHttpSocket();
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
        $this->CloudSearch = null;
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
            'datasource' => 'AWS.CloudSearchSource',
            'search_endpoint' => 'search-tests-px2qjztrvfmtcvmik3ohbdq6vy.us-east-1.cloudsearch.amazonaws.com',
            'document_endpoint' => 'doc-tests-px2qjztrvfmtcvmik3ohbdq6vy.us-east-1.cloudsearch.amazonaws.com',
            'api_version' => '2011-02-01'
        );
        $this->CloudSearch->setConfig($config);
        $this->assertEqual($this->CloudSearch->config, $config);
        
    }
    
    /**
     * Test setHttpSocket
     *
     * @return void
     */
    public function testSetHttpSocket() {
        
        $this->assertEqual(
            $this->CloudSearch->Http,
            $this->CloudSearch->setHttpSocket()
        );
        
    }
    
    /**
     * Test calculate
     *
     * @return void
     */
    public function testCalculate() {
        
        $this->assertTrue($this->CloudSearch->calculate());
        
    }
    
    /**
     * Test create
     *
     * @return void
     */
    public function testCreate() {
        
        $this->CloudSearch->Http = false;
        $this->assertFalse($this->CloudSearch->create($this->Model));
        $this->CloudSearch->Http = new MockHttpSocket();
        
        $id = uniqid();
        $data = array(
            'id' => $id,
            'title' => 'Movie '.$id,
            'director' => 'CakePHP, PHP',
            'genre' => array('Programming', 'Data'),
            'actor' => array('CloudSearch', 'AWS Plugin')
        );
        
        $this->Model->data = $data;
        $response = '{"status": "success", "adds": 1, "deletes": 0}';
        $this->CloudSearch->Http->setReturnValueAt(0, 'post', $response);
        $this->assertTrue($this->CloudSearch->create($this->Model));
        
        $this->Model->data = $data;
        $response = '{"status": "error", "adds": 0, "deletes": 0}';
        $this->CloudSearch->Http->setReturnValueAt(1, 'post', $response);
        $this->assertFalse($this->CloudSearch->create($this->Model));
        
        $this->Model->data = $data;
        $fields = array_keys($data);
        $response = '{"status": "success", "adds": 1, "deletes": 0}';
        $this->CloudSearch->Http->setReturnValueAt(2, 'post', $response);
        $this->assertTrue($this->CloudSearch->create($this->Model, $fields));
        
        unset($data['id']);
        $this->Model->data = $data;
        $response = '{"status": "success", "adds": 1, "deletes": 0}';
        $this->CloudSearch->Http->setReturnValueAt(3, 'post', $response);
        $this->assertTrue($this->CloudSearch->create($this->Model, $fields));
        
        $this->Model->data = $data;
        $response = '{"status": "error", "adds": 0, "deletes": 0}';
        $this->CloudSearch->Http->setReturnValueAt(4, 'post', $response);
        $this->assertFalse($this->CloudSearch->create($this->Model, $fields));
        
        $id = uniqid();
        $version = time();
        $data = array(
            'id' => $id,
            'version' => $version,
            'title' => 'Movie '.$id,
            'director' => 'CakePHP, PHP',
            'genre' => array('Programming', 'Data'),
            'actor' => array('CloudSearch', 'AWS Plugin')
        );
        $this->Model->data = $data;
        $expected = '[{"type":"add","id":"'. $data['id'] .'","version":'. $data['version'] .',"lang":"en","fields":{"title":"Movie '. $data['id'] .'","director":"CakePHP, PHP","genre":["Programming","Data"],"actor":["CloudSearch","AWS Plugin"]}}]';
        $this->CloudSearch->Http->expectAt(5, 'post', array('*', $expected, '*'));
        $this->CloudSearch->create($this->Model);
        
    }
    
    /**
     * Test read
     *
     * @return void
     */
    public function testRead() {
        
        $this->CloudSearch->Http = false;
        $this->assertFalse($this->CloudSearch->read($this->Model));
        $this->CloudSearch->Http = new MockHttpSocket();
        
        $query = array('conditions'=>array('Model.id'=>'tt1408101'));
        $response = '{"rank":"-text_relevance","match-expr":"(label docid:\'tt1408101\')","hits":{"found":1,"start":0,"hit":[{"id":"tt1408101"}]},"info":{"rid":"1d64c0a48f50ba1f61a1d466d92171192721d909a0d01e7f9fbdb5e77957166a99690fb2e25626dc","time-ms":3,"cpu-time-ms":0}}';
        $this->CloudSearch->Http->setReturnValueAt(0, 'get', $response);
        $result = $this->CloudSearch->read($this->Model, $query);
        $expected = array(array('id'=>'tt1408101'));
        $this->assertEqual($result, $expected);
        
        $query = array('conditions'=>array());
        $this->expectError(__('Empty query string', true));
        $this->CloudSearch->read($this->Model, $query);
        
        $query = array('conditions' => array('Model.id'=>'tt1408102'));
        $this->Model->findQueryType = 'first';
        $expected = array('bq' => 'docid:\'tt1408102\'', 'size'=>1);
        $this->CloudSearch->Http->expectAt(0, 'get', array('*', $expected, '*'));
        $this->CloudSearch->read($this->Model, $query);
        
        $query = array(
            'conditions' => array('Model.id'=>'tt1408102'),
            'page' => 5
        );
        $this->Model->findQueryType = 'all';
        $expected = array('bq' => 'docid:\'tt1408102\'', 'start'=>5);
        $this->CloudSearch->Http->expectAt(1, 'get', array('*', $expected, '*'));
        
        $this->CloudSearch->read($this->Model, $query);
        
        $query = array('conditions'=>array('Model.id'=>'tt1408101'));
        $this->Model->findQueryType = 'count';
        $response = '{"rank":"-text_relevance","match-expr":"(label docid:\'tt1408101\')","hits":{"found":1,"start":0,"hit":[{"id":"tt1408101"}]},"info":{"rid":"1d64c0a48f50ba1f61a1d466d92171192721d909a0d01e7f9fbdb5e77957166a99690fb2e25626dc","time-ms":3,"cpu-time-ms":0}}';
        $this->CloudSearch->Http->setReturnValueAt(3, 'get', $response);
        $result = $this->CloudSearch->read($this->Model, $query);
        $expected = array(array(array('count'=>1)));
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test update
     *
     * @return void
     */
    public function testUpdate() {
        
        $this->CloudSearch->Http = false;
        $this->assertFalse($this->CloudSearch->update($this->Model));
        $this->CloudSearch->Http = new MockHttpSocket();
        
        $this->Model->data = array();
        $this->expectError(__('The document ID is required for updates', true));
        $this->CloudSearch->update($this->Model);
        
        $version = time();
        $this->Model->data = array(
            'id' => 'tt1408101',
            'title' => 'Untitled Star Trek Sequel (updated)',
            'version' => $version
        );
        $expected = '[{"type":"add","id":"tt1408101","version":';
        $expected.= $version. ',"lang":"en","fields":{"title":"Untitled Star Trek Sequel (updated)"}}]';
        $this->CloudSearch->Http->expectAt(0, 'post', array('*', $expected, '*'));
        $this->CloudSearch->update($this->Model);
        
        $version = time();
        $data = array(
            'id' => 'tt1408101',
            'title' => 'Untitled Star Trek Sequel (updated)',
            'version' => $version
        );
        $expected = '[{"type":"add","id":"tt1408101","version":';
        $expected.= $version. ',"lang":"en","fields":{"title":"Untitled Star Trek Sequel (updated)"}}]';
        $fields = array_keys($data);
        $values = array_values($data);
        $this->CloudSearch->Http->expectAt(0, 'post', array('*', $expected, '*'));
        $this->CloudSearch->update($this->Model, $fields, $values);
        
        // can't verify if version is begin set when not passed
        $response = '{"status": "success", "adds": 1, "deletes": 0}';
        $this->Model->data = array(
            'id' => 'tt1408101',
            'title' => 'Untitled Star Trek Sequel (updated)',
        );
        $this->CloudSearch->Http->setReturnValueAt(2, 'post', $response);
        $this->assertTrue($this->CloudSearch->update($this->Model));
        
    }
    
    /**
     * Test delete
     *
     * @return void
     */
    public function testDelete() {
        
        $this->CloudSearch->Http = false;
        $this->assertFalse($this->CloudSearch->delete($this->Model));
        $this->CloudSearch->Http = new MockHttpSocket();
        
        $conditions = array('key1'=>'val1', 'key2'=>'val2');
        $this->expectError(__('Conditional delete are not supported yet...', true));
        $this->CloudSearch->delete($this->Model, $conditions);
        
        $conditions = array('key1'=>'val1');
        $this->expectError(__('Document ID is required for delete', true));
        $this->CloudSearch->delete($this->Model, $conditions);
        
        $response = '{"status": "error", "adds": 0, "deletes": 0}';
        $conditions = array('Model.id'=>'tt1408101');
        $this->CloudSearch->Http->setReturnValueAt(0, 'post', $response);
        $this->assertFalse($this->CloudSearch->delete($this->Model, $conditions));
        
        $version = time();
        $conditions = array('Model.id'=>'tt1408101');
        $response = '{"status": "success", "adds": 0, "deletes": 1}';
        $expected = array(
            'type' => 'delete',
            'id' => 'tt1408101',
            'version' => $version
        );
        $this->CloudSearch->Http->setReturnValueAt(1, 'post', $response);
        $this->CloudSearch->Http->expectAt(2, 'post', array('*', $expected, '*'));
        $this->assertTrue($this->CloudSearch->delete($this->Model, $conditions));
        
    }
    
    /**
     * Test query
     *
     * @return void
     */
    public function testQuery() {
        
        $method = 'this_method_do_not_exists';
        $this->expectError(__('Invalid method call: '.$method, true));
        $this->CloudSearch->query($method, array());
        
        $params[] = array(
            'type' => 'delete',
            'id' => 'tt1408105',
            'version' => 1
        );
        $response = '{"status": "success", "adds": 0, "deletes": 1}';
        $this->CloudSearch->Http->setReturnValueAt(0, 'post', $response);
        $result = $this->CloudSearch->query('document', $params);
        $expected = array(
            'status' => 'success',
            'adds' => 0,
            'deletes' => 1
        );
        $this->assertEqual($result, $expected);
        
        $this->expectError(__('findBy not supported', true));
        $this->CloudSearch->query('findByTitle', array(), $this->Model);
        
    }
    
    /**
     * Test search
     *
     * @return void
     */
    public function testSearch() {
        
        $this->expectError(__('Invalid search parameters', true));
        $this->CloudSearch->search(array());
        
        $url = sprintf(
            'https://%s/%s/search',
            $this->config['search_endpoint'],
            $this->config['api_version']
        );
        $params = array('bq'=>'docid:\'tt1408101\'');
        $response = '{"rank":"-text_relevance","match-expr":"(label docid:\'tt1408101\')","hits":{"found":1,"start":0,"hit":[{"id":"tt1408101"}]},"info":{"rid":"1d64c0a48f50ba1f61a1d466d92171192721d909a0d01e7f9fbdb5e77957166a99690fb2e25626dc","time-ms":3,"cpu-time-ms":0}}';
        $request = array('header' => array('Content-Type' => 'application/json'));
        $this->CloudSearch->Http->setReturnValueAt(1, 'get', $response);
        $this->CloudSearch->Http->expectAt(1, 'get', array($url, $params, $request));
        $result = $this->CloudSearch->search($params);
        $expected = array(array('id'=>'tt1408101'));
        $this->assertEqual($expected, $result);
        
        $params = array('bq'=>'docid:\'tt1408101\'');
        $response = 'this_is_not_an_object';
        $this->CloudSearch->Http->setReturnValueAt(2, 'get', $response);
        $this->assertFalse($this->CloudSearch->search($params));
        
    }
    
    /**
     * Test document
     *
     * @return void
     */
    public function testDocument() {
        
        $this->expectError(__('Invalid document parameters', true));
        $this->CloudSearch->document();
        
        $url = sprintf(
            'https://%s/%s/documents/batch',
            $this->config['document_endpoint'],
            $this->config['api_version']
        );
        $params = array(
            'type' => 'delete',
            'id' => 'tt0000001',
            'version' => 1
        );
        $response = '{"status": "success", "adds": 0, "deletes": 1}';
        $request = array('header' => array('Content-Type' => 'application/json'));
        $this->CloudSearch->Http->setReturnValueAt(1, 'post', $response);
        $this->CloudSearch->Http->expectAt(1, 'post', array($url, json_encode($params), $request));
        $result = $this->CloudSearch->document($params);
        $expected = array(
            'status' => 'success',
            'adds' => 0,
            'deletes' => 1
        );
        $this->assertEqual($expected, $result);
        
        $params = array(
            'type' => 'delete',
            'id' => 'tt0000002',
            'version' => 1
        );
        $response = 'this_is_not_an_object';
        $this->CloudSearch->Http->setReturnValueAt(2, 'post', $response);
        $this->assertFalse($this->CloudSearch->document($params));
        
        $params = array();
        $params[] = array(
            'type' => 'delete',
            'id' => 'tt0000003',
            'version' => 1
        );
        $params[] = array(
            'type' => 'delete',
            'id' => 'tt0000004',
            'version' => 1
        );
        $response = '{"status": "success", "adds": 0, "deletes": 2}';
        $request = array('header' => array('Content-Type' => 'application/json'));
        $this->CloudSearch->Http->setReturnValueAt(3, 'post', $response);
        $this->CloudSearch->Http->expectAt(3, 'post', array($url, json_encode($params), $request));
        $result = $this->CloudSearch->document(array($params));
        $expected = array(
            'status' => 'success',
            'adds' => 0,
            'deletes' => 2
        );
        $this->assertEqual($expected, $result);
        
    }
    
    public function testFindBy() {
        
        $this->expectError(__('findBy not supported', true));
        $this->CloudSearch->findBy(null, array(), $this->Model);
        
    }
    
    /**
     * Test searching text fields conditions
     *
     * @link http://docs.aws.amazon.com/cloudsearch/latest/developerguide/searching.text.html
     * @return void
     */
    public function testSearchingTextFieldsConditions() {
        
        // search?bq='star'
        // searches the term with boolean query
        $conditions = array('bq'=>'star');
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, $conditions);
        
        // search?q=star
        // searches the termwith query
        $conditions = array(
            'q' => 'star'
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, $conditions);
        
        $conditions = 'star';
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('q'=>'star'));
        
        $conditions = array('star');
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>'star'));
        
        // search?bq=title:'star'
        // searches the title field of each document and matches all 
        // documents whose titles contain the term star
        $conditions = array(
            'title' => 'star'
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>'title:star'));
        
        // search?q=star|wars
        // matches movies that contain either star or wars in 
        // the default search field.
        $conditions = array('star|wars');
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>'star|wars'));
        
        // search?bq=title:'story funny|underdog'
        // matches movies that contain both the terms story and funny 
        // or the term underdog in the title field.
        $conditions = array(
            'title' => array('story funny', 'underdog')
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>"title:'story funny|underdog'"));
        
        $conditions = array(
            'title' => 'story funny|underdog'
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>"title:'story funny|underdog'"));
        
        // search?bq=title:'red|white|blue'
        // matches movies that contain either red, white, or blue 
        // in the title field.
        $conditions = array(
            'title' => array('red', 'white', 'blue')
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>"title:red|white|blue"));
        
        $conditions = array(
            'title' => "'red|white|blue'"
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>"title:'red|white|blue'"));
        
        // search?bq=actor:'"evans, chris"|"Garity, Troy"'
        // matches movies that contain either the phrase evans, chris or 
        // the phrase Garity, Troy in the actor field.
        $conditions = array(
            'actor' => array('evans, chris', 'Garity, Troy')
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>"actor:'evans, chris|Garity, Troy'"));
        
        $conditions = array(
            'actor' => '"evans, chris"|"Garity, Troy"'
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>'actor:\'"evans, chris"|"Garity, Troy"\''));
        
        // search?bq='title:-star+war|world'
        // matches movies whose titles do not contain star, but do 
        // contain either war or world.
        $conditions = array(
            'title' => array('-star+war', 'world')
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>"title:-star+war|world"));
        
        $conditions = array(
            'title' => '-star+war|world'
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>'title:-star+war|world'));
        
        // search?bq=title:'star*'&return-fields=title
        // matches wildcards in text searches
        $conditions = array(
            'title' => "'star*'"
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>"title:'star*'"));
        
        // search?q='with love'
        // matches phrases in text fields
        $conditions = array(
            'with love'
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>"'with love'"));
        
        // search?bq="with love"
        // matches phrases in text fields
        $conditions = array(
            '"with love"'
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>'"with love"'));
        //debug($result);
        
        // search?bq='with love'
        // matches phrases in text fields
        $conditions = array(
            "'with love'"
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>"'with love'"));
        
        // http://docs.aws.amazon.com/cloudsearch/latest/developerguide/searching.literal.html
        // searching uint fields conditions
        
        // search?bq=genre:'with love'
        $conditions = array(
            'genre' => "'sci-fi action'"
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>"genre:'sci-fi action'"));
        
        $conditions = array(
            'genre' => array("'sci-fi'")
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>"genre:'sci-fi'"));
        
        // http://docs.aws.amazon.com/cloudsearch/latest/developerguide/searching.uint.html
        // searching uint fields conditions
        
        // search?bq=year:2010
        $conditions = array(
            'year' => 2010
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>'year:2010'));
        
        // search?bq=year:2008..2010
        $conditions = array(
            'year' => array(2008, 2010)
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>'year:2008..2010'));
        
        $conditions = array(
            'year' => '2008..2010'
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>'year:2008..2010'));
        
        // search?bq=year:2002..
        $conditions = array(
            'year' => array(2002, '..')
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>'year:2002..'));
        
        $conditions = array(
            'year' => '2002..'
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>'year:2002..'));
        
        //search?bq=year:..1970
        $conditions = array(
            'year' => array('..', 1970)
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>'year:..1970'));
        
        $conditions = array(
            'year' => '..1970'
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual($result, array('bq'=>'year:..1970'));
        
        // http://docs.aws.amazon.com/cloudsearch/latest/developerguide/searching.uint.html
        // boolean search conditions
        // search?bq=(and title:'star' genre:'drama')
        $conditions = array(
            'and' => array('title'=>'star', 'genre'=>'drama')
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual(
            $result,
            array('bq'=>"(and title:'star' genre:'drama')")
        );
        
        $conditions = array(
            '(and title:\'star\' genre:\'drama\')'
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual(
            $result,
            array('bq'=>"(and title:'star' genre:'drama')")
        );
        
        // search?bq=(or title:'star' (not title:'wars'))
        $conditions = array(
            'or' => array('title'=>'star', array('not'=>array('title'=>'wars')))
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual(
            $result,
            array('bq'=>"(or title:'star' (not title:'wars'))")
        );
        
        $conditions = array(
            'or' => array('title'=>'star', '(not title:\'wars\')')
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual(
            $result,
            array('bq'=>"(or title:'star' (not title:'wars'))")
        );
        
        // search?bq=(or title:'star' title:'-wars')
        $conditions = array(
            'or' => array('title'=>'star', "title:'-wars'")
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual(
            $result,
            array('bq'=>"(or title:'star' title:'-wars')")
        );
        
        $conditions = array(
            'or' => array('title:\'star\' title:\'-wars\'')
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual(
            $result,
            array('bq'=>"(or title:'star' title:'-wars')")
        );
        
        // search?bq=(or title:'star' (not title:'wars'))
        $conditions = array(
            'or' => array('title'=>'star', array('not'=>array('title'=>'wars')))
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual(
            $result,
            array('bq'=>"(or title:'star' (not title:'wars'))")
        );
        
        $conditions = array(
            'or' => array('title'=>'star', '(not title:\'wars\')')
        );
        $result = $this->CloudSearch->_conditions($conditions);
        $this->assertEqual(
            $result,
            array('bq'=>"(or title:'star' (not title:'wars'))")
        );
        
    }
    
    /**
     * Test getting results as XML
     *
     * @link http://docs.aws.amazon.com/cloudsearch/latest/developerguide/gettingxmlresults.html
     * @return void
     */
    public function testGettingResultsAsXML() {
        
        // search?q=star+wars&results-type=xml
        $query = array(
            'conditions' => array(
                'title' => '-star+war|world'
            ),
            'results-type' => 'xml'
        );
        $results = $this->CloudSearch->_query($query);
        $this->assertEqual($results, $query);
        
    }
    
    /**
     * Test paginating results
     *
     * @link http://docs.aws.amazon.com/cloudsearch/latest/developerguide/pagination.html
     * @return void
     */
    public function testPaginatingResults() {
        
        // search?q=-star&start=10
        $query = array(
            'conditions' => array('-star'),
            'start' => 10
        );
        $this->assertEqual($this->CloudSearch->_query($query), $query);
        
        // search?q=-star&size=25
        $query = array(
            'conditions' => array('-star'),
            'size' => 25
        );
        $this->assertEqual($this->CloudSearch->_query($query), $query);
        
        // search?q=-star&size=25&start=50
        $query = array(
            'conditions' => array('-star'),
            'size' => 25,
            'start' => 50
        );
        $this->assertEqual($this->CloudSearch->_query($query), $query);
        
    }
    
    /**
     * Test retrieving data from index fields
     *
     * @link http://docs.aws.amazon.com/cloudsearch/latest/developerguide/retrievingdata.html
     * @return void
     */
    public function testRetrievingDataFromIndexFields() {
        
        // search?q=star+wars&return-fields=actor,title,text_relevance
        $query = array(
            'conditions' => array('star', '+wars'),
            'return-fields' => array('actor', 'title', 'text_relevance')
        );
        $expected = array(
            'conditions' => array('star', '+wars'),
            'return-fields' => 'actor,title,text_relevance'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test sorting results
     *
     * @link http://docs.aws.amazon.com/cloudsearch/latest/developerguide/sortingresults.html
     * @return void
     */
    public function testSortResults() {
        
        // search?q=star+wars&return-fields=title&rank=title
        $query = array(
            'conditions' => array('star', '+wars'),
            'return-fields' => array('title'),
            'rank' => 'title'
        );
        $expected = array(
            'conditions' => array('star', '+wars'),
            'return-fields' => 'title',
            'rank' => 'title'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
        // search?q=star+wars&rank=-title
        $query = array(
            'conditions' => array('star', '+wars'),
            'rank' => '-title'
        );
        $expected = array(
            'conditions' => array('star', '+wars'),
            'rank' => '-title'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
        // search?q=star+wars&return-fields=title,year&rank=-year
        $query = array(
            'conditions' => array('star', '+wars'),
            'return-fields' => array('title', 'year'),
            'rank' => '-year'
        );
        $expected = array(
            'conditions' => array('star', '+wars'),
            'return-fields' => 'title,year',
            'rank' => '-year'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test getting facet information for text and literal fields
     *
     * @link http://docs.aws.amazon.com/cloudsearch/latest/developerguide/faceting.text.html
     * @return void
     */
    public function testGettingFacetInformationForTextAndLiteralFields() {
        
        // search?bq=title:'star'&facet=genre&facet-genre-top-n=5
        $query = array(
            'conditions' => array('title'=>'star'),
            'facet' => 'genre',
            'facet-genre-top-n' => 5
        );
        $expected = array(
            'conditions' => array('title'=>'star'),
            'facet' => 'genre',
            'facet-genre-top-n' => 5
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test getting facet information for uint fields
     *
     * @link http://docs.aws.amazon.com/cloudsearch/latest/developerguide/faceting.uint.html
     * @return void
     */
    public function testGettingFacetInformationForUintFields() {
        
        // "facets":{"year":{"min":1974,"max":2012}}
        
    }
    
    /**
     * Test getting facet information for particular values
     *
     * @link http://docs.aws.amazon.com/cloudsearch/latest/developerguide/faceting.constraints.html
     * @return void
     */
    public function testGettingFacetInformationForParticularValues() {
        
        // search?q=star&facet=genre&facet-genre-constraints='Drama','Sci-Fi'
        $query = array(
            'conditions' => array('star'),
            'facet' => 'genre',
            'facet-genre-constraints' => array('Drama', 'Sci-Fi')
        );
        $expected = array(
            'conditions' => array('star'),
            'facet' => 'genre',
            'facet-genre-constraints' => "'Drama','Sci-Fi'"
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
        // search?q=star&facet=year&facet-year-constraints=2000,2001,2002..2004,2005..
        $query = array(
            'conditions' => array('star'),
            'facet' => 'year',
            'facet-year-constraints' => array(2000,2001,'2002..2004','2005')
        );
        $expected = array(
            'conditions' => array('star'),
            'facet' => 'year',
            'facet-year-constraints' => "'2000','2001','2002..2004','2005'"
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test sorting facet information
     *
     * @link http://docs.aws.amazon.com/cloudsearch/latest/developerguide/faceting.sorting.html
     * @return void
     */
    public function testSortingFacetInformation() {
        
        // search?bq=title:'star'&facet=genre&facet-genre-sort=alpha
        $query = array(
            'conditions' => array('title'=>'star'),
            'facet' => 'genre',
            'facet-genre-sort' => 'alpha'
        );
        $expected = array(
            'conditions' => array('title'=>'star'),
            'facet' => 'genre',
            'facet-genre-sort' => 'alpha'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
        // search?bq=title:'star'&facet=genre&facet-genre-sort=-max(text_relevance)
        $query = array(
            'conditions' => array('title'=>'star'),
            'facet' => 'genre',
            'facet-genre-sort' => '-max(text_relevance)'
        );
        $expected = array(
            'conditions' => array('title'=>'star'),
            'facet' => 'genre',
            'facet-genre-sort' => '-max(text_relevance)'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
        // search?bq='state'&facet=chief&facet-chief-sort=sum(majvotes)
        $query = array(
            'conditions' => array('state'),
            'facet' => 'chief',
            'facet-chief-sort' => 'sum(majvotes)'
        );
        $expected = array(
            'conditions' => array('state'),
            'facet' => 'chief',
            'facet-chief-sort' => 'sum(majvotes)'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test other face queries
     *
     * @return void
     */
    public function testOtherFacetQueries() {
        
        // search?q=star&facet=actor,genre&facet-actor-top-n=10
        // &facet-genre-top-n=5&size=5&results-type=xml
        $query = array(
            'conditions' => array('star'),
            'facet' => array('actor', 'genre'),
            'facet-actor-top-n' => 10,
            'facet-genre-top-n' => 5,
            'size' => 5,
            'results-type' => 'xml'
        );
        $expected = array(
            'conditions' => array('star'),
            'facet' => 'actor,genre',
            'facet-actor-top-n' => 10,
            'facet-genre-top-n' => 5,
            'size' => 5,
            'results-type' => 'xml'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
        // search?bq=(and 'star' actor:'William Shatner')&facet=actor,genre
        // &facet-actor-top-n=10&facet-genre-top-n=5&size=5
        // &results-type=xml
        $query = array(
            'conditions' => array('and'=>array('actor'=>'William Shatner')),
            'facet' => array('actor', 'genre'),
            'facet-actor-top-n' => 10,
            'facet-genre-top-n' => 5,
            'size' => 5,
            'results-type' => 'xml'
        );
        $expected = array(
            'conditions' => array('and'=>array('actor'=>'William Shatner')),
            'facet' => 'actor,genre',
            'facet-actor-top-n' => 10,
            'facet-genre-top-n' => 5,
            'size' => 5,
            'results-type' => 'xml'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
        // search?bq=(and 'star' actor:'William Shatner' actor:'Adamson, Joseph')
        // &return-fields=title&facet=actor,genre&facet-actor-top-n=10
        // &facet-genre-top-n=5&size=5&results-type=xml
        $query = array(
            'conditions' => array(
                'and' => array(
                    'star',
                    'actor' => 'William Shatner',
                    'actor' => 'Adamson, Joseph'
                )
            ),
            'return-fields' => 'title',
            'facet' => array('actor', 'genre'),
            'facet-actor-top-n' => 10,
            'facet-genre-top-n' => 5,
            'size' => 5,
            'results-type' => 'xml'
        );
        $expected = array(
            'conditions' => array(
                'and' => array(
                    'star',
                    'actor' => 'William Shatner',
                    'actor' => 'Adamson, Joseph'
                )
            ),
            'return-fields' => 'title',
            'facet' => 'actor,genre',
            'facet-actor-top-n' => 10,
            'facet-genre-top-n' => 5,
            'size' => 5,
            'results-type' => 'xml'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test ranking customisation
     *
     * @link http://docs.aws.amazon.com/cloudsearch/latest/developerguide/tuneranking.html
     * @return void
     */
    public function testRankingCustomisation() {
        
        // search?q=terminator&rank-expression1=sin(text_relevance)
        // &rank-expression2=cos(text_relevance)&rank=expression1,expression2
        // &return-fields=title,text_relevance,expression2
        $query = array(
            'conditions' => 'terminator',
            'rank-expression1' => 'sin(text_relevance)',
            'rank-expression2' => 'cos(text_relevance)',
            'rank' => array('expression1', 'expression2'),
            'return-fields' => array('title', 'text_relevance', 'expression2')
        );
        $expected = array(
            'conditions' => 'terminator',
            'rank-expression1' => 'sin(text_relevance)',
            'rank-expression2' => 'cos(text_relevance)',
            'rank' => 'expression1,expression2',
            'return-fields' => 'title,text_relevance,expression2'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
        // search?q=star+wars&return-fields=title&rank=-popularhits
        $query = array(
            'conditions' => 'star+wars',
            'return-fields' => 'title',
            'rank' => '-popularhits'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $query);
        
        // search?q=star+wars&return-fields=title&rank=-custom_relevance&t-is_available=1
        $query = array(
            'conditions' => 'star+wars',
            'return-fields' => 'title',
            'rank' => '-custom_relevance',
            't-is_available' => 1
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $query);
        
        // search?q=star+wars&return-fields=title&t-text_relevance=500..
        $query = array(
            'conditions' => 'star+wars',
            'return-fields' => 'title',
            't-text_relevance' => '500..'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $query);
        
    }
    
    /**
     * Test _isSingleConditionArray
     *
     * @return void
     */
    public function testSingleConditionArray() {
        
        $arr = array('one', 'two');
        $this->assertFalse($this->CloudSearch->_isSingleConditionArray($arr));
        
        $arr = array('q'=>'one');
        $this->assertFalse($this->CloudSearch->_isSingleConditionArray($arr));
        
        $arr = array('bq'=>'one');
        $this->assertFalse($this->CloudSearch->_isSingleConditionArray($arr));
        
        $arr = array('title'=>'one');
        $this->assertFalse($this->CloudSearch->_isSingleConditionArray($arr));
        
        $arr = array('star');
        $this->assertTrue($this->CloudSearch->_isSingleConditionArray($arr));
        
    }
    
    /**
     * Test _isAnOperator
     *
     * @return void
     */
    public function testIsAnOperator() {
        
        $field = 'bq';
        $value = 'is_not_an_array';
        $this->assertFalse($this->CloudSearch->_isAnOperator($field, $value));
        
        $field = 'is_not_bq_q_and_or_not';
        $value = array('one');
        $this->assertFalse($this->CloudSearch->_isAnOperator($field, $value));
        
        $field = 'bq';
        $value = array('title'=>'star');
        $this->assertTrue($this->CloudSearch->_isAnOperator($field, $value));
        
    }
    
    /**
     * Test _isSingleConditionQueryOrBooleanQuery
     *
     * @return void
     */
    public function testIsSingleConditionQueryOrBooleanQuery() {
        
        $field = 'not_q_or_bq';
        $this->assertFalse($this->CloudSearch->_isSingleConditionQueryOrBooleanQuery($field));
        
        $field = 'q';
        $value = array('this is an array');
        $this->assertFalse($this->CloudSearch->_isSingleConditionQueryOrBooleanQuery($field, $value));
        
        $field = 'q';
        $value = 'star wars';
        $this->assertTrue($this->CloudSearch->_isSingleConditionQueryOrBooleanQuery($field, $value));
        
    }
    
    /**
     * Test _isAnUintSearchRangeOfValues
     *
     * @return void
     */
    public function testIsAnUintSearchRangeOfValues() {
        
        $value = 'one';
        $this->assertFalse($this->CloudSearch->_isAnUintSearchRangeOfValues($value));
        
        $value = array('one');
        $this->assertFalse($this->CloudSearch->_isAnUintSearchRangeOfValues($value));
        
        $value = array('one', 2013);
        $this->assertFalse($this->CloudSearch->_isAnUintSearchRangeOfValues($value));
        
        $value = array(2013, 'one');
        $this->assertFalse($this->CloudSearch->_isAnUintSearchRangeOfValues($value));
        
        $value = array(2001, 2013);
        $this->assertTrue($this->CloudSearch->_isAnUintSearchRangeOfValues($value));
        
    }
    
    /**
     * Test _isAnUintSearchOpenEndedValueAtStart
     *
     * @return void
     */
    public function testIsAnUintSearchOpenEndedValueAtStart() {
        
        $value = 'one';
        $this->assertFalse($this->CloudSearch->_isAnUintSearchOpenEndedValueAtStart($value));
        
        $value = array('one');
        $this->assertFalse($this->CloudSearch->_isAnUintSearchOpenEndedValueAtStart($value));
        
        $value = array('one', 'two');
        $this->assertFalse($this->CloudSearch->_isAnUintSearchOpenEndedValueAtStart($value));
        
        $value = array('one', 2013);
        $this->assertFalse($this->CloudSearch->_isAnUintSearchOpenEndedValueAtStart($value));
        
        $value = array('..', 'one');
        $this->assertFalse($this->CloudSearch->_isAnUintSearchOpenEndedValueAtStart($value));
        
        $value = array('..', 2013);
        $this->assertTrue($this->CloudSearch->_isAnUintSearchOpenEndedValueAtStart($value));
        
    }
    
    /**
     * Test _isAnUintSearchOpenEndedValueAtEnd
     *
     * @return void
     */
    public function testIsAnUintSearchOpenEndedValueAtEnd() {
        
        $value = 'one';
        $this->assertFalse($this->CloudSearch->_isAnUintSearchOpenEndedValueAtEnd($value));
        
        $value = array('one');
        $this->assertFalse($this->CloudSearch->_isAnUintSearchOpenEndedValueAtEnd($value));
        
        $value = array('one', 'two');
        $this->assertFalse($this->CloudSearch->_isAnUintSearchOpenEndedValueAtEnd($value));
        
        $value = array(2013, 'two');
        $this->assertFalse($this->CloudSearch->_isAnUintSearchOpenEndedValueAtEnd($value));
        
        $value = array(2013, '..');
        $this->assertTrue($this->CloudSearch->_isAnUintSearchOpenEndedValueAtEnd($value));
        
    }
    
    /**
     * Test _encloseQuotes
     *
     * @return void
     */
    public function testEncloseQuotes() {
        
        $string = "(or title:'star' (not title:'wars'))";
        $this->assertEqual($string, $this->CloudSearch->_encloseQuotes($string));
        
        $string = 'one';
        $this->assertEqual('one', $this->CloudSearch->_encloseQuotes($string));
        
        $string = 'one two';
        $this->assertEqual("'one two'", $this->CloudSearch->_encloseQuotes($string));
        
        $string = '"one"';
        $this->assertEqual($string, $this->CloudSearch->_encloseQuotes($string));
        
        $string = "'one'";
        $this->assertEqual($string, $this->CloudSearch->_encloseQuotes($string));
        
        $string = "'one\'s'";
        $this->assertEqual($string, $this->CloudSearch->_encloseQuotes($string));
        
        $string = '"one", "two"';
        $this->assertEqual("'{$string}'", $this->CloudSearch->_encloseQuotes($string));
        
        $string = "'one', 'two'";
        $this->assertEqual($string, $this->CloudSearch->_encloseQuotes($string));
        
        $string = '"evans, chris"|"Garity, Troy"';
        $result = $this->CloudSearch->_encloseQuotes($string);
        $this->assertEqual("'{$string}'", $this->CloudSearch->_encloseQuotes($string));
        
        $strings = array(
            'one',
            'one two',
            '"one"',
            "'one'",
            '"one", "two"',
            "'one', 'two'",
            '"evans, chris"|"Garity, Troy"'
        );
        $expected = array(
            'one',
            "'one two'",
            '"one"',
            "'one'",
            "'\"one\", \"two\"'",
            "'one', 'two'",
            "'\"evans, chris\"|\"Garity, Troy\"'"
        );
        $result = $this->CloudSearch->_encloseQuotes($strings);
        $this->assertEqual($result, $expected);
        
    }
    
    /**
     * Test _isAssociativeArray
     *
     * @return void
     */
    public function testIsAssociativeArray() {
        
        $arr = 'not_an_array';
        $this->assertFalse($this->CloudSearch->_isAssociativeArray($arr));
        
        $arr = array('a', 'b', 'c');
        $this->assertFalse($this->CloudSearch->_isAssociativeArray($arr));
        
        $arr = array("0" => 'a', "1" => 'b', "2" => 'c');
        $this->assertFalse($this->CloudSearch->_isAssociativeArray($arr));
        
        $arr = array("1" => 'a', "0" => 'b', "2" => 'c');
        $this->assertTrue($this->CloudSearch->_isAssociativeArray($arr));
        
        $arr = array("a" => 'a', "b" => 'b', "c" => 'c');
        $this->assertTrue($this->CloudSearch->_isAssociativeArray($arr));
        
    }
    
    /**
     * Test _query
     *
     * @return void
     */
    public function testQueryArray() {
        
        // test facets
        $query = array(
            'conditions' => array('title'=>'star'),
            'facet' => array('title', 'text_relevance')
        );
        $expected = array(
            'conditions' => array('title'=>'star'),
            'facet' => 'title,text_relevance'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
        // test facets constraints
        $query = array(
            'conditions' => array('title'=>'star'),
            'facet-year-constraints' => array(2001,2013),
            'facet-tags-constraints' => array('php', 'cakephp', 'amazon')
        );
        $expected = array(
            'conditions' => array('title'=>'star'),
            'facet-year-constraints' => '2001..2013',
            'facet-tags-constraints' => "'php','cakephp','amazon'",
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
        // test return fields
        $query = array(
            'conditions' => array('title'=>'star'),
            'return-fields' => array('title', 'text_relevance')
        );
        $expected = array(
            'conditions' => array('title'=>'star'),
            'return-fields' => 'title,text_relevance'
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
        // test with other parameters
        $query = array(
            'conditions' => array('title'=>'star'),
            'facet-year-constraints' => array(2001,2013),
            'facet-tags-constraints' => array('php', 'cakephp', 'amazon'),
            'start' => 25,
            'page' => 5
        );
        $expected = array(
            'conditions' => array('title'=>'star'),
            'facet-year-constraints' => '2001..2013',
            'facet-tags-constraints' => "'php','cakephp','amazon'",
            'start' => 25,
            'page' => 5
        );
        $result = $this->CloudSearch->_query($query);
        $this->assertEqual($result, $expected);
        
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
        $result = $this->CloudSearch->_toArray($data);
        $this->assertEqual($result, $expected);
        
    }
    
}