<?php

namespace libFaceDetection;

use Exception;

define('USAGE_DEFAULT', 0);
define('CV_AUTOSTEP', 0x7fffffff);
define('CV_MAT_CONT_FLAG_SHIFT', 14);
define('CV_MAT_CONT_FLAG', (1 << CV_MAT_CONT_FLAG_SHIFT));

class MatSize 
{
  
}

class MatStep
{
  
}

/*
struct CV_EXPORTS MatSize
{
    explicit MatSize(int* _p);
    int dims() const;
    Size operator()() const;
    const int& operator[](int i) const;
    int& operator[](int i);
    operator const int*() const;  // TODO OpenCV 4.0: drop this
    bool operator == (const MatSize& sz) const;
    bool operator != (const MatSize& sz) const;

    int* p;
};

struct CV_EXPORTS MatStep
{
    MatStep();
    explicit MatStep(size_t s);
    const size_t& operator[](int i) const;
    size_t& operator[](int i);
    operator size_t() const;
    MatStep& operator = (size_t s);

    size_t* p;
    size_t buf[2];
protected:
    MatStep& operator = (const MatStep&);
};
*/

class Mat
{
  public $iFlags;
  public $iDims;
  public $iCols;
  public $iRows;
  public $aData;
  
  public $oSize;
  public $oStep;
  
  public $iOffset = 0;

  const MAGIC_VAL = 0x42FF0000;
  const AUTO_STEP = 0;
  const CONTINUOUS_FLAG = CV_MAT_CONT_FLAG;
  const SUBMATRIX_FLAG = CV_SUBMAT_FLAG;  
  const MAGIC_MASK = 0xFFFF0000;
  const TYPE_MASK = 0x00000FFF;
  const DEPTH_MASK = 7;
  
  function __construct(...$aArguments) 
  {
    if (count($aArguments)==0) {
      $this->iCols = 0;
      $this->iRows = 0;
    }
    if (count($aArguments)==3) {
      $this->iCols = $aArguments[0];
      $this->iRows = $aArguments[1];
      $this->iFlags = $aArguments[2];
    }
    $this->oSize = new MatSize();
  }
  
  public function fnSetOffset($iOffset)
  {
    $this->iOffset = $iOffset;
    
    return $this;
  }
  
  public function fnAllocate($iDims, $aSizes, $iType, $aData, &$oStep, $iFlags, $iUsageFlags)
  {
    $iTotal = 1;
    
    for ($iI = $iDims-1; $iI >= 0; $iI--) {
      if ($oStep) {
        if ($aData && $oStep[$iI] != CV_AUTOSTEP) {
          $iTotal = $oStep[$iI];
        } else {
          $oStep[$iI] = $iTotal;
        }
      }
      $iTotal *= $aSizes[$iI];
    }
    
    $this->aData = array_fill(0, $iTotal, 0);
    /*
    UMatData* allocate(int dims, const int* sizes, int type,
                       void* data0, size_t* step, int flags, UMatUsageFlags usageFlags) const CV_OVERRIDE
    {
        size_t total = CV_ELEM_SIZE(type);
        for( int i = dims-1; i >= 0; i-- )
        {
            if( step )
            {
                if( data0 && step[i] != CV_AUTOSTEP )
                {
                    CV_Assert(total <= step[i]);
                    total = step[i];
                }
                else
                    step[i] = total;
            }
            total *= sizes[i];
        }
        uchar* data = data0 ? (uchar*)data0 : (uchar*)fastMalloc(total);
        UMatData* u = new UMatData(this);
        u->data = u->origdata = data;
        u->size = total;
        if(data0)
            u->flags |= UMatData::USER_ALLOCATED;

        return u;
    }
     */    
  }
  
  public function fnCreate(...$aArguments)
  {
    //$iD, $aSizes, $iType
    if (count($aArguments)==3) {
      if (is_int($aArguments[1])) {
        if ($this->iDims <= 2 
            && $this->iRows == $aArguments[0] 
            && $this->iCols == $aArguments[1] 
            /*&& type() == _type 
            && data*/)
          return;
        $this->fnCreate(2, [ 'width' => $aArguments[0], 'height' => $aArguments[1]], $aArguments[2]);
      }
      if (is_array($aArguments[1])) {
        $iI;
        
        if (/*data &&*/ ($aArguments[0] == $this->iDims || ($aArguments[0] == 1 && $this->iDims <= 2)) /*&& _type == type()*/) {
          if ($aArguments[0] == 2 && $this->iRows == $aArguments[1][0] && $this->iCols == $aArguments[1][1] )
            return;
          for ($iI = 0; $iI < $aArguments[0]; $iI++ )
            if ($this->oSize[$iI] != $aArguments[1][$iI] )
              break;
          if ($iI == $aArguments[0] && ($aArguments[0] > 1 || $this->oSize[1] == 1))
            return;
        }
        
        $this->iFlags = $aArguments[2]; //($iType & CV_MAT_TYPE_MASK) | MAGIC_VAL;        
        $this->fnSetSize(*this, d, _sizes, 0, true);
        
        $this->fnAllocate($this->iDims, $this->oSize, $aArguments[2], 0, $this->oStep, 0, USAGE_DEFAULT);
        /*
        $this->iDims = $aArguments[0];
        $this->iCols = $aArguments[1]['width'];
        $this->iRows = $aArguments[1]['height'];      
         */
      }
    }
    if (count($aArguments)==2) {
      $this->fnCreate($aArguments[0]['width'], $aArguments[0]['height'], $aArguments[1]);
    }
  }
  
  public function fnSetSize($iDims, $aSz, $aSteps, $bAutoSteps)
  {
    
    /*
    setSize( Mat& m, int _dims, const int* _sz, const size_t* _steps, bool autoSteps)
     * 
    CV_Assert( 0 <= _dims && _dims <= CV_MAX_DIM );
    if( m.dims != _dims )
    {
        if( m.step.p != m.step.buf )
        {
            fastFree(m.step.p);
            m.step.p = m.step.buf;
            m.size.p = &m.rows;
        }
        if( _dims > 2 )
        {
            m.step.p = (size_t*)fastMalloc(_dims*sizeof(m.step.p[0]) + (_dims+1)*sizeof(m.size.p[0]));
            m.size.p = (int*)(m.step.p + _dims) + 1;
            m.size.p[-1] = _dims;
            m.rows = m.cols = -1;
        }
    }

    m.dims = _dims;
    if( !_sz )
        return;

    size_t esz = CV_ELEM_SIZE(m.flags), esz1 = CV_ELEM_SIZE1(m.flags), total = esz;
    for( int i = _dims-1; i >= 0; i-- )
    {
        int s = _sz[i];
        CV_Assert( s >= 0 );
        m.size.p[i] = s;

        if( _steps )
        {
            if (_steps[i] % esz1 != 0)
            {
                CV_Error(Error::BadStep, "Step must be a multiple of esz1");
            }

            m.step.p[i] = i < _dims-1 ? _steps[i] : esz;
        }
        else if( autoSteps )
        {
            m.step.p[i] = total;
            int64 total1 = (int64)total*s;
            if( (uint64)total1 != (size_t)total1 )
                CV_Error( CV_StsOutOfRange, "The total matrix size does not fit to \"size_t\" type" );
            total = (size_t)total1;
        }
    }

    if( _dims == 1 )
    {
        m.dims = 2;
        m.cols = 1;
        m.step[1] = esz;
    }
     */
  }
  
  public function fnCopyTo(&$oMat)
  {
    $oMat = clone $this;
  }
  
  public function fnSize()
  {
    return [ 'width' => $this->iCols, 'height' => $this->iRows ];
  }

  public function fnOffset(...$aArguments)
  {
    if (count($aArguments) == 1) {
      if (isset($aArguments[0]['x'])) {
        return $aPt['y']*$this->iCols + $aPt['x'];
      }
    }    
    if (count($aArguments) == 2) {
      if (is_int($aArguments[0])) {
        $iIndex = $aArguments[0];
        foreach ($aArguments[1] as $iOffset) {
          $iIndex += $iOffset;
        }
        return $iIndex;
      }
      if (isset($aArguments[0]['x'])) {
        $iIndex = $aPt['y']*$this->iCols + $aPt['x'];
        foreach ($aArguments[1] as $iOffset) {
          $iIndex += $iOffset;
        }
        return $iIndex;
      }
    }
  }
  
  public function fnAt(...$aArguments)
  {
    if (count($aArguments) == 1) {
      if (isset($aArguments[0]['x'])) {
        return $this->aData[$aPt['y']*$this->iCols + $aPt['x']];
      }
    }    
    if (count($aArguments) == 2) {
      if (is_int($aArguments[0])) {
        $iIndex = $aArguments[0];
        foreach ($aArguments[1] as $iOffset) {
          $iIndex += $iOffset;
        }
        return $this->aData[$iIndex];
      }
      if (isset($aArguments[0]['x'])) {
        $iIndex = $aPt['y']*$this->iCols + $aPt['x'];
        foreach ($aArguments[1] as $iOffset) {
          $iIndex += $iOffset;
        }
        return $this->aData[$iIndex];
      }
    }
  }
}