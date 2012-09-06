<?php

namespace Gcm;


class MulticastResult
{
    /** @var int final */
    private $success;
    /** @var int final */
    private $failure;
    /** @var int final */
    private $canonicalIds;
    /** @var int final */
    private $multicastId;
    /** @var \ArrayObject.<Result> final */
    private $results;
    /** @var \ArrayObject.<int> final */
    private $retryMulticastIds;

    public function __construct(MulticastResultBuilder $builder)
    {

        $this->success = $builder->getSuccess();
        $this->failure = $builder->getFailure();
        $this->canonicalIds = $builder->getCanonicalIds();
        $this->multicastId = $builder->getMulticastId();
        $this->results = $builder->getResults();

        $tmpList = $builder->getRetryMulticastIds();

        if ($tmpList == null) {
            $tmpList = new \ArrayObject();
        }
        $this->retryMulticastIds = $tmpList;
    }

    /**
     * @return int
     */
    public function getCanonicalIds()
    {
        return $this->canonicalIds;
    }

    /**
     * @return int
     */
    public function getFailure()
    {
        return $this->failure;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->success + $this->failure;
    }

    /**
     * @return int
     */
    public function getMulticastId()
    {
        return $this->multicastId;
    }

    /**
     * @return \ArrayObject
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @return \ArrayObject
     */
    public function getRetryMulticastIds()
    {
        return $this->retryMulticastIds;
    }

    /**
     * @return int
     */
    public function getSuccess()
    {
        return $this->success;
    }

    public function __toString()
    {
        $string = 'MulticastResult(';
        $string .= 'multicast_id=';
        $string .= $this->getMulticastId();
        $string .= ',';
        $string .= 'total=';
        $string .= $this->getTotal();
        $string .= ',';
        $string .= 'success=';
        $string .= $this->getSuccess();
        $string .= ',';
        $string .= 'failure=';
        $string .= $this->getFailure();
        $string .= ',';
        $string .= 'canonical_ids=';
        $string .= $this->getCanonicalIds();
        $string .= ',';

        if ($this->results->count() != 0) {
            $string .= 'results: ';
            $string .= $this->getResults();
        }

        return $string;
    }

}


