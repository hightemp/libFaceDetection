<?php

namespace libFaceDetection;

use libFaceDetection\FisherfacesDetector;
use libFaceDetection\EigenfacesDetector;
use libFaceDetection\LBPDetector;
use libFaceDetection\HaarDetector;
use Exception;
use libFaceDetection\DetectorInterface;

class FaceDetector implements DetectorInterface
{
  protected $hResource;
  protected $oMethodObject; 
  
  function __construct($sFileName, $sMethodName, $aOptions=[])
  {
    if (!function_exists('gd_info'))
      throw new Exception("GD library required");
      
    if (!is_file($sFileName))
      throw new Exception("File $sFileName not found");
    
    $sExtension = pathinfo($sFileName, PATHINFO_EXTENSION);
    $sExtension = strtolower($sExtension);
    
    if ($sExtension == "jpg")
      $sExtension = "jpeg";
    
    $sImageCreateFunctionName = "imagecreatefrom$sExtension";
    $sImageFunctionName = "image$sExtension";
    
    if (!function_exists($sImageFunctionName))
      throw new Exception("There is no function for $sExtension extension");
    
    $sClassMethodName = "libFaceDetection\\{$sMethodName}Detector";
    if (!class_exists($sClassMethodName)) {
      $sClassMethodName = strtolower($sMethodName);
      $sClassMethodName[0] = strtoupper($sClassMethodName[0]);
      $sClassMethodName = "libFaceDetection\\{$sClassMethodName}Detector";

      if (!class_exists($sClassMethodName))
        throw new Exception("There is no class for $sMethodName method");
    }
    
    $this->hResource = $sImageCreateFunctionName($sFileName);
    
    $this->oMethodObject = new $sClassMethodName($this->hResource);    
  }

  public function fnDetect() 
  {
    return $this->oMethodObject->fnDetect();  
  }

  public function fnDetectWithImage() 
  {
    return $this->oMethodObject->fnDetectWithImage();  
  }

}
