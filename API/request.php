<?php
header("Content-Type:text/plain;charset=utf-8");
require_once("../trendDetection.php");
class trendApi{

	public function run(){

		$td=new TrendDetection();
		$r=$_REQUEST;
		switch ($r['task']){

			case 'startAnalysis':

				if (isset($r['interval'],$r['date']))
					print_r($td->setAnalysisInterval($r['interval'],$r['date']));				

			break;

			case 'cached':
					print_r($td->getCachedAnalysis());
			break;

			case 'getAnalysis':

				if (isset($r['analysisId']))
					print_r($td->getCachedAnalysis($r['analysisId']));

			break;

			case 'getEventDetail':
				if (isset($r['analysisId'],$r['eventText'])){
					echo $r['eventText'];die();			
					print_r($td->getEventDetail($r['analysisId'],$r['eventText']));
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
