<?php

/* 
 * Objective kd-Tree testing class
 */

require "kdTree.php";
require 'handlerDB.php';

$db = new HandlerDB('master.db');
$ATtrackArray = $db->getATTrkPts();

$time_start1 = microtime(true);
$ATkd = new kdTree('load', ['array' => 'ATtrackArray', 'file' => "ATkd"]);
// $ATkd->kdDump('ATkd');
$time_end1 = microtime(true);
echo "\n kd build time: " . ($time_end1 - $time_start1);

$testPt1 = ["lat" => 41.543825, 'lon' => -73.714398]; // I-84
$testPt2 = ["lat" => 41.534797, 'lon' => -73.734706]; // Mtn top mkt NY
$randPt = ['lat' => randomFloat(30, 50), 'lon' => randomFloat(-90, -60)];
$testPt3 = ['lat' => 41.059437, 'lon' => -74.963547]; // millbrook rd
$testPt4 = ['lat' => 44.26185, 'lon' => -71.307508]; // tucks crossover
$testPt5 = ['lat' => 44.252744, 'lon' => -71.294081]; // boott spur

$time_start2 = microtime(true);
var_dump($ATkd->kdNN($testPt5));
$time_end2 = microtime(true);

$time_start3 = microtime(true);
var_dump(bruteNN($testPt5, $ATtrackArray));
$time_end3 = microtime(true);

echo "\n kd NN search time: " . ($time_end2 - $time_start2); 
echo "\n brute NN search time: " . ($time_end3 - $time_start3); 
//echo "\n haversine: " . $ATkd->haversine(['lon' => -73.1324325, 'lat' => 45.1234321], ['lon' => -75.1342352, 'lat' => 45.1234321]);
//echo "\n orthodist: " . $ATkd->haversine(['lon' => -73.1324325, 'lat' => 45.1234321], -75.1342352, 'lon');

function randomFloat($min = 0, $max = 1) {
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}
    
/*
 * performs brute force NN search for testPt an array of waypts 
 */
function bruteNN($testPt, $array) {
    $startPt = $array[0];
    foreach ($array as $wpt) {
        if (haversine($wpt, $testPt) < haversine($startPt, $testPt)) {
            $startPt = $wpt;
        }
    }
    return $startPt;
}

/*
 * using haversine formula, returns the unitless displacement between two 
 * trackpoint arrays containing degree values for keys 'lat' and 'lon'
 */
function haversine($p1, $p2) {
    $phi1 = deg2rad($p1['lat']);
    $phi2 = deg2rad($p2['lat']);
    $deltaLambda = deg2rad($p2['lon'] - $p1['lon']);

    $a = sin(($phi2 - $phi1) / 2) * sin(($phi2 - $phi1) / 2) + cos($phi1) * 
            cos($phi2) * sin($deltaLambda / 2) * sin($deltaLambda/2);
    return 2 * atan2(sqrt($a), sqrt(1 - $a));
}