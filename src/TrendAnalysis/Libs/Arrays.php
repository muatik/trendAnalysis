<?php

namespace TrendAnalysis\Libs;

class Arrays
{
	
	/*
	 * dizi elemanlarını tek bir metin haline getirir
	 * */
	public static function makeCloud($arr,$field,$separator=', '){
		$cloud='';
		if(count($arr)<1) return null;
		if(is_object($arr[0])){
			foreach($arr as $i)
				$cloud.=$i->$field.$separator;
		}
		elseif(is_array($arr[0])){
			foreach($arr as $i)
				$cloud.=$i[$field].$separator;
		}
		$cloud=mb_substr($cloud,0,mb_strlen($separator)*-1);
		return $cloud;
	}
	
	/* @brief	nesneleri diziye çevirir.
	 * @params	$field	dizi değeri olaca nesne özelliği
	 * @params	$fieldKey	dizi anahtarı olacak nesne özelliği
	 * @example	
	 * $x->a=elma; $x->b=5;
	 * print_r(arrays::convertToArray($x,'a','b'));
	 * çıktı: Array([5] => elma) 
	 * */
	public static function toArray($arr,$field,$keyField=null){
		$newArr='';
		if(count($arr)<1) return array();
		
		if($keyField==null)
			foreach($arr as $i)
				$newArr[]=$i->$field;
		else
			foreach($arr as $i)
				$newArr[$i->$keyField]=$i->$field;
		
		return $newArr;
	}
	
	public static function makeUnique($arr,$field=null){
		if(count($arr)<1) return null;
		$narr=array();
		$vals=array();
		
		if(is_object($arr[0])){
			foreach($arr as $k=>$i)
			if(!in_array($i,$vals)){
				$narr[$k]=$i;
				$vals[]=$i[$field];
			}
		}
		elseif(is_array($arr[0])){
			foreach($arr as $k=>$i) 
				if(!in_array($i[$field],$vals)){
					$narr[$k]=$i;
					$vals[]=$i[$field];
				}
		}
		else{
			foreach($arr as $k=>$i)
			if(!in_array($i,$vals)){
				$narr[$k]=$i;
				$vals[]=$i;
			}
		}
		
		return $narr;
	}
	
	public static function removeEmpties($arr){
		foreach($arr as $k=>$v){
			$v=trim($v);
			if($v=='') unset($arr[$k]);
			else $arr[$k]=$v;
		}
		return $arr;
	}
	
	/*
	 * belirtilen dizinin belirtilen elemanlarıyla bir metin yaratır
	 * @params	array	arr				dizi
	 * @params	array	fields			birleştirilecek dizi elemanlarının
	 * anahtarları.
	 * @params	string	separator		birleştirilen elemanların ayracı
	 * @return	string	birleştirilen metin
	 * @exammple
	 * $a=array('x'=>'elma','y'=>'armut','z'=>'karpuz','c'=>'kiraz');
	 * $s=concatFields($a,array('x','c','y'),'-');
	 * echo $s; // elma-kiraz-armut
	 * */
	public static function concatFields($arr,$fields,$separator=null){
		$str='';
		foreach($fields as $f)
			$str.=$arr[$f].$separator;
		
		$str=mb_substr(
			$str,0,
			mb_strlen($str)-mb_strlen($separator)
		);
		return $str;
	}

	/**
	 * usort 
	 * 
	 * @param mixed $arr 
	 * @param mixed $field 
	 * @param string $order 
	 * @static
	 * @access public
	 * @return void
	 */
	public static function usort($arr, $field, $order='asc'){

		$uFunction=function ($a,$b) use ($field,$order) {
		
			if($a[$field]==$b[$field]){
				return 0;
			}

			if($order=='asc')
				return ((int)$a[$field] < (int)$b[$field]) ? -1 : 1;
			else
				return ((int)$a[$field] > (int)$b[$field]) ? -1 : 1;

		};

		usort($arr, $uFunction);

		return $arr;
	}

}