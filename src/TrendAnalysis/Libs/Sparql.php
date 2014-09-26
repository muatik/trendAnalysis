<?php

namespace TrendAnalysis\Libs;

class Sparql
{

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

	public function __construct($endpointUrl) {
		if (!$endpointUrl)
			throw new \Exception("Sparql requires its endpointurl to be set");

		$this->endpoint = $endpointUrl;
	}

	public function query($query,$output='json',$stylesheet='/xml-to-html.xsl'){

		$query=$this->endpoint.
			urlencode($this->prefix).urlencode($query).
			'&output='.$output.'&stylesheet='.urlencode($stylesheet);

		$ctx=stream_context_create(array('http'=>array(
		        'timeout' => 120 // 2 minutes
		    )
		));

		$contents = @file_get_contents($query,false,$ctx);
		if ($contents) {
			return $contents;
		}
		else {
			self::$error='cannot connect to the endpoint '.$this->endpoint;
			return false;
		}
	}

	public function getLastError(){
		return self::$error;
	}
}
