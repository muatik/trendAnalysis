<?php
namespace TrendAnalysis\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use TrendAnalysis\Helpers\Response;
use \MongoID;

class Analysis implements ControllerProviderInterface
{
	public function connect(Application $app)
	{
		$index = $app['controllers_factory'];

		$index->get('/startAnalysis/{interval}/{date}', 
			array($this, 'startAnalysisInterval'));

		$index->get('/cachedAnalyses', 
			array($this, 'getListOfCachedAnalysesList'));

		$index->get('/cachedAnalyses/{analysisId}', 
			array($this, 'getListOfCachedAnalyses'));

		$index->get('/analyses/{analysisId}/events/{eventId}', 
			array($this, 'getEventOfAnalyses'));

		return $index;
	}

	public function startAnalysisInterval(Application $app, $interval, $date) 
	{
		$response = new Response();

		return $app->json($response);
	}

	public function getListOfCachedAnalysesList(Application $app) 
	{
		$response = new Response();
		
		return $app->json($response);
	}

	public function getListOfCachedAnalyses(Application $app, $analysisId) 
	{
		$response = new Response();
		
		return $app->json($response);
	}

	public function getEventOfAnalyses(Application $app, $analysisId, $eventId) 
	{
		$response = new Response();
		
		return $app->json($response);
	}
}
