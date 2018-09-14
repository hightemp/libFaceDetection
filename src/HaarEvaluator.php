<?php

namespace libFaceDetection;

use Exception;
use libFaceDetection\FeatureEvaluator;

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

    foreach ($oNode->rects->_ as $oRectNode) {
      $aValue = explode(' ', trim($oRectNode->__toString()));
      $this->aRect[$iRi]['r']['x'] = $aValue[0];
      $this->aRect[$iRi]['r']['y'] = $aValue[1];
      $this->aRect[$iRi]['r']['width'] = $aValue[2];
      $this->aRect[$iRi]['r']['height'] = $aValue[3];
      $this->aRect[$iRi]['r']['weight'] = $aValue[4];
    }

    if (!isset($oNode->tilted))
      $this->bTilted = false;
    else
      $this->bTilted = !empty($oNode->tilted->__toString());
    
    return true;
  }
};

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
  }
  
  public function fnRead($oNode, $aOrigWinSize)
  {
    if (!parent::fnRead($oNode, $aOrigWinSize))
      return false;
    
    $iI;
    
    $bHasTiltedFeatures = false;
    $aSbufSize = [ 'width' => 0, 'height' => 0 ];

    foreach ($oNode->_ as $iI => $oFeatureNode) {
      $this->aFeatures[$iI] = new Feature();
      
      if (!$this->aFeatures[$iI]->fnRead($oFeatureNode))
        return false;
      if ($this->aFeatures[$iI]->bTilted)
        $bHasTiltedFeatures = true;
    }
    $this->iNchannels = $bHasTiltedFeatures ? 3 : 2;
    $this->oNormrect = new Rect(1, 1, $this->aOrigWinSize['width'] - 2, $this->aOrigWinSize['height'] - 2);

    $this->aLocalSize = $this->aLbufSize = [ 'width' => 0, 'height' => 0 ];

    return true;
  }
}