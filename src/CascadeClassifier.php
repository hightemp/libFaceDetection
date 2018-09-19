<?php

namespace libFaceDetection;

use Exception;
use libFaceDetection\FeatureEvaluator;
use libFaceDetection\Data;
use libFaceDetection\InputArray;
use libFaceDetection\Mat;
use libFaceDetection\Rect;

class MaskGenerator {
  public function fnGenerateMask($oSrc) {}
  public function fnInitializeMask($oSrc) {}
};
    
class CascadeClassifier
{
  protected $aCascadeNodes;
  protected $oData;
  protected $sType;
  protected $oFeatureEvaluator;
  protected $oOldCascade;
  public $oMaskGenerator;
  
  /*
  Ptr<FeatureEvaluator> featureEvaluator;
  Ptr<CvHaarClassifierCascade> oldCascade;

  Ptr<MaskGenerator> maskGenerator;
  UMat ugrayImage;
  UMat ufacepos, ustages, unodes, uleaves, usubsets;
  */
  
  function __construct($aOptions)
  {
    $this->sType = $aOptions["type"];
    $this->fnLoad($aOptions["file"]);
  }
  
  public function fnLoad($sFileName)
  {
    $this->aCascadeNodes = simplexml_load_file($sFileName);
    
    if ($this->aCascadeNodes->cascade->featureType != $this->sType)
      throw new Exception("Types do not match");
    
    $this->oData = new Data();
    
    if(!$this->oData->fnRead($this->aCascadeNodes->cascade))
      throw new Exception("Error while reading data");
    
    if (!isset(FeatureEvaluator::$aTypes[$this->sType]))
      throw new Exception("Wrong feature type");
    
    $sClassName = strtolower($this->sType);
    $sClassName[0] = strtoupper($sClassName[0]);
    $sClassName = "libFaceDetection\\{$sClassName}Evaluator";

    if (!class_exists($sClassName))
      throw new Exception("There is no class for {$this->sType} type");
    
    $this->oFeatureEvaluator = new $sClassName();
    $this->oFeatureEvaluator->fnRead($this->aCascadeNodes->cascade->features, $this->oData->aOrigWinSize);
  }
  
  public function fnDetectMultiScale($hImageResource, 
                                     &$aObjects, 
                                     &$aRejectLevels, 
                                     &$aLevelWeights,
                                     $fScaleFactor, 
                                     $aMinObjectSize, 
                                     $aMaxObjectSize,
                                     $bOutputRejectLevels)
  {
    //CV_INSTRUMENT_REGION()
    
    $aImgSz = [ 
      "width" => imagesx($hImageResource), 
      "height" => imagesy($hImageResource),
    ];
    
    $aOriginalWindowSize = $this->oData->aOrigWinSize;
    
    if ( $aMaxObjectSize['height'] == 0 || $aMaxObjectSize['width'] == 0 )
        $aMaxObjectSize = $aImgSz;
    
    if ( ($aImgSz['height'] < $aOriginalWindowSize['height']) || ($aImgSz['width'] < $aOriginalWindowSize['width']) )
        return;
    
    $aAllScales = [];
    $aScales = [];
    
    for ($fFactor = 1; ; $fFactor *= $fScaleFactor) {
      $aWindowSize = [ 
        "width" => round($aOriginalWindowSize['width']*$fFactor), 
        "height" => round($aOriginalWindowSize['height']*$fFactor),
      ];
      if( $aWindowSize['width'] > $aImgSz['width'] || $aWindowSize['height'] > $aImgSz['height'] )
        break;
      array_push($aAllScales, $fFactor);
    }
    
    for ( $iIndex = 0; $iIndex < count($aAllScales); $iIndex++) {
      $aWindowSize = [ 
        "width" => round($aOriginalWindowSize['width']*$aAllScales[$iIndex]), 
        "height" => round($aOriginalWindowSize['height']*$aAllScales[$iIndex]),
      ];
      if( $aWindowSize['width'] > $aMaxObjectSize['width'] || $aWindowSize['height'] > $aMaxObjectSize['height'])
          break;
      if( $aWindowSize['width'] < $aMinObjectSize['width'] || $aWindowSize['height'] < $aMinObjectSize['height'] )
          continue;
      array_push($aScales, $aAllScales[$iIndex]);
    }
    
    if ( empty($aScales) && !empty($aAllScales) ) {
      $aDistances = [];

      for($iIndex = 0; $iIndex < count($aAllScales); $iIndex++){
        $aWindowSize = [ 
          "width" => round($aOriginalWindowSize['width']*$aAllScales[$iIndex]), 
          "height" => round($aOriginalWindowSize['height']*$aAllScales[$iIndex]),
        ];
        $fD = ($aMinObjectSize['width'] - $aWindowSize['width']) * ($aMinObjectSize['width'] - $aWindowSize['width'])
              + ($aMinObjectSize['height'] - $aWindowSize['height']) * ($aMinObjectSize['height'] - $aWindowSize['height']);
        array_push($aDistances, $fD);
      }

      $iMin = 0;
      for ($iI = 0; $iI < count($aDistances); ++$iI) {
        if ($aDistances[$iMin] > $aDistances[$iI])
          $iMin = $iI;
      }
      
      array_push($aScales, $aAllScales[$iMin]);
    }
    
    $aObjects = [];
    $aRejectLevels = [];
    $aLevelWeights = [];
    
    if (imageistruecolor($hImageResource)) {
      imagefilter($hImageResource, IMG_FILTER_GRAYSCALE);
    }
    
    $oGray = new InputArray($hImageResource);
    
    if (!$this->oFeatureEvaluator->fnSetImage($oGray, $aScales))
        return;
    
    $this->oFeatureEvaluator->fnGetMats();
    
    $oCurrentMask;
    
    if ($this->oMaskGenerator)
      $oCurrentMask = $this->oMaskGenerator->fnGenerateMask($oGray->fnGetMat());

    $iI;
    $iNscales = count($aScales);
    $aStripeSizes = [];
    $aS = $this->oFeatureEvaluator->fnGetScaleData();
    $aSzw = $aS[0]->fnGetWorkingSize($this->oData->aOrigWinSize);
    $iNstripes = ceil($aSzw['width']/32.);
    for ($iI = 0; $iI < $iNscales; $iI++) {
      $aSzw = $aS[$iI]->fnGetWorkingSize($this->oData->aOrigWinSize);
      $aStripeSizes[$iI] = max(($aSzw['height']/$aS[$iI]->ystep + $iNstripes-1)/$iNstripes, 1)*$aS[$iI]->ystep;
    }

    /*
    $oInvoker = new ;
    CascadeClassifierInvoker invoker(*this, (int)nscales, nstripes, s, stripeSizes,
                                     candidates, rejectLevels, levelWeights,
                                     outputRejectLevels, currentMask, &mtx);
    parallel_for_(Range(0, nstripes), invoker);
     */
    
    $fGypWeight = 0.;
    $aOrigWinSize = $this->oData->aOrigWinSize;
    $aRange = [ 'start' => 0, 'end' => $iNstripes ];

    for($iScaleIdx = 0; $iScaleIdx < $iNscales; $iScaleIdx++ ) {
      $oS = $aS[$iScaleIdx];
      $fScalingFactor = $oS->scale;
      $iYStep = $oS->ystep;
      $iStripeSize = $aStripeSizes[$iScaleIdx];
      $iY0 = $aRange['start']*$iStripeSize;
      $aSzw = $oS->fnGetWorkingSize($aOrigWinSize);
      $iY1 = min($aRange['end']*$iStripeSize, $aSzw['height']);
      
      $aWinSize = [ 
        'width' => round($aOrigWinSize['width'] * $fScalingFactor),
        'height' => round($aOrigWinSize['height'] * $fScalingFactor),
      ];

      for ($iY = $iY0; $iY < $iY1; $iY += $iYStep) {
        for ($iX = 0; $iX < $aSzw['width']; $iX += $iYStep) {
          $iResult = $this->fnRunAt([ 'x' => $iX, 'y' => $iY], $iScaleIdx, $fGypWeight);
          if ($aRejectLevels) {
            if ($iResult == 1)
              $iResult = -count($this->oData->aStages);
            
            if (count($this->oData->aStages) + $iResult == 0) {
              //mtx->lock();
              array_push(
                $aObjects, 
                new Rect(
                  round($iX*$fScalingFactor),
                  round($iY*$fScalingFactor),
                  $aWinSize['width'],
                  $aWinSize['height']
                )
              );
              array_push($aRejectLevels, -$iResult);
              array_push($aLevelWeights, $fGypWeight);
              //mtx->unlock();
            }
          } else if ($iResult > 0) {
            //mtx->lock();
            array_push(
              $aObjects, 
              new Rect(
                round($iX*$fScalingFactor),
                round($iY*$fScalingFactor),
                $aWinSize['width'],
                $aWinSize['height']
              )
            );
            //mtx->unlock();
          }
          if ($iResult == 0)
            $iX += $iYStep;
        }
      }
    }
  }
  
  public function fnRunAt($aPt, $iScaleIdx, &$fWeight)
  {    
    //CV_INSTRUMENT_REGION()
    //$this->oFeatureEvaluator
    //if (!oldCascade && isset(FeatureEvaluator::$aTypes[$this->sType]))
    //  throw new Exception("");

    if (!$this->oFeatureEvaluator->fnSetWindow($aPt, $iScaleIdx))
      return -1;
    
    if ($this->oData->iMaxNodesPerTree == 1 ) {
      if ($this->sType == 'HAAR')
        return $this->fnPredictOrderedStump($fWeight);
      elseif ($this->sType == 'LBP')
        return $this->fnPredictCategoricalStump($fWeight);
      else
        return -2;
    } else {
      if ($this->sType == 'HAAR')
        return $this->fnPredictOrderedStump($fWeight);
      elseif ($this->sType == 'LBP')
        return $this->fnPredictCategoricalStump($fWeight);
      else
        return -2;
    }
  }
  
  public function fnPredictOrderedStump(&$fWeight)
  {
    //CV_INSTRUMENT_REGION()

    if (!empty($this->oData->aStumps)) {
      throw new Exception("");
    }
    
    $aCascadeStumps = $this->oData->aStumps;
    $aCascadeStages = $this->oData->aStages;
    
    $aCascadeStumpsIndex = 0;

    $iNstages = count($aCascadeStages);
    $fTmp = 0;

    for ($iStageIdx = 0; $iStageIdx < $iNstages; $iStageIdx++ ) {
      $oStage = $aCascadeStages[$iStageIdx];
      $fTmp = 0;

      $iNtrees = $oStage['ntrees'];
      for ($iI = 0; $iI < $iNtrees; $iI++) {
        $oStump = $aCascadeStumps[$aCascadeStumpsIndex + $iI];
        $fValue = featureEvaluator($oStump['featureIdx']);
        $fTmp += $fValue < $oStump['threshold'] ? $oStump['left'] : $oStump['right'];
      }

      if ($fTmp < $oStage['threshold']) {
        return -$iStageIdx;
      }
      $aCascadeStumpsIndex += $iNtrees;
    }

    return 1;
  }
  
  public function fnPredictCategoricalStump(&$fWeight)
  {
    
  }
}
