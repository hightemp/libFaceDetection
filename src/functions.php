<?php

function fnCopyVectorToUMat(&$aV, UMat &$oUm) 
{
  $iVarSize = 0;
  if (is_int($aV[0]))
    $iVarSize = PHP_INT_SIZE;
  if (is_float($aV[0]))
    $iVarSize = 8;
  (new Mat(1, count($aV)*$iVarSize, CV_8U, $aV[0])).fnCopyTo($oUm);
}

