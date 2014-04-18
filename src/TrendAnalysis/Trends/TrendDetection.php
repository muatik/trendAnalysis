<?php

namespace TrendAnalysis\Trends;

use TrendAnalysis\Stream\Stream;
use \MongoID;
use \stdClass;
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

	/**
	 * overriding
	 *
	 * @var array
	 * @access protected
	 */
	protected $streamCriteria;
	
	public function __construct($db, $stream, $logging){

		$this->logging = $logging;
		$this->logging->addDebug('--------The Analyzer started---------');
		parent::__construct($logging);

		$this->db = $db;
		$this->stream = $stream;

		$dummyFile = __DIR__.'/../../../resources/dummy/dummyStatistics.json';
		if (file_exists($dummyFile))
			$this->dummyStatistics=json_decode(file_get_contents($dummyFile));
		else
			$this->dummyStatistics='';		
	}
	
	/**
	 * fetchStream 
	 * 
	 * overriding the parent method
	 *
	 * @param array $streamCriteria 
	 * @access protected
	 * @return array
	 */
	protected function fetchStream($streamCriteria)
	{
		return $this->stream->get($streamCriteria);
	}

	/**
	 * sets the initial(default) criteria to restrict data stream 
	 * this may be an empty array.
	 * 
	 * @param array $criteria
	 * @access public
	 * @return void
	 */
	public function setStreamCriteria($criteria)
	{
		if (isset($criteria['ma']))
			$this->logging->addDebug('monitoring activity:'.json_encode($criteria['ma']));

		$this->streamCriteria=$criteria;
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
	public function setAnalysisInterval($interval, $sdate){
		$this->intervalName=$interval;
		
		$date=strtotime($sdate);
		
		if($interval=='hourly'){
			$this->logging->addDebug('setting analysis interval: hourly '.$sdate);
			$this->thresholdChi=1.5;
			$this->thresholdRatio=0.009;
			$this->frameLength=3600*1; // 1 hour
			$this->frameDistance=3600*24; // 24 hours
			
			$this->sampleLength=60*5; // 60 minutes, old value 5
			$this->sampleDistance=60*0; // 0 minutes, because of low amount of data in LDS, old value 10

			$this->presentEnd=$date;
			$this->presentStart=$this->presentEnd-($this->frameLength); // 1 hour
			
			$this->pastEnd=$this->presentEnd-$this->frameDistance;
			$this->pastStart=$this->presentEnd-(3600*24*8); // 8 days
			
		} elseif ($interval=='daily') {

			$this->logging->addDebug('setting analysis interval: daily '.$sdate);
			$this->frameLength=3600*24; // 24 hours
			$this->frameDistance=3600*24; // same as the frame length for no distance
			
			$this->sampleLength=60*240; // 240 minutes
			$this->sampleDistance=60*0; // 0 minutes, because of low amount of data in LDS

			$this->presentEnd=$date;
			$this->presentStart=$this->presentEnd-($this->frameLength); // 24 hours
			
			$this->pastEnd=$this->presentEnd-$this->frameDistance;
			$this->pastStart=$this->presentEnd-(3600*24*8); // 8 days
		} elseif ($interval=='weekly') {

			$this->logging->addDebug('setting analysis interval: weekly '.$sdate);
			$this->frameLength=3600*24*7; // 7 days
			$this->frameDistance=3600*24*7; // same as the frame length for no distance
			
			$this->sampleLength=60*30; //  0.5 hour
			$this->sampleDistance=3600*3; // 3 hours

			$this->presentEnd=$date;
			$this->presentStart=$this->presentEnd-($this->frameLength); // 1 week
			
			$this->pastEnd=$this->presentEnd-$this->frameDistance;
			$this->pastStart=$this->presentEnd-(3600*24*7*4); // 4 weeks
		} elseif ($interval=='monthly') {

			$this->logging->addDebug('setting analysis interval: monthly '.$sdate);
			$this->frameLength=3600*24*30; // 30 days
			$this->frameDistance=3600*24*30; // same as the frame length for no distance
			
			$this->sampleLength=60*120; //  120 minutes
			$this->sampleDistance=3600*12; // 12 hours

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
		$this->logging->addDebug('preparing the result');
		$o=new stdClass();
		$r=$this->burstyEvents;
		if(!is_array($r)) 
			$r=array();
		
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
		
		if($this->getLastError() !== '')
			$o->error = $this->getLastError();
		
		$o->entries=array();
		$eventId=0;
		foreach($r as $event){
			$oe=new stdClass();
			$oe->event=$event['text'];
			$oe->eventId=$eventId;
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
			$eventId++;
		}
		
		if(count($o->entries)>0)
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
		$this->logging->addDebug('caching the result of the analysis');
		$this->db->analysis->insert($result);
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
		return $this->db->analysis->findOne(array(
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

		$this->loggin->addDebug('getting list of cached analysis');

		$r=$this->db->analysis->find(
			array(),
			array('interval'=>1,'date'=>1)
		);

		$r=iterator_to_array($r);
		foreach($r as $k=>$i){
			$r[$k]['id']=(string)$i['_id'];
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
		$a = $this->db->analysis->findOne(
			array('_id'=>new MongoID($analysisId))
		);
		
		if($a) {
			$a['id'] = (string)$a['_id'];
			unset($a['_id']);
		}

		return $a;
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

		$a=$this->db->analysis->findOne(
			array(
				'_id'=>new MongoID($analysisId)
			)
		);
		
		if(!isset($a['entries'][$eventId]))
			return false;

		$event=$a['entries'][$eventId];
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
		/*
		$cache=$this->isAnalysisCached();
		if($cache)
			return $cache;
*/
		$r=parent::detect();
		$r=$this->prepareResult();
		
		// saving the result of detection into the database for caching
		$r=$this->cacheAnalysis($r);
		
		$r->id=(string)$r->_id;
		unset($r->_id);
		return (array)$r;
	}
}
