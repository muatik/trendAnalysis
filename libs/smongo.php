<?php
/**
 * This class is a static model of Mongo Class provied by PHP.
 * In a project, instead of creating mongo objects and connecting 
 * to mongo server, you can use this as a shortway.
 * example:
 * smongo::collectionName->find()
 * smongo::blogPosts->find()
 * */
class smongo
{
	
	public static $o=null;
	
	public static $db=null;

	public static function connect(){
		if(self::$o!=null)
			return true;

		try{
			self::$o=new Mongo();
		}catch(Exception $e){
			echo "Cannot connect to MongoDB Server.\n";
			return false;
		}

		self::$db=self::$o->trend;
		
		return true;
	}

}

smongo::connect();
ini_set('mongo.long_as_object','0');
ini_set('mongo.native_long','0');
?>
