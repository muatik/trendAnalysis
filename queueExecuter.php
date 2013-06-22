<?php
require_once('trendDetection.php');
require_once('trendQueue.php');

function run(){
	
	$jobs = trendQueue::getAll(
		trendQueueStatus::$waiting
	);
	
	$td = new trendDetection();

	foreach($jobs as $i){
		$td->setStreamCriteria($i['criteria']);
		$td->setAnalysisInterval($i['interval'],$i['date']);
		$n = $td->detect();
		
		if(isset($n['id'])){
			$i['analysisId'] = (string)$n['id'];
			
			trendQueue::update($i);
			
			trendQueue::changeStatus(
				(string)$i['_id'], 
				trendQueueStatus::$completed
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

while(1){
	run();
	sleep(7);
}

?>
