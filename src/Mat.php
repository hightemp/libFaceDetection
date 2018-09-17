<?php

namespace libFaceDetection;

use Exception;

class Mat
{
  public $iFlags;
  public $iDims;
  public $iCols;
  public $iRows;
  public $aData;
  
  function __construct(...$aArguments) 
  {
    if (count($aArguments)==0) {
      $this->iCols = 0;
      $this->iRows = 0;
    }
    if (count($aArguments)==3) {
      $this->iCols = $aArguments[0];
      $this->iRows = $aArguments[1];
      $this->iFlags = $aArguments[2];
    }
  }
  
  public function fnSize()
  {
  }  
}