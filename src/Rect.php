<?php

namespace libFaceDetection;

use Exception;

class Rect
{
  public $x;
  public $y;
  public $width;
  public $height;
  
  function __construct($x=0, $y=0, $width=0, $height=0) 
  {
    $this->x = $x;
    $this->y = $y;
    $this->width = $width;
    $this->height = $height;
  }
  
  public function fnArea()
  {
    return $this->width*$this->height;
  }
  
  public function fnSize()
  {
    return [ 'width' => $this->width, 'height' => $this->height ];
  }
}

