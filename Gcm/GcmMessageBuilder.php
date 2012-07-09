<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/06
 * Time: 16:34
 * To change this template use File | Settings | File Templates.
 */
class GcmMessageBuilder
{

    /** @var ArrayData */
    private $data;

    /** @var string */
    private $collapseKey;

    /** @var boolean */
    private $delayWhileIdle;

    /** @var int */
    private $timeToLive;

    /**
     * GcmMessageBuilder
     */
    public function __construct()
    {
        $this->data = new ArrayData('string');
    }

    /**
     * @param string $value
     * @return GcmMessageBuilder
     */
    public function  setCollapseKey($value)
    {
        $this->collapseKey = $value;
        return $this;
    }

    public function getCollapseKey()
    {
        return $this->collapseKey;
    }

    /**
     * Sets the delayWhileIdle property (default value is {@literal false}).
     * @param int $value
     * @return GcmMessageBuilder
     */
    public function setDelayWhileIdle($value)
    {
        $this->delayWhileIdle = $value;
        return $this;
    }

    public function getDelayWhileIdle()
    {
        return $this->delayWhileIdle;
    }

    /**
     * Sets the time to live, in seconds.
     * @param int $value
     * @return GcmMessageBuilder
     */
    public function setTimeToLive($value)
    {
        $this->timeToLive = $value;
        return $this;
    }

    public function getTimeToLive()
    {
        return $this->timeToLive;
    }

    /**
     * Adds a key/value pair to the payload data.
     * @param string $key
     * @param string $value
     * @return GcmMessageBuilder
     */
    public function addData($key, $value)
    {
        $this->data->offsetSet($key, $value);
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function build()
    {
        return new GcmMessage($this);
    }

}
