<?php

namespace libFaceDetection;

use Exception;

class UMat extends Mat
{
  /*
  public $iFlags;
  public $iDims;
  public $iCols;
  public $iRows;
  public $aData;

  function __construct(...$aArguments) 
  {
    //sprintf("%u", $int);
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
  
  public function fnCopyTo(&$oMat)
  {
    $oMat = clone $this;
  }
  
  public function fnSize()
  {
    return [ 'width' => $this->iCols, 'height' => $this->iRows ];
  }

  public function fnAt($aPt)
  {
    return $this->aData[$aPt['y']*$this->iCols + $aPt['x']];
  }
   */
}
