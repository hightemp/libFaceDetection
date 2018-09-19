<?php

namespace libFaceDetection;

use Exception;

class Utilities
{
  const CV_8U =0;
  const CV_8S =1;
  const CV_16U=2;
  const CV_16S=3;
  const CV_32S=4;
  const CV_32F=5;
  const CV_64F=6;
  const CV_USRTYPE1=7;
  const CV_16F=7;
  
  public static function fnAlignSize($iSz, $iN)
  {
    //CV_DbgAssert((n & (n - 1)) == 0); // n is a power of 2
    return ($iSz + $iN-1) & -$iN;
  }
  
  public static function fnCopyVectorToUMat(&$aV, &$oUm) 
  {
    $iVarSize = 0;
    if (is_int($aV[0]))
      $iVarSize = PHP_INT_SIZE;
    if (is_float($aV[0]))
      $iVarSize = 8;
    (new Mat(1, count($aV)*$iVarSize, self::CV_8U, $aV[0]))->fnCopyTo($oUm);
  }

  public static function fnCvSumOfs(&$iP0, &$iP1, &$iP2, &$iP3, $iSum, $oRect, $iStep)
  {
    $iP0 = $iSum + $oRect->x + $iStep * $oRect->y;
    $iP1 = $iSum + $oRect->x + $oRect->width + $iStep * $oRect->y;
    $iP2 = $iSum + $oRect->x + $iStep * ($oRect->y + $oRect->height);
    $iP3 = $iSum + $oRect->x + $oRect->width + $iStep * ($oRect->y + $oRect->height);
  }

  public static function fnCvTiltedOfs(&$iP0, &$iP1, &$iP2, &$iP3, $iTilted, $oRect, $iStep) 
  {
    $iP0 = $iTilted + $oRect->x + $iStep * $oRect->y;
    $iP1 = $iTilted + $oRect->x - $oRect->height + $iStep * ($oRect->y + $oRect->height);
    $iP2 = $iTilted + $oRect->x + $oRect->width + $iStep * ($oRect->y + $oRect->width);
    $iP3 = $iTilted + $oRect->x + $oRect->width - $oRect->height
    + $iStep * ($oRect->y + $oRect->width + $oRect->height);
  }
  
  public static function fnCalcSumOfs($aRect, $oMat, $iOffset)
  {
    function _fnCalcSumOfs($iP0, $iP1, $iP2, $iP3, $oMat, $iOffset) 
    {
      return $oMat->fnAt($iP0, [$iOffset]) - 
              $oMat->fnAt($iP1, [$iOffset]) - 
              $oMat->fnAt($iP2, [$iOffset]) + 
              $oMat->fnAt($iP3, [$iOffset]);
    }
    return _fnCalcSumOfs($aRect[0], $aRect[1], $aRect[2], $aRect[3], $oMat, $iOffset);
  }
}

