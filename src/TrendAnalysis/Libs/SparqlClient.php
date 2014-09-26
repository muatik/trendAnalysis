<?php
namespace TrendAnalysis\Libs;

class SparqlClient
{
	static $error;
	static $endpointUrl;

	public function getParams(){

		$r=$_REQUEST;
		if (isset($r['keyword'],$r['startDate'],$r['endDate'])){
			$this->keyword=$r['keyword'];

			if ($r['source']=='twitter.com'){

				$this->startDate=date('Y-m-d',$r['startDate']).'T'.date('H:i:s',$r['startDate']).'Z';
				$this->endDate=date('Y-m-d',$r['endDate']).'T'.date('H:i:s',$r['endDate']).'Z';

			}elseif ($r['source']=='foursquare.com'){

				$this->startDate=$r['startDate'];
				$this->endDate=$r['endDate'];
			}elseif ($r['source']=='qype.co.uk'){

				$this->startDate=date('Y-m-d',$r['startDate']).'T'.date('H:i:s',$r['startDate']);
				$this->endDate=date('Y-m-d',$r['endDate']).'T'.date('H:i:s',$r['endDate']);

			}else{

				$this->startDate=date('Y-m-d',$r['startDate']).'T'.date('H:i:s',$r['startDate']);
				$this->endDate=date('Y-m-d',$r['endDate']).'T'.date('H:i:s',$r['endDate']);
			}

		}else {
			echo "startDate,endDate,keyword parametreleri olmalı.";
			die();
		}

		if (isset($r['lang']))
			$this->filterlang=' && regex(?lang, "'.$r['lang'].'","i") ';
		else
			$this->filterlang='';

		if (isset($r['source']))
			$this->filtersource='&& regex(str(?cw),"'.$r['source'].'") ';
		else
			$this->filtersource='';

		if (isset($r['gender']))
			$this->filtergender=' && regex(?gender, "'.$r['gender'].'","i") ';
		else
			$this->filtergender='';

		if (isset($r['location']))
			$this->filterlocation=' && regex(?location, "'.$r['location'].'","i") ';
		else
			$this->filterlocation='';

	}

	public static function setEndPoint($endpointUrl) {
		self::$endpointUrl = $endpointUrl;
	}

	public static function getData($keywords=null,$dates,
		$lang=null,$ma=null,$limit=null){

		$filterkeywords='';
		if (isset($keywords)){

			if (!is_array($keywords)) $keywords=array($keywords);
			foreach($keywords as $keyword)
				$filterkeywords.=' regex(?title,"'.$keyword.'","i") && ';
		}

		if (isset($lang))
			$filterlang=' && regex(?lang, "'.$lang.'","i") ';
		else
			$filterlang='';

		if (isset($limit))
			$limit=' limit '.$limit;
		else
			$limit='';

		if (isset($ma)){
			$filterma='?cga l2m:associatedMonitoringActivity <'.$ma.'>. ';
		}
		else
			$filterma='';

		$filterdate='';
		foreach($dates as $i){
			$startDate=date('Y-m-d',$i[0]).'T'.date('H:i:s',$i[0]).'Z';
			$endDate=date('Y-m-d',$i[1]).'T'.date('H:i:s',$i[1]).'Z';
			$filterdate.='|| (?date>"'.$startDate.'" &&
				?date<"'.$endDate.'")';
		}

		$filterdate='('.substr($filterdate,2,strlen($filterdate)-2).')';

		$query='
                        SELECT * WHERE {
                                ?cw a sioct:MicroblogPost;
                                dcterms:created ?date;
                                dc:language ?lang;
                                sioc:content ?content;
                                dcterms:title ?title;
                                sioc:has_creator ?user;
                                prov:wasGeneratedBy ?cga.
                                '.$filterma.'
                        FILTER (
                                '.$filterkeywords.$filterdate.'
                                 '.$filterlang.'
                        )
                } order by ?date'.$limit;

		$sq = new Sparql(self::$endpointUrl);
		$qresult=$sq->query($query);

		if ($qresult){

			$result=json_decode($qresult);
			if (count($result->results->bindings)==0) return array();
			return self::parseData($result->results->bindings);

		}else{

			self::$error=$sq->getLastError();
			return false;

		}
	}

	public static function getDataCount($keywords=null,$dates,
	$lang=null,$ma=null,$limit=null){

		$filterkeywords='';
		if (isset($keywords)){

			if (!is_array($keywords)) $keywords=array($keywords);
			foreach($keywords as $keyword)
				$filterkeywords.=' regex(?title,"'.$keyword.'","i") && ';
		}

		if (isset($lang))
			$filterlang=' && regex(?lang, "'.$lang.'","i") ';
		else
			$filterlang='';

		if (isset($limit))
			$limit=' limit '.$limit;
		else
			$limit='';

		if (isset($ma)){
			$filterma='?cga l2m:associatedMonitoringActivity <'.$ma.'>. ';
		}
		else
			$filterma='';

		$filterdate='';
		foreach($dates as $i){
			$startDate=date('Y-m-d',$i[0]).'T'.date('H:i:s',$i[0]).'Z';
			$endDate=date('Y-m-d',$i[1]).'T'.date('H:i:s',$i[1]).'Z';
			$filterdate.='|| (?date>"'.$startDate.'" &&
				?date<"'.$endDate.'")';
		}

		$filterdate='('.substr($filterdate,2,strlen($filterdate)-2).')';

		$query='
                        SELECT (count(*) as ?amount) WHERE {
                                ?cw a sioct:MicroblogPost;
                                dcterms:created ?date;
                                dc:language ?lang;
                                sioc:content ?content;
                                dcterms:title ?title;
                                sioc:has_creator ?user;
                                prov:wasGeneratedBy ?cga.
                                '.$filterma.'
                        FILTER (
                                '.$filterkeywords.$filterdate.'
                                 '.$filterlang.'
                        )
                }';

		$sq = new Sparql(self::$endpointUrl);
		$qresult=$sq->query($query);

		if ($qresult){

			$result=json_decode($qresult);
			return $result->results->bindings[0]->amount->value;

		}else{

			self::$error=$sq->getLastError();
			return false;

		}
	}

	public static function parseData(&$bindings){

		/**
		 * topic içinde aranan source metinleri
		 * */
		$source=array('twitter.com','facebook.com','foursquare.com','qype.co.uk');

		foreach($bindings as $k=>$item){

			$o=array();

			$date=strtotime(str_replace(array('T','Z'),array(' ',' '),$item->date->value));

			$o['time']=$date;

			if (empty($item->title->value)) continue;
				$o['text']=$item->title->value;

			if (isset($item->cw)){

				foreach($source as $src){
					if (strstr($item->cw->value,$src))
						$o['source']=$src;
				}

				if (!isset($o->source))
					$o['source']=$item->cw->value;
			}else
				$o['source']=null;

			/*
			file_put_contents('x.txt',$o->content);
			exec ('python /var/www/myhost/ldig/ldig.py -m /var/www/myhost/ldig/models/model.latin x.txt>s.txt');
			$c=file_get_contents('s.txt');
			$lang=trim(mb_substr($c,0,3));
			$o->lang=$lang;*/

			if (isset($item->lang))
				$o['lang']=$item->lang->value;
			else
				$o['lang']=null;

			if (isset($item->location))
				$o['location']=$item->location->value;
			else
				$o['location']=null;

			if (isset($item->gender))
				$o['gender']=$item->gender-value;
			else
				$o['gender']=null;


			if (isset($item->user)){
				preg_match('/[^http:\/\/twitter.com\/].*[^#me]/',$item->user->value,$m);
				if (isset($m[0]))
					$o['user']=$m[0];
			}
			else
				$o['user']=null;

			$bindings[$k]=$o;
		}

		return $bindings;
	}

	public static function getLastError(){
		return self::$error;
	}
}

//print_r(sparqlClient::getData('pepsi',1300068842,1360068842));
?>
