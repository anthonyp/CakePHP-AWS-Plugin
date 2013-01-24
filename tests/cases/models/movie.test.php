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
    
    public $useTable = false;
    
    public $useDbConfig = 'cloudsearch_test';
    
    public $_schema = array(
        'id' => array(
            'type' => 'string',
            'null' => false,
            'length' => 255,
        ),
        'title' => array(
            'type' => 'string',
            'null' => false,
            'length' => 255,
        ),
        'actor' => array(
            'type' => 'array',
            'null' => false,
            'length' => 100,
        ),
        'director' => array(
            'type' => 'string',
            'null' => false,
            'length' => 255,
        ),
        'year' => array(
            'type' => 'integer',
            'null' => false,
            'length' => 32
        ),
        'genre' => array(
            'type' => 'array',
            'null' => false,
            'length' => 100
        )
    );
    
}

class MovieTestCase extends CakeTestCase {
    
    public $skipDocumentApiTests = true;
    
    public $skipSearchApiTests = true;
    
    public $skipCakephpApiTests = false;
    
    public $config = array(
        'datasource' => 'AWS.CloudSearchSource',
        'search_endpoint' => 'search-tests-px2qjztrvfmtcvmik3ohbdq6vy.us-east-1.cloudsearch.amazonaws.com',
        'document_endpoint' => 'doc-tests-px2qjztrvfmtcvmik3ohbdq6vy.us-east-1.cloudsearch.amazonaws.com',
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
    public function testDocumentAdd() {
        
        $skip = $this->skipIf(
            $this->skipDocumentApiTests,
            'testing Document API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        debug(__FUNCTION__);
        
        $id = uniqid();
        $params = array(
            'type' => 'add',
            'id' => $id,
            'version' => 1,
            'lang' => 'en',
            'fields' => array(
                'title' => 'Movie '.$id,
                'director' => 'CakePHP, PHP',
                'genre' => array('Programming', 'Data'),
                'actor' => array('CloudSearch', 'AWS Plugin'),
            )
        );
        $response = $this->Movie->document($params);
        debug(json_decode($response));
        
    }
    
    public function testDocumentDelete() {
        
        $skip = $this->skipIf(
            $this->skipDocumentApiTests,
            'testing Document API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        debug(__FUNCTION__);
        
        $id = uniqid();
        $params = array(
            'type' => 'add',
            'id' => $id,
            'version' => 1,
            'lang' => 'en',
            'fields' => array(
                'title' => 'Movie '.$id,
                'director' => 'CakePHP, PHP',
                'genre' => array('Programming', 'Data'),
                'actor' => array('CloudSearch', 'AWS Plugin'),
            )
        );
        $response = $this->Movie->document($params);
        debug(json_decode($response));
        
        $params = array(
            'type' => 'delete',
            'id' => $id .'noexists',
            'version' => 2
        );
        $response = $this->Movie->document($params);
        debug(json_decode($response));
        
        $params = array(
            'type' => 'delete',
            'id' => $id,
            'version' => 1
        );
        $response = $this->Movie->document($params);
        debug(json_decode($response));
        
    }
    
    public function testDocumentBulk() {
        
        $skip = $this->skipIf(
            $this->skipDocumentApiTests,
            'testing Document API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        debug(__FUNCTION__);
        
        $params = array();
        for($i=0; $i<5; $i++) {
            $id = uniqid();
            $types = array('add', 'delete');
            $type = $types[array_rand($types)];
            if ($type == 'add') {
                $params[] = array(
                    'type' => 'add',
                    'id' => $id,
                    'version' => 1,
                    'lang' => 'en',
                    'fields' => array(
                        'title' => 'Movie '.$id,
                        'director' => 'CakePHP',
                        'genre' => array('Programming', 'Data'),
                        'actor' => array('CloudSearch', 'AWS Plugin')
                    )
                );
            } else {
                $params[] = array(
                    'type' => 'delete',
                    'id' => $id,
                    'version' => 2
                );
            }
        }
        
        $response = $this->Movie->document($params);
        debug(json_decode($response));
        
    }
    
    /**
     * Test Amazon CloudSearch / Search API
     *
     */
    public function testSearchBQParam() {
        
        $skip = $this->skipIf(
            $this->skipSearchApiTests,
            'testing Search API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        debug(__FUNCTION__);
        
        // using boolean operators in text searches
        $params = array(
            'bq' => "(or title:'star' (not title:'wars'))"
        );
        $response = $this->Movie->search($params);
        debug(json_decode($response));
        
        // using wildcards in text fields
        $params = array(
            'bq' => "title:'star*'"
        );
        $response = $this->Movie->search($params);
        debug(json_decode($response));
        
        // searching literal fields
        $params = array(
            'bq' => "genre:'Action'"
        );
        $response = $this->Movie->search($params);
        debug(json_decode($response));
        
        // searching uint fields
        $params = array(
            'bq' => 'year:2010'
        );
        $response = $this->Movie->search($params);
        debug(json_decode($response));
        
    }
    
    public function testSearchFacetParam() {
        
        $skip = $this->skipIf(
            $this->skipSearchApiTests,
            'testing Search API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        debug(__FUNCTION__);
        
        $params = array(
            'bq' => "title:'star'",
            'facet' => 'genre,year',
            'facet-genre-top-n' => 5,
            'facet-year-constraints' => '1970..1999,2000..2005,2006..2012'
        );
        $response = $this->Movie->search($params);
        debug(json_decode($response));
        
    }
    
    public function testSearchQParam() {
        
        $skip = $this->skipIf(
            $this->skipSearchApiTests,
            'testing Search API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        debug(__FUNCTION__);
        
        $params = array(
            'q' => 'star'
        );
        $response = $this->Movie->search($params);
        debug(json_decode($response));
        
        $params = array(
            'q' => 2012
        );
        $response = $this->Movie->search($params);
        debug(json_decode($response));
        
    }
    
    public function testSearchRankParam() {
        
        $skip = $this->skipIf(
            $this->skipSearchApiTests,
            'testing Search API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        debug(__FUNCTION__);
        
        $params = array(
            'q' => 'star',
            'rank' => 'text_relevance'
        );
        $response = $this->Movie->search($params);
        debug(json_decode($response));
        
    }
    
    /**
     * Rank Expressions in Search Requests
     *
     * @link http://docs.aws.amazon.com/cloudsearch/latest/developerguide/rankexpressionquery.html
     * @return void
     */
    public function testSearchRankExpressionsParam() {
        
        $skip = $this->skipIf(
            $this->skipSearchApiTests,
            'testing Search API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        debug(__FUNCTION__);
        
        $params = array(
            'q' => 'terminator',
            'rank-expression1' => 'sin(text_relevance)',
            'rank-expression2' => 'cos(text_relevance)',
            'rank' => 'expression1,expression2',
            'return-fields' => 'title,text_relevance,expression1,expression2'
        );
        $response = $this->Movie->search($params);
        debug(json_decode($response));
        
    }
    
    public function testSearchResultTypeParam() {
        
        $skip = $this->skipIf(
            $this->skipSearchApiTests,
            'testing Search API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        debug(__FUNCTION__);
        
        $params = array(
            'q' => 'star',
            'results-type' => 'xml'
        );
        $response = $this->Movie->search($params);
        debug(h($response));
        
    }
    
    public function testSearchReturnFieldsParam() {
        
        $skip = $this->skipIf(
            $this->skipSearchApiTests,
            'testing Search API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        debug(__FUNCTION__);
        
        $params = array(
            'q' => 'star',
            'return-fields' => 'text_relevance,actor,director,title,year'
        );
        $response = $this->Movie->search($params);
        debug(json_decode($response));
        
    }
    
    public function testSearchSizeParam() {
        
        $skip = $this->skipIf(
            $this->skipSearchApiTests,
            'testing Search API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        debug(__FUNCTION__);
        
        $params = array(
            'q' => 'star',
            'size' => 12
        );
        $response = $this->Movie->search($params);
        debug(json_decode($response));
        
        
    }
    
    public function testSearchStartParam() {
        
        $skip = $this->skipIf(
            $this->skipSearchApiTests,
            'testing Search API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        debug(__FUNCTION__);
        
        $params = array(
            'q' => 'star',
            'start' => 5,
            'size' => 5
        );
        $response = $this->Movie->search($params);
        debug(json_decode($response));
        
    }
    
    /**
     * undocumented function
     *
     * @link http://docs.aws.amazon.com/cloudsearch/latest/developerguide/thresholdresults.html
     * @return void
     */
    public function testSearchTfieldParam() {
        
        $skip = $this->skipIf(
            $this->skipSearchApiTests,
            'testing Search API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        debug(__FUNCTION__);
        
        $params = array(
            'q' => 'star wars',
            'return-fields' => 'title,text_relevance',
            't-text_relevance' => '300..'
        );
        $response = $this->Movie->search($params);
        debug(json_decode($response));
        
    }
    
    // /**
    //  * Test CakePHP API
    //  *
    //  */
    // public function testFind() {
    //     
    //     $skip = $this->skipIf(
    //         $this->skipCakephpApiTests,
    //         'testing CakePHP API '. __FUNCTION__
    //     );
    //     if ($skip) {
    //         return;
    //     }
    //     
    //     debug(__FUNCTION__);
    //     
    //     $response = $this->Movie->find('all');
    //     debug($response);
    //     
    //     $response = $this->Movie->find('all', array(
    //         'conditions' => array(
    //             'q' => 'star wars'
    //         )
    //     ));
    //     debug($response);
    //     
    //     $response = $this->Movie->find('all', array(
    //         'conditions' => array(
    //             'bq' => "(or title:'star' (not title:'wars'))"
    //         )
    //     ));
    //     debug($response);
    //     
    //     $response = $this->Movie->find('all', array(
    //         'conditions' => array(
    //             'bq' => "title:'star*'"
    //         )
    //     ));
    //     debug($response);
    //     
    //     $response = $this->Movie->find('all', array(
    //         'conditions' => array(
    //             'bq' => "genre:'Action'"
    //         )
    //     ));
    //     debug($response);
    //     
    //     $response = $this->Movie->find('all', array(
    //         'conditions' => array(
    //             'bq' => 'year:2010'
    //         )
    //     ));
    //     debug($response);
    //     
    //     $response = $this->Movie->find('all', array(
    //         'conditions' => array(
    //             'bq' => "title:'star'",
    //             'facet' => 'genre,year',
    //             'facet-genre-top-n' => 5,
    //             'facet-year-constraints' => '1970..1999,2000..2005,2006..2012'
    //         )
    //     ));
    //     debug($response);
    //     
    //     $response = $this->Movie->find('all', array(
    //         'conditions' => array(
    //             'q' => 'star',
    //             'rank' => 'text_relevance'
    //         )
    //     ));
    //     debug($response);
    //     
    //     $response = $this->Movie->find('all', array(
    //         'conditions' => array(
    //             'q' => 'terminator',
    //             'rank-expression1' => 'sin(text_relevance)',
    //             'rank-expression2' => 'cos(text_relevance)',
    //             'rank' => 'expression1,expression2',
    //             'return-fields' => 'title,text_relevance,expression1,expression2'
    //         )
    //     ));
    //     debug($response);
    //     
    //     $response = $this->Movie->find('all', array(
    //         'conditions' => array(
    //             'q' => 'star',
    //             'results-type' => 'xml'
    //         )
    //     ));
    //     debug($response);
    //     
    //     $response = $this->Movie->find('all', array(
    //         'conditions' => array(
    //             'q' => 'star',
    //             'return-fields' => 'text_relevance,actor,director,title,year'
    //         )
    //     ));
    //     debug($response);
    //     
    //     $response = $this->Movie->find('all', array(
    //         'conditions' => array(
    //             'q' => 'star',
    //             'size' => 12
    //         )
    //     ));
    //     debug($response);
    //     
    //     $response = $this->Movie->find('all', array(
    //         'conditions' => array(
    //             'q' => 'star',
    //             'start' => 5,
    //             'size' => 5
    //         )
    //     ));
    //     debug($response);
    //     
    //     // http://docs.aws.amazon.com/cloudsearch/latest/developerguide/thresholdresults.html
    //     $response = $this->Movie->find('all', array(
    //         'conditions' => array(
    //             'q' => 'star',
    //             'return-fields' => 'title,text_relevance',
    //             't-text_relevance' => '300..'
    //         )
    //     ));
    //     debug($response);
    //     
    // }
    // 
    // public function testFindBy() {
    //     
    //     $skip = $this->skipIf(
    //         $this->skipCakephpApiTests,
    //         'testing CakePHP API '. __FUNCTION__
    //     );
    //     if ($skip) {
    //         return;
    //     }
    //     
    //     debug(__FUNCTION__);
    //     
    //     $response = $this->Movie->findByTitle('star wars');
    //     debug($response);
    //     
    // }
    // 
    // // public function testFindConditions() {
    // //     
    // //     $skip = $this->skipIf(
    // //         $this->skipCakephpApiTests,
    // //         'testing CakePHP API '. __FUNCTION__
    // //     );
    // //     if ($skip) {
    // //         return;
    // //     }
    // //     
    // //     debug(__FUNCTION__);
    // //     
    // // }
    // 
    
    public function testCreate() {
        
        $skip = $this->skipIf(
            $this->skipCakephpApiTests,
            'testing CakePHP API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        $id = uniqid();
        $data = array(
            'id' => $id,
            'title' => 'Movie '.$id,
            'director' => 'CakePHP, PHP',
            'genre' => array('Programming', 'Data'),
            'actor' => array('CloudSearch', 'AWS Plugin')
        );
        
        //$this->assertTrue($this->Movie->save($data));
        
        // @todo test error
        
    }
    
    public function testRead() {
        
        $skip = $this->skipIf(
            $this->skipCakephpApiTests,
            'testing CakePHP API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        $result = $this->Movie->read(null, 'tt1408101');
        $expected = array(
            'id' => 'tt1408101'
        );
        $this->assertEqual($result, $expected);
        
    }
    
    public function testUpdate() {
        
        $skip = $this->skipIf(
            $this->skipCakephpApiTests,
            'testing CakePHP API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        $data = array(
            'id' => 'tt1408101',
            'title' => 'Untitled Star Trek Sequel ',
        );
        
        $this->assertTrue($this->Movie->save($data));
        
    }
    
    public function testDelete() {
        
        $skip = $this->skipIf(
            $this->skipCakephpApiTests,
            'testing CakePHP API '. __FUNCTION__
        );
        if ($skip) {
            return;
        }
        
        $id = uniqid();
        $data = array(
            'id' => $id,
            'title' => 'Movie '.$id,
            'director' => 'CakePHP, PHP',
            'genre' => array('Programming', 'Data'),
            'actor' => array('CloudSearch', 'AWS Plugin')
        );
        
        $this->assertTrue($this->Movie->save($data));
        
        // delay 10 seconds before can be searched
        sleep(10);
        
        $this->assertTrue($this->Movie->delete($id));
        
    }
    
    // public function testSave() {
    //     
    //     $skip = $this->skipIf(
    //         $this->skipCakephpApiTests,
    //         'testing CakePHP API '. __FUNCTION__
    //     );
    //     if ($skip) {
    //         return;
    //     }
    //     
    //     debug(__FUNCTION__);
    //     
    //     $id = uniqid();
    //     $data = array(
    //         'id' => $id,
    //         'title' => 'Movie '.$id,
    //         'director' => 'CakePHP, PHP',
    //         'genre' => array('Programming', 'Data'),
    //         'actor' => array('CloudSearch', 'AWS Plugin')
    //     );
    //     
    //     $response = $this->Movie->save($data);
    //     debug($response);
    //     
    // }
    
    // public function testSaveAll() {
    //     
    //     $skip = $this->skipIf(
    //         $this->skipCakephpApiTests,
    //         'testing CakePHP API '. __FUNCTION__
    //     );
    //     if ($skip) {
    //         return;
    //     }
    //     
    //     debug(__FUNCTION__);
    //     
    //     $id = uniqid();
    //     $data[] = array(
    //         'id' => $id,
    //         'title' => 'Movie '.$id,
    //         'director' => 'CakePHP, PHP',
    //         'genre' => array('Programming', 'Data'),
    //         'actor' => array('CloudSearch', 'AWS Plugin')
    //     );
    //     
    //     $id = uniqid();
    //     $data[] = array(
    //         'id' => $id,
    //         'title' => 'Movie '.$id,
    //         'director' => 'CakePHP, PHP',
    //         'genre' => array('Programming', 'Data'),
    //         'actor' => array('CloudSearch', 'AWS Plugin')
    //     );
    //     
    //     $id = uniqid();
    //     $data[] = array(
    //         'id' => $id,
    //         'title' => 'Movie '.$id,
    //         'director' => 'CakePHP, PHP',
    //         'genre' => array('Programming', 'Data'),
    //         'actor' => array('CloudSearch', 'AWS Plugin')
    //     );
    //     
    //     $response = $this->Movie->saveAll($data);
    //     debug($response);
    //     
    // }
    // 
    // public function testUpdateAll() {
    //     
    //     $skip = $this->skipIf(
    //         $this->skipCakephpApiTests,
    //         'testing CakePHP API '. __FUNCTION__
    //     );
    //     if ($skip) {
    //         return;
    //     }
    //     
    //     debug(__FUNCTION__);
    //     
    //     $fields = array(
    //         'year' => 2012
    //     );
    //     $conditions = array(
    //         'title' => '2012*'
    //     );
    //     $response = $this->Movie->updateAll($fields, $conditions);
    //     debug($response);
    //     
    // }
    // 
    // public function testDeleteAll() {
    //     
    //     $skip = $this->skipIf(
    //         $this->skipCakephpApiTests,
    //         'testing CakePHP API '. __FUNCTION__
    //     );
    //     if ($skip) {
    //         return;
    //     }
    //     
    //     debug(__FUNCTION__);
    //     
    //     $conditions = array(
    //         'year' => '1980'
    //     );
    //     
    //     $response = $this->Movie->deleteAll($conditions);
    //     debug($response);
    //     
    // }
    
}

