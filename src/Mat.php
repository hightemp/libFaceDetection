<?php

namespace libFaceDetection;

use Exception;

class Mat
{
  public $iCols;
  public $iRows;
  
  function __construct(...$aArguments) 
  {
    if ($aArguments[0]) {
      $aArguments[0];
      $aArguments[1];
    }
  }
  
  public function fnSize()
  {
  }  
}