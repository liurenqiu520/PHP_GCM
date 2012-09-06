<?php

namespace Net\Http;

class Response {

    /** @var Header */
    private $header;

    /** @var string */
    private $requestLine;

    /** @var int */
    private $statusCode;

    /** @var string */
    private $body;

    /**
     *
     * @param string $requestLine
     * @param string $header
     * @param string $body
     * @throws Exception
     */
    public function __construct($requestLine, $header, $body ) {
        $this->requestLine = $requestLine;
        $this->header = $header;
        $this->body = $body;
        if (preg_match('/^HTTP\/\d\.\d (\d{3})/', $requestLine, $matches)) {
            $this->statusCode = intval($matches[1]);
        }
        else {
            throw new Exception('RequestLine is not valid. rack status code.');
        }
    }

    /**
     * @param string $key
     * @return string
     */
    public function getProperty($key) {
        return $this->header->getProperty($key);
    }

    /**
     * @return string
     */
    public function getResponseBody() {
        return $this->body;
    }

    /**
     * @return int
     */
    public function getResponseBodySize() {
        return strlen($this->getResponseBody());
    }

    /**
     * @return string
     */
    public function getResponseLine() {
        return $this->requestLine;
    }

    /**
     * @return int
     */
    public function getResponseCode() {
        return $this->statusCode;
    }

    public function toString() {
        $response = $this->getResponseLine();
        $response .= "\n";
        $response .= $this->header->toString();
        $response .= $this->getResponseBody();
        $response .= "\r\n";

        return $response;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->toString();
    }

    /**
     * HTTPResponse文字列をパースしてResponseオブジェクトを生成
     *
     * @static
     * @param string $responseString
     * @return Response
     */
    public static function create($responseString)
    {
        $responseString = str_replace(array("\r\n","\r"), "\n", $responseString);
        list($headerString, $body) = explode("\n\n", $responseString, 2);
        $header = new Header();
        $headers = explode("\n", $headerString);
        $responseLine = $headers[0];
        array_shift($headers);
        foreach ($headers as $string) {
            list($key, $value) = explode(':', $string, 2);
            $header->setProperty(trim($key), trim($value));
        }
        return new Response($responseLine, $header, $body);
    }
}