<?php
header("Content-Type:text/plain;charset=utf-8");

require_once("../trendDetection.php");
class trendApi{

	public function run(){

		$td=new TrendDetection();
		$r=$_REQUEST;
		switch ($r['task']){

		case 'startAnalysisInterval':

				if (isset($r['interval'],$r['date'])){					
					$td->setAnalysisInterval($r['interval'],$r['date']);
					echo json_encode($td->detect());
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
