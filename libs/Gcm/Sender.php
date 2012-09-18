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

/**
 * TODO Requestの生成・送信・結果の制御を分離して送信内容によって分岐
 * ※送信時の各登録IDに対する結果は別で裁く必要がある
 */
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
    const DEFAULT_CONTENT_TYPE = 'application/json';

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
        $this->key = Util::nonNull($key);
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
    public function send(Message $message, $registrationId, $retries)
    {
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

        do {
            $attempt++;

            $multiCastResult = $this->sendNoRetry($message, $registrationIds);

            $tryAgain = ($multiCastResult === null && $attempt <= $retries);

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

        return $multiCastResult;
    }

    /**
     * @param Message $message
     * @param array $registrationIds
     * @return MulticastResult
     * @throws \Net\Http\InvalidRequestException
     * @throws \InvalidArgumentException
     */
    private function sendNoRetry(Message $message, array $registrationIds)
    {
        try {

            /** @var $response \Net\Http\Response */
            $response = $this->post($this->createRequest($message, $registrationIds));

            $status = $response->getResponseCode();

            if ($status == 503) {
                $this->log("GCM service is unavailable");
            }

            if ($status != 200) {
                throw new \Net\Http\InvalidRequestException('Response status is not 200 ', $status);
            }

        }
        catch (\Exception $e) {

            $this->log("Catch Exception " . $e->getMessage() . '[' . $e->getCode() . ']');

            return null;
        }

        $this->log("Success Send Message . Result(" . $response . ")");

        return $this->parseResponse($response);
    }


    /**
     * JSONPayloadの生成
     *
     * @param Message $message
     * @param array $registrationIds
     * @return string
     */
    private function createPayload(Message $message, array $registrationIds)
    {

        $jsonRequest = new \ArrayObject();
        Util::setJsonField($jsonRequest, Constants::PARAM_TIME_TO_LIVE, $message->getTimeToLive());
        Util::setJsonField($jsonRequest, Constants::PARAM_COLLAPSE_KEY, $message->getCollapseKey());
        Util::setJsonField($jsonRequest, Constants::PARAM_DELAY_WHILE_IDLE, $message->getDelayWhileIdle());
        Util::setJsonField($jsonRequest, Constants::PARAM_REGISTRATION_IDS, $registrationIds);

        /** @var $payloadBody \ArrayObject */
        $payloadBody = $message->getData();
        if ($payloadBody->count() > 0) {
            $jsonRequest->offsetSet(Constants::JSON_PAYLOAD, $payloadBody);
        }

        return json_encode($jsonRequest);
    }

    /**
     * 結果のJSONを元にMulticastResultの生成
     *
     * @param \Net\Http\Response $response
     * @return MulticastResult
     * @throws \Exception
     */
    public function parseResponse(\Net\Http\Response $response)
    {
        try {

            $jsonResponse = json_decode($response->getResponseBody(), true);

            $success = (int)Util::getNumber($jsonResponse, Constants::JSON_SUCCESS);
            $failure = (int)Util::getNumber($jsonResponse, Constants::JSON_FAILURE);
            $canonicalIds = (int)Util::getNumber($jsonResponse, Constants::JSON_CANONICAL_IDS);
            $multicastId = (int)Util::getNumber($jsonResponse, Constants::JSON_MULTICAST_ID);

            /** @var $builder MulticastResultBuilder */
            $builder = new MulticastResultBuilder($success, $failure, $canonicalIds, $multicastId);

            $results = $jsonResponse[Constants::JSON_RESULTS];

            if ($results != null) {
                foreach ($results as $jsonResult) {
                    $builder->addResult($this->buildResult($jsonResult));
                }
            }

            return $builder->build();

        } catch (\Exception $e) {

            $jsonError = json_last_error();

            if ($jsonError !== 0) {
                throw new \Exception('json parse error:' . $e->getMessage(), $jsonError);
            } else {
                throw $e;
            }

        }
    }

    /**
     *
     * @param array $jsonResult
     * @return Result
     */
    private function buildResult(array $jsonResult)
    {
        $resultBuilder = new ResultBuilder();

        if (array_key_exists(Constants::JSON_MESSAGE_ID, $jsonResult))
            $resultBuilder->messageId($jsonResult[Constants::JSON_MESSAGE_ID]);

        if (array_key_exists(Constants::TOKEN_CANONICAL_REG_ID, $jsonResult))
            $resultBuilder->canonicalRegistrationId($jsonResult[Constants::TOKEN_CANONICAL_REG_ID]);

        if (array_key_exists(Constants::JSON_ERROR, $jsonResult))
            $resultBuilder->errorCode($jsonResult[Constants::JSON_ERROR]);

        return $resultBuilder->build();
    }

    /**
     * @param Message $message
     * @param array $registrationIds
     * @return \Net\Http\Request
     * @throws \InvalidArgumentException
     */
    public function createRequest(Message $message, array $registrationIds)
    {

        $count = count(Util::nonNull($registrationIds));

        //送信先は1000件までしか送れない
        if ($count === 0 || $count > Constants::MAX_TARGET_DEVICE_COUNT) {
            throw new \InvalidArgumentException('registrationIds cannot be empty '
                . 'and cannot be over 1000 count.');
        }

        $request = \Net\Http\Method::create(\Net\Http\Method::POST, Constants::GCM_SEND_ENDPOINT);

        //ヘッダの追加
        $request->addProperty(\Net\Http\Header::CONTENT_TYPE, self::DEFAULT_CONTENT_TYPE);
        $request->addProperty(\Net\Http\Header::AUTHORIZATION, 'key=' . $this->key);

        //Requestbody
        $payload = $this->createPayload($message, $registrationIds);
        $request->setPayload($payload);

        return $request;
    }

    /**
     * POST送信
     *
     * @param \Net\Http\Request $request
     * @return \Net\Http\Response
     */
    private function post(\Net\Http\Request $request)
    {
        $this->log('Sending POST :' . $request->toString());

        /* @var $conn \Net\Http\Connection */
        $conn = $this->getConnection($request->getHost());

        return $conn->send($request);
    }

    /**
     * Connectionの取得
     *
     * @param string $host;
     * @return \Net\Http\Connection
     */
    private function getConnection($host)
    {
        if ($this->connection == null) {
            $this->connection = new \Net\Http\Connection($host, 443, true);
            $this->connection->setTimeout(10);
        }
        return $this->connection;
    }


}
