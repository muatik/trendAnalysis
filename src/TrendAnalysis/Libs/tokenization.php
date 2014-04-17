<?php

class Tokenization
{

	public static $pattern='[ ,;.()!#"\'\[\]]+';

	/**
	 * tokenize 
	 * 
	 * @param mixed $text 
	 * @static
	 * @access public
	 * @return void
	 */
	public static function tokenize($text){
		preg_match_all('#\p{L}+#u',$text,$m);
		return $m[0];
		return preg_split(
			'/'.self::$pattern.'/i', // pattern
			$text, // text
			-1, // limit
			PREG_SPLIT_NO_EMPTY // options
		);

	}

	public static function produceTFList($posts){

		$tokens=array();

		foreach($posts as $post){

			$postTokens=self::tokenize(mb_strtolower($post['text']));

			foreach($postTokens as $token){

				$token=$token;

				if(isset($tokens[$token]))
					$tokens[$token]++;
				else
					$tokens[$token]=1;
			}

		}

		return $tokens;
	}


}

?>
