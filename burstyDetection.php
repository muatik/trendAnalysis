<?php
require_once('main.php');


/**
 * BurstyDetection
 * 
 * This class detects bursty terms and bursty events in a time frame.
 * A bursty term or a bursty event is a word or a text whose frequency 
 * is suddenly increasing in present time frame regarding past time frames.
 * 
 * No need for stopwords or language specific resources, parameters. The 
 * algorithm can detect stopwords and eliminate them easily. But if a stopword,
 * for example "the" in English, emerges dramatically, this word will not be
 * considired as a stopword but a bursty term.
 *
 * @package 
 * @version $id$
 * @author Mustafa Atik <muatik@gmail.com> 
 */
class BurstyDetection
{
	/**
	 * present start in unixtimestamp
	 * 
	 * @var int
	 * @access public
	 */
	public $presentStart;

	/**
	 * present end in unixtimestamp
	 * 
	 * @var int
	 * @access public
	 */
	public $presentEnd;

	/**
	 * past start in unixtimestamp
	 * 
	 * @var int
	 * @access public
	 */
	public $pastStart;

	/**
	 * past end in unixtimestamp
	 * 
	 * @var int
	 * @access public
	 */
	public $pastEnd;
	
	/**
	 * frame length  in seconds
	 * 
	 * @var int
	 * @access public
	 */
	public $frameLength;
	
	/**
	 * frame distance in seconds
	 * 
	 * @var int
	 * @access public
	 */
	public $frameDistance;
	
	/**
	 * sample length in seconds
	 * 
	 * @var int
	 * @access public
	 */
	public $sampleLength;

	/**
	 * sample distance in seconds 
	 * 
	 * @var mixed
	 * @access public
	 */
	public $sampleDistance;

	/**
	 * An array comprised of stream criteria. The criteria depends on stream sources.
	 * For example you may need to analyze only on texts written in French language
	 * or written by females.
	 *
	 * This array's value must be set by subclass.
	 * 
	 * @var array
	 * @access protected
	 */
	protected $streamCriteria;

	/**
	 * stream is an array of text objects. 
	 * each object must have the property named "text"
	 * [{"text":"..."}, {"text":"..."}, ...]
	 *
	 * @var array
	 * @access protected
	 */
	protected $stream;

	/**
	 * An array which holds volumes of time frames. 
	 * [
	 *  "present": 756,
	 *  "past": [
	 *    657, 789, 598, 752, ...
	 *  ]
	 * ]
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $streamVolume;
	
	/**
	 * tokens is a multidimensional array comprised of words in stream. 
	 * Each word can appear in once. Each token/word has a frequency value
	 * that represent how many times the word appeared in the stream.
	 *
	 * example:
	 * [
	 * 	"present": [ ["the":50], ["car":30], ["apple":42], ["banana":12] ]
	 * 	"pastFrames": [ 
	 * 		[ ["the":48], ["car":30], ["apple":42], ["hello":46] ],
	 * 		[ ["the":43], ["car":30], ["apple":42], ["house":42] ],
	 * 		[ ["the":47], ["car":30], ["apple":42], ["life":42] ],
	 * 	]
	 * ]
	 * 
	 * @var array
	 * @access protected
	 */
	protected $tokens;

	/**
	 * an array comprised of words which are emerging/bursting in the present frame
	 * Each element(word/term) is a sub array as shown below:
	 * [
	 *  [
	 * 	 'term': "banana",
	 * 	 'streamVolume': 134,
	 * 	 'presentFrequency': 32,
	 *	 'pastFrequency': [ 
	 * 		['frequency': 2, 'frameVolume': 135],  
	 * 		['frequency': 0, 'frameVolume': 114],  
	 * 	 ]
	 *	 'pastRatio': [ 
	 *		0.0148148,
	 *		0.0 
	 * 	 ], 
	 * 	 'presentRatio': 0.2388059,
	 * 	 'chi-value': 7.22861811,
	 *  ]
	 * ]
	 *
	 * @var array
	 * @access protected
	 */
	protected $burstyTerms;

	/**
	 * an array comprised of stream text which are bursty.  
	 *
	 * [
	 *  [
	 *  	"text": "This is the first bursty text.",
	 *  	"frequency": 60,
	 *  	"tokens": ["this", "is", "the", "first", "bursty", "text"],
	 *  	"similarity": 0.89561,
	 *  	"intersect": [
	 *  		burstyTermObject, burstyTermObject,....
	 *  	]
	 *  ]
	 * ]
	 * @var array
	 * @access protected
	 */
	protected $burstyEvents;

	/**
	 * Threshold for chi-value. It's default value is 1.5
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $thresholdChi;

	/**
	 * Threshold for ratio. It's default value is 0.0009
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $thresholdRatio;

	public function __construct(){
	
		/**
		 * For default, it is going to be hourly analysis for
		 * the current time.
		 * */

		$this->frameLength=3600*1; // 1 hour
		$this->frameDistance=3600*24; // 24 hours
		
		$this->sampleLength=60*5; // 5 minutes
		$this->sampleDistance=60*10; // 10 minutes

		$this->presentEnd=time()-3600*24*2; // right now
		$this->presentEnd=strtotime(date('2013-02-11 15:00'));
		$this->presentStart=$this->presentEnd-($this->frameLength); // 1 hour
		
		$this->pastEnd=$this->presentEnd-$this->frameDistance;
		$this->pastStart=$this->presentEnd-(3600*24*7); // 7 days

		$this->streamCriteria=array();

		$this->thresholdChi=1.5;
		$this->thresholdRatio=0.0009;
	}
	
	public function init(){
		$this->burstyTerms=array();
		$this->streamVolumes=array();
		$this->tokens=array();
		
		// the times of samples in frames are being created, 
		// so data stream can be restricted with these times.
		$this->sampleTimes['present']=$this->prepareTimeListOfSamples(
			$this->presentStart,$this->presentEnd
		);
		$this->sampleTimes['past']=$this->prepareTimeListOfSamples(
			$this->pastStart,$this->pastEnd
		);

	}
	
	protected function prepareTimeListOfSamples($start, $end){
		$frames=array();
		$frameEnd=$end;
		while( ($frameStart=$frameEnd-$this->frameLength)>=$start ){

			$list=array();
			while(($sampleStart=$frameEnd-$this->sampleLength) >=$frameStart){
				$list[]=array(
					'start'=>date('Y-m-d H:i:00',$sampleStart), 
					'end'=>date('Y-m-d H:i:00', $frameEnd)
				);
				$frameEnd=$sampleStart-$this->sampleDistance;
			}

			$frames[]=$list;
			$frameEnd=$frameStart-$this->frameDistance+$this->frameLength;
		}
		return $frames;
	}
	
	protected function fetchFrameStream($timesOfFrame){
		$criteria['$or']=array();
		
		foreach($timesOfFrame as $i)
			$criteria['$or'][]=array('at'=> array(
				'$gt'=>strtotime($i['start']), '$lt'=>strtotime($i['end'])
			));

		$criteria=array_merge($criteria,$this->streamCriteria);

		return $this->fetchStream($criteria);
	}
	
	/**
	 * This method must be overridden by subclass according to stream source.
	 * The return value must be an array that contains text objects of stream.
	 *
	 * [ {text:"..."}, {"text":"..."}, ... ] 
	 *
	 * @param array $criteria 
	 * @access protected
	 * @return array
	 */
	protected function fetchStream($criteria){
		return $stream=Stream::get($criteria);
	}

	protected function prepareFrameData(){
		// fetch present
		// count and remove duplicates in the present stream
		// then tokenize
		
		$stream=$this->fetchFrameStream($this->sampleTimes['present'][0]);
		$this->streamVolume['present']= count($stream);
		$this->tokens['present']=Tokenization::produceTFList($stream);
		
		$present=array();
		foreach($stream as $i){
			$i['text']=trim(preg_replace('#(@\w+)|(^RT)|:#ui','',$i['text']));
			$found=false;
			foreach($present as $k=>$j)
				if($i['text']==$j['text']){
					$present[$k]['frequency']++;
					$found=true;
					break;
				}

			if(!$found){
				$tokens=Tokenization::tokenize(mb_strtolower($i['text']));
				if(count($tokens)>0)
					$present[]=array(
						'text'=>$i['text'], 
						'frequency'=>1,
						'tokens'=>$tokens
					);
			}

		}

		$this->streams=array('present'=>$present);
		
		$past=array();
		foreach($this->sampleTimes['past'] as $frame){
			$stream=$this->fetchFrameStream($frame);
			$this->streamVolume['pastFrames'][]=count($stream);
			$this->tokens['pastFrames'][]= Tokenization::produceTFList($stream);
		}
		
	}

	protected function detectBurstyTerms(){

		foreach($this->tokens['present'] as $token=>$tokenFrequency){

			$pastFrequency=array();
			$pastRatio=array();

			foreach($this->tokens['pastFrames'] as $frameId=>$frameTokens)
				if(isset($frameTokens[$token])){
					
					$frameVolume=$this->streamVolume['pastFrames'][$frameId];

					$pastFrequency[]=array(
						'frequency'=>$frameTokens[$token],
						'frameVolume'=>$frameVolume
					);

					$pastRatio[]=$frameTokens[$token] / $frameVolume;
				}

			
			if(count($pastRatio)>0)
				$avgRatio=array_sum($pastRatio)/count($this->tokens['pastFrames']);
			else
				$avgRatio=0.000001;

			$presentRatio=$tokenFrequency/$this->streamVolume['present'];
			
			if($presentRatio>$avgRatio)
				$chi=pow($presentRatio-$avgRatio,2)/$avgRatio;
			else
				$chi=-1;
			
			if($chi>$this->thresholdChi && $presentRatio>$this->thresholdRatio)
				$this->burstyTerms[]=array(
					'term'=>$token,
					'streamVolume'=>$this->streamVolume['present'],
					'presentFrequency'=>$tokenFrequency,
					'pastFrequency'=>$pastFrequency,
					'pastRatio'=>$pastRatio,
					'presentRatio'=>$presentRatio,
					'chi-value'=>$chi,
				);

		}

		$this->burstyTerms= Arrays::usort(
			$this->burstyTerms, 'presentFrequency', 'desc'
		);
	}

	protected function detectBurstyEvents(){
		
		$burstyTerms=array();
		foreach($this->burstyTerms as $i)
			$burstyTerms[]=$i['term'];
		
		while(count($burstyTerms)>0){
			$mostSimilar=null;
			$highestScore=0;

			foreach($this->streams['present'] as $i){
				
				$tokenWeight=$i['frequency'] / count($i['tokens']);
				$intersect=array_intersect($i['tokens'], $burstyTerms);
				$similarityScore=count($intersect) * $tokenWeight;
				
				if($similarityScore>$highestScore){
					$i['similarity']=$similarityScore;
					
					foreach($intersect as $itoken)
						foreach($this->burstyTerms as $bToken)
							if($bToken['term']==$itoken){
								$i['intersect'][]=$bToken;
								break;
							}
					unset($i['tokens']);
					$mostSimilar=$i;
					$highestScore=$similarityScore;
				}
			
			} // end of foreach of stream 

			if($mostSimilar==null) break;
			
			$this->burstyEvents[]=$mostSimilar;
			
			foreach($mostSimilar['intersect'] as $bursty)
				foreach($burstyTerms as $k=>$i)
					if($i==$bursty['term'])
						unset($burstyTerms[$k]);

		} // end of while

	}

	public function detect(){
		$this->init();
		
		$this->prepareFrameData();

		$this->detectBurstyTerms();
		$this->detectBurstyEvents();
		
		return $this->burstyEvents;
	}

	
}

?>
