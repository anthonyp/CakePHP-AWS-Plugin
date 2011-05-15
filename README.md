# AWS Plugin for CakePHP 1.3+

CakePHP plugin containing datasources & models to facilitate interacting with Amazon Web Services.

For now, this plugin only provides basic S3 functionality. It is my hope that over time, more advanced S3 functionality will be included, in addition to support for interacting with other popular AWS services, including EC2, RD2, SQS, SES, CloudFront, and more.

## Installation

1. Extract the downloaded archive from [here](http://github.com/anthonyp/CakePHP-AWS-Plugin/zipball/master)
2. Move or copy the extracted directory anthonyp-CakePHP-AWS-Plugin-[hash] to /path/to/your/app/plugins/aws
3. Copy the AWS setting stubs from the plugin's config/core.php into your app's config/core.php and modify to suit

## Instructions / Currently Supported Functionality

### S3

#### Creating Buckets

	$this->S3Bucket = ClassRegistry::init('AWS.S3Bucket');
	$created = $this->S3Bucket->save(array(
		'S3Bucket' => array(
			'name' => 'some-bucket',
			'location' => 'EU' // This will default to the classic US region if not provided
			'acl' => 'public-read' // This will default to 'private' if not provided
		)
	));
	if ($created) {
		$output = 'Your new bucket was just created with the following name: ' . $this->S3Bucket->id;
	}

#### Deleting Buckets

	$this->S3Bucket = ClassRegistry::init('AWS.S3Bucket');
	$eu_buckets_deleted = $this->S3Bucket->deleteAll(array(
		'location' => 'EU'
	));
	$specific_bucket_deleted = $this->S3Bucket->delete('some-bucket');
	if ($eu_buckets_deleted && $specific_bucket_deleted) {
		$output = 'All of your EU buckets plus the specific bucket you selected were deleted';
	}

#### Listing Buckets

	$this->S3Bucket = ClassRegistry::init('AWS.S3Bucket');
	/*
	 * Always make sure you include only the fields you need here. The datasource does a lot of
	 * work to compile them and you can avoid unnecessary API calls by limiting your fields
	 */
	$buckets = $this->S3Bucket->find('all', array(
		'fields' => array('id', 'name', 'object_count')
	));
	if (!empty($buckets)) {
		$output = 'The following buckets were found: ';
		foreach ($buckets as $key => $bucket) {
			$output .= $bucket['S3Bucket']['name'] . ' (' . $bucket['S3Bucket']['object_count'] . ' object(s))';
			if (isset($buckets[$key+1])) $output .= ', ';
		}
	}

#### Creating Objects

	$this->S3Object = ClassRegistry::init('AWS.S3Object');
	$saved = $this->S3Object->save(array(
		'S3Object' => array(
			'name' => 'some-image.jpg',
			'bucket' => 'some-bucket',
			'folder' => 'some/folder/', // This will default to no folder if not provided
			'acl' => 'public-read', // This will default to 'private' if not provided
			'data' => file_get_contents('/path/to/some/file.jpg') // This must be the actual data you are trying to save, not the file name
		)
	));
	if ($saved) {
		$output = 'Your new object was created with ID ' . $this->S3Object->id; // The ID is compiled as: [bucket-name]:[folder-name][object-name]
	}

#### Deleting Objects

	$this->S3Object = ClassRegistry::init('AWS.S3Object');
	$jpegs_in_folder_deleted = $this->S3Object->deleteAll(array(
		'folder' => 'some/folder/',
		'type' => 'image/jpeg'
	));
	$specific_object_deleted = $this->S3Object->delete('some-bucket:some/folder/some-image.jpg');
	if ($jpegs_in_folder_deleted && $specific_object_deleted) {
		$output = 'All of the JPEGs in some/folder/ plus the specific image you selected were deleted';
	}

#### Listing Objects

	$this->S3Object = ClassRegistry::init('AWS.S3Object');
	/*
	 * Always make sure you include only the fields you need here. The datasource does a lot of
	 * work to compile them and you can avoid unnecessary API calls by limiting your fields.
	 *
	 * Also ensure your conditions are as specific as possible. The datasource can theoretically
	 * find all objects across all buckets, with no/few conditions, but it is inefficient and
	 * unrealistic in practice.
	 */
	$objects = $this->S3Object->find('all', array(
		'fields' => array('id', 'bucket', 'name', 'folder', 'size'),
		'conditions' => array(
			'S3Object.bucket' => 'some-bucket',
			'S3Object.folder' => 'some/folder/',
			'S3Object.size >' => (10 * 1024 * 1024) // Size is in bytes, so we are finding all images over 10MB
		)
	));
	if (!empty($objects)) {
		$output = 'The following >10MB objects were found in the some-bucket bucket and the some/folder/ folder: ';
		foreach ($objects as $key => $object) {
			$output .= $object['S3Object']['name'] . ' (' . ($object['S3Object']['size'] / 1024 / 1024) . ' MB)';
			if (isset($objects[$key+1])) $output .= ', ';
		}
	}

## Roadmap

* S3
    - Save & retrieve metadata with objects
	- Save & retrieve storage classes for objects
    - Object versioning
    - Multipart uploads
    - Website configs (subdomains)
    - Bucket logging
    - Retrieve a secure temporary web or torrent URL for an object
* General
    - Unit tests
    - Integration with EC2, RD2, SQS, SES, CloudFront, and more

## Known Issues

* Conditions do not fully support all operators/options, most notably OR & IN

## Authors

See the AUTHORS file.

## Copyright & License

AWS Plugin for CakePHP is Copyright (c) 2011 Anthony Putignano if not otherwise stated. The code is distributed under the terms of the MIT License. For the full license text see the LICENSE file.