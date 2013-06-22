<?php
header("Content-Type:text/plain;charset=utf-8");

require_once("../trendQueue.php");
require_once("../trendDetection.php");
class trendApi{

	public function run(){

		$td=new TrendDetection();
		$r=$_REQUEST;
		switch ($r['task']){

		case 'startAnalysisInterval':

			if (isset($r['interval'],$r['date'])){

				$criteria = array();

				if(isset($r['ma']))
					$criteria = array('ma'=>$r['ma']);
			
				if(isset($r['queue']) && $r['queue'] == 1){
					
					$job = trendQueue::add(
						$r['date'], 
						$r['interval'], 
						$criteria,
						(isset($r['callback']) ? $r['callback'] : null )
					);

					// if the job was already there and completed, 
					// return the analysis result related this job.
					if($job['status'] == trendQueueStatus::$completed)
						echo json_encode($td->getCachedAnalysis($job['analysisId']));
					else{
						$job['description'] = 'The job has been inserted into the queue.';
						echo json_encode($job);
					}
					
				}
				else{
					$td->setStreamCriteria($criteria);				
					$td->setAnalysisInterval($r['interval'],$r['date']);
					echo json_encode($td->detect());
				}

			}

			break;

			case 'getListOfCachedAnalyses':				
					echo json_encode($td->getListOfCachedAnalyses());
			break;

			case 'getCachedAnalysis':

				if (isset($r['analysisId'])){
					echo json_encode($td->getCachedAnalysis($r['analysisId']));
				}

			break;

			case 'getEventOfAnalysis':
				if (isset($r['analysisId'],$r['eventId'])){
					echo json_encode($td->getEventOfAnalysis($r['analysisId'],$r['eventId']));
				}
			break;

			default: 
				echo "parameters are required";die();
			break;
		}
	}
}

$api=new trendApi();
$api->run();

?>
