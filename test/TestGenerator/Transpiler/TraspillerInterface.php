<?php declare(strict_types=1);

namespace Dodo\TestGenerator\Transpiler;

interface TraspillerInterface {

  public function transpileTest($file);

  public function transpileTests(array $files): array;

}