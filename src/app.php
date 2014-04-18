<?php

require ROOT."/vendor/autoload.php";

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Monolog\Logger;
use Monolog\Handler\MongoDBHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

if (isset($app)) {
	$appConfig = $app;
}

$app = new Silex\Application();
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
if (isset($appConfig)) {
	foreach($appConfig as $k => $v)
		$app[$k] = $v;
	
	// from now, all configurations will be in $app,
	// so we can unset this
	unset($appConfig);
}

/**
 * registering services
 * */
if ($app['debug']) {
	error_reporting(E_ALL);
	ini_set('display_errors','on');
	$app->register(new \Whoops\Provider\Silex\WhoopsServiceProvider);
}

$app['db.mongodb.getConnection'] = function($app) {
	$connParams = $app['db.config.mongodb'];
	if (isset($connParams['auth']) && $connParams['auth']) {
		$connString = sprintf(
			'mongodb://%s:%s@%s/%s',
			$connParams['username'],
			$connParams['password'],
			$connParams['host'],
			$connParams['database']
		);
	} else {
		$connString = sprintf(
			'mongodb://%s/%s',
			$connParams['host'],
			$connParams['database']
		);
	}

	return new \MongoClient($connString);
};

$app['db.mongodb'] = $app->share(function($app) {
	$connParams = $app['db.config.mongodb'];
	$conn = $app['db.mongodb.getConnection'];
	$db = $conn->selectDB($connParams['database']);
	return $db;
});

$app['logging'] = $app->share(function($app) {

	$logger = new Logger('TrendAnalysis');
	$mongoHandler = new MongoDBHandler(
		$app['db.mongodb.getConnection'], 
		$app['logging.db.databaseName'],
		$app['logging.db.collectionName']
	);
	
	$level = constant('\Monolog\Logger::'.$app['logging.level']);
	$logger->pushHandler($mongoHandler, $level);
	return $logger;
});


$app['stream'] = $app->share(function ($app){
	$stream = false;
	if ($app['streamType']=='local'){
		$stream = new TrendAnalysis\Stream\MongoStream();
		$stream::init($app['db.mongodb']);
	}else if ($app['streamType']=='sparql')
		$stream = new TrendAnalysis\Stream\Stream();
	
	return $stream;
});

require ROOT.'src/TrendAnalysis/router.php';

return $app;
