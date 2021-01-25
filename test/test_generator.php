<?php
/******************************************************************************
 *
 * Run with: 'php ./test_generator.php'
 ******************************************************************************/
namespace Dodo\TestGenerator;

use Dodo\TestGenerator\Transpiler\JavascriptTraspiler;

require_once 'vendor/autoload.php';

$test_loader = new TestsLoader();
$w3c_tests = $test_loader->loadW3CTests();
$wpt_tests = $test_loader->loadWptTests();

$traspiler = new JavascriptTraspiler();

$w3c_tests = $traspiler->transpileTests($w3c_tests);
$wpt_tests = $traspiler->transpileTests($wpt_tests);