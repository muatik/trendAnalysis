<?php
namespace TrendAnalysis\Trends;

class TrendQueueStatus
{

	public static $waiting = 'waiting';
	public static $completed = 'completed';
	public static $cancelled= 'cancelled';
	public static $running = 'running';
	public static $inProgress= 'inProgress';
	
	public static function get(){
		return array(
			'waiting',
			'cancelled',
			'running',
			'inProgress',
			'completed'
		);
	}
}