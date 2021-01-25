<?php


namespace Dodo\TestGenerator\FileLoader;

use Exception;
use Symfony\Component\HttpClient\HttpClient;

abstract class TestLoader implements TestLoaderInterface {

  /**
   * @var \Symfony\Contracts\HttpClient\HttpClientInterface
   */
  private $http_client;

  /**
   * FileLoader constructor.
   */
  public function __construct(HttpClient $http_client) {
    $this->http_client = $http_client;
  }

  public function loadFile(string $url): array {
    $content = [];
    $response = $this->http_client->request('GET', 'https://api.github.com/repos/symfony/symfony-docs');
    $statusCode = $response->getStatusCode();
    if ($statusCode) {
      $contentType = $response->getHeaders()['content-type'][0];
      $content = $response->getContent();
      $content = $response->toArray();
    }

    return $content;
  }

}