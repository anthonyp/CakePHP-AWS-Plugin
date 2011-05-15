<?php
/**
 * S3Object Model File
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
class S3Object extends AWSAppModel {

	/**
	 * Name of model
	 *
	 * @var string
	 */
	public $name = 'S3Object';
	
	/**
	 * Schema
	 *
	 * @var array
	 */
	public $_schema = array(
		'id' => array('type' => 'string', 'length' => '255'),
		'name' => array('type' => 'string', 'length' => '255'),
		'bucket' => array('type' => 'string', 'length' => '255'),
		'folder' => array('type' => 'string', 'length' => '255'),
		'acl' => array('type' => 'string', 'length' => '255'),
		'size' => array('type' => 'integer', 'length' => '20'),
		'hash' => array('type' => 'string', 'length' => '255'),
		'type' => array('type' => 'string', 'length' => '255'),
		'data' => array('type' => 'text')
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
	public $useDbConfig = 's3_object';
	
	/*
	 * Validation
	 * 
	 * @var
	 */
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'Please enter a name',
				'allowEmpty' => false,
				'required' => true,
				'last' => true
			),
			'maxLength' => array(
				'rule' => array('maxLength', 255),
				'message' => 'Please enter a name no longer than 255 characters'
			)
		),
		'bucket' => array(
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
		'folder' => array(
			'maxLength' => array(
				'rule' => array('maxLength', 255),
				'message' => 'Please enter a folder no longer than 255 characters',
				'allowEmpty' => true
			)
		),
		'acl' => array(
			'inList' => array(
				'rule' => array('inList', array('private', 'public-read', 'public-read-write', 'authenticated-read')),
				'message' => 'Please enter a valid permission',
				'allowEmpty' => true
			)
		),
		'data' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'Please enter data',
				'allowEmpty' => false,
				'required' => true
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
		
		if (!in_array('s3_object', $sources)) {
			ConnectionManager::create('s3_object', array('datasource' => 'AWS.S3ObjectSource'));
		}
		
		parent::__construct($id, $table, $ds);

	}
	
}
?>