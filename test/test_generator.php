<?php
/******************************************************************************
 *
 * Run with: 'php ./test_generator.php'
 ******************************************************************************/

namespace Dodo\TestGenerator;

use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use Dodo\TestGenerator\Transpiler\JavascriptTraspiler;

require_once 'vendor/autoload.php';

$test_loader = new TestsLoader();
$w3c_tests = $test_loader->loadW3CTests();
$wpt_tests = $test_loader->loadWptTests();

$traspiler = new JavascriptTraspiler();

$w3c_tests = $traspiler->transpileTests($w3c_tests);
$wpt_tests = $traspiler->transpileTests($wpt_tests);

$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

foreach (array_merge($w3c_tests, $wpt_tests) as $test_code) {
  try {
    $ast = $parser->parse($test_code);

  } catch (Error $error) {
    echo "Parse error: {$error->getMessage()}\n";
    return;
  }

  //  $dumper = new NodeDumper;
  //  echo $dumper->dump($ast) . "\n";
}