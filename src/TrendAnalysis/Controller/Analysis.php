<?php
namespace TrendAnalysis\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use TrendAnalysis\Trends\TrendDetection;
use TrendAnalysis\Trends\TrendQueue;
use TrendAnalysis\Trends\TrendQueueStatus;
use TrendAnalysis\Helpers\Response;
use \MongoID;

class Analysis implements ControllerProviderInterface
{
	public function connect(Application $app)
	{
		$index = $app['controllers_factory'];

		$index->get('/{interval}/{date}',
			array($this, 'startAnalysisInterval'));

		$index->get('/cached',
			array($this, 'getListOfCachedAnalysesList'));

		$index->get('/{analysisId}',
			array($this, 'getListOfCachedAnalyses'));

		$index->get('/{analysisId}/events/{eventId}',
			array($this, 'getEventOfAnalyses'));

		return $index;
	}

	public function startAnalysisInterval(Application $app, $interval, $date)
	{
		$response = new Response();
		TrendQueue::init($app['db.mongodb']);
		$TrendDetection = new TrendDetection($app['db.mongodb'], $app['stream'], $app['logging']);

		if (isset($interval,$date)){

			$criteria = array();

			$ma = $app['request']->get('ma');
			$queue = $app['request']->get('queue');
			$callback = $app['request']->get('callback');

			if(!empty($ma))
				$criteria = array('ma'=>$ma);

			if($queue == 1){
				$job = TrendQueue::add(
					$date,
					$interval,
					$criteria,
					(empty($callback) ? null : $callback)
				);

				// if the job was already there and completed,
				// return the analysis result related this job.
				if($job['status'] == TrendQueueStatus::$completed)
				        $response->setData($TrendDetection->getCachedAnalysis($job['analysisId']));
				else{

					$job['id'] = (string)$job['_id'];
					unset($job['_id']);
					$job['description'] = 'The job has been inserted into the queue.';
					$response->setData('analysis', $job);
				}
			}
			else{
				$TrendDetection->setStreamCriteria($criteria);
				$TrendDetection->setAnalysisInterval($interval,$date);
				$response->setData('analysis', $TrendDetection->detect());
			}
		}


		return $app->json($response);
	}

	public function getListOfCachedAnalysesList(Application $app)
	{
		$response = new Response();
		$trendDetection = new TrendDetection($app['db.mongodb'], $app['stream'], $app['logging']);
		$response->setData('list', $trendDetection->getListOfCachedAnalyses());

		return $app->json($response);
	}

	public function getListOfCachedAnalyses(Application $app, $analysisId)
	{
		$response = new Response();
		$TrendDetection=new TrendDetection($app['db.mongodb'], $app['stream'], $app['logging']);
		$response->setData('analysis', $TrendDetection->getCachedAnalysis($analysisId));
		return $app->json($response);
	}

	public function getEventOfAnalyses(Application $app, $analysisId, $eventId)
	{
		$response = new Response();
		$TrendDetection=new TrendDetection($app['db.mongodb'], $app['stream'], $app['logging']);
        $response->setData('eventAnalysis', $TrendDetection->getEventOfAnalysis($analysisId, $eventId));
		return $app->json($response);
	}
}
