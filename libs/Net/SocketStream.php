<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/10
 * Time: 22:31
 * To change this template use File | Settings | File Templates.
 */
namespace Net;

class SocketStream
{
    /** @var string */
    const PROTOCOL_SSL = 'ssl';

    /** @var string */
    const PROTOCOL_TLS = 'tls';

    /** @var resource */
    private $socket;

    /** @var \Log\Logger */
    private $logger;

    /** @var int */
    private $timeout = 10;

    /** @var string */
    private $host;

    /** @var int */
    private $port;

    /** @var string */
    private $protocol;

    /** @var int */
    private $retryInterval = 100;

    /** @var int */
    private $maxRetryTimes = 0;

    /** @var \CertificationManager */
    private $certification;
	
	
	/** @var boolean **/
	private $blockMode = true;

    /**
     * @param string $host
     * @param int $port
     * @param string $protocol
     */
    public function __construct($host, $port, $protocol = 'tcp') {
        $this->protocol = $protocol;
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @return string
     */
    private function getRemoteSocket() {
        return $this->protocol . '://' . $this->host . ':' . strval($this->port);
    }

    public function write($message) {
        if ($this->isConnected()) {
            $this->log('[SocketStream] Socket write' . $message);
            return fwrite($this->socket, $message, strlen($message));
        }
        return false;
    }

    /**
     * @param int $timeout
     * @return int
     */
    public function select($timeout = 1000000) {
        $read   = array($this->socket);
        $NULL = NULL;
        return stream_select($read, $NULL, $NULL, 0, $timeout);
    }

    /**
     * @param $maxLength
     * @param int $offset
     * @return NULL|string
     */
    public function read($maxLength = -1, $offset = -1) {
        if ($this->isConnected() && !feof($this->socket)) {
            return stream_get_contents($this->socket, $maxLength, $offset);
        }
        return NULL;
    }

    /**
     * @return NULL|string
     */
    public function readLine() {
        if ($this->isConnected() && !feof($this->socket)) {
            return fgets($this->socket);
        }
        return NULL;
    }

    /**
     * @param int $time
     */
    public function setTimeout($time) {
        $this->timeout = (int) $time;
    }

    public function setLogger(\Log\Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * @param string $message
     */
    private function log($message) {
        if($this->logger != NULL) {
            $this->logger->log($message);
        }
    }

    /**
     * @param string $wrapper
     * @param string $option
     * @param mixed $value
     * @return bool
     */
    private function setContextPotion($wrapper , $option , $value) {
        if($this->isConnected()) {
            return stream_context_set_option ( $this->socket , $wrapper , $option , $value );
        }
        return false;
    }

    /**
     * @param bool $blockMode
     * @throws StreamException
     */
    public function open($blockMode = true) {
        $connected = false;
        $retry = 0;
        while(!$connected) {
            try {
				$this->blockMode = $blockMode;
                $connected = $this->connect();
            }
            catch (\Exception $e) {
                if($retry >= $this->maxRetryTimes) {
                    throw new StreamException('Connect Error :' . $e->getMessage(), $e->getCode(), $e);
                }
                else {
                    $this->log('Error' . $e->getMessage());
                    usleep($this->retryInterval);
                }
            }
            $retry++;
        }
    }

    /**
     * @return bool
     */
    private function isSecureProtocol() {
        return $this->protocol === self::PROTOCOL_SSL
            || $this->protocol === self::PROTOCOL_TLS;
    }

    /**
     * @param array $options
     * @param array $params
     * @return resource
     */
    private function createContext(array $options=array(), $params=array()) {

        if($this->certification != NULL && $this->isSecureProtocol()) {

            $options['ssl'] = array(
                'verify_peer' => ( strlen($this->certification->getRootCertAuthFile()) > 0 ) ,
                'cafile' => $this->certification->getRootCertAuthFile(),
                'local_cert' => $this->certification->getLocalCertAuthFile(),
            );

            if( strlen($this->certification->getLocalCertPassPhrase()) > 0 ) {
                $options['ssl']['passphrase'] = $this->certification->getLocalCertPassPhrase();
            }
        }

        return stream_context_create($options, $params);
    }

    public function isConnected() {
        return is_resource($this->socket);
    }

	public function setBlockMode($value=true)
	{
		if($this->isConnected()) {
			if($value) {
				stream_set_blocking($this->socket, 1);
			}
			else {
				stream_set_blocking($this->socket, 0);
			}
		}
		
		$this->blockMode = $value ? true : false;
	}

    /**
     * @return bool
     * @throws StreamException
     */
    private function connect() {

        if($this->isConnected()) {
            $this->log('[SocketStream] SocketStream is already connected.');
            return true;
        }

        $context = $this->createContext();

        $this->socket = stream_socket_client($this->getRemoteSocket(), $errorCode, $errorMessage,
            $this->timeout, STREAM_CLIENT_CONNECT, $context);

        if (!$this->isConnected()) {
            throw new StreamException(
                'Unable to connect to ' . $this->getRemoteSocket() . ': '. $errorMessage,
                (int) $errorCode
            );
        }
		
        $this->setBlockMode($this->blockMode);
		
        stream_set_write_buffer($this->socket, 0);

        $this->log('[SocketStream] SocketStream is connected to ' . $this->getRemoteSocket());

        return true;
    }

    public function close() {
        if ($this->isConnected()) {
            $this->log('[SocketStream] Close connection.');
            fclose($this->socket);
        }
        $this->socket = NULL;
    }

    /**
     * @param int $retryInterval
     */
    public function setRetryInterval($retryInterval)
    {
        $this->retryInterval = $retryInterval;
    }

    /**
     * @param int $maxRetryTimes
     */
    public function setMaxRetryTimes($maxRetryTimes)
    {
        $this->maxRetryTimes = $maxRetryTimes;
    }

    /**
     * @param \CertificationManager $certification
     */
    public function setCertification(\CertificationManager $certification)
    {
        $this->certification = $certification;
    }

    public function __destruct() {
        $this->close();
    }
}
