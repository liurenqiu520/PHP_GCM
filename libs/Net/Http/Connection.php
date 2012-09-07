<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/06
 * Time: 12:13
 * To change this template use File | Settings | File Templates.
 */

namespace Net\Http;

use Net\SocketStream;

class Connection
{

    /** @var SocketStream */
    private $stream;

    private $host;
    /**
     * @param string $host
     * @param int $port
     * @param bool $ssl
     */
    public function __construct($host, $port, $ssl = false)
    {
        $streamProtocol = $ssl ? 'ssl' : 'tcp';
        $this->stream = new SocketStream($host, $port, $streamProtocol);
        $this->stream->setTimeout(10);
        $this->host = $host;
    }

    /**
     *
     * @param string $path
     * @param array $params
     * @param array $option
     * @return Response
     */
    public function get($path, $params = array(), $option = array())
    {
        if(count($params) > 0) {
            if(strpos($path, '?') === false) {
                $path .= '?';
            }
            $path .= http_build_query($params, '', '&');
        }

        $request = Method::create(Method::GET, $this->host, $path);

        foreach($option as $key => $value) {
           $request->addProperty($key, $value);
        }

        return $this->send($request);
    }

    /**
     * $paramsが文字列の場合はそのままセット配列の場合はクエリとして扱う
     * CONTENT_TYPEはデフォルトx-​www-form-urlencodedとして扱うので
     * multipartの場合は$optionで指定する
     *
     * @param string $path
     * @param mixed $params
     * @param array $option
     * @throws \InvalidArgumentException
     * @return Response
     */
    public function post($path, $params = array(), $option = array())
    {
        $request = Method::create(Method::POST, $this->host, $path);

        if(is_array($params)) {
            $request->setPayload(http_build_query($params, '', '&'));
        }
        else if(is_string($params)) {
            $request->setPayload($params);
        }
        else {
            throw new \InvalidArgumentException('invalid $params type.');
        }

        $request->addProperty(Header::CONTENT_TYPE, 'application/x-​www-form-urlencoded');

        foreach($option as $key => $value) {
            $request->addProperty($key, $value);
        }
        return $this->send($request);
    }


    /**
     *
     */
    public function open()
    {
        if(!$this->stream->isConnected())
            $this->stream->open(true);
    }

    /**
     *
     */
    public function close()
    {
        if($this->stream->isConnected()) $this->stream->close();
    }

    /**
     * @param Request $request
     * @return Response
     */
    private function send(Request $request)
    {

        if (!$this->stream->isConnected()) {
            $this->open();
        }

        if ($this->stream->isConnected()) {

            if ($this->stream->write($request->toString()) !== false) {

                $read = '';
                while($line = $this->stream->readLine()) {
                    $read .= $line;
                }

                $response = Response::create($read);

                if(strtolower($response->getProperty(Header::CONNECTION)) === 'close') {
                    $this->close();
                }

                return $response;
            }
        }

        return null;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->stream->setTimeout($timeout);
    }

    public function __destruct()
    {
        $this->close();
    }

}

