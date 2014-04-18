<?php
require_once __DIR__ . '/../resources/config/dev.php';

use Silex\Application;
$app = require __DIR__ . '/../src/app.php';
$app->run();

use TrendAnalysis\Trends\TrendDetection;
use TrendAnalysis\Trends\TrendQueue;
use TrendAnalysis\Trends\TrendQueueStatus;

function run($trendDetection, $logging){

	$jobs = TrendQueue::getAll(
		TrendQueueStatus::$waiting
	);
	
	if ($jobs->count()>0) {
		$logging->addDebug($jobs->count().' queued jobs are in progress now');
	}

	foreach($jobs as $i){
		$trendDetection->setStreamCriteria($i['criteria']);
		$trendDetection->setAnalysisInterval($i['interval'],$i['date']);
		$n = $trendDetection->detect();
		
		if(isset($n['id'])){
			$i['analysisId'] = (string)$n['id'];
			
			TrendQueue::update($i);
			
			TrendQueue::changeStatus(
				(string)$i['_id'], 
				TrendQueueStatus::$completed
			);

		}
		
		echo date('Y-m-d H:i:s')." - ".$i['_id']." is completed.\n";
		makeCallback($i);
	}	
}

function makeCallback($job){
	if(!isset($job['callback']) && $job['callback'] == '')
		return false;

	$url = $job['callback'];
	$url = str_replace('$ANALYSIS_ID', $job['analysisId'], $url);
	@file_get_contents($url);
	return true;
}


TrendQueue::init($app['db.mongodb']);
$trendDetection=new TrendDetection($app['db.mongodb'], $app['stream'], $app['logging']);

while(1){
	run($trendDetection, $app['logging']);
	sleep(5);
}

?>
