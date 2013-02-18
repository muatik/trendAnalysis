<?php
require_once('main.php');
require_once('sparqlClient.php');

class Stream
{
	public static $table='content_sample';

	public static function get($criteria){
		
		$data=array();
		foreach($criteria['$or'] as $item){			
			$data[]=sparqlClient::getData(
				$criteria['keyword'],
				$item['at']['$gt'],
				$item['at']['$lt']
			);
		}
		
		return $data;
	}

	public static function mapreduceHourlyVolume($collection, $domain, $startDate, $endDate){
				
		$data=sparqlClient::getData(
			$domain,
			$startDate,
			$endDate
		);
		
		$result=array();
		foreach($data as $i){
			
			$hour=date('Y-m-d H',$i['time']);
			
			if (isset($result[$hour]))
				$result[$hour]++;
			else 
				$result[$hour]=1;
		}
		
		return $result;		
	}

	public static function getVolume($domain, $date=null, $term=null){
	
		if($date!=null)	{
			$startDate=$date['$gt'];
			$endDate=$date['$lt'];
		}
		
		$keywords=array();
		$keywords[]=$domain;

		if($term!=null)	$keywords[]=$term['$regex'];
		
		$data=sparqlClient::getData(
			$keywords,
			$startDate,
			$endDate
		);
		
		$result=array();
		foreach($data as $i){
			
			if (isset($result[$i['time']]))
				$result[$i['time']]++;
			else 
				$result[$i['time']]=1;
		}
		
		ksort($result);
		
		return $result;
	}

	public static function getDomainVolume($domain){
		
		$data=sparqlClient::getData(
			$domain,
			$startDate,
			$endDate
		);
		
		$result=array();
		foreach($data as $i){
			
			$hour=date('Y-m-d H',$i['time']);
			
			if (isset($result[$hour]))
				$result[$hour]++;
			else 
				$result[$hour]=1;
		}
		
		ksort($result);
		
		return $result;
	}

	public static function getStreamByTerm($domain, $term, $date=null,$limit=10){
		
		if($date!=null)	{
			$startDate=$date['$gt'];
			$endDate=$date['$lt'];
		}
		
		$keywords=array();
		$keywords[]=$domain;

		if($term!=null)	$keywords[]=$term['$regex'];
		
		$data=sparqlClient::getData(
			$keywords,
			$startDate,
			$endDate,
			null,
			$limit
		);
		
		return $data;
		
	}
	
}

?>
