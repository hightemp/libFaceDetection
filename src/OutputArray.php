<?php

namespace libFaceDetection;

use Exception;

function noArray() 
{
  static $oNone = null;
  
  if (is_null($oNone))
    $oNone = new OutputArray();
  
  return $oNone;
}

class OutputArray
{
  
}


