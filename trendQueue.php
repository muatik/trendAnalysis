<?php
require_once('main.php');

/**
 * The queue system for the trend analyzer
 * 
 * EXAMPLES
 *
 * 1. adding
 * -------------------------------------------------------
 * trendQueue::add('2013-06-21 18:30:00', 'hourly');
 *
 *
 * 2. adding with some criteria
 * -------------------------------------------------------
 * trendQueue::add('2013-06-21 18:30:00', 'hourly', array(
 * 	'ma' => 'the monitoring activity url',
 * 	'lang' => 'en'
 * ));
 *
 *
 * 3. getting the list of items in the queue
 * -------------------------------------------------------
 * $jobs = trendQueue::getList();
 * 
 *
 * 4. getting the list of filtered items 
 * -------------------------------------------------------
 * $jobs = trendQueue::getList(trendQueueStatus::$waiting);
 *
 *
 * 5. changing a job's status
 * -------------------------------------------------------
 * trendQueue::changeStatus('the id of the job', trendQueueStatus::$completed);
 *
 *
 *
 * @package 
 * @version $id$
 * @author Mustafa Atik <muatik@gmail.com>
 */
class trendQueue
{

	/**
	 * adds a job into the queue. Every new job's status is 'waiting'.
	 * 
	 * @param string $date 
	 * @param string $interval 
	 * @param array $criteria 
	 * @static
	 * @access public
	 * @return array
	 */
	public static function add($date, $interval, $criteria = array()){
		
		$n = array(
			'at' => time(),
			'status' => trendQueueStatus::$waiting,
			'date' => $date,
			'interval' => $interval,
			'criteria' => $criteria
		);
			
		smongo::$db->queue->insert($n);
		return $n;
	}


	/**
	 * changes the given job's status.
	 * 
	 * @param int $id 
	 * @param string $status 
	 * @static
	 * @access public
	 * @return void
	 */
	public static function changeStatus($id, $status){
		$n = self::get($id);
		if($n === false)
			return false;

		$n['status'] = $status;
		$n['changed'] = time();

		smongo::$db->queue->save($n);
	}


	/**
	 * returns the job matches the given id
	 * 
	 * @param mixed $id 
	 * @static
	 * @access public
	 * @return void
	 */
	public static function get($id){
		return smongo::$db->queue->findOne(
			array('_id' => new MongoID($id))
		);
	}


	/**
	 * returns the job list in the queue. 
	 * Passing a status filters the list.
	 * 
	 * @param string $status 
	 * @static
	 * @access public
	 * @return array
	 */
	public static function getAll($status = null){
		$filter= array();
		
		if($status != null)
			$filter['status'] = $status;

		return smongo::$db->queue->find($filter);
	}

}


/**
 * The list of status for the queue system.
 * 
 * @author Mustafa Atik <muatik@gmail.com>
 */
class trendQueueStatus
{

	public static $waiting = 'waiting';
	public static $completed = 'completed';
	public static $calceled = 'calceled';
	public static $running = 'running';
	
	public static function get(){
		return array(
			'waiting',
			'calcaled',
			'running',
			'completed'
		);
	}

}


?>
