<?php


namespace Dodo\TestGenerator\FileLoader;


use Dodo\TestGenerator\Helper\Sanitizer;

class HtmlTestLoader extends TestLoader {

  function loadFile(string $url): array {
    $file = parent::loadFile($url);
    $file = Sanitizer::removeHtml($file);
    $file = Sanitizer::sanitize($file);
    return $file;
  }
}