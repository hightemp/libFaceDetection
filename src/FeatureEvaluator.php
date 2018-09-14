<?php

namespace libFaceDetection;

use Exception;
use libFaceDetection\Utilities;
use libFaceDetection\Rect;

define('FLT_EPSILON', 1.19209290E-07);

class ScaleData
{
  public $scale; 
  public $szi;
  public $layer_ofs;
  public $ystep;
  
  function __construct()
  {
    $this->scale = 0; 
    $this->layer_ofs = 0;
    $this->ystep = 0;
  }
  
  public function fnGetWorkingSize($aWinSize)
  {
    return [ 
      "width" => max($this->szi['width'] - $aWinSize['width'], 0),
      "height" => max($this->szi['height'] - $aWinSize['height'], 0)
    ];
  }
}

class FeatureEvaluator
{
  public static $aTypes = [
   "HAAR" => 0,
   "LBP" => 1,
  ];
          
  const SBUF_VALID = 1;
  const USBUF_VALID = 2;
  
  protected $iSbufFlag;

  //bool updateScaleData( Size imgsz, const std::vector<float>& _scales );
  //virtual void computeChannels( int, InputArray ) {}
  //virtual void computeOptFeatures() {}

  protected $aOrigWinSize = [ 'width' => 0, 'height' => 0 ];
  protected $aSbufSize = [ 'width' => 0, 'height' => 0 ];
  protected $aLocalSize = [ 'width' => 0, 'height' => 0 ];
  protected $aLbufSize = [ 'width' => 0, 'height' => 0 ];
  
  protected $iNchannels;
  protected $oSbuf;
  protected $oRbuf;
  protected $oUrbuf;
  protected $oUsbuf;
  protected $oUfbuf;
  protected $aUscaleData;

  protected $aScaleData;
  
  function __construct() 
  {
    
  }
  
  public function fnSetWindow($aPt, $iScaleIdx)
  {
    return true;
  }
  
  public function fnGetMats()
  {
    if (!($this->iSbufFlag & self::SBUF_VALID))
    {
      $this->aSbuf = clone $this->aUsbuf; // ?
      $this->sbufFlag |= self::SBUF_VALID;
    }
  }
  
  public function fnGetScaleData($iScaleIdx=null)
  {
    if (0 <= $iScaleIdx && $iScaleIdx < count($this->aScaleData))
      throw new Exception("Wrong index");
    
    if (is_null($iScaleIdx)) {
      return $this->aScaleData;
    }
    
    return $this->aScaleData[$iScaleIdx];
  }
  
  public function fnRead($oNode, $iOrigWinSize)
  {
    $this->aOrigWinSize = $iOrigWinSize;
    $this->aLocalSize = $this->aLbufSize = ['width' => 0, 'height' => 0];
    $this->aScaleData = [];
    
    return true;
  }
  
  public function fnUpdateScaleData($aImgsz, &$aScales)
  {
    $iI;
    $iNscales = count($aScales);
    
    $bRecalcOptFeatures = $iNscales != count($this->aScaleData);
    $this->aScaleData = array_slice($this->aScaleData, 0, $iNscales);

    $iLayer_dy = 0;
    $aLayer_ofs = [ 'x' => 0, 'y' => 0];
    $aPrevBufSize = $this->aSbufSize;
    $this->aSbufSize['width'] = max($this->aSbufSize['width'], Utilities::fnAlignSize(round($aImgsz['width']/$aScales[0]) + 31, 32));
    $bRecalcOptFeatures = $bRecalcOptFeatures || $this->aSbufSize['width'] != $aPrevBufSize['width'];

    for ($iI = 0; $iI < $iNscales; $iI++ ) {
      $this->aScaleData[$iI] = new ScaleData();
      $oS = &$this->aScaleData[$iI];
      
      if (!$bRecalcOptFeatures && abs($oS->scale - $aScales[$iI]) > FLT_EPSILON*100*$aScales[$iI])
        $bRecalcOptFeatures = true;
      
      $fSc = $aScales[$iI];
      $aSz = [];
      $aSz['width'] = round($aImgsz['width']/$fSc);
      $aSz['height'] = round($aImgsz['height']/$fSc);
      $oS->ystep = $fSc >= 2 ? 1 : 2;
      $oS->scale = $fSc;
      $oS->szi = [ 'width' => $aSz['width']+1, 'height' => $aSz['height']+1 ];

      if ($iI == 0) {
        $iLayer_dy = $oS->szi['height'];
      }

      if ($aLayer_ofs['x'] + $oS->szi['width'] > $this->aSbufSize['width']) {
        $aLayer_ofs = [ 'x' => 0, 'y' => $aLayer_ofs['y'] + $iLayer_dy];
        $iLayer_dy = $oS->szi['height'];
      }
      $oS->layer_ofs = $aLayer_ofs['y']*$this->aSbufSize['width'] + $aLayer_ofs['x'];
      $aLayer_ofs['x'] += $oS->szi['width'];
    }

    $aLayer_ofs['y'] += $iLayer_dy;
    $this->aSbufSize['height'] = max($this->aSbufSize['height'], $aLayer_ofs['y']);
    
    $bRecalcOptFeatures = $bRecalcOptFeatures || $this->aSbufSize['height'] != $aPrevBufSize['height'];
    
    return $bRecalcOptFeatures;
  } 
  
  public function fnSetImage(InputArray $oImage, &$aScales)
  {
    //CV_INSTRUMENT_REGION()

    $aImgsz = $oImage->fnSize();
    
    $bRecalcOptFeatures = $this->fnUpdateScaleData($aImgsz, $aScales);

    $iI;
    $iNscales = count($this->aScaleData);
    if ($iNscales == 0) {
        return false;
    }
    
    $aSz0 = $this->aScaleData[0]->szi;
    $aSz0 = [ 
      'width' => max($this->oRbuf->iCols, Utilities::fnAlignSize($aSz0['width'], 16)), 
      'height' => max($this->oRbuf->iRows, $aSz0['height'])
    ];
    
    if ($bRecalcOptFeatures) {
      $this->fnComputeOptFeatures();
      fnCopyVectorToUMat($this->aScaleData, $this->aUscaleData);
    }

    if ($oImage->fnIsUMat() && $this->aLocalSize['width']*$this->aLocalSize['height'] > 0) {
      $this->oUsbuf->fnCreate($this->aSbufSize['height']*$this->iNchannels, $this->aSbufSize['width'], CV_32S);
      $this->oUrbuf->fnCreate($aSz0, CV_8U);

      for ($iI = 0; $iI < $iNscales; $iI++) {
        $oS = $this->aScaleData[$iI];
        $oDst = new UMat($this->oUsbuf, new Rect(0, 0, $oS->szi['width'] - 1, $oS->szi['height'] - 1));
        //UMat dst(urbuf, Rect(0, 0, s.szi.width - 1, s.szi.height - 1));
        resize($oImage, $oDst, $oDst->fnSize(), 1. / $oS->scale, 1. / $oS->scale, INTER_LINEAR_EXACT);
        computeChannels($iI, $oDst);
      }
      $this->iSbufFlag = self::USBUF_VALID;
    } else {
      $oNewImage = $oImage->fnGetMat();
      $this->oSbuf->fnCreate($this->aSbufSize['height']*$this->iNchannels, $this->aSbufSize['width'], CV_32S);
      $this->oRbuf->fnCreate($aSz0, CV_8U);

      for ($iI = 0; $iI < $iNscales; $iI++) {
        $oS = $this->aScaleData[$iI];
        $oDst = new Mat($oS->szi['height'] - 1, $oS->szi['width'] - 1, CV_8U, $this->oRbuf);
        resize($oNewImage, $oDst, $oDst->fnSize(), 1. / $oS->scale, 1. / $oS->scale, INTER_LINEAR_EXACT);
        computeChannels($iI, $oDst);
      }
      $this->iSbufFlag = self::SBUF_VALID;
    }

    return true;
  }
  
}