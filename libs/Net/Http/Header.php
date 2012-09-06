<?php

namespace Net\Http;

class Header
{
    /**
     * RFC 2616で定義された標準ヘッダ
     */
    /** @var string */
    const ACCEPT = 'Accept';
    /** @var string */
    const ACCEPT_CHARSET = 'Accept-Charset';
    /** @var string */
    const ACCEPT_LANGUAGE = 'Accept-Language';
    /** @var string */
    const ACCEPT_RANGE = 'Accept-Range';
    /** @var string */
    const AGE = 'Age';
    /** @var string */
    const ALLOW = 'Allow';
    /** @var string */
    const AUTHORIZATION = 'Authorization';
    /** @var string */
    const CACHE_CONTROL = 'Cache-Control';
    /** @var string */
    const CONNECTION = 'Connection';
    /** @var string */
    const CONTENT_LANGUAGE = 'Content-Language';
    /** @var string */
    const CONTENT_LENGTH = 'Content-Length';
    /** @var string */
    const CONTENT_LOCATION = 'Content-Location';
    /** @var string */
    const CONTENT_MD5 = 'Content-MD5';
    /** @var string */
    const CONTENT_RANGE = 'Content-Range';
    /** @var string */
    const CONTENT_TYPE = 'Content-Type';
    /** @var string */
    const DATE = 'Date';
    /** @var string */
    const EXPECT = 'Expect';
    /** @var string */
    const EXPIRES = 'Expires';
    /** @var string */
    const FROM = 'From';
    /** @var string */
    const HOST = 'Host';
    /** @var string */
    const IF_MODIFIED_SINCE = 'If-Modified-Since';
    /** @var string */
    const IF_RANGE = 'If-Range';
    /** @var string */
    const IF_UNMODIFIED_SINCE = 'If-Unmodified-Since';
    /** @var string */
    const LAST_MODIFIED = 'Last-Modified';
    /** @var string */
    const LOCATION = 'Location';
    /** @var string */
    const MAX_FORWARDS = 'Max-Forwards';
    /** @var string */
    const PRAGMA = 'Pragma';
    /** @var string */
    const RANGE = 'Range';
    /** @var string */
    const REFERER = 'Referer';
    /** @var string */
    const RETRY_AFTER = 'Retry-After';
    /** @var string */
    const SERVER = 'Server';
    /** @var string */
    const UPGRADE = 'Upgrade';
    /** @var string */
    const USER_AGENT = 'User-Agent';
    /** @var string */
    const VARY = 'Vary';
    /** @var string */
    const VIA = 'Via';
    /** @var string */
    const WWW_AUTHENTICATE = 'WWW-Authenticate';

    /**
     * Cookie用Httpヘッダ　RFC 2109
     */

    /** @var string  */
    const COOKIE = 'Cookie';
    /** @var string  */
    const SET_COOKIE = 'Set-Cookie';

    /** @var array() */
    private $headers;

    /** @var string */
    const LINE_BREAK = "\n";
    /** @var string */
	const SEPARATOR = ': ';

    /**
     * @param array $headers
     */
    public function __construct($headers = array())
    {
        $this->headers = $headers;
    }

    /**
     * HttpHeader形式で文字列化
     * @return string
     */
    public function toString()
    {
        $headers = '';
        foreach ($this->headers as $header => $value) {
            $headers .= $header . self::SEPARATOR . $value . self::LINE_BREAK;
        }
        return $headers . self::LINE_BREAK;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @param $headers
     */
    public function addProperties($headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * keyはHttpHeaderのプロパティ名
     *
     * @param $key string
     * @param $value string
     */
    public function setProperty($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * @param string $key
     */
    public function removeProperty($key)
    {
        unset($this->headers[$key]);
    }

    /**
     *
     */
    public function clearProperties()
    {
        $this->headers = array();
    }

    /**
     * @param $key
     * @return string
     */
    public function getProperty($key)
    {
        if(array_key_exists($key, $this->headers)) {
            return $this->headers[$key];
        }
        return null;
    }
}