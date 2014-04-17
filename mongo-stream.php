<?php
require_once('main.php');

class Stream
{

	public static $table='content_sample';

	public static function get($criteria, $asArray=1){
		$docs = smongo::$db->stream->find(
			$criteria,
			array('id'=>1,'text'=>1,'created_at')
		);
		
		return ($asArray? iterator_to_array($docs) : $docs);
	}

	public static function mapreduceHourlyVolume($collection, $domain, $startDate, $endDate){
		
		$map=new MongoCode( file_get_contents('resources/mapStreamByYMDH.js') );
		$reduce=new MongoCode( file_get_contents('resources/reduceStreamBySumming.js') );
		
		$criterias=array();
		
		$criteria['at']=array(
			'$gt'=>$startDate, '$lt'=>$endDate
		);

		$criteria['keyword']=$domain;

		$res=smongo::$db->command(array(
			'mapreduce'=>$collection,
			'map'=>$map,
			'reduce'=>$reduce,
			'query'=>$criteria,
			'out'=>array('merge'=>'hourly'.$collection)
		));

		return smongo::$db->mpHourlyVolume->find();
	}

	public static function getVolume($domain, $date=null, $term=null){
		$criteria=array();

		if($date!=null)	$criteria['at']=$date;
		$criteria['keyword']=$domain;

		if($term!=null)	$criteria['text']=$term;

		$map=new MongoCode( file_get_contents('resources/mapStreamByYMDH.js') );
		$reduce=new MongoCode( file_get_contents('resources/reduceStreamBySumming.js') );

		$res=smongo::$db->command(array(
			'mapreduce'=>'stream',
			'map'=>$map,
			'reduce'=>$reduce,
			'query'=>$criteria,
			'out'=>array('inline'=>'1')
		));
		
		$list=array();
		foreach($res['results'] as $i)
			$list[$i['value']['t']]=$i['value']['c'];

		ksort($list);
		return $list;
	}

	public static function getDomainVolume($domain){
		
		$criteria=array('value.d'=>$domain);
		$res=smongo::$db->hourlystream->find( $criteria );

		$list=array();
		foreach($res as $i)
			$list[$i['value']['t']]=$i['value']['c'];

		ksort($list);
		return $list;
	}

	public static function getStreamByTerm($domain, $term, $date=null,$limit=10){
		
		$criteria=array();

		if($date!=null)	$criteria['at']=$date;
		$criteria['keyword']=$domain;

		if($term!=null)	$criteria['text']=$term;
		
		return smongo::$db->stream->find( $criteria )->limit($limit);
		
	}
	
}

?>
