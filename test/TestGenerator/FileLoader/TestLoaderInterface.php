<?php declare(strict_types=1);


namespace Dodo\TestGenerator\FileLoader;

interface TestLoaderInterface {

  public function loadFile(string $url);

}