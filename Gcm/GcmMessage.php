<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/06/29
 * Time: 17:31
 * To change this template use File | Settings | File Templates.
 */
class GcmMessage
{
    /** @var string */
    private $collapseKey;
    /** @var boolean */
    private $delayWhileIdle;
    /** @var int */
    private $timeToLive;
    /** @var ArrayData */
    private $data;

    public function __construct(GcmMessageBuilder $builder)
    {
        $this->collapseKey = $builder->getCollapseKey();
        $this->delayWhileIdle = $builder->getDelayWhileIdle();
        $builder->getData()->setReadOnly(true);
        $this->data = $builder->getData();
        $this->timeToLive = $builder->getTimeToLive();
    }

    public function getData()
    {
        return $this->data;
    }

    public function getCollapseKey()
    {
        return $this->collapseKey;
    }

    public function getDelayWhileIdle()
    {
        return $this->delayWhileIdle;
    }

    public function getTimeToLive()
    {
        return $this->timeToLive;
    }

    public function __toString()
    {
        $string = 'Message(';

        if ($this->collapseKey != null) {
            $string .= 'collapseKey=';
            $string .= ', ';
            $string .= $this->collapseKey;
        }
        if ($this->timeToLive != null) {
            $string .= 'timeToLive=';
            $string .= $this->timeToLive;
            $string .= ', ';
        }
        if ($this->delayWhileIdle != null) {
            $string .= 'delayWhileIdle=';
            $string .= $this->delayWhileIdle;
            $string .= ', ';
        }

        if ($this->data->count() > 0) {

            $string .= 'data: {';
            foreach ($this->data as $key => $value) {
                $string .= $key;
                $string .= '=';
                $string .= $value;
                $string .= ',';
            }
            $string = mb_substr(0, mb_strlen($string) - 1, $string);
            $string .= '}';
        }

        if (mb_substr(mb_strlen($string) - 1, 1, $string) == ' ') {
            $string = mb_substr(0, mb_strlen($string) - 2, $string);
        }

        $string .= ')';

        return $string;
    }

}


