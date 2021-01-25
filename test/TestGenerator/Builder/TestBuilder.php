<?php

namespace Dodo\TestGenerator;

use Dodo\TestGenerator\Factory\TranspilerFactory;
use Dodo\TestGenerator\Transpiler\TraspillerInterface;
use Nette\PhpGenerator\ClassType;

class TestBuilder implements TestBuilderInterface {

  public function __construct($test) {
    $this->traspiller = $test;
  }

  public function buildTest($traspiled_test) {
    $class = new ClassType('TestCase');
    $test_name = $traspiled_test['name'];
    $class->addMethod('test' . $test_name);

    return $class;
  }



}