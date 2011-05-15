<?php
/**
 * S3BaseSource DataSource File
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
class S3BaseSource extends DataSource {

	/**
	 * The description of this data source
	 *
	 * @var string
	 */
	public $description = 'S3Base DataSource';
	
	/**
	 * Possible fields
	 *
	 * @var array
	 */
	public $fields = array();
	
	/*
	 * List of available services
	 * 
	 * array('service-name' => array('api' => 'api-class-name', 'exception' => 'exception-class-name'))
	 * 
	 * @var
	 */
	public $services = array(
		'S3' => array(
			'api' => 'AmazonS3',
			'exception' => 'S3_Exception'
		)
	);
	
	/*
	 * Object container for services
	 */
	public $API = null;
	
	/*
	 * ACL Options
	 * 
	 * @var	array
	 */
	public $acl_options = array(
		'private' => 'Private',
		'public-read' => 'Public Read',
		'public-read-write' => 'Public Read Write',
		'authenticated-read' => 'Authenticated Read'
	);
	
	/**
	 * Loads the S3 vendor library for use within the datasource
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @return	void
	 */
	protected function _loadLibrary ($service='') {
		
		if (empty($service) || !isset($this->services[$service])) {
			$this->showError(__('That service is not available', true));
			return false;
		}
		
		if (!isset($this->API->{$service})) {
			
			App::import('Vendor', 'AWS.SDKClass');
			
			$access_key = Configure::read('AWS.' . $service . '.AccessKey');
			if (empty($access_key)) {
				$access_key = Configure::read('AWS.AccessKey');
			}
			$secret_key = Configure::read('AWS.' . $service . '.SecretKey');
			if (empty($secret_key)) {
				$secret_key = Configure::read('AWS.SecretKey');
			}
			
			$this->API->{$service} = new $this->services[$service]['api'](
				$access_key,
				$secret_key
			);
			
		}
		
	}
	
	/**
	 * Generates errors as a result of a read() attempt
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array	$queryData
	 * @param	array	$options	Options include:
	 * 								- 'is_count' bool - Whether or not the current query is attempting a COUNT()
	 * @return	bool
	 */
	protected function _readErrors (&$model, $queryData = array(), $options=array()) {
		
		extract($queryData);
		extract(array_merge(
			array(
				'is_count' => $this->_isCount($fields)
			),
			$options
		));
		
		if (!empty($conditions) && !is_array($conditions)) {
			$this->showError(__('Conditions must be in array format', true));
			return false;
		}
		
		if (!empty($order) && !is_array($order)) {
			$this->showError(__('Order must be in array format', true));
			return false;
		}
		
		if (count($order) > 1) {
			$this->showError('Only 1 level of ordering is supported', true);
			return false;
		}
		
		if (!empty($joins)) {
			$this->showError(__('Joins are not supported', true));
			return false;
		}
		
		if (!empty($group)) {
			$this->showError(__('Group is not supported', true));
			return false;
		}
		
		return true;
		
	}
	
	/**
	 * Convert dates in a read() result set to/from UNIX timestamps to/from MySQL format
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array	$data
	 * @param	string	'mysql' or 'unix'
	 * @return	array
	 */
	protected function _convertDates (&$model, $data=array(), $to='mysql') {
		
		if (empty($data)) {
			return $data;
		}
		
		foreach ($data as $key => $value) {
			foreach ($value as $model_alias => $fields) {
				foreach ($fields as $field => $field_value) {
					if (
						!empty($model->_schema[$field]['type']) && 
						(
							$model->_schema[$field]['type'] == 'date' || 
							$model->_schema[$field]['type'] == 'datetime'
						)
					) {
						$date_format = ($model->_schema[$field]['type'] == 'date') ? 'Y-m-d' : 'Y-m-d H:i:s';
						$data[$key][$model_alias][$field] = ($to == 'mysql') ? date($date_format, $field_value) : strtotime($field_value);
					}
				}
			}
		}
		
		return $data;
		
	}
	
	/**
	 * Determines if the current query is a COUNT query based on the fields being requested
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	array	$fields
	 * @return	bool
	 */
	protected function _isCount ($fields=array()) {
		
		if (!empty($fields) && is_string($fields) && $fields == 'count') {
			return true;
		}
		
		return false;
		
	}
	
	/**
	 * Send a command to the API and get a response
	 * 
	 * This is the single point of failure for the API, and handles all the ugly stuff like
	 * try/catch, error checking, etc
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	string	$service	Currently only 'S3' is available
	 * @param	string	$method
	 * @param	array	$arguments
	 * @return	mixed	Response
	 */
	protected function _getResponse ($service='', $method='', $arguments=array()) {
		
		if (empty($this->services[$service])) {
			$this->showError(__('This service is not available', true));
			return false;
		}
		
		if (empty($method)) {
			$this->showError(__('A method must be supplied', true));
			return false;
		}
		
		$this->_loadLibrary($service);
		
		if (!method_exists($this->API->{$service}, $method)) {
			$this->showError(__('Method does not exist', true));
			return false;
		}
		
		if (!is_array($arguments)) {
			$arguments = array($arguments);
		}
		
		try {
			$response = call_user_func_array(array($this->API->{$service}, $method), $arguments);
		} catch (S3_Exception $e) { // add more catches to this list as the service list grows
			$response = false;
		}
		
		if ($response === false) {
			$this->showError($e);
			return false;
		}
		
		if (
			!empty($response->status) && 
			(
				$response->status < 200 || 
				$response->status >= 300
			)
		) {
			$this->showError('Invalid Status Code ' . $response->status);
			return false;
		}
		
		return $response;
		
	}
	
	/**
	 * Get simple ACL option string from ACL array when reading
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	Returned ACL response
	 * @return	string	ACL option
	 */
	protected function _getAclOption ($response) {
		
		if (empty($response->body->AccessControlList->Grant[0])) {
			return '';
		}
		
		if (!empty($response->body->AccessControlList->Grant[0]->Grantee->URI)) {
			$uri = (string)$response->body->AccessControlList->Grant[0]->Grantee->URI;
		} else {
			$uri = '';
		}
		
		if ($response->body->AccessControlList->Grant[0]->Permission) {
			$permission = (string)$response->body->AccessControlList->Grant[0]->Permission;
		} else {
			$permission = '';
		}
		
		if (empty($response->body->AccessControlList->Grant[0]->Grantee->ID)) {
			$group = true;
		} else {
			$group = false;
		}
		
		if (
			$group && 
			$uri == 'http://acs.amazonaws.com/groups/global/AllUsers' && 
			$permission == 'READ'
		) {
			$return = 'public-read';
		} elseif (
			$group && 
			$uri == 'http://acs.amazonaws.com/groups/global/AllUsers' && 
			$permission == 'FULL_CONTROL'
		) {
			$return = 'public-read-write';
		} elseif (
			$group && 
			$uri == 'http://acs.amazonaws.com/groups/global/AuthenticatedUsers' && 
			$permission == 'READ'
		) {
			$return = 'authenticated-read';
		} elseif (
			!$group && 
			$permission == 'FULL_CONTROL'
		) {
			$return = 'private';
		} else {
			$return = '';
		}
		
		return $return;
		
	}
	
	/**
	 * Get count array that is formatted for datasource output
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	int		Count
	 * @return	array
	 */
	protected function _getFormattedCount ($count=0) {
		
		return array(
			0 => array(
				0 => array(
					'count' => $count
				)
			)
		);
		
	}
	
	/**
	 * Gets conditions based on the 'conditions' option in queryData
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array	$conditions
	 * @return	array
	 */
	protected function _getConditions (&$model, $conditions=array()) {
		
		if (empty($conditions)) {
			return array();
		}
		
		foreach ($conditions as $field => $value) {
			
			unset($conditions[$field]);
			
			// We will not support nesting/OR
			if (is_array($value)) {
				continue;
			}
			
			$field = array_pop(explode('.', $field));
			if (strpos($field, ' ') === false) {
				$operator = '=';
			} else {
				list($field, $operator) = explode(' ', $field);
			}
			
			if (!in_array($operator, array('=', '>', '<', '>=', '<=', '!=', '<>', 'LIKE'))) {
				continue;
			}
			
			$conditions[$field] = array(
				'operator' => $operator,
				'value' => $value
			);
			
		}
		
		foreach ($conditions as $field => $value) {
			
			switch ($value['operator']) {
				case 'LIKE':
					$value['operator'] = '=';
					$value['value'] = '/' . str_replace('%', '', $value['value']) . '/i';
					break;
				case '<>':
					$value['operator'] = '!=';
					break;
				default:
					break;
			}
			
			$conditions[$field] = $value;
			
		}
		
		return $conditions;
		
	}
	
	/**
	 * Gets order based on the 'order' option in queryData
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array	$order
	 * @return	array
	 */
	protected function _getOrder (&$model, $order=array()) {
		
		if (empty($order)) {
			return $order;
		}
		
		foreach ($order as $key => $sort) {
			
			unset($order[$key]);
			
			if (!is_array($sort)) {
				continue;
			}
			
			$field = key($sort);
			$dir = current($sort);
			
			if (is_numeric($field)) {
				$exploded = explode(' ' , $dir);
				if (count($exploded) == 2) {
					$field = $exploded[0];
					$dir = $exploded[1];
				}
			}
			
			$field = array_pop(explode('.', $field));
			$dir = strtolower($dir);
			
			if (in_array($dir, array('asc', 'desc'))) {
				$order[$field] = $dir;
				break;
			}
			
		}
		
		return $order;
		
	}
	
	/**
	 * Merge supplied queryData with defaults and return a standardized array
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	array	$queryData
	 * @return	array
	 */
	protected function _getQueryData ($queryData=array()) {
		
		return array_merge(
			array(
				'conditions' => null,
				'fields' => null,
				'joins' => array(),
				'limit' => 1,
				'offset' => null,
				'order' => array(0 => null),
				'page' => 1,
				'group' => null,
				'callbacks' => 1,
				'contain' => false,
				'recursive' => -1
			),
			$queryData
		);
		
	}
	
	/**
	 * Determines which fields need to be requested and returns them as an array
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array	Processed queryData
	 * @return	array
	 */
	protected function _getFieldList (&$model, $queryData=array()) {
		
		extract($queryData);
		
		$is_count = $this->_isCount($fields);
		
		if (!$is_count && is_string($fields)) {
			$fields = array($fields);
		}
		
		if (!is_array($fields)) {
			$fields = array();
		} elseif (is_array($fields) && !empty($fields)) {
			foreach ($fields as $key => $value) {
				$fields[$key] = array_pop(explode('.', $value));
			}
		}
		
		if (empty($fields)) {
			$fields = $this->fields;
		}
		
		if ($is_count && empty($conditions)) {
			$fields = array($model->primaryKey);
		}
		
		// Make sure we have all the fields we need
		if (!empty($conditions)) {
			$tmp_conditions = $this->_getConditions($model, $conditions);
			if (!empty($tmp_conditions)) {
				foreach ($tmp_conditions as $field => $value) {
					if (!in_array($field, $fields)) {
						$fields[] = $field;
					}
				}
			}
		}
		$tmp_order = $this->_getOrder($model, $order);
		if (!empty($tmp_order)) {
			foreach ($tmp_order as $field => $dir) {
				if (!in_array($field, $fields)) {
					$fields[] = $field;
				}
			}
		}
		
		return $fields;
		
	}
	
	/**
	 * Get list of IDs that should be deleted based on conditions
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array	$conditions
	 * @return	mixed	Array if IDs can be found, false if there is an error
	 */
	protected function _getIdsToBeDeleted (&$model, $conditions=array()) {
		
		if (empty($conditions) && !empty($model->id)) {
			$conditions = array($model->alias . '.' . $model->primaryKey => $model->id);
		}
		
		if (empty($conditions)) {
			$this->showError(__($model->alias . '.' . $model->primaryKey . ' must be set in order to delete', true));
			return false;
		}
		
		if (
			count($conditions) == 1 && 
			key($conditions) == $model->alias . '.' . $model->primaryKey
		) {
			$ids = array_shift($conditions);
		} else {
			$ids = array_values($model->find('list', array(
				'fields' => array($model->primaryKey),
				'conditions' => $conditions
			)));
		}
		
		if (!is_array($ids)) {
			$ids = array($ids);
		}
		
		return $ids;
		
	}
	
	/**
	 * Orders a full list of results based on the 'order' option in queryData
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array	$data
	 * @param	array	$order
	 * @return	array
	 */
	protected function _postOrder (&$model, $data=array(), $order=array()) {
		
		if (empty($data) || empty($order) || count($data) < 2) {
			return $data;
		}
		
		$order = $this->_getOrder($model, $order);
		
		if (!empty($order)) {
		
			$data = $this->_convertDates($model, $data, 'unix');
			
			foreach ($order as $field => $dir) {
				$data = Set::sort($data, '{n}.' . $model->alias . '.' . $field, $dir);
			}
			
			$data = $this->_convertDates($model, $data, 'mysql');
		
		}
		
		return $data;
		
	}
	
	/**
	 * Filters a full list of results based on conditions
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array	$data
	 * @param	array	$conditions
	 * @return	array
	 */
	protected function _postFilter (&$model, $data=array(), $conditions=array()) {
		
		if (empty($data) || empty($conditions)) {
			return $data;
		}
		
		$tmp_conditions = $conditions;
		$sample = array_shift($tmp_conditions);
		$conditions = (is_array($sample) && !empty($sample['operator'])) ? $conditions : $this->_getConditions($model, $conditions);
		
		if (empty($conditions)) {
			return $data;
		}
		
		foreach ($conditions as $field => $value) {
			$data = Set::extract('/' . $model->alias . '[' . $field . $value['operator'] . $value['value'] . ']', $data);
		}
		
		return $data;
		
	}
	
	/**
	 * Paginates a full list of results
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	array	$results
	 * @param	int		$page
	 * @param	int		$limit
	 * @return	array
	 */
	protected function _postPaginate ($results=array(), $page=0, $limit=0) {
		
		if (empty($results)) {
			return $results;
		}
		
		return array_slice(
			$results,
			(($page - 1) * $limit),
			$limit
		);
		
	}

	/**
	 * Create
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array 	$fields
	 * @param 	array 	$values
	 * @return	bool
	 */
	public function create (&$model, $fields = null, $values = null) {
		
		$this->showError(__('Create unavailable', true));
		
		return false;
		
	}
	
	/**
	 * Update
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array 	$fields
	 * @param 	array 	$values
	 * @return	bool
	 */
	public function update (&$model, $fields = null, $values = null) {
		
		$this->showError(__('Update unavailable', true));
		
		return false;
		
	}

	/**
	 * Read
	 * 
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array 	$queryData
	 * @return 	array
	 */
	public function read (&$model, $queryData = array()) {
		
		$this->showError(__('Read unavailable', true));
		
		return false;
		
	}
	
	/**
	 * Deletes a record via the API
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @param	array	$conditions
	 * @return	bool
	 */
	public function delete (&$model, $conditions=array()) {
		
		$this->showError(__('Delete unavailable', true));
		
		return false;
		
		if (empty($conditions) && !empty($model->id)) {
			$conditions = array($model->alias . '.' . $model->primaryKey => $model->id);
		}
		
		if (empty($conditions)) {
			$this->showError(__($model->alias . '.' . $model->primaryKey . ' must be set in order to delete', true));
			return false;
		}
		
		if (
			count($conditions) == 1 && 
			key($conditions) == $model->alias . '.' . $model->primaryKey
		) {
			$ids = array_shift($conditions);
		} else {
			$ids = array_values($model->find('list', array(
				'fields' => array($model->primaryKey),
				'conditions' => $conditions
			)));
		}
		
		$this->_loadLibrary();
		
		$result = true;
		
		foreach ($ids as $id) {
			try{
				$result = ($result && $this->S3->deleteBucket($id)) ? true : false;
			} catch (S3Exception $e) {
				$this->showError($e);
				$result = false;
			}
		}
		
		return $result;
		
	}
	
	/**
	 * An overwrite of the calculate() method to get it to play nice with an API-based DataSource
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @return	string
	 */
	public function calculate(&$model) {
		return 'count';
	}
	
	/**
	 * Shows errors based on debug level
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	object	$model
	 * @return	string
	 */
	public function showError($error) {
		
		if (Configure::read('debug') > 0) {
			trigger_error($error, E_USER_WARNING);
		} else {
			$this->log($error);
		}
		
	}

}
?>