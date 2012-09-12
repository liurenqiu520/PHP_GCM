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


    /** @var $logger \Log\Logger */
    private $logger;

    /** @var $key string */
    private $key;

    /** @var $connection \Net\Http\Connection */
    private $connection;

    /**
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = self::nonNull($key);
    }

    /**
     * @param \Log\Logger $logger
     */
    public function setLogger(\Log\Logger $logger)
    {
        $this->logger = $logger;
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
     * @param Message $message
     * @param mixed $registrationId
     * @param int $retries
     * @return MulticastResult|null
     */
	public function send(Message $message, $registrationId, $retries) {
		
		$registrationIds = is_array($registrationId) ? $registrationId : array($registrationId);
		
		return $this->_send($message, $registrationIds, $retries);
		
	}

    /**
     * @param Message $message
     * @param array $registrationIds
     * @param $retries
     * @return MulticastResult|null
     * @throws \Exception
     */
    private function _send(Message $message, array $registrationIds, $retries)
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
    private function sendNoRetryMulti(Message $message, \ArrayObject $registrationIds)
    {
        /** @var $r \ArrayObject */
        $r = $this->nonNull($registrationIds);

        //送信先は1000件までしか送れない
        if ($r->count() === 0 || $r->count() > Constants::MAX_TARGET_DEVICE_COUNT) {
            throw new \InvalidArgumentException('registrationIds cannot be empty '
				. 'and cannot be over 1000 count.');
        }
		
        $payload = $this->createJsonRequest($message, $registrationIds) ;

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

        return $this->parseResponseBody($response->getResponseBody());
    }

    /**
     * @param \ArrayObject $unsentRegIds
     * @param \ArrayObject $allResults
     * @param MulticastResult $multiCastResult
     * @throws \RuntimeException
     * @return \ArrayObject
     */
    private function updateStatus(\ArrayObject $unsentRegIds, 
		\ArrayObject $allResults, MulticastResult $multiCastResult)
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
	 * 
	 *
	 */
	private function createJsonRequest(Message $message, \ArrayObject $registrationIds) {
		
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
		
		return json_encode($jsonRequest);
	}
	
    /**
     * @param string $responseBody
     * @return MulticastResult
     * @throws \Exception
     */
    private function parseResponseBody($responseBody)
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

                    if (array_key_exists(Constants::JSON_MESSAGE_ID, $jsonResult))
                        $resultBuilder->messageId($jsonResult[Constants::JSON_MESSAGE_ID]);

                    if (array_key_exists(Constants::TOKEN_CANONICAL_REG_ID, $jsonResult))
                        $resultBuilder->canonicalRegistrationId($jsonResult[Constants::TOKEN_CANONICAL_REG_ID]);

                    if (array_key_exists(Constants::JSON_ERROR, $jsonResult))
                        $resultBuilder->errorCode($jsonResult[Constants::JSON_ERROR]);

                    $builder->addResult($resultBuilder->build());

                }

            }


            return $builder->build();

        } catch (\Exception $e) {

            $jsonError = json_last_error();

            if($jsonError !== 0) {
                $this->log('json parse error:' . $jsonError);
                throw new \Exception('json parse error:' . $e->getMessage(), $jsonError);
            }
            else {
                throw $e;
            }

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
        if($this->connection == null) {
            $this->connection = new \Net\Http\Connection($host, 443, true);
        }
        return $this->connection;
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
