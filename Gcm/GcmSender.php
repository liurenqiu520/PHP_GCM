<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/06/29
 * Time: 19:51
 * To change this template use File | Settings | File Templates.
 *
 * TODO Multicast送信実装
 */
class GcmSender
{
    /** @var string */
    const UTF8 = "UTF-8";

    /**
     * Initial delay before first retry, without jitter.
     * @var int
     */
    const BACKOFF_INITIAL_DELAY = 1000;

    const DEFAULT_CONTENT_TYPE = 'application/x-www-form-urlencoded;charset=UTF-8';

    /**
     * Maximum delay before a retry.
     * @var int
     */
    const MAX_BACKOFF_DELAY = 1024000;

    /** @var Logger */
    protected $logger;

    /** @var string */
    private $key;

    /**
     * @param string $key
     */
    public function __construct($key)
    {
        $this->logger = Logger::getLogger(GcmConstants::LOG_FILE);
        $this->key = self::nonNull($key);
    }

    /**
     * Sends a message to one device, retrying in case of unavailability.
     *
     * <p>
     * <strong>Note: </strong> this method uses exponential back-off to retry in
     * case of service unavailability and hence could block the calling thread
     * for many seconds.
     *
     * @param GcmMessage $message to be sent, including the device's registration id.
     * @param string $registrationId device where the message will be sent.
     * @param int $retries number of retries in case of service unavailability errors.
     * @param bool $isLast
     *
     * @return GcmResult result of the request (see its javadoc for more details)
     *
     * @throws Exception if registrationId is {@literal null}.
     */
    public function send(GcmMessage $message, $registrationId, $retries, $isLast = true)
    {

        /** @var $attempt int */
        $attempt = 0;
        /** @var $result GcmResult */
        $result = null;

        $backOff = self::BACKOFF_INITIAL_DELAY;

        do {

            $attempt++;
            $result = $this->sendNoRetry($message, $registrationId, $isLast);
            
            $tryAgain = ($result == null && $attempt <= $retries);
            
            if ($tryAgain) {
                throw new Exception("!!!Could not send message after " . $attempt ." attempts");
                $sleepTime = $backOff / 2 + mt_rand(0, $backOff);
                usleep($sleepTime);
                if (2 * $backOff < self::MAX_BACKOFF_DELAY) {
                    $backOff *= 2;
                }
            }
            
        } while ($tryAgain);

        if ($result === null) {
            throw new Exception("Could not send message after " . $attempt ." attempts");
        }

        return $result;
    }

    /**
     * Sends a message without retrying in case of service unavailability
     *
     * @param GcmMessage $message
     * @param $registrationId
     * @param bool $isLast
     * @return GcmResult
     * @throws InvalidRequestException
     * @throws Exception
     */
    public function sendNoRetry(GcmMessage $message, $registrationId, $isLast = true) {

        /** @var array */
        $body = $this->newBody(GcmConstants::PARAM_REGISTRATION_ID, $registrationId);
        /** @var boolean */
        $delayWhileIdle = $message->getDelayWhileIdle();

        if ($delayWhileIdle != null) {
            $this->addParameter(&$body, GcmConstants::PARAM_DELAY_WHILE_IDLE, ($delayWhileIdle ? "1" : "0") );
        }
        
        /** @var string */
        $collapseKey = $message->getCollapseKey();

        if ($collapseKey != null) {
            $this->addParameter(&$body, GcmConstants::PARAM_COLLAPSE_KEY, $collapseKey);
        }

        $timeToLive = $message->getTimeToLive();

        if ($timeToLive != null) {
            $this->addParameter(&$body, GcmConstants::PARAM_TIME_TO_LIVE, strval($timeToLive));
        }
        
        $messageData = $message->getData();
        
        foreach($messageData as $key => $value) {
            $this->addParameter(&$body, GcmConstants::PARAM_PAYLOAD_PREFIX . $key, urlencode($value));
        }
        
        /** @var $conn HttpConnection */
        $conn = $this->post(GcmConstants::GCM_SEND_ENDPOINT, $body);

        if($isLast) {
            $conn->close();
        }
        $status = $conn->getResponseCode();

        if ($status == 503) {
            $this->logger->put("GCM service is unavailable");
            return null;
        }

        if ($status != 200) {
            throw new InvalidRequestException($status);
        }

        $responseBody = $conn->getResponseBody();
        
        $this->logger->put("ResponseBody (" . $responseBody . ")");
        
        $lines        = explode('\n', $responseBody);
        $index = 0;

        if (empty($lines[$index])) {
            throw new Exception("Received empty response from GCM service.");
        }
        
        $line = $lines[$index];
        $responseParts = $this->split($line);
        $token = $responseParts[0];
        $value = $responseParts[1];
        
        if ($token == GcmConstants::TOKEN_MESSAGE_ID) {
            
            $builder = new GcmResultBuilder();
            $builder->messageId($value);

            $index++;
            if (isset($lines[$index])) {
                $line = $lines[$index];
                $responseParts = $this->split($line);
                $token = $responseParts[0];
                $value = $responseParts[1];
                if ($token == GcmConstants::TOKEN_CANONICAL_REG_ID) {
                    $builder->canonicalRegistrationId($value);
                } else {
                    $this->logger->put("Received invalid second line from GCM: "
                        + $line);
                }
            }

            $result = $builder->build();
            
            $this->logger->put("Message created succesfully (" . $result . ")");

            return $result;

        } else if ($token == GcmConstants::TOKEN_ERROR) {
            
            $builder = new GcmResultBuilder();
            
            return $builder->errorCode($value)->build();
            
        } else {
            throw new Exception("Received invalid response from GCM: " . $line);
        }
        
    }



    private function split($line) {
        $split = explode('=', $line, 2);
        if (count($split) != 2) {
            throw new Exception("Received invalid response line from GCM: "
            , $line);
        }
        return $split;
    }
    /**
     * @param string $url
     * @param array $body
     * @param string $contentType
     * @throws InvalidArgumentException
     * @return HttpConnection
     */
    private function post($url, $body, $contentType = self::DEFAULT_CONTENT_TYPE) {

        if ($url == null || $body == null) {
            throw new InvalidArgumentException("arguments cannot be null");
        }

        if (strpos($url, 'https://') != 0) {
            $this->logger->put("URL does not use https: " . $url);
        }

        $this->logger->put("Sending POST to " . $url);

        $urlInfo = parse_url($url);
        
        $requestBody = http_build_query($body, '', '&');
        
        
        $this->logger->put("POST body: " . $requestBody);
        
        /* @var HttpConnection */
        $conn = $this->getConnection($urlInfo['host']);
        
        $conn->setTimeout(10);
        $conn->addRequestProperty("Content-Type", $contentType);
        $conn->addRequestProperty("Authorization", "key=" . $this->key);
        $conn->addRequestProperty("Content-Length", strval(strlen($requestBody)));
        $conn->addRequestProperty("Host", $urlInfo['host']);
        $conn->addRequestProperty('Connection', 'keep-alive');
        $conn->open();
        
        $requestBody .= "\r\n";
        $conn->post($urlInfo['path'], $requestBody);

        return $conn;
    }

    /**
     * Gets an {@link HttpURLConnection} given an URL.
     * @param string $host;
     * @return HttpConnection
     */
    protected function getConnection($host)  {
        $conn = new HttpConnection($host, 443, true);
        return $conn;
    }

    private function addParameter(&$params, $name, $value) {
        $params[self::nonNull($name)] = self::nonNull($value);
    }

    /**
     * @param string $name
     * @param string $value
     * @return array
     */
    private function newBody($name, $value) {
        $body = array();
        $body[self::nonNull($name)] = self::nonNull($value);
        return $body;
    }

    /**
     * @static
     * @param $argument
     * @return mixed
     * @throws InvalidArgumentException
     */
    private static function nonNull($argument)
    {
        if ($argument === null) {
            throw new InvalidArgumentException("argument cannot be null");
        }
        return $argument;
    }

}
