<?php

namespace libFaceDetection;

use Exception;

class Utilities
{
  public static function fnAlignSize($iSz, $iN)
  {
    //CV_DbgAssert((n & (n - 1)) == 0); // n is a power of 2
    return ($iSz + $iN-1) & -$iN;
  }
}

