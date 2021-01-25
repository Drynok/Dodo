<?php


namespace Dodo\TestGenerator\FileLoader;

use Dodo\TestGenerator\Helper\Sanitizer;

class JslTestLoader extends TestLoader {

  function loadFile(string $url): array {
    $file = parent::loadFile($url);
    $file = Sanitizer::sanitize($file);
    return $file;
  }

}