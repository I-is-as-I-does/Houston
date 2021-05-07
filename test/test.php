<?php

require_once(dirname(__DIR__).'\\vendor\\autoload.php');

use ExoProject\Houston\Houston;

$pathtosample = dirname(__DIR__).'\\config\\houston-sample.json';
$datatolog = "I think we have a problem";
$origin = __FILE__;
$lvl =2;
$houston = new Houston($datatolog, $origin, $lvl, $pathtosample);
