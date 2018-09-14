<?php

namespace libFaceDetection;

use Exception;

class Data
{
  public $iStageType;
  public $iFeatureType;
  public $iNcategories;
  public $iMinNodesPerTree;
  public $iMaxNodesPerTree;
  public $aOrigWinSize = [];

  public $aStages = [];
  public $aClassifiers = [];
  public $aNodes = [];
  public $aLeaves = [];
  public $aSubsets = [];
  public $aStumps = [];
  
  public function fnRead($aNodes)
  {
    $THRESHOLD_EPS = 1e-5;
    
    $sStageTypeStr = $aNodes->stageType->__toString();
    if ($sStageTypeStr == 'BOOST')
      $this->iStageType = 0;
    else
      return false;
    
    $sFeatureTypeStr = $aNodes->featureType->__toString();
    if (isset(FeatureEvaluator::$aTypes[$sFeatureTypeStr])) {
      $this->iFeatureType = FeatureEvaluator::$aTypes[$sFeatureTypeStr];
    } else
      return false;
    
    $this->aOrigWinSize['width'] = $aNodes->width->__toString();
    $this->aOrigWinSize['height'] = $aNodes->height->__toString();
    if (!($this->aOrigWinSize['height'] > 0 && $this->aOrigWinSize['width'] > 0))
      throw new Exception("");
    
    if (!$aNodes->featureParams->count())
      return false;
    
    $this->iNcategories = $aNodes->featureParams->maxCatCount->__toString();
    $iSubsetSize = ($this->iNcategories + 31)/32;
    $iNodeStep = 3 + ( $this->iNcategories>0 ? $iSubsetSize : 1 );
    
    if (!$aNodes->stages->count())
      return false;
    
    $this->aClassifiers = [];
    $this->aNodes = [];
    $this->aStumps = [];
    
    $iMinNodesPerTree = PHP_INT_MAX;
    $iMaxNodesPerTree = 0;
    
    foreach ($aNodes->stages->_ as $aStageNode) {
      $aStage = [];
      $aStage['threshold'] = $aStageNode->stageThreshold->__toString() - $THRESHOLD_EPS;

      if (!$aStageNode->weakClassifiers->count())
        return false;

      $aStage['ntrees'] = $aStageNode->weakClassifiers->count();
      $aStage['first'] = count($this->aClassifiers);
      
      array_push($this->aStages, $aStage);
      
      foreach ($aStageNode->weakClassifiers->_ as $aWeakClassifierNode) {
        if (!$aWeakClassifierNode->internalNodes->count() || !$aWeakClassifierNode->leafValues->count())
          return false;
        
        $aTree = [];
        
        $aTree['nodeCount'] = (int) $aWeakClassifierNode->internalNodes->count()/$iNodeStep;
        $iMinNodesPerTree = min($iMinNodesPerTree, $aTree['nodeCount']);
        $iMaxNodesPerTree = max($iMaxNodesPerTree, $aTree['nodeCount']);

        array_push($this->aClassifiers, $aTree);
        
        $aInternalNodes = explode(' ', $aWeakClassifierNode->internalNodes->__toString());
        $this->aLeaves = explode(' ', $aWeakClassifierNode->leafValues->__toString());
        
        reset($aInternalNodes);
        
        do {
          $aNode = [];
          
          $aNode['left'] = current($aInternalNodes);
          next($aInternalNodes);
          $aNode['right'] = current($aInternalNodes);
          next($aInternalNodes);
          $aNode['featureIdx'] = current($aInternalNodes);
          next($aInternalNodes);
          
          if ($iSubsetSize > 0) {
            for($iJ = 0; $iJ < $iSubsetSize; $iJ++, next($aInternalNodes))
              array_push($this->aSubsets, current($aInternalNodes));
            $aNode['threshold'] = 0;
          } else {
            $aNode['threshold'] = current($aInternalNodes);
            next($aInternalNodes);
          }
          
          array_push($this->aNodes, $aNode);
        } while (next($aInternalNodes));
      }
    }
    
    if ($iMaxNodesPerTree == 1)
    {
      $iNodeOfs = 0;
      $iLeafOfs = 0;
      
      $iNstages = count($this->aStages);
      for ($iStageIdx = 0; $iStageIdx < $iNstages; $iStageIdx++ )
      {
        $aStage = $this->aStages[$iStageIdx];

        $iNtrees = $aStage['ntrees'];
        for ($iI = 0; $iI < $iNtrees; $iI++, $iNodeOfs++, $iLeafOfs+= 2 )
        {
          $aNode = $this->aNodes[$iNodeOfs];
          array_push(
            $this->aStumps, 
            [
              'featureIdx' => $aNode['featureIdx'], 
              'threshold' => $aNode['threshold'], 
              'left' => $this->aLeaves[$iLeafOfs],
              'right' => $this->aLeaves[$iLeafOfs+1],
            ]
          );
        }
      }
    }
    
    return true;
  }
}
