<?php
/* Post Test cases */
App::import('Model', 'Post');

class PostTestCase extends CakeTestCase {
    
    public $fixtures = array('app.post');
    
    public function startTest() {
        $this->Post =& ClassRegistry::init('Post');
    }
    
    public function endTest() {
        unset($this->Post);
        ClassRegistry::flush();
    }

}
