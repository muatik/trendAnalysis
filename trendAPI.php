<?php
require_once('trendAnalyzer.php');
$r=$_REQUEST;

if(!isset($r['request']))
	die('Requred parameters not found. Please read the documantation');


class TrendAPI
{
	public function __construct(){
		$this->r=$_REQUEST;
		$this->ta=new trendAnalyzer();
		echo $this->run();
	}

	public function run(){
		switch($this->r['request']){
			case 'analyze': return $this->parseResult($this->analyze());
		}
	}

	public function analyze(){
		$r=$this->r;
		$interval=$r['interval'];
		$ta=$this->ta;
		
		if($interval=='hourly'){
			$ta->frameLength=3600*1;
			$ta->frameDistance=3600*24;
			$ta->sampleLength=60*5;
			$ta->sampleDistance=60*10;
			$ta->presentEnd=strtotime($r['date']);
			$ta->presentStart=$ta->presentEnd-($ta->frameLength);
			$ta->pastEnd=$ta->presentEnd-$ta->frameDistance;
			$ta->pastStart=$ta->presentEnd-(3600*24*8); // 7 days
			return $ta->detect();
		} 
		
		return 'coming soon...';
	}

	public function parseResult($o){
		$r=$this->r;
		$data=array(
			"analysis_id"=>"NULL",
			"monitoringActivity"=>"NULL",
			"interval"=>"NULL",
			"language"=>"NULL",
			"age"=>"NULL",
			"gender"=>"NULL",
			"geo-loc"=>"NULL",
			"date"=>$r['date'],
			);
		$data['pastPeriod']=array(
			"dateStart"=>date("Y-m-d H:i:s",$this->ta->pastStart),
			"dateEnd"=>date("Y-m-d H:i:s",$this->ta->pastEnd));

		$data['presentPeriod']=array(
			"dateStart"=>date("Y-m-d H:i:s",$this->ta->presentStart),
			"dateEnd"=>date("Y-m-d H:i:s",$this->ta->presentEnd));
		$data['entries']=array();
		foreach($o as $i=>$k){
			
			$item=array("event"=>$k['text']);
			$item["terms"]=array();
			foreach($k['intersect'] as $ints){
				$e['term']=$ints['term'];
				$e['presentFreq']=$ints['presentFreq'];
				$e['presentRatio']=$ints['presentRatio'];
				$avgpf=0;
				foreach($ints['pastFreq'] as $pf)
					$avgpf+=$pf['tokenFrequency'];

				if(count($ints['pastFreq'])>1)
					$avgpf=$avgpf/count($ints['pastFreq']);
				else
					$avgpf=0;
				if(count($ints['pastRatio'])>0)
					$avgRatio=array_sum($ints['pastRatio'])/count($ints['pastRatio']);
				else
					$avgRatio=0;

				$e['pastFreq']=$avgpf;
				$e['pastRatio']=$avgRatio;
				$item['terms'][]=$e;
			}
			$data['entries'][]=$item;
		}
		die(json_encode($data));
	}
}

$tapi=new TrendAPI();

?>
