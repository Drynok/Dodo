<?php


namespace Dodo\TestGenerator;


use Dodo\TestGenerator\FileLoader\TestLoaderInterface;
use Dodo\TestGenerator\FileLoader\HtmlTestLoader;
use Dodo\TestGenerator\FileLoader\JslTestLoader;
use Symfony\Component\HttpClient\HttpClient;

class TestsLoader {

  /**
   * @var \Dodo\TestGenerator\FileLoader\JslTestLoader
   */
  private $js_file_loader;

  /**
   * @var \Dodo\TestGenerator\FileLoader\HtmlTestLoader
   */
  private $html_file_loader;

  public function __construct() {
    $this->js_file_loader = new JslTestLoader(new HttpClient());
    $this->html_file_loader = new HtmlTestLoader(new HttpClient());
  }

  public function loadW3CTests() {
    $tests = $this->getListOfW3cTestFiles();
    foreach ($tests as &$test) {
      $test = $this->js_file_loader->loadFile($test);
    }

    return $tests;
  }

  public function getListOfW3cTestFiles() {
    $tests = [];
    //https://github.com/fgnass/domino/tree/master/test/w3c/level1/html
    return [];
  }

  public function loadWptTests() {
    $tests = $this->getListOfWptTestFiles();
    foreach ($tests as &$test) {
      $test = $this->html_file_loader->loadFile($test);
    }

    return $tests;
  }

  public function getListOfWptTestFiles() {
    //https://github.com/web-platform-tests/wpt/tree/master/dom/nodes
    return [];
  }

}