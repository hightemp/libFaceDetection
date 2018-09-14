<?php

include '../vendor/autoload.php';

$oDetector = new libFaceDetection\FaceDetector("adult-bangkok-belief-260898.jpg", "haar");

$aCoordiantes = $oDetector->fnDetect();

var_dump($aCoordiantes);