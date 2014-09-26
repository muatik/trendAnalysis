<?php

namespace TrendAnalysis\Stream;
use Silex\Application;
use TrendAnalysis\Libs\SparqlClient;

class Stream
{
	static $error;

	public static $table='content_sample';

	protected $app;

	public function __construct(Application $app){
		$this->app = $app;
		SparqlClient::setEndPoint($app['sparqlEndPoint']);
	}

	public static function get($criteria){

		$data=array();
		$ma=(isset($criteria['ma']))?$criteria['ma']:null;
		$dates=array();

		foreach($criteria['$or'] as $item)
			$dates[]=array($item['at']['$gt'],$item['at']['$lt']);


		$data=SparqlClient::getData(
				//$criteria['keyword'],
				null,
				$dates,
				null,
				$ma
			);

		if ($data)
			return $data;
		else{
			self::$error=SparqlClient::getLastError();
			return false;
		}
	}

	public static function count($criteria){

		$data=array();
		$ma=(isset($criteria['ma']))?$criteria['ma']:null;
		$dates=array();

		foreach($criteria['$or'] as $item)
			$dates[]=array($item['at']['$gt'],$item['at']['$lt']);


		$data=SparqlClient::getDataCount(
				//$criteria['keyword'],
				null,
				$dates,
				null,
				$ma
			);

		if ($data || $data==0)
			return $data;
		else{
			self::$error=SparqlClient::getLastError();
			return false;
		}
	}

	public static function mapreduceHourlyVolume($collection, $domain, $startDate, $endDate){

		$dates=array(array($startDate,$endDate));
		$data=SparqlClient::getData(
			//$domain,
			null,
			$dates
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
			$dates=array(array($date['$gt'],$date['$lt']));
		}

		$keywords=array();
		$keywords[]=$domain;

		if($term!=null)	$keywords[]=$term['$regex'];

		$data=SparqlClient::getData(
			//$keywords,
			null,
			$dates
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

		$data=SparqlClient::getData(
			//$domain,
			null,
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
			$dates=array(array($date['$gt'],$date['$lt']));
		}

		$keywords=array();
		$keywords[]=$domain;

		if($term!=null)	$keywords[]=$term['$regex'];

		$data=SparqlClient::getData(
			//$keywords,
			null,
			$dates,
			null,
			$limit
		);

		return $data;

	}

	public static function getLastError(){
		return self::$error;
	}
}
