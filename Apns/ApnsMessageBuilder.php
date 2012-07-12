<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/10
 * Time: 19:53
 * To change this template use File | Settings | File Templates.
 */
class ApnsMessageBuilder
{
    /** @var string */
    private $alert;

    /** @var string */
    private $sound;

    /** @var int */
    private $badge;

    /** @var ArrayObject */
    private $data;

    /**
     *
     */
    public function __construct()
    {
        $this->data = new ArrayObject();
    }

    /**
     * @param string $key
     * @param string $value
     * @return GcmMessageBuilder
     */
    public function addData($key, $value)
    {
        $this->data->offsetSet($key, $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getAlert()
    {
        return $this->alert;
    }

    /**
     * @return int
     */
    public function getBadge()
    {
        return $this->badge;
    }

    /**
     * @return string
     */
    public function getSound()
    {
        return $this->sound;
    }

    /**
     * @param string $alert
     * @return ApnsMessageBuilder
     */
    public function alert($alert)
    {
        $this->alert = $alert;
        return $this;
    }

    /**
     * @param int $badge
     * @return ApnsMessageBuilder
     */
    public function badge($badge)
    {
        $this->badge = $badge;
        return $this;
    }

    /**
     * @param string $sound
     * @return ApnsMessageBuilder
     */
    public function sound($sound)
    {
        $this->sound = $sound;
        return $this;
    }

    /**
     * @return ApnsMessage
     */
    public function build()
    {
        return new ApnsMessage($this);
    }

    /**
     * @return \ArrayObject
     */
    public function getData()
    {
        return $this->data;
    }
}
