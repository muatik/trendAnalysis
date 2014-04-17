<?php
require_once __DIR__ . '/../resources/config/dev.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;


$app = require __DIR__ . '/../src/app.php';

$app->run();
