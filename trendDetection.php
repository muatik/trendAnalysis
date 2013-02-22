<?php
require_once('burstyDetection.php');


/**
 * TrendDetection 
 * 
 *
 *
 * @EXAMPLES
 * $exp=new TrendDetection();
 * //1. starting an analysis
 * $exp->setAnalysisInterval('hourly','2013-02-11 20:00');
 * print_r($exp->detect());
 * 
 * //2. getting all the cached analysis
 * print_r($exp->getCachedAnalysis());
 *
 *
 * @uses BurstyDetection
 * @package 
 * @version $id$
 * @author Mustafa Atik <muatik@gmail.com>
 */
class TrendDetection extends BurstyDetection
{

	protected $intervalName;

	public function __construct(){
		parent::__construct();
		$this->dummyStatistics=json_decode(file_get_contents('dummyStatistics.json'));
	}
	
	/**
	 * fetchStream 
	 * 
	 * overriding the parent method
	 *
	 * @param mixed $criteria 
	 * @access protected
	 * @return array
	 */
	protected function fetchStream($criteria){
		return iterator_to_array(stream::get($criteria));
	}

	/**
	 * sets the time of analysis with predefined parameters.
	 * $interval can be "hourly", "daily", "weekly", "monthly"
	 * 
	 * @param string $period 
	 * @param string $periodDate 
	 * @access protected
	 * @return void
	 */
	public function setAnalysisInterval($interval, $date){
		
		$this->intervalName=$interval;
		
		$date=strtotime($date);
		
		if($interval=='hourly'){
			$this->thresholdChi=1.5;
			$this->thresholdRatio=0.009;
			$this->frameLength=3600*1; // 1 hour
			$this->frameDistance=3600*24; // 24 hours
			
			$this->sampleLength=60*5; // 5 minutes
			$this->sampleDistance=60*10; // 10 minutes

			$this->presentEnd=$date;
			$this->presentStart=$this->presentEnd-($this->frameLength); // 1 hour
			
			$this->pastEnd=$this->presentEnd-$this->frameDistance;
			$this->pastStart=$this->presentEnd-(3600*24*8); // 8 days
			
		} elseif ($interval=='daily') {

			$this->frameLength=3600*24; // 24 hours
			$this->frameDistance=3600*24; // same as the frame length for no distance
			
			$this->sampleLength=60*5; // 5 minutes
			$this->sampleDistance=60*30; // 30 minutes

			$this->presentEnd=$date;
			$this->presentStart=$this->presentEnd-($this->frameLength); // 24 hours
			
			$this->pastEnd=$this->presentEnd-$this->frameDistance;
			$this->pastStart=$this->presentEnd-(3600*24*8); // 8 days
		} elseif ($interval=='weekly') {

			$this->frameLength=3600*24*7; // 7 days
			$this->frameDistance=3600*24*7; // same as the frame length for no distance
			
			$this->sampleLength=60*30; //  0.5 hour
			$this->sampleDistance=3600*3; // 3 hours

			$this->presentEnd=$date;
			$this->presentStart=$this->presentEnd-($this->frameLength); // 1 week
			
			$this->pastEnd=$this->presentEnd-$this->frameDistance;
			$this->pastStart=$this->presentEnd-(3600*24*7*4); // 4 weeks
		} elseif ($interval=='monthly') {

			$this->frameLength=3600*24*30; // 7 days
			$this->frameDistance=3600*24*30; // same as the frame length for no distance
			
			$this->sampleLength=60*10; //  10 minutes
			$this->sampleDistance=3600*6; // 6 hours

			$this->presentEnd=$date;
			$this->presentStart=$this->presentEnd-($this->frameLength); // 1 month
			
			$this->pastEnd=$this->presentEnd-$this->frameDistance;
			$this->pastStart=$this->presentEnd-(3600*24*7*30*4); // 4 months
		}
	}

	/**
	 * prepares result of detection for convenient and easy reading
	 * 
	 * @access protected
	 * @return object
	 */
	protected function prepareResult(){
		$o=new Stdclass();
		$r=$this->burstyEvents;
		$dateFormat='Y-m-d H:i:s';

		$o->interval=$this->intervalName;
		//$o->language;
		//$o->language;
		//$o->gender;
		//$o->geo-loc;
		$o->date=date($dateFormat, $this->presentEnd);
		$o->presentPeriod=array(
			'dateStart'=>date($dateFormat,$this->presentStart),
			'dateEnd'=>date($dateFormat,$this->presentEnd)
		);
		$o->pastPeriod=array(
			'dateStart'=>date($dateFormat,$this->pastStart),
			'dateEnd'=>date($dateFormat,$this->pastEnd)
		);
		
		$o->entries=array();
		foreach($r as $event){
			$oe=new stdClass();
			$oe->event=$event['text'];
			$oe->terms=array();
			foreach($event['intersect'] as $i){
				$oei=new stdClass();
				$oei->term=$i['term'];
				$oei->presentFreqeuncy=$i['presentFrequency'];
				$oei->presentRatio=$i['presentRatio'];
				$oei->pastFrequency=0;
				foreach($i['pastFrequency'] as $j)
					$oei->pastFrequency+=$j['frequency'];

				$oei->pastFrequency=(
					$oei->pastFrequency>0?
					$oei->pastFrequency/count($i['pastFrequency']) : 0
				);
				$oei->pastRatio=(
					count($i['pastRatio']) ? 
					array_sum($i['pastRatio']) / count($i['pastRatio']) :0
				);
				$oe->terms[]=$oei;
			}
			$o->entries[]=$oe;
		}
		
		if(count($o->entires)>0)
			$o->statistics=$this->dummyStatistics;

		return $o;

	}
	
	/**
	 * caches the result of analysis into local store 
	 * 
	 * @param object $result 
	 * @access protected
	 * @return object
	 */
	protected function cacheAnalysis($result){
		smongo::$db->analysis->insert($result);
		return $result;
	}

	/**
	 * checks if analysis with specified parameters is already done and
	 * cached in local store. If so, returns cached result
	 * 
	 * @access protected
	 * @return object
	 */
	protected function isAnalysisCached(){
		// one more criteria should be added for user authorization.
		// Analysis request may come from different users for different 
		// domain, source etc. 
		return smongo::$db->analysis->findOne(array(
			"date"=>date('Y-m-d H:i:s',$this->presentEnd),
			"interval"=>$this->intervalName
		));
	}

	/**
	 * returns list of cached analysis in local store
	 * 
	 * @access public
	 * @return array
	 */
	public function getListOfCachedAnalyses(){
		$r=smongo::$db->analysis->find(
			array(),
			array('interval'=>1,'date'=>1)
		);

		$r=iterator_to_array($r);
		foreach($r as $k=>$i){
			$r[$k]['analysis_id']=(string)$i['_id'];
			unset($r[$k]['_id']);
		}

		return $r;
	}
	
	/**
	 * returns the result of the given cached analysis
	 * 
	 * @param id $analysisId 
	 * @access public
	 * @return object
	 */
	public function getCachedAnalysis($analysisId){
		return smongo::$db->analysis->findOne(
			array('_id'=>new MongoID($analysisId))
		);
	}

	/**
	 * returns the information of the event of the cached analysis
	 * 
	 * @param string $analysisId
	 * @param int $eventId
	 * @access public
	 * @return object
	 */
	public function getEventOfAnalysis($analysisId, $eventId){

		$a=smongo::$db->analysis->findOne(
			array(
				'_id'=>new MongoID($analysisId),
				'entries.event'=>$event
			)
		);
		
		if(!isset($a['entries'][$eventId]))
			return false;

		$event=$a['entries'][$eventId)];
		$event['statistics']=$this->dummyStatistics;
		$event['pastPeriod']=$a['pastPeriod'];
		$event['presentPeriod']=$a['presentPeriod'];
		$event['analysis_id']=(string)$a['_id'];
		return $event;	
	}

	/**
	 * overriding the method detect() for saving 
	 * 
	 * @access public
	 * @return array
	 */
	public function detect(){
		$cache=$this->isAnalysisCached();
		if($cache)
			return $cache;
		
		$r=parent::detect();
		$r=$this->prepareResult();
		
		// saving the result of detection into the database for caching
		$r=$this->cacheAnalysis($r);
		
		$r->analysis_id=(string)$r->_id;
		unset($r->_id);
		return $r;
	}
	

}

?>
