<?php

namespace libFaceDetection;

use Exception;
use libFaceDetection\DetectorInterface;
use libFaceDetection\CascadeClassifier;

class HaarDetector implements DetectorInterface
{
  protected $oClassifier;
  protected $hResource;
  
  function __construct($hResource, $aOptions = []) 
  {
    $aDefaultOptions = [
      "file" => "../data/haarcascades/haarcascade_frontalface_alt.xml",
      "type" => "HAAR",
    ];
    
    $aOptions = array_merge($aDefaultOptions, $aOptions);
    
    $this->oClassifier = new CascadeClassifier($aOptions);
    $this->hResource = $hResource;
  }
  
  public function fnDetect($aOptions=[]) 
  {
    $aObjects = [];
    
    $aDefaultOptions = [
      'aRejectLevels' => [],
      'aLevelWeights' => [],
      'fScaleFactor' => 1.1,
      'aMinObjectSize' => [ 'width' => 0, 'height' => 0],
      'aMaxObjectSize' => [ 'width' => 30, 'height' => 30],
      'bOutputRejectLevels' => false
    ];
    
    $aOptions = array_merge($aDefaultOptions, $aOptions);
    
    $this->oClassifier->fnDetectMultiScale(
      $this->hResource, 
      $aObjects,  
      $aOptions['aRejectLevels'],  
      $aOptions['aLevelWeights'],  
      $aOptions['fScaleFactor'],  
      $aOptions['aMinObjectSize'],  
      $aOptions['aMaxObjectSize'],  
      $aOptions['bOutputRejectLevels']
    );
    
    return $aObjects;
  }

  public function fnDetectWithImage() 
  {
    
  }

}