<?php
return array(

	// the big switch to enable/disable the logging
	// note that this doesn't affect the display of statistics
	'enabled' => TRUE,

	// enable the detailed logging that keeps track of each uri that the visitors visit in trails
	// it will make database space footprint to increase fast
	// (note: if you disable this, HitLogger will utilize Kohana::cache to keep temporary trails of visitors so it's one way or another :P)
	'enable_trails' => TRUE,

	// auto truncate trails that are at least this number of days old
	// set to 0 to keep trails forever (not recommended)
	'trails_cleanup_age' => 1,

	// the database profile to use (leave empty string to use default profile)
	'db_profile' => '',

	// tables prefix (useful if you want to use the same database for separate hitlogger instances for different websites)
	'tbl_prefix' => '',

	// IPs you don't want logged (supports wildcards, e.g '192.168.*')
	'incognito_ip' => array(
	),

	// Hostnames you don't want logged (supports wildcards, e.g '*googlebot*')
	'incognito_host' => array(
	),

	// translate URI before recording it -- useful to group URI together as to not clutter statistics with uri 'noises'
	// * the source URI supports regular expressions, e.g. 'hitlogger(.*)'
	// * use references {1}, {2} so on in target URI
	// * translation will be chained
	// * translate to NULL to skip the URI from logging (warning: skipped URI splits the trail logs)
	'translate_uri' => array(
		'hitlogger(.*)' => NULL,
		'hitmeter(.*)' => NULL,
		'captcha(.*)' => NULL,
	),

	// page title
	'page_title' => 'HitLogger',

	// number images that are used to generate hitmeter
	'num_images_path' => MODPATH.'hitlogger/numbers/a/',
	'num_images_ext' => 'png',

	// javascript/image file paths
	'filepaths' => array(
		'jquery' => url::base(FALSE, FALSE).'hitlogger_files/js/jquery.min.js',
		'flot' => url::base(FALSE, FALSE).'hitlogger_files/js/jquery.flot.min.js',
		'excanvas' => url::base(FALSE, FALSE).'hitlogger_files/js/excanvas.min.js',
		'treeview' => url::base(FALSE, FALSE).'hitlogger_files/js/jquery.treeview.min.js',
		'images' => url::base(FALSE, FALSE).'hitlogger_files/images',
		'css' => url::base(FALSE, FALSE).'hitlogger_files/styles.css',
	),

	// how long (in seconds) to keep the session cache file on the disk
	// this file is used to flag pages as visited so that we don't double count
	'cache_timeout' => 600,

);