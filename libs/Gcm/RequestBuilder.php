<?php
/**
 * Created by JetBrains PhpStorm.
 * User: blue
 * Date: 2012/09/19
 * Time: 1:28
 * To change this template use File | Settings | File Templates.
 */

namespace Gcm;

class RequestBuilder
{
    /** @var $message Message*/
    private $message;

    /** @var $registrationIds array */
    private $registrationIds;

    /** @var $key string */
    private $key;

    /** @var string */
    const DEFAULT_CONTENT_TYPE = 'application/json';

    public function __construct() {

    }

    /**
     * @param string $key
     */
    public function setAuthorizationKey($key)
    {
        $this->key = $key;
    }

    /**
     * @param \Gcm\Message $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @param array $registrationIds
     */
    public function setRegistrationIds($registrationIds)
    {
        $this->registrationIds = $registrationIds;
    }


    /**
     * @return \Net\Http\Request
     * @throws \InvalidArgumentException
     */
    public function build()
    {

        $count = count(Util::nonNull($this->registrationIds));

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
        $payload = $this->createPayload($this->message, $this->registrationIds);
        $request->setPayload($payload);

        return $request;
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

}
