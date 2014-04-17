<?php
date_default_timezone_set('Europe/Istanbul');
define('ROOT',dirname(__DIR__).'/../');
$app['debug'] = true;
$app['locale'] = 'en';
$app['resources_path'] = realpath(ROOT.'/resources/');

/**
 * DATA STORAGE CONFIGURATIONS
 * ------------------------------------------------------------------
 * This section sets where the application will save its data such 
 * as topics, streams, users' configurations, alarms etc.
 */

// For MongoDB, configure and comment out the following set:
$app['db.config.mongodb'] = array(
	'host' => 'localhost',
	'database' => 'trendAnalysis_dev',
	'auth' => false, // Does mongodb require authentication?
	'user' => '',
	'password' => ''
);


/**
 * LOGGING CONFIGURATIONS
 * ------------------------------------------------------------------
 * Warning: This is about the applications itself loging, not projects.
 */

// if you would like to save logs in a text file, use the following set:
$app['logging.handler'] = 'mongodb';
$app['logging.db.databaseName'] = $app['db.config.mongodb']['database'];
$app['logging.db.collectionName'] = 'log';
$app['logging.level'] = 'DEBUG';