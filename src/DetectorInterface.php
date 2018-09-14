<?php

namespace libFaceDetection;

use Exception;

interface DetectorInterface
{
  public function fnDetect();
  public function fnDetectWithImage();
}