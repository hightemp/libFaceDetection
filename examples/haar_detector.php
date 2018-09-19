<?php

include '../vendor/autoload.php';

ini_set('memory_limit', '2G');

$oDetector = new libFaceDetection\FaceDetector("adult-bangkok-belief-260898.jpg", "haar");

$aCoordiantes = $oDetector->fnDetect();

var_dump($aCoordiantes);