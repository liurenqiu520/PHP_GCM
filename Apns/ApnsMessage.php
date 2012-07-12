<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/10
 * Time: 19:51
 * To change this template use File | Settings | File Templates.
 */
class ApnsMessage
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
     * @param ApnsMessageBuilder $builder
     */
    public function __construct(ApnsMessageBuilder $builder) {
        $this->alert = $builder->getAlert();
        $this->sound = $builder->getSound();
        $this->badge = $builder->getBadge();
        $this->data  = $builder->getData();
    }

    /**
     * @return ArrayObject
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getAlert()
    {
        return $this->alert;
    }

    /**
     * @return string
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

    public function toArray() {

    }
}
