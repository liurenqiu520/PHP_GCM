<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/09/06
 * Time: 19:15
 * To change this template use File | Settings | File Templates.
 */
namespace Gcm;

class MulticastResultBuilder
{

    /** @var int final */
    private $success = 0;
    /** @var int final */
    private $failure = 0;
    /** @var int final */
    private $canonicalIds = 0;
    /** @var int (long) final */
    private $multicastId;

    /** @var \ArrayObject.<Result> final */
    private $results;

    /** @var \ArrayObject.<int> final */
    private $retryMulticastIds;

    /**
     * @param int $success
     * @param int $failure
     * @param int $canonicalIds
     * @param int $multicastId
     */
    public function __construct($success, $failure, $canonicalIds, $multicastId)
    {

        $this->results = new \ArrayObject();

        $this->success = intval($success);
        $this->failure = intval($failure);
        $this->canonicalIds = $canonicalIds;
        $this->multicastId = $multicastId;
    }

    /**
     * @param Result $result
     * @return MulticastResultBuilder
     */
    public function addResult($result)
    {
        $this->results->append($result);
        return $this;
    }

    /**
     * @param \ArrayObject $retryMulticastIds
     * @return MulticastResultBuilder
     */
    public function retryMulticastIds($retryMulticastIds)
    {
        $this->retryMulticastIds = $retryMulticastIds;
        return $this;
    }

    /**
     * @return MulticastResult
     */
    public function build()
    {
        return new MulticastResult($this);
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
     * @return int (long)
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
}
