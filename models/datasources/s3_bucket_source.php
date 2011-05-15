<?php
/**
 * S3BucketSource DataSource File
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
 * @subpackage 	aws.models.datasources
 * @since		0.1
 * @copyright  	2011 Anthony Putignano <contact@anthonyputignano.com>
 * @license    	http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       	http://github.com/anthonyp/CakePHP-AWS-Plugin
 */
App::import('DataSource', 'AWS.S3BaseSource');
class S3BucketSource extends S3BaseSource {

	/**
	 * The description of this data source
	 *
	 * @var string
	 */
	public $description = 'S3Bucket DataSource';
	
	/**
	 * Possible fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id',
		'name',
		'acl',
		'location',
		'size',
		'object_count',
		'created'
	);
	
	/**
	 * Get a bucket's location
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	string	$bucket_name
	 * @return	string
	 */
	private function _getLocation ($bucket_name='') {
		
		if (empty($bucket_name)) {
			$this->showError(__('Empty bucket name', true));
			return false;
		}
		
		$response = $this->_getResponse('S3', 'get_bucket_region', $bucket_name);
		
		if (!empty($response->body->LocationConstraint)) {
			$location = $response->body->LocationConstraint;
		} else {
			$location = '';
		}
		
		return $location;
		
	}
	
	/**
	 * Get a bucket's size
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	string	$bucket_name
	 * @return	string
	 */
	private function _getSize ($bucket_name='') {
		
		if (empty($bucket_name)) {
			$this->showError(__('Empty bucket name', true));
			return false;
		}
		
		return $this->_getResponse('S3', 'get_bucket_filesize', $bucket_name);
		
	}
	
	/**
	 * Get a bucket's object count
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	string	$bucket_name
	 * @return	string
	 */
	private function _getObjectCount ($bucket_name='') {
		
		if (empty($bucket_name)) {
			$this->showError(__('Empty bucket name', true));
			return false;
		}
		
		return $this->_getResponse('S3', 'get_bucket_object_count', $bucket_name);
		
	}

	/**
	 * Creates a new bucket via the API
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array 	$fields
	 * @param 	array 	$values
	 * @return	bool
	 */
	public function create (&$model, $fields = null, $values = null) {
		
		if (empty($fields)) {
			return false;
		}
		
		$data = array();
		foreach ($fields as $key => $field) {
			$data[$field] = $values[$key];
		}
		
		if (empty($data['name'])) {
			$this->showError(__('No name specified', true));
			return false;
		}
		
		if (empty($data['acl'])) {
			$data['acl'] = 'private';
		}
		
		if (empty($this->acl_options[$data['acl']])) {
			$this->showError(__('Invalid ACL', true));
			return false;
		}
		
		if (empty($data['location'])) {
			$data['location'] = '';
		}
		
		$result = $this->_getResponse('S3', 'create_bucket', array(
			$data['name'],
			$data['location'],
			$data['acl']
		)) ? true : false;
		
		if (!$result) {
			return false;
		}
		
		$model->setInsertID($data['name']);
		$model->id = $data['name'];
		
		return true;
		
	}
	
	/**
	 * Updates an existing bucket via the API
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array 	$fields
	 * @param 	array 	$values
	 * @return	bool
	 */
	public function update (&$model, $fields = null, $values = null) {
		
		$this->showError(__('Cannot update a bucket', true));
		
		return false;
		
	}

	/**
	 * Reads bucket(s) from the API
	 * 
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array 	$queryData
	 * @return 	array
	 */
	public function read (&$model, $queryData = array()) {
		
		$queryData = $this->_getQueryData($queryData);
		pr($queryData);
		extract($queryData);
		
		$is_count = $this->_isCount($fields);
		
		if (!$this->_readErrors($model, $queryData)) {
			return false;
		}
		
		$response = $this->_getResponse('S3', 'list_buckets');
		pr($response);
		
		$bucket_count = !empty($response->body->Buckets->Bucket) ? count($response->body->Buckets->Bucket) : 0;
		
		if ($bucket_count == 0) {
			if ($is_count) {
				return $this->_getFormattedCount($bucket_count);
			} else {
				return false;
			}
		}
		
		$fields = $this->_getFieldList($model, $queryData);
		
		$results = array();
		$iteration = 0;
		foreach ($response->body->Buckets->Bucket as $bucket) {
			
			$name = (string)$bucket->Name;
			$creation_date = (string)$bucket->CreationDate;
			
			foreach ($fields as $field) {
				switch ($field) {
					case 'id':
						$results[$iteration][$model->alias][$field] = $name;
						break;
					case 'name':
						$results[$iteration][$model->alias][$field] = $name;
						break;
					case 'location':
						$results[$iteration][$model->alias][$field] = $this->_getLocation($name);
						break;
					case 'acl':
						$results[$iteration][$model->alias][$field] = $this->_getAclOption($this->_getResponse('S3', 'get_bucket_acl', $name));
						break;
					case 'size':
						$results[$iteration][$model->alias][$field] = $this->_getSize($name);
						break;
					case 'object_count':
						$results[$iteration][$model->alias][$field] = $this->_getObjectCount($name);
						break;
					case 'created':
						$results[$iteration][$model->alias][$field] = date('Y-m-d H:i:s', strtotime($creation_date));
						break;
					default:
						break;
				}
			}
			$iteration++;
		}
		
		$results = $this->_postFilter($model, $results, $conditions);
		
		if ($is_count) {
			return $this->_getFormattedCount(count($results));
		}
		
		$results = $this->_postOrder($model, $results, $order);
		
		$results = $this->_postPaginate($results, $page, $limit);
		
		return $results;
		
	}
	
	/**
	 * Deletes a bucket via the API
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array	$conditions
	 * @return	bool
	 */
	public function delete (&$model, $conditions=array()) {
		
		$ids = $this->_getIdsToBeDeleted($model, $conditions);
		
		if ($ids === false) {
			return false;
		}
		
		$result = true;
		
		if (!empty($ids)) {
			foreach ($ids as $id) {
				$result = ($this->_getResponse('S3', 'delete_bucket', $id) ? true : false);
			}
		}
		
		return $result;
		
	}

}
?>