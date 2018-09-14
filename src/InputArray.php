<?php

namespace libFaceDetection;

use Exception;

class InputArray
{
  protected $iFlags;
  protected $oObj;
  protected $aSz;
  
  const KIND_SHIFT = 16;
  const FIXED_TYPE = 0x8000 << self::KIND_SHIFT;
  const FIXED_SIZE = 0x4000 << self::KIND_SHIFT;
  const KIND_MASK = 31 << self::KIND_SHIFT;

  const NONE              = 0 << self::KIND_SHIFT;
  const MAT               = 1 << self::KIND_SHIFT;
  const MATX              = 2 << self::KIND_SHIFT;
  const STD_VECTOR        = 3 << self::KIND_SHIFT;
  const STD_VECTOR_VECTOR = 4 << self::KIND_SHIFT;
  const STD_VECTOR_MAT    = 5 << self::KIND_SHIFT;
  const EXPR              = 6 << self::KIND_SHIFT;
  const OPENGL_BUFFER     = 7 << self::KIND_SHIFT;
  const CUDA_HOST_MEM     = 8 << self::KIND_SHIFT;
  const CUDA_GPU_MAT      = 9 << self::KIND_SHIFT;
  const UMAT              =10 << self::KIND_SHIFT;
  const STD_VECTOR_UMAT   =11 << self::KIND_SHIFT;
  const STD_BOOL_VECTOR   =12 << self::KIND_SHIFT;
  const STD_VECTOR_CUDA_GPU_MAT = 13 << self::KIND_SHIFT;
  const STD_ARRAY         =14 << self::KIND_SHIFT;
  const STD_ARRAY_MAT     =15 << self::KIND_SHIFT;

  function __construct(...$aArguments) 
  {
    if (is_resource($aArguments[0])) {
      
    }
  }
  
  public function fnKind()
  {
    return $this->iFlags & self::KIND_MASK;
  }
  
  public function fnSize()
  {
    $iKind = $this->fnKind();
    
    switch($iKind) {
      case self::MAT:
      case self::UMAT:
      case self::EXPR:
        return $oObj->fnSize();
    }
    
    throw new Exception("Unknown/unsupported array type");
  }
  
  public function fnCopyTo($oArr)
  {
    $iKind = $this->fnKind();

    switch($iKind) {
      case self::MAT:
      case self::MATX:
      case self::STD_VECTOR:
      case self::STD_ARRAY:
      case self::STD_BOOL_VECTOR:
        $this->fnGetMat()->fnCoptyTo($oArr);
        break;
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
  
  void init(int _flags, const void* _obj);
  void init(int _flags, const void* _obj, Size _sz);
   */
}
