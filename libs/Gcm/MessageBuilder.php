<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/06
 * Time: 16:34
 * To change this template use File | Settings | File Templates.
 */
namespace Gcm;

class MessageBuilder
{

    /** @var \ArrayObject */
    private $data;

    /** @var string */
    private $collapseKey;

    /** @var boolean */
    private $delayWhileIdle;

    /** @var int */
    private $timeToLive = 3600;

    /**
     * GcmMessageBuilder
     */
    public function __construct()
    {
        $this->data = new \ArrayObject();
    }

    /**
     * @param string $value
     * @return MessageBuilder
     */
    public function  setCollapseKey($value)
    {
        $this->collapseKey = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getCollapseKey()
    {
        return $this->collapseKey;
    }

    /**
     * Sets the delayWhileIdle property (default value is {@literal false}).
     * @param int $value
     * @return MessageBuilder
     */
    public function setDelayWhileIdle($value)
    {
        $this->delayWhileIdle = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function getDelayWhileIdle()
    {
        return $this->delayWhileIdle;
    }

    /**
     * Sets the time to live, in seconds.
     * @param int $value
     * @return MessageBuilder
     */
    public function setTimeToLive($value)
    {
        $this->timeToLive = $value;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeToLive()
    {
        return $this->timeToLive;
    }

    /**
     * Adds a key/value pair to the payload data.
     * @param string $key
     * @param string $value
     * @return MessageBuilder
     */
    public function addData($key, $value)
    {
        $this->data->offsetSet($key, $value);
        return $this;
    }

    /**
     * @return \ArrayObject
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return Message
     */
    public function build()
    {
        return new Message($this);
    }

}
