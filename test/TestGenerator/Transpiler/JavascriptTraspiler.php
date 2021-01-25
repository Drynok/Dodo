<?php

namespace Dodo\TestGenerator\Transpiler;

class JavascriptTraspiler implements TraspillerInterface {

  public function __construct() {
  }

  public function transpileTest($file) {

    return $file;
  }

  public function transpileTests(array $files): array {
    foreach ($files as &$file){
      $file = $this->transpileTest($file);
    }

    return $files;

  }

}