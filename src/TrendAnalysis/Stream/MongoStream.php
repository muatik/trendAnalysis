<?php

namespace TrendAnalysis\Stream;

class MongoStream
{

	public static $table='content_sample';

	private static $db = null;

	public static function init($db)
	{
		self::$db = $db;
	}

	public static function get($criteria, $asArray=1){
		$docs = self::$db->stream->find(
			$criteria,
			array('id'=>1,'text'=>1,'created_at')
		);
		
		return ($asArray? iterator_to_array($docs) : $docs);
	}

	public static function mapreduceHourlyVolume($collection, $domain, $startDate, $endDate){
		
		$dir = __DIR__.'/../../../resources/dummy/';
		$map=new MongoCode( file_get_contents($dir.'mapStreamByYMDH.js') );
		$reduce=new MongoCode( file_get_contents($dir.'reduceStreamBySumming.js') );
		
		$criterias=array();
		
		$criteria['at']=array(
			'$gt'=>$startDate, '$lt'=>$endDate
		);

		$criteria['keyword']=$domain;

		$res=self::$db->command(array(
			'mapreduce'=>$collection,
			'map'=>$map,
			'reduce'=>$reduce,
			'query'=>$criteria,
			'out'=>array('merge'=>'hourly'.$collection)
		));

		return self::$db->mpHourlyVolume->find();
	}

	public static function getVolume($domain, $date=null, $term=null){
		$criteria=array();

		if($date!=null)	$criteria['at']=$date;
		$criteria['keyword']=$domain;

		if($term!=null)	$criteria['text']=$term;

		$dir = __DIR__.'/../../../resources/dummy/';
		$map=new MongoCode( file_get_contents($dir.'mapStreamByYMDH.js') );
		$reduce=new MongoCode( file_get_contents($dir.'reduceStreamBySumming.js') );

		$res=self::$db->command(array(
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
		$res=self::$db->hourlystream->find( $criteria );

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
		
		return self::$db->stream->find( $criteria )->limit($limit);
		
	}
}
