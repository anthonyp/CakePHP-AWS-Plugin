<?php
/**
 * Movie Model Test File for CloudSearch datasource
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
class Movie extends CakeTestModel {
    
    public $name = 'Movie';
    
    public $validate = array();
    
    //public $useTable = false;
    
    public $useDbConfig = 'cloudsearch_test';
    
    public $schema = array();
    
}

class MovieTestCase extends CakeTestCase {
    
    public $config = array(
        'datasource' => 'AWS.CloudSearchSource',
        'search_endpoint' => 'search-tests-px2qjztrvfmtcvmik3ohbdq6vy.us-east-1.cloudsearch.amazonaws.com',
        'document_endpoint' => 'doc-tests-px2qjztrvfmtcvmik3ohbdq6vy.us-east-1.cloudsearch.amazonaws.com',
        'api_version' => '2011-02-01'
    );
    
    public function startTest() {
        $config = new DATABASE_CONFIG();
        if (isset($config->cloudsearch_test)) {
            $this->config = $config->cloudsearch_test;
        }
        ConnectionManager::create('cloudsearch_test', $this->config);
        $this->Movie =& ClassRegistry::init('Movie');
    }
    
    public function endTest() {
        unset($this->Movie);
        ClassRegistry::flush();
    }
    
    /**
     * Test Amazon CloudSearch / Document API
     *
     */
    public function testDocumentTypeProperty() {
        
        
        
    }
    
    public function testDocumentIdProperty() {
        
        
        
    }
    
    public function testDocumentVersionProperty() {
        
        
        
    }
    
    public function testDocumentLangProperty() {
        
        
        
    }
    
    public function testDocumentFieldsProperty() {
        
        
        
    }
    
    public function testDocumentFieldNameProperty() {
        
        
        
    }
    
    /**
     * Test Amazon CloudSearch / Search API
     *
     */
    public function testSearchBQParam() {
        
        $params = array(
            'bq' => "title:'Die hard'"
        );
        
        $response = $this->Movie->search($params);
        
        debug($response);
        
    }
    
    public function testSearchFacetParam() {
        
    }
    
    public function testSearchQParam() {
        
        $params = array(
            'q' => 'Die hard'
        );
        
        $response = $this->Movie->search($params);
        
        debug($response);
        
    }
    
    public function testSearchRankParam() {
        
        $params = array(
            'q' => 'Die Hard 4',
            'rank' => 'text_relevance'
        );
        
        $response = $this->Movie->search($params);
        
        debug($response);
        
        
    }
    
    public function testSearchResultTypeParam() {
        
        
        
    }
    
    public function testSearchReturnFieldsParam() {
        
        
        
    }
    
    public function testSearchSizeParam() {
        
        
        
    }
    
    public function testSearchStartParam() {
        
        
        
    }
    
    public function testSearchTfieldParam() {
        
        
        
    }
    
    /**
     * Test CakePHP API
     *
     */
    public function testFind() {
        
        //debug($this->Movie->find('all'));
        
    }
    
    public function testFindBy() {
        
        //debug($this->Movie->findByTitle('Die Hard'));
        
    }
    
    public function testFindConditions() {
        
        // debug($this->Movie->find('all', array(
        //     'conditions' => array(
        //         'Movie.title LIKE' => 'Die Hard'
        //     )
        // )));
        
    }
    
    public function testFindOptimalParams() {
        
        
        
    }
    
    public function testCreate() {
        
        
        
    }
    
    public function testRead() {
        
        
        
    }
    
    public function testUpdate() {
        
        
        
    }
    
    public function testDelete() {
        
        
        
    }
    
    public function testSave() {
        
        
        
    }
    
    public function testSaveAll() {
        
        
        
    }
    
    public function testUpdateAll() {
        
        
        
    }
    
    public function testDeleteAll() {
        
        
        
    }
    
    public function testValidation() {
        
        
        
    }
    
}

