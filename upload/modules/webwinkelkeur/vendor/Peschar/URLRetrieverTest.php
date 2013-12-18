<?php
/**
 * @author Albert Peschar <albert@peschar.net>
 */

require_once dirname(__FILE__) . '/URLRetriever.php';

class Peschar_URLRetrieverTest extends PHPUnit_Framework_TestCase {
    private $success_url = 'http://example.com/';
    private $success_re = '/Example Domain/';
    private $failure_url = 'http://abcdef.ghijkl/';

    public function testRetrieve() {
        foreach(array('retrieve', 'retrieveWithCURL', 'retrieveWithFile') as $method) {
            $this->trySuccess($method);
            $this->tryFailure($method);
        }
    }

    private function retrieveWithMethod($method, $url) {
        $retriever = new Peschar_URLRetriever();
        return $retriever->$method($url);
    }

    private function trySuccess($method) {
        $response = $this->retrieveWithMethod($method, $this->success_url);
        $this->assertInternalType('string', $response);
        $this->assertRegExp($this->success_re, $response);
    }

    private function tryFailure($method) {
        $response = $this->retrieveWithMethod($method, $this->failure_url);
        $this->assertFalse($response);
    }
}
