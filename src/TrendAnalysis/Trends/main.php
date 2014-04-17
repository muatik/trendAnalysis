<?php
error_reporting(E_ALL);
ini_set('display_errors','on');
set_time_limit(0);

require_once('libs/smongo.php');
require_once('libs/arrays.php');
require_once('libs/tokenization.php');
require_once('stream.php');

MongoCursor::$timeout = -1;

?>
