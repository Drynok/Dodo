<?php


namespace Dodo\TestGenerator;


use Dodo\TestGenerator\Transpiler\JavascriptTraspiler;
use Robo\Task\Testing\PHPUnit;

class TestRunner {

  /**
   * @var \Dodo\TestGenerator\TestBuilder
   */
  private $testBuilder;

  public function __construct() {
    $this->traspiller = new JavascriptTraspiler();
    $this->testBuilder = new TestBuilder();
  }

  public function init() {

  }

  public function loadTestFiles() {

  }

  public function runTest($test) {

  }


}