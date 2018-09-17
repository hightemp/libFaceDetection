<?php

namespace libFaceDetection\HaarEvaluator;

use libFaceDetection\Rect;
use libFaceDetection\Utilities;

class Feature
{
  public $bTilted;
  public $aRect = [];
  
  const RECT_NUM = 3;
  
  public function fnRead($oNode)
  {
    for ($iRi = 0; $iRi < self::RECT_NUM; $iRi++ ) {
      $this->aRect[$iRi]['r'] = new Rect();
      $this->aRect[$iRi]['weight'] = 0.;
    }

    $iRi = 0;
    foreach ($oNode->rects->_ as $oRectNode) {
      $aValue = explode(' ', trim($oRectNode->__toString()));
      $this->aRect[$iRi]['r']->x = $aValue[0];
      $this->aRect[$iRi]['r']->y = $aValue[1];
      $this->aRect[$iRi]['r']->width = $aValue[2];
      $this->aRect[$iRi]['r']->height = $aValue[3];
      $this->aRect[$iRi]['weight'] = $aValue[4];
      $iRi++;
    }

    if (!isset($oNode->tilted))
      $this->bTilted = false;
    else
      $this->bTilted = !empty($oNode->tilted->__toString());
    
    return true;
  }
  
};

class OptFeature
{
  const RECT_NUM = 3;

  public $aOfs = [];
  public $aWeight = [];
  
  function __construct()
  {
    for ($iI = 0; $iI < self::RECT_NUM; $iI++)
      $this->aOfs[$iI] = array_fill(0, 4, 0);
    $this->aWeight = array_fill(0, 4, 0);
  }
       
  public function fnSetOffsets($oF, $iStep, $iTofs )
  {
    $this->aWeight[0] = $oF->aRect[0]['weight'];
    $this->aWeight[1] = $oF->aRect[1]['weight'];
    $this->aWeight[2] = $oF->aRect[2]['weight'];

    if ($oF->bTilted)
    {
      Utilities::fnCvTiltedOfs($this->aOfs[0][0],$this->aOfs[0][1],$this->aOfs[0][2],$this->aOfs[0][3], $iTofs, $oF->aRect[0]['r'], $iStep);
      Utilities::fnCvTiltedOfs($this->aOfs[1][0],$this->aOfs[1][1],$this->aOfs[1][2],$this->aOfs[1][3], $iTofs, $oF->aRect[1]['r'], $iStep);
      Utilities::fnCvTiltedOfs($this->aOfs[2][0],$this->aOfs[2][1],$this->aOfs[2][2],$this->aOfs[2][3], $iTofs, $oF->aRect[2]['r'], $iStep);
    }
    else
    {
      Utilities::fnCvSumOfs($this->aOfs[0][0],$this->aOfs[0][1],$this->aOfs[0][2],$this->aOfs[0][3], 0, $oF->aRect[0]['r'], $iStep);
      Utilities::fnCvSumOfs($this->aOfs[1][0],$this->aOfs[1][1],$this->aOfs[1][2],$this->aOfs[1][3], 0, $oF->aRect[1]['r'], $iStep);
      Utilities::fnCvSumOfs($this->aOfs[2][0],$this->aOfs[2][1],$this->aOfs[2][2],$this->aOfs[2][3], 0, $oF->aRect[2]['r'], $iStep);
    }
  }  
}

namespace libFaceDetection;

use Exception;
use libFaceDetection\FeatureEvaluator;
use libFaceDetection\Utilities;

class HaarEvaluator extends FeatureEvaluator
{
  protected $aFeatures = [];
  protected $aOptfeatures = [];
  protected $aOptfeatures_lbuf = [];
  protected $bHasTiltedFeatures;

  protected $iTofs;
  protected $iSqofs;
  protected $aNofs;
  protected $oNormrect;
  protected $iPwin;
  protected $aOptfeaturesPtr; // optimization
  protected $fVarianceNormFactor;  
  
  function __construct() {
    parent::__construct();
    
    $this->aOptfeaturesPtr = 0;
    $this->iPwin = 0;
    $this->aLocalSize = [ 'width' => 4, 'height' => 2 ];
    $this->aLbufSize = [ 'width' => 0, 'height' => 0 ];
    $this->iNchannels = 0;
    $this->iTofs = 0;
    $this->iSqofs = 0;
    $this->fVarianceNormFactor = 0;
    $this->bHasTiltedFeatures = false;
    $this->oNormrect = new Rect();
  }
  
  public function fnRead($oNode, $aOrigWinSize)
  {
    if (!parent::fnRead($oNode, $aOrigWinSize))
      return false;
    
    $iI = 0;
    
    $bHasTiltedFeatures = false;
    $aSbufSize = [ 'width' => 0, 'height' => 0 ];

    foreach ($oNode->_ as $oFeatureNode) {
      $this->aFeatures[$iI] = new HaarEvaluator\Feature();
      
      if (!$this->aFeatures[$iI]->fnRead($oFeatureNode))
        return false;
      if ($this->aFeatures[$iI]->bTilted)
        $bHasTiltedFeatures = true;
      
      $iI++;
    }
    $this->iNchannels = $bHasTiltedFeatures ? 3 : 2;
    $this->oNormrect = new Rect(1, 1, $this->aOrigWinSize['width'] - 2, $this->aOrigWinSize['height'] - 2);

    $this->aLocalSize = $this->aLbufSize = [ 'width' => 0, 'height' => 0 ];

    return true;
  }
  
  public function fnSetWindow($aPt, $iScaleIdx) 
  {
    $oS = $this->fnGetScaleData($iScaleIdx);

    if( $aPt['x'] < 0 || $aPt['y'] < 0 ||
        $aPt['x'] + $this->aOrigWinSize['width'] >= $oS->szi['width'] ||
        $aPt['y'] + $this->aOrigWinSize['height'] >= $oS->szi['height'] )
      return false;

    /*
    pwin = &sbuf.at<int>(pt) + $oS->aLayer_ofs;
    const int* pq = (const int*)(pwin + sqofs);
    int valsum = CALC_SUM_OFS(nofs, pwin);
    unsigned valsqsum = (unsigned)(CALC_SUM_OFS(nofs, pq));

    double area = normrect.area();
    double nf = area * valsqsum - (double)valsum * valsum;
    if( nf > 0. )
    {
      nf = std::sqrt(nf);
      varianceNormFactor = (float)(1./nf);
      return area*varianceNormFactor < 1e-1;
    }
    else
    {
      varianceNormFactor = 1.f;
      return false;
    }
     */
  }
  
  public function fnComputeOptFeatures()
  {
    //CV_INSTRUMENT_REGION()

    if ($this->bHasTiltedFeatures)
      $this->iTofs = $this->aSbufSize["width"]*$this->aSbufSize["height"];

    $iStep = $this->aSbufSize["width"];
    Utilities::fnCvSumOfs($this->aNofs[0], $this->aNofs[1], $this->aNofs[2], $this->aNofs[3], 0, $this->oNormrect, $iStep);

    $iFi;
    $iNfeatures = count($this->aFeatures);
    //const std::vector<Feature>& ff = *features;
    //optfeatures->resize(nfeatures);
    //optfeaturesPtr = &(*optfeatures)[0];
    for ($iFi = 0; $iFi < $iNfeatures; $iFi++) {
      if (!isset($this->aOptfeatures[$iFi])) {
        $this->aOptfeatures[$iFi] = new HaarEvaluator\OptFeature();
      }
      $this->aOptfeatures[$iFi]->fnSetOffsets($this->aFeatures[$iFi], $iStep, $this->iTofs);
    }
    //optfeatures_lbuf->resize(nfeatures);

    for ($iFi = 0; $iFi < $iNfeatures; $iFi++) {
      if (!isset($this->aOptfeatures_lbuf[$iFi])) {
        $this->aOptfeatures_lbuf[$iFi] = new HaarEvaluator\OptFeature();
      }
      $this->aOptfeatures_lbuf[$iFi]->fnSetOffsets($this->aFeatures[$iFi], $this->aLbufSize['width'] > 0 ? $this->aLbufSize['width'] : $iStep, $this->iTofs);
    }
    
    Utilities::fnCopyVectorToUMat($this->aOptfeatures_lbuf, $this->oUfbuf);
  }
}