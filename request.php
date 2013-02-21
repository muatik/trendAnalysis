<?php
header("Content-Type:text/plain;charset=utf-8");
require_once("trendDetection.php");
class trendApi{

	public function run(){

		$td=new TrendDetection();
		$r=$_REQUEST;
		switch ($r['task']){

			case 'startAnalysis':

				if (isset($r['interval'],$r['date'])){					
					$td->setAnalysisInterval($r['interval'],$r['date']);
					echo json_encode($td->detect());
				}

			break;

			case 'cached':				
					json_encode($td->getListOfCachedAnalyses());
			break;

			case 'getAnalysis':

				if (isset($r['analysisId'])){
					echo $r['analysisId'];
					json_encode($td->getCachedAnalysis($r['analysisId']));
				}

			break;

			case 'getEventDetail':
				if (isset($r['analysisId'],$r['eventText'])){
					echo $r['analysisId'].'---'.$r['eventText'];
					json_encode($td->getEventOfAnalysis($r['analysisId'],$r['eventText']));
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
