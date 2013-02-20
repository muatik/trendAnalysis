<?php
require_once('burstyDetection.php');


/**
 * TrendDetection 
 * 
 * @example
 * $exp=new TrendDetection();
 * $exp->setAnalysisInterval('daily','2013-02-10 21:00');
 * $exp->detect();
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
		return $o;

	}

	/**
	 * checks if analysis with specified parameters is already done and
	 * cached in local store. If so, returns cached result
	 * 
	 * @access protected
	 * @return object
	 */
	protected function isCached(){
		// one more criteria should be added for user authorization.
		// Analysis request may come from different users for different 
		// domain, source etc. 
		return smongo::$db->analysis->findOne(array(
			"date"=>date('Y-m-d H:i:s',$this->presentEnd),
			"interval"=>$this->intervalName
		));
	}

	/**
	 * overriding the method detect() for saving 
	 * 
	 * @access public
	 * @return array
	 */
	public function detect(){
		$cache=$this->isCached();
		if($cache)
			return $cache;
		
		$r=parent::detect();
		$r=$this->prepareResult();
		
		// saving the result of detection into the database for caching
		smongo::$db->analysis->insert($r);
		
		$r->analysis_id=(string)$r->_id;
		unset($r->_id);
		return $r;
	}
	

}


$exp=new TrendDetection();
$exp->setAnalysisInterval('hourly','2013-02-11 22:00');
echo json_encode($exp->detect());

?>
