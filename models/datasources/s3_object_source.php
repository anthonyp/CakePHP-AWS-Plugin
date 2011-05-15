<?php
/**
 * S3ObjectSource DataSource File
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
class S3ObjectSource extends S3BaseSource {

	/**
	 * The description of this data source
	 *
	 * @var string
	 */
	public $description = 'S3Object DataSource';
	
	/**
	 * Possible fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id',
		'name',
		'bucket',
		'folder',
		'acl',
		'size',
		'hash',
		'type',
		'data'
	);
	
	/**
	 * Get object name
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	string	$filepath
	 * @return	string
	 */
	private function _getObjectName ($filepath='') {
		
		return array_pop(explode('/', $filepath));
		
	}
	
	/**
	 * Get object folder
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	string	$filepath
	 * @return	string
	 */
	private function _getObjectFolder ($filepath='') {
		
		$folder = '';
		$exploded = explode('/', $filepath);
		if (count($exploded) > 1) {
			$last_key = (count($exploded) - 1);
			foreach ($exploded as $key => $value) {
				if ($key == $last_key) break;
				$folder .= $value . '/';
			}
		}
		
		return $folder;
		
	}
	
	/**
	 * Format an object's response
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	string	$bucket
	 * @param	string	$name
	 * @param	object	$response
	 * @return	mixed
	 */
	private function _formatObjectResponse ($bucket='', $name='', $response=null) {
		
		if (empty($response)) {
			return false;
		}
		
		$object = array(
			'bucket' => $bucket,
			'name' => $name,
			'time' => strtotime((string)$response->header['last-modified']),
			'hash' => str_replace('"', '', (string)$response->header['etag']),
			'type' => (string)$response->header['content-type'],
			'size' => (int)$response->header['content-length']
		);
		
		if (!empty($response->body)) {
			$object['body'] = (string)$response->body;
		}
		
		return $object;
		
	}
	
	/**
	 * Format an object's response
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$response
	 * @return	mixed
	 */
	private function _getObjects ($bucket='', $options=array(), $objects=array()) {
		
		$response = $this->_getResponse('S3', 'list_objects', array($bucket, $options));
		
		if (empty($response)) {
			return false;
		}
		
		$new_objects = $this->_formatObjectsResponse($response);
		
		$last_file = $new_objects[(count($new_objects)-1)]['name'];
		
		// Don't include folders, just actual files
		foreach ($new_objects as $key => $object) {
			if (empty($object['size'])) {
				unset($new_objects[$key]);
			}
		}
		
		$objects = array_merge(
			$objects,
			$new_objects
		);
		
		if ((string)$response->body->IsTruncated == 'false') {
			return $objects;
		}
		
		$options = array_merge(
			$options,
			array(
				'marker' => $last_file
			)
		);
		
		$objects = $this->_getObjects($bucket, $options, $objects);
		
		return $objects;
		
	}
	
	private function _formatObjectsResponse ($response=null) {
		
		if (empty($response->body->Contents)) {
			return array();
		}
		
		$objects = array();
		foreach ($response->body->Contents as $object) {
			$objects[] = array(
				'bucket' => (string)$response->body->Name,
				'name' => (string)$object->Key,
				'time' => strtotime((string)$object->LastModified),
				'hash' => str_replace('"', '', (string)$object->ETag),
				'size' => (int)$object->Size
			);
		}
		
		return $objects;
		
	}

	/**
	 * Creates a new object via the API
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
		
		if (empty($data['bucket'])) {
			$this->showError(__('No bucket specified', true));
			return false;
		}
		
		if (empty($data['name'])) {
			$this->showError(__('No name specified', true));
			return false;
		}
		
		if (empty($data['data'])) {
			$this->showError(__('No data specified', true));
			return false;
		}
		
		if (empty($data['folder'])) {
			$data['folder'] = '';
		}
		
		if (empty($data['acl'])) {
			$data['acl'] = 'private';
		}
		
		if (empty($this->acl_options[$data['acl']])) {
			$this->showError(__('Invalid ACL', true));
			return false;
		}
		
		$finfo = new finfo(FILEINFO_MIME);
		$mime_type = array_shift(explode(';', $finfo->buffer($data['data'])));
		
		$result = $this->_getResponse('S3', 'create_object', array(
			$data['bucket'],
			$data['folder'] . $data['name'],
			array(
				'body' => $data['data'],
				'contentType' => $mime_type,
				'acl' => $data['acl']
			)
		)) ? true : false;
		
		if (!$result) {
			return false;
		}
		
		$id = $data['bucket'] . ':' . $data['folder'] . $data['name'];
		$model->setInsertID($id);
		$model->id = $id;
		
		return true;
		
	}
	
	/**
	 * Updates an existing object via the API
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
	 * Reads object(s) from the API
	 * 
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array 	$queryData
	 * @return 	array
	 */
	public function read (&$model, $queryData = array()) {
		
		$queryData = $this->_getQueryData($queryData);
		extract($queryData);
		
		$is_count = $this->_isCount($fields);
		
		if (!$this->_readErrors($model, $queryData)) {
			return false;
		}
		
		$conditions = $this->_getConditions($model, $conditions);
		
		if (
			!empty($conditions['bucket']) && 
			$conditions['bucket']['operator'] == '='
		) {
			$bucket = $conditions['bucket']['value'];
		}
		if (
			!empty($conditions['folder']) && 
			$conditions['folder']['operator'] == '=' && 
			!empty($conditions['name']) && 
			$conditions['name']['operator'] == '='
		) {
			$filepath = $conditions['folder']['value'] . $conditions['name']['value'];
		}
		
		if (!empty($conditions[$model->primaryKey])) {
			$exploded = explode(':', $conditions[$model->primaryKey]['value']);
			if (empty($exploded[1])) {
				$this->showError(__('The primary key must be in the form of "bucket:name"', true));
				return false;
			}
			if ($conditions[$model->primaryKey]['operator'] == '=') {
				$bucket = $exploded[0];
				$filepath = $exploded[1];
			}
		}
		
		$fields = $this->_getFieldList($model, $queryData);
		if (!in_array('name', $fields)) {
			$fields[] = 'name';
		}
		if (!in_array('bucket', $fields)) {
			$fields[] = 'bucket';
		}
		if (!in_array('folder', $fields)) {
			$fields[] = 'folder';
		}
		
		if (!empty($bucket) && !empty($filepath)) {
			
			unset(
				$conditions[$model->primaryKey],
				$conditions['folder'],
				$conditions['name']
			);
			
			$method = in_array('data', $fields) ? 'get_object' : 'get_object_headers';
			$object = $this->_formatObjectResponse($bucket, $filepath, $this->_getResponse('S3', $method, array($bucket, $filepath)));
			
			if (empty($object)) {
				return false;
			}
			
			$objects = array(0 => $object);
			
		} elseif (!empty($conditions['bucket']) && $conditions['bucket']['operator'] == '=') {
			
			$options = array();
			if (!empty($conditions['folder']) && $conditions['folder']['operator'] == '=') {
				$options['prefix'] = $conditions['folder']['value'];
			}
			$objects = $this->_getObjects($conditions['bucket']['value'], $options);
			
		} else {
			
			$response = $this->_getResponse('S3', 'list_buckets');
			
			if (empty($response->body->Buckets->Bucket)) {
				return false;
			}
			
			$objects = array();
			foreach ($response->body->Buckets->Bucket as $current_bucket) {
				$objects = array_merge(
					$objects,
					$this->_getObjects((string)$current_bucket->Name)
				);
			}
			
			if (empty($objects)) {
				return false;
			}
			
		}
		
		$object_count = !empty($objects) ? count($objects) : 0;
		
		if ($object_count == 0) {
			if ($is_count) {
				return $this->_getFormattedCount($object_count);
			} else {
				return false;
			}
		}
		
		foreach ($objects as $key => $object) {
			$objects[$key]['folder'] = $this->_getObjectFolder($object['name']);
			$objects[$key]['name'] = $this->_getObjectName($object['name']);
			if (empty($objects[$key]['name'])) {
				unset($objects[$key]);
			}
		}
		
		$objects = array_values($objects);
		
		$results = array();
		$iteration = 0;
		foreach ($objects as $object) {
			$object_headers = array();
			foreach ($fields as $field) {
				switch ($field) {
					case 'id':
						$results[$iteration][$model->alias][$field] = $object['bucket'] . ':' . $object['folder'] . $object['name'];
						break;
					case 'type':
						if (empty($object['type'])) {
							if (empty($object_headers)) {
								$object_headers = $this->_formatObjectResponse(
									$object['bucket'], 
									$object['folder'] . $object['name'], 
									$this->_getResponse('S3', 'get_object_headers', array(
										$object['bucket'], 
										$object['folder'] . $object['name']
									))
								);
							}
							$object['type'] = $object_headers['type'];
						}
						$results[$iteration][$model->alias]['type'] = $object['type'];
						break;
					case 'acl':
						$results[$iteration][$model->alias][$field] = $this->_getAclOption(
							$this->_getResponse('S3', 'get_object_acl', array(
								$object['bucket'], 
								$object['folder'] . $object['name']
							))
						);
						break;
					case 'data':
						if (!empty($object['data'])) {
							$results[$iteration][$model->alias]['data'] = $object['body'];
						}
						break;
					case 'created':
						$results[$iteration][$model->alias][$field] = date('Y-m-d H:i:s', $object['time']);
						break;
					default:
						$results[$iteration][$model->alias][$field] = $object[$field];
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
		
		// Getting the data last so that we use the least amount of bandwidth possible
		foreach ($results as $key => $result) {
			if (in_array('data', $fields) && !isset($result[$model->alias]['data'])) {
				$object = $this->_formatObjectResponse(
					$result[$model->alias]['bucket'], 
					$result[$model->alias]['folder'] . $result[$model->alias]['name'], 
					$this->_getResponse('S3', 'get_object', array(
						$result[$model->alias]['bucket'], 
						$result[$model->alias]['folder'] . $result[$model->alias]['name']
					))
				);
				if (empty($object['body'])) {
					return false;
				}
				$results[$key][$model->alias]['data'] = $object['body'];
			}
		}
		
		return $results;
		
	}
	
	/**
	 * Delete object(s) via the API
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
				$exploded = explode(':', $id);
				if (count($exploded) != 2) {
					continue;
				}
				$bucket = $exploded[0];
				$uri = $exploded[1];
				$result = ($result && $this->_getResponse('S3', 'delete_object', array($bucket, $uri))) ? true : false;
			}
		}
		
		return $result;
		
	}

}
?>