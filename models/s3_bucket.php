<?php
/**
 * S3Bucket Model File
 *
 * Copyright (c) 2011 Anthony Putignano
 *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 *
 * PHP version 5.3
 * CakePHP version 1.3
 *
 * @package    	aws
 * @subpackage 	aws.models
 * @copyright  	2011 Anthony Putignano <contact@anthonyputignano.com>
 * @license    	http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       	http://github.com/anthonyp/CakePHP-AWS-Plugin
 */
class S3Bucket extends AWSAppModel {

	/**
	 * Name of model
	 *
	 * @var string
	 */
	public $name = 'S3Bucket';
	
	/**
	 * Schema
	 *
	 * @var array
	 */
	public $_schema = array(
		'id' => array('type' => 'string', 'length' => '255'),
		'name' => array('type' => 'string', 'length' => '255'),
		'location' => array('type' => 'string', 'length' => '255'),
		'acl' => array('type' => 'string', 'length' => '255'),
		'size' => array('type' => 'integer', 'length' => '20'),
		'object_count' => array('type' => 'integer', 'length' => '20')
	);

	/**
	 * useTable
	 *
	 * @var string
	 */
	public $useTable = false;
	
	/**
	 * Name of datasource config to use
	 *
	 * @var string
	 */
	public $useDbConfig = 's3_bucket';
	
	/*
	 * Validation
	 * 
	 * @var
	 */
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'Please enter a bucket name',
				'allowEmpty' => false,
				'required' => true,
				'last' => true
			),
			'isValidBucketName' => array(
				'rule' => 'isValidBucketName',
				'message' => 'Please enter a valid bucket name for DNS purposes'
			)
		),
		'acl' => array(
			'inList' => array(
				'rule' => array('inList', array('private', 'public-read', 'public-read-write', 'authenticated-read')),
				'message' => 'Please enter a valid permission',
				'allowEmpty' => true
			)
		),
		'location' => array(
			'inList' => array(
				'rule' => array('inList', array('us-west-1', 'EU', 'ap-southeast-1', 'ap-northeast-1')),
				'message' => 'Please enter a valid location',
				'allowEmpty' => true
			)
		)
	);
	
	/**
	 * Adds the datasource to the connection manager if it's not already there,
	 * which it won't be if you've not added it to your app/config/database.php
	 * file.
	 * 
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param 	int
	 * @param 	string
	 * @param 	string
	 * @return	void
	 */
	public function __construct ($id = false, $table = null, $ds = null) {

		$sources = ConnectionManager::sourceList();
		
		if (!in_array('s3_bucket', $sources)) {
			ConnectionManager::create('s3_bucket', array('datasource' => 'AWS.S3BucketSource'));
		}
		
		parent::__construct($id, $table, $ds);

	}
	
}
?>