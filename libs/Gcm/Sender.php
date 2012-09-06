<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/06/29
 * Time: 19:51
 * To change this template use File | Settings | File Templates.
 *
 */

namespace Gcm;

class Sender
{
    /** @var string */

    const UTF8 = "UTF-8";

    /**
     * Initial delay before first retry, without jitter.
     * @var int
     */
    const BACKOFF_INITIAL_DELAY = 1000;

    /** @var string */
    const DEFAULT_CONTENT_TYPE = 'application/x-www-form-urlencoded;charset=UTF-8';

    /**
     * Maximum delay before a retry.
     * @var int
     */
    const MAX_BACKOFF_DELAY = 1024000;


    /** @var \Log\Logger */
    private $logger;

    /** @var string */
    private $key;

    /**
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = self::nonNull($key);
    }

    /**
     * @param string $logfile
     */
    public function setLogger($logfile)
    {
        $this->logger = \Log\Logger::getLogger($logfile);
    }

    /**
     * @param string $message
     */
    public function log($message)
    {
        if ($this->logger != null) {
            $this->logger->log($message);
        }
    }

    /**
     * Sends a message to one device, retrying in case of unavailability.
     *
     * <p>
     * <strong>Note: </strong> this method uses exponential back-off to retry in
     * case of service unavailability and hence could block the calling thread
     * for many seconds.
     *
     * @param Message $message to be sent, including the device's registration id.
     * @param string $registrationId device where the message will be sent.
     * @param int $retries number of retries in case of service unavailability errors.
     *
     * @return Result result of the request (see its javadoc for more details)
     *
     * @throws \Exception if registrationId is {@literal null}.
     */
    public function send(Message $message, $registrationId, $retries)
    {

        /** @var $attempt int */
        $attempt = 0;
        /** @var $result Result */
        $result = null;

        $backOff = self::BACKOFF_INITIAL_DELAY;

        do {

            $attempt++;

            $result = $this->sendNoRetry($message, $registrationId);

            $tryAgain = ($result == null && $attempt <= $retries);

            if ($tryAgain) {
                $sleepTime = $backOff / 2 + mt_rand(0, $backOff);
                usleep($sleepTime);
                if (2 * $backOff < self::MAX_BACKOFF_DELAY) {
                    $backOff *= 2;
                }
            }
        } while ($tryAgain);

        if ($result === null) {
            throw new \Exception("Could not send message after " . $attempt . " attempts");
        }

        return $result;
    }

    /**
     * Sends a message without retrying in case of service unavailability
     *
     * @param Message $message
     * @param $registrationId
     * @return Result
     * @throws \Net\Http\InvalidRequestException
     * @throws \Exception
     */
    public function sendNoRetry(Message $message, $registrationId)
    {

        /** @var \ArrayObject */
        $body = $this->newPostBody(Constants::PARAM_REGISTRATION_ID, $registrationId);

        $payload = http_build_query($this->buildPostBody($body, $message)->getArrayCopy(), '', '&');

        /** @var $response \Net\Http\Response */
        $response = $this->post(Constants::GCM_SEND_ENDPOINT, $payload);

        $status = $response->getResponseCode();

        if ($status == 503) {
            $this->log("GCM service is unavailable");
            return null;
        }

        if ($status != 200) {
            throw new \Net\Http\InvalidRequestException($status);
        }

        return $this->parseResponseBody($response->getResponseBody());
    }

    /**
     * @param Message $message
     * @param array $registrationIds
     * @param $retries
     * @return MulticastResult|null
     * @throws \Exception
     */
    public function sendMulti(Message $message, array $registrationIds, $retries)
    {

        /** @var $attempt int */
        $attempt = 0;

        /** @var $multicastResult MulticastResult */
        $multicastResult = null;

        $backOff = self::BACKOFF_INITIAL_DELAY;

        /** @var $results \ArrayObject.<Result> */
        $results = new \ArrayObject();

        /** @var $results \ArrayObject.<string> */
        $unsentRegIds = new \ArrayObject($registrationIds);

        /** @var $results \ArrayObject.<int> */
        $multiCastIds = new \ArrayObject();

        do {
            $attempt++;

            $multiCastResult = $this->sendNoRetryMulti($message, $unsentRegIds);
            $multiCastId = $multiCastResult->getMulticastId();
            $multiCastIds->append($multiCastId);
            $unsentRegIds = $this->updateStatus($unsentRegIds, $results, $multiCastResult);
            $tryAgain = ($unsentRegIds->count() !== 0 && $attempt <= $retries);

            if ($tryAgain) {
                $sleepTime = $backOff / 2 + mt_rand(0, $backOff);
                usleep($sleepTime);
                if (2 * $backOff < self::MAX_BACKOFF_DELAY) {
                    $backOff *= 2;
                }
            }
        } while ($tryAgain);

        // calculate summary
        if ($multiCastResult === null) {
            throw new \Exception("Could not send message after " . $attempt . " attempts");
        }

        $success = 0;
        $failure = 0;
        $canonicalIds = 0;

        foreach ($results as $result) {
            /* @var $result Result */
            if ($result->getMessageId() != null) {
                $success++;
                if ($result->getCanonicalRegistrationId() != null) {
                    $canonicalIds++;
                }
            } else {
                $failure++;
            }
        }
        // build a new object with the overall result
        $multiCastId = $multiCastIds->offsetGet(0);
        $multiCastIds->offsetUnset(0);

        $builder = new MulticastResultBuilder($success, $failure, $canonicalIds, $multiCastId);
        $builder->retryMulticastIds($multiCastIds);

        // add results, in the same order as the input
        foreach ($registrationIds as $regId) {
            $result = $results->offsetGet($regId);
            $builder->addResult($result);
        }

        return $multiCastResult;
    }

    /**
     * @param Message $message
     * @param \ArrayObject $registrationIds
     * @return MulticastResult
     * @throws \Net\Http\InvalidRequestException
     * @throws \InvalidArgumentException
     */
    public function sendNoRetryMulti(Message $message, \ArrayObject $registrationIds)
    {

        /** @var $r \ArrayObject */
        $r = $this->nonNull($registrationIds);
        if ($r->count() === 0) {
            throw new \InvalidArgumentException('registrationIds cannot be empty');
        }

        $jsonRequest = new \ArrayObject();
        $this->setJsonField($jsonRequest, Constants::PARAM_TIME_TO_LIVE, $message->getTimeToLive());
        $this->setJsonField($jsonRequest, Constants::PARAM_COLLAPSE_KEY, $message->getCollapseKey());
        $this->setJsonField($jsonRequest, Constants::PARAM_DELAY_WHILE_IDLE, $message->getDelayWhileIdle());
        $this->setJsonField($jsonRequest, Constants::PARAM_REGISTRATION_IDS, $registrationIds->getArrayCopy());

        /** @var $payloadBody \ArrayObject */
        $payloadBody = $message->getData();
        if ($payloadBody->count() > 0) {
            $jsonRequest->offsetSet(Constants::JSON_PAYLOAD, $payloadBody);
        }

        $payload = json_encode($jsonRequest);

        $this->log("Send JSON Payload (" . $payload . ")");

        /** @var \Net\Http\Response */
        $response = $this->post(Constants::GCM_SEND_ENDPOINT, $payload, 'application/json');

        $status = $response->getResponseCode();

        if ($status == 503) {
            $this->log("GCM service is unavailable");
            return null;
        }

        if ($status != 200) {
            throw new \Net\Http\InvalidRequestException($status);
        }

        $this->log("Success Send Message (" . $response . ")");

        return $this->parseResponseBodyMulti($response->getResponseBody());
    }

    /**
     * @param \ArrayObject $unsentRegIds
     * @param \ArrayObject $allResults
     * @param MulticastResult $multiCastResult
     * @throws \RuntimeException
     * @return \ArrayObject
     */
    private function updateStatus(\ArrayObject $unsentRegIds, \ArrayObject $allResults, MulticastResult $multiCastResult)
    {

        $results = $multiCastResult->getResults();

        if ($results->count() !== $unsentRegIds->count()) {
            throw new \RuntimeException('Internal error: sizes do not match. ' .
                'currentResults: ' . $results . '; unsentRegIds: ' . $unsentRegIds);
        }

        $newUnsentRegIds = new \ArrayObject();

        foreach ($unsentRegIds as $key => $regId) {
            /** @var $result Result */
            $result = $results->offsetGet($key);
            $allResults->offsetSet($regId, $result);
            $error = $result->getErrorCode();
            if ($error != null && $error == Constants::ERROR_UNAVAILABLE) {
                $newUnsentRegIds->append($regId);
            }
        }

        return $newUnsentRegIds;
    }

    /**
     * @param \ArrayObject $jsonRequest
     * @param $key
     * @param $value
     */
    private function setJsonField(\ArrayObject $jsonRequest, $key, $value)
    {
        if ($value != null) {
            $jsonRequest->offsetSet($key, $value);
        }
    }

    /**
     * @param string $responseBody
     * @return MulticastResult
     * @throws \Exception
     */
    private function parseResponseBodyMulti($responseBody)
    {
        try {

            $jsonResponse = json_decode($responseBody, true);

            $this->log("Get Response (" . $jsonResponse . ")");

            $success = (int)$this->getNumber($jsonResponse, Constants::JSON_SUCCESS);
            $failure = (int)$this->getNumber($jsonResponse, Constants::JSON_FAILURE);
            $canonicalIds = (int)$this->getNumber($jsonResponse, Constants::JSON_CANONICAL_IDS);
            $multicastId = (int)$this->getNumber($jsonResponse, Constants::JSON_MULTICAST_ID);

            /** @var $builder MulticastResultBuilder */
            $builder = new MulticastResultBuilder($success, $failure, $canonicalIds, $multicastId);
            $results = $jsonResponse[Constants::JSON_RESULTS];

            if ($results != null) {

                foreach ($results as $jsonResult) {

                    $resultBuilder = new ResultBuilder();

                    if(array_key_exists(Constants::JSON_MESSAGE_ID, $jsonResult))
                        $resultBuilder->messageId($jsonResult[Constants::JSON_MESSAGE_ID]);

                    if(array_key_exists(Constants::TOKEN_CANONICAL_REG_ID, $jsonResult))
                        $resultBuilder->canonicalRegistrationId($jsonResult[Constants::TOKEN_CANONICAL_REG_ID]);

                    if(array_key_exists(Constants::JSON_ERROR, $jsonResult))
                        $resultBuilder->errorCode($jsonResult[Constants::JSON_ERROR]);

                    $builder->addResult($resultBuilder->build());

                }

            }


            return $builder->build();

        } catch (\Exception $e) {

            $this->log('json parse error:' . json_last_error());
            throw new \Exception('json parse error:' . $e->getMessage(), json_last_error());

        }
    }

    /**
     * @param array $json
     * @param $field
     * @return int|float
     * @throws \Exception
     */
    private function getNumber(array $json, $field)
    {
        $value = $json[$field];
        if ($value === null) {
            throw new \Exception('Missing field: ' . $field);
        }

        if (!is_numeric($value)) {
            throw new \Exception('Field ' . $field .
                ' does not contain a number: ' . $value);
        }
        return $value;
    }

    /**
     * @param string $responseBody
     * @return Result
     * @throws \Exception
     */
    private function parseResponseBody($responseBody)
    {

        $this->log("ResponseBody (" . $responseBody . ")");

        $lines = explode('\n', $responseBody);
        $index = 0;

        if (empty($lines[$index])) {
            throw new \Exception("Received empty response from GCM service.");
        }

        $line = $lines[$index];
        list($token, $value) = $this->split($line);

        if ($token == Constants::TOKEN_MESSAGE_ID) {

            $builder = new ResultBuilder();
            $builder->messageId($value);

            $index++;
            if (isset($lines[$index])) {
                $line = $lines[$index];
                list($token, $value) = $this->split($line);
                if ($token == Constants::TOKEN_CANONICAL_REG_ID) {
                    $builder->canonicalRegistrationId($value);
                } else {
                    $this->log("Received invalid second line from GCM: "
                        + $line);
                }
            }

            $result = $builder->build();

            $this->log("Message created succesfully (" . $result . ")");

            return $result;
        } else if ($token == Constants::TOKEN_ERROR) {

            $builder = new ResultBuilder();

            return $builder->errorCode($value)->build();
        } else {
            throw new \Exception("Received invalid response from GCM: " . $line);
        }
    }

    /**
     * @param string $line
     * @return array
     * @throws \Exception
     */
    private function split($line)
    {
        $split = explode('=', $line, 2);
        if (count($split) != 2) {
            throw new \Exception("Received invalid response line from GCM: "
                , $line);
        }
        return $split;
    }

    /**
     * @param string $url
     * @param string $body
     * @param string $contentType
     * @throws \InvalidArgumentException
     * @return \Net\Http\Response
     */
    private function post($url, $body, $contentType = '')
    {

        if ($url == null || $body == null) {
            throw new \InvalidArgumentException('arguments cannot be null');
        }

        if (strpos($url, 'https://') != 0) {
            $this->log('URL does not use https: ' . $url);
        }

        $this->log('Sending POST to ' . $url);

        $urlInfo = parse_url($url);

        /* @var $conn \Net\Http\Connection */
        $conn = $this->getConnection($urlInfo['host']);
        $conn->setTimeout(10);
        $option = array(
            \Net\Http\Header::CONTENT_TYPE => self::DEFAULT_CONTENT_TYPE,
            \Net\Http\Header::AUTHORIZATION => 'key=' . $this->key
        );

        if ($contentType !== '') {
            $option[\Net\Http\Header::CONTENT_TYPE] = $contentType;
        }

        return $conn->post($urlInfo['path'], $body, $option);
    }

    /**
     * Gets an {@link HttpURLConnection} given an URL.
     * @param string $host;
     * @return \Net\Http\Connection
     */
    private function getConnection($host)
    {
        $conn = new \Net\Http\Connection($host, 443, true);
        return $conn;
    }

    /**
     * @param \ArrayObject $params
     * @param string $name
     * @param string $value
     */
    private function addPostParameter(\ArrayObject $params, $name, $value)
    {
        $params->offsetSet(self::nonNull($name), self::nonNull($value));
    }

    /**
     * @param string $name
     * @param string $value
     * @return \ArrayObject
     */
    private function newPostBody($name, $value)
    {
        $body = new \ArrayObject();
        $body->offsetSet(self::nonNull($name), self::nonNull($value));
        return $body;
    }

    /**
     * @param \ArrayObject $body
     * @param Message $message
     * @return \ArrayObject
     */
    private function buildPostBody(\ArrayObject $body, Message $message)
    {

        /** @var boolean */
        $delayWhileIdle = $message->getDelayWhileIdle();

        if ($delayWhileIdle != null) {
            $this->addPostParameter($body, Constants::PARAM_DELAY_WHILE_IDLE, ($delayWhileIdle ? "1" : "0"));
        }

        /** @var string */
        $collapseKey = $message->getCollapseKey();

        if ($collapseKey != null) {
            $this->addPostParameter($body, Constants::PARAM_COLLAPSE_KEY, $collapseKey);
        }

        $timeToLive = $message->getTimeToLive();

        if ($timeToLive != null) {
            $this->addPostParameter($body, Constants::PARAM_TIME_TO_LIVE, strval($timeToLive));
        }

        $messageData = $message->getData();

        foreach ($messageData as $key => $value) {
            $this->addPostParameter($body, Constants::PARAM_PAYLOAD_PREFIX . $key, urlencode($value));
        }

        return $body;
    }

    /**
     * @static
     * @param $argument
     * @return mixed
     * @throws \InvalidArgumentException
     */
    private static function nonNull($argument)
    {
        if ($argument === null) {
            throw new \InvalidArgumentException("argument cannot be null");
        }
        return $argument;
    }

}
