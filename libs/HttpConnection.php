<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/06
 * Time: 12:13
 * To change this template use File | Settings | File Templates.
 */
class HttpConnection
{

    /** @var HttpHeader */
    private $requestHeader;

    /** @var HttpHeader */
    private $responseHeader;

    private $responseBody;

    const LINE_BRAKE = "\r\n";

    private $timeout = 15;

    /** @var SocketStream */
    private $stream;

    public function __construct($host, $port, $ssl = false)
    {
        $streamProtocol = $ssl ? 'ssl' : 'tcp';
        $this->stream = new SocketStream($host, $port, $streamProtocol);

        $this->requestHeader = new HttpHeader(array(), self::LINE_BRAKE);
    }

    public function addRequestProperty($key, $value)
    {
        $this->requestHeader->addProperty($key, $value);
    }

    public function removeRequestProperty($key)
    {
        $this->requestHeader->removeProperty($key);
    }

    public function clearRequestProperties()
    {
        $this->requestHeader->clearProperties();
    }
    
    public function get($path, $params = '', $headers = array())
    {
        return $this->send($path, 'get', $params, $headers);
    }

    public function post($path, $params = '', $headers = array())
    {
        return $this->send($path, 'post', $params, $headers);
    }

    public static function serializeAuth($user, $pass)
    {
        return base64_encode($user . ':' . $pass);
    }

    public static function serializeParams($params)
    {
        $queryString = array();
        foreach ($params as $key => $value) {
            $queryString[] = urlencode($key) . '=' . urlencode($value);
        }
        return implode('&', $queryString);
    }

    public function open()
    {
        if(!$this->stream->isConnected())
            $this->stream->open(true);
    }

    public function close()
    {
        if($this->stream->isConnected()) $this->stream->close();
    }

    private function send($path, $method, $params='', $headers = array())
    {
        $this->requestHeader->addProperties($headers);
        $method = strtoupper($method);
        $request = strtoupper($method) . ' ' . $path;
        
        if($method === 'GET' && !empty($params)) {
            if(strpos($path, '?') === false) {
                $request .= '?';
            }
            $request .= $params . ' ';
        }
        
        $request .= ' HTTP/1.0'. self::LINE_BRAKE;
        $request .= $this->requestHeader->toString() . self::LINE_BRAKE;
        
        if($method === 'POST' && !empty($params)) {
            $request .= $params;
        }

        if (!$this->stream->isConnected()) {
            $this->open();
        }

        if ($this->stream->isConnected()) {

            if ($this->stream->write($request) !== false) {
                $response = '';
                while($line = $this->stream->readLine()) {
                    $response .= $line;
                }
                $this->parseResponse($response);

                return true;
            }

        }

        return false;
    }

    private function parseResponse($response)
    {
        $response = str_replace("\r\n", "\n", $response);

        list($headers, $body) = explode("\n\n", $response, 2);

        $this->responseHeader = HttpHeader::create($headers, self::LINE_BRAKE);

        $this->responseBody = $body;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function getRequestHeader()
    {
        return $this->requestHeader;
    }

    public function getResponseBody()
    {
        return $this->responseBody;
    }

    public function getResponseCode()
    {
        return $this->responseHeader->getResponseCode();
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }
}

/**
 * ResponseCode別にしたい
 *
 */
class HttpHeader
{

    private $headers;

    private $responseCode = 0;

    private $lineBreak;

    public function __construct($headers = array(), $lineBreak = "\n")
    {
        $this->lineBreak = $lineBreak;
        $this->headers = $headers;
    }

    public function toString()
    {
        $headers = '';
        foreach ($this->headers as $header => $value) {
            $headers .= $header.': '. $value . $this->lineBreak;
        }
        return $headers;
    }

    public function toArray()
    {
        return $this->headers;
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function addProperties($headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    public function addProperty($key, $value)
    {
        $this->headers[$key] = $value;
    }

    public function removeProperty($key)
    {
        unset($this->headers[$key]);
    }

    public function clearProperties()
    {
        $this->headers = array();
    }

    public function getProperty($key)
    {
        return $this->headers[$key];
    }

    public function getResponseCode()
    {
        return $this->responseCode;
    }

    private function setResponseCode($responseCode)
    {
        $this->responseCode = $responseCode;
    }

    public static function create($headerString, $lineBreak)
    {
        $httpHeader = new HttpHeader();
        $replace = ($lineBreak == "\n" ? "\r\n" : "\n");
        $headerString = str_replace($replace, $lineBreak, trim($headerString));
        $headers = explode($lineBreak, $headerString);
        if (preg_match('/^HTTP\/\d\.\d (\d{3})/', $headers[0], $matches)) {
            $httpHeader->setResponseCode(intval($matches[1]));
            array_shift($headers);
        }

        foreach ($headers as $string) {
            list($key, $value) = explode(':', $string, 2);
            $httpHeader->addProperty(trim($key), trim($value));
        }
        return $httpHeader;
    }
}
