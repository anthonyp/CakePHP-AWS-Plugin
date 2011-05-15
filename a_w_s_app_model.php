<?php
/**
 * AWS App Controller File
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
 * @since		0.1
 * @copyright  	2011 Anthony Putignano <contact@anthonyputignano.com>
 * @license    	http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       	http://github.com/anthonyp/CakePHP-AWS-Plugin
 */
class AWSAppModel extends AppModel {
	
	/**
	 * Validation for bucket names
	 * 
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param 	array	$check
	 * @return	bool
	 */
	public function isValidBucketName ($check=array()) {
		
		$bucket = array_shift($check);
		
		if (
			($bucket === null || $bucket === false) ||                  // Must not be null or false
			preg_match('/[^(a-z0-9\-\.)]/', $bucket) ||                 // Must be in the lowercase Roman alphabet, period or hyphen
			!preg_match('/^([a-z]|\d)/', $bucket) ||                    // Must start with a number or letter
			!(strlen($bucket) >= 3 && strlen($bucket) <= 63) ||         // Must be between 3 and 63 characters long
			(strpos($bucket, '..') !== false) ||                        // Bucket names cannot contain two, adjacent periods
			(strpos($bucket, '-.') !== false) ||                        // Bucket names cannot contain dashes next to periods
			(strpos($bucket, '.-') !== false) ||                        // Bucket names cannot contain dashes next to periods
			preg_match('/(-|\.)$/', $bucket) ||                         // Bucket names should not end with a dash or period
			preg_match('/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/', $bucket)    // Must not be formatted as an IP address
		) {
			return false;
		}
		
		return true;
		
	}
	
	/**
	 * beforeSave
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param 	array	$options
	 * @return 	bool
	 */
	public function beforeSave ($options=array()) {
		
		$this->_tmp_schema = $this->_schema;
		foreach ($this->_tmp_schema as $field => $properties) {
			if (!empty($properties['type']) && in_array($properties['type'], array('date', 'datetime'))) {
				unset($this->_tmp_schema[$field]);
			}
		}
		
		return true;
		
	}
	
	/**
	 * afterSave
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param 	bool	$created
	 * @return 	bool
	 */
	public function afterSave ($created=false) {
		
		if (isset($this->_tmp_schema)) {
			$this->_schema = $this->_tmp_schema;
		}
		
		return true;
		
	}
	
}
?>