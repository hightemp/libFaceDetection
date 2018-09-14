<?php

namespace libFaceDetection;

use Exception;

function fnCopyVectorToUMat(&$aV, UMat &$oUm) 
{
  $iVarSize = 0;
  if (is_int($aV[0]))
    $iVarSize = PHP_INT_SIZE;
  if (is_float($aV[0]))
    $iVarSize = 8;
  (new Mat(1, count($aV)*$iVarSize, CV_8U, $aV[0])).fnCopyTo($oUm);
}

class InputArray
{
  function __construct(...$aArguments) 
  {
    if (is_resource($aArguments[0])) {
      
    }
  }
  
  public function fnGetMat($iIdx=-1)
  {
    
  }
  /*
  Mat getMat_(int idx=-1) const;
  UMat getUMat(int idx=-1) const;
  void getMatVector(std::vector<Mat>& mv) const;
  void getUMatVector(std::vector<UMat>& umv) const;
  void getGpuMatVector(std::vector<cuda::GpuMat>& gpumv) const;
  cuda::GpuMat getGpuMat() const;
  ogl::Buffer getOGlBuffer() const;

  int getFlags() const;
  void* getObj() const;
  Size getSz() const;

  int kind() const;
  int dims(int i=-1) const;
  int cols(int i=-1) const;
  int rows(int i=-1) const;
  Size size(int i=-1) const;
  int sizend(int* sz, int i=-1) const;
  bool sameSize(const _InputArray& arr) const;
  size_t total(int i=-1) const;
  int type(int i=-1) const;
  int depth(int i=-1) const;
  int channels(int i=-1) const;
  bool isContinuous(int i=-1) const;
  bool isSubmatrix(int i=-1) const;
  bool empty() const;
  void copyTo(const _OutputArray& arr) const;
  void copyTo(const _OutputArray& arr, const _InputArray & mask) const;
  size_t offset(int i=-1) const;
  size_t step(int i=-1) const;
  bool isMat() const;
  bool isUMat() const;
  bool isMatVector() const;
  bool isUMatVector() const;
  bool isMatx() const;
  bool isVector() const;
  bool isGpuMat() const;
  bool isGpuMatVector() const;
  
  protected $iFlags;
  protected $oObj;
  protected $aSz;

  void init(int _flags, const void* _obj);
  void init(int _flags, const void* _obj, Size _sz);
   */
}

class UMat
{
  
}

class Mat
{
  
}