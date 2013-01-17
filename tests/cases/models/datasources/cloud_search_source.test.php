<?php
/**
 * CloudSearch DataSource Test File
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
    
    /**
     * Test conditions
     *
     * @return void
     */
    public function testConditions() {
        
        
        
    }
    
}