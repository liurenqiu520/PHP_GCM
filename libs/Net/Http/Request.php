<?php

namespace Net\Http;

class Request {

	/** @var Header */
	protected $header;
    /** @var string */
    protected $version = '1.1';
    /** @var string */
    protected $method = Method::GET;
    /** @var string */
    protected $payload = null;
    /** @var string */
    protected $path = null;

    /**
     * @param string $host
     * @param string $path
     * @param string $httpMethod
     */
    public function __construct($host, $path = '/', $httpMethod = Method::GET ) {
        $this->method = $httpMethod;
        $this->header = new Header(array(
            Header::HOST => $host,
        ));
        $this->setPath($path);
	}

    /**
     * @param string $path
     */
    public function setPath($path) {
        $this->path = $path;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function addProperty($key, $value) {
        $this->header->setProperty($key, $value);
    }

    /**
     * @param string $key
     * @return string
     */
    public function getProperty($key) {
        return $this->header->getProperty($key);
    }

    /**
     * @param string $payload
     */
    public function setPayload($payload) {
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    public function getPayload() {
        return $this->payload;
    }

    /**
     * @return int
     */
    public function getPayloadSize() {
        return strlen($this->getPayload());
    }

    /**
     * @return string
     */
    public function getRequestLine() {
        return sprintf('%s %s HTTP/%s', $this->method, $this->path, $this->version);
    }

    /**
     * @return string
     */
    public function toString() {

        $httpRequest = $this->getRequestLine();
        $httpRequest .= "\n";
        $httpRequest .= $this->header->toString();
        $httpRequest .= $this->getPayload();
        $httpRequest .= "\r\n";

        return $httpRequest;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->toString();
    }

    public function __destruct() {
        $this->payload = null;
        $this->path = null;
        $this->host = null;
        $this->method = null;
        $this->header = null;
    }

}
