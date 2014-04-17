<?php
header('Content-Type: text/plain; charset=utf-8');
class sparql{
	
	static $error;
	
	public $prefix="
		PREFIX owl: 	<http://www.w3.org/2002/07/owl#>
		PREFIX xsd: 	<http://www.w3.org/2001/XMLSchema#>
		PREFIX rdfs: 	<http://www.w3.org/2000/01/rdf-schema#>
		PREFIX rdf: 	<http://www.w3.org/1999/02/22-rdf-syntax-ns#>
		PREFIX foaf: 	<http://xmlns.com/foaf/0.1/>
		PREFIX dc:		<http://purl.org/dc/elements/1.1/>
		PREFIX dcterms:	<http://purl.org/dc/terms/>
		PREFIX rev: 	<http://purl.org/stuff/rev#>
		PREFIX gr:		<http://purl.org/goodrelations/v1#>
		PREFIX skos:	<http://www.w3.org/2004/02/skos/core#>
		PREFIX sioct:	<http://rdfs.org/sioc/types#>
		PREFIX sioc:	<http://rdfs.org/sioc/ns#>
		PREFIX schema:	<http://schema.org/>
		PREFIX prov:	<http://www.w3.org/ns/prov-o/>
		PREFIX l2m:		<http://vacab.deri.ie/l2m#>
	";
	
	public $endpoint='http://vmegov01.deri.ie:8080/l2m/query?query=';	
	
	public function query($query,$output='json',$stylesheet='/xml-to-html.xsl'){
		
		$query=$this->endpoint.
			urlencode($this->prefix).urlencode($query).
			'&output='.$output.'&stylesheet='.urlencode($stylesheet);
		
		$ctx=stream_context_create(array('http'=>array(
		        'timeout' => 120 // 2 minutes
		    )
		));

		$contents=@file_get_contents($query,false,$ctx);
		if ($contents)
			return $contents;
		else {
			self::$error='Doesn\'t connect this endpoint:'.$this->endpoint;
			return false;
		}
	}
	
	public function getLastError(){
		return self::$error;
	}
}

class sparqlClient	
{
	static $error;
	
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
	
		$sq=new sparql();
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
	
		$sq=new sparql();
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
