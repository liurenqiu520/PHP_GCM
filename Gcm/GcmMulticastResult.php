<?php
/**
 * 送信側未実装なので使ってない！！
 *
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/06/29
 * Time: 19:27
 * To change this template use File | Settings | File Templates.
 */
class GcmMulticastResult
{
    /** @var int final */
    private $success;
    /** @var int final */
    private $failure;
    /** @var int final */
    private $canonicalIds;
    /** @var int final */
    private $multicastId;
    /** @var ArrayData.<Result> final */
    private $results;
    /** @var ArrayData.<long> final */
    private $retryMulticastIds;

    public function __construct(MulticastResultBuilder $builder) {

        $this->success = $builder->getSuccess();
        $this->failure = $builder->getFailure();
        $this->canonicalIds = $builder->getCanonicalIds();
        $this->multicastId = $builder->getMulticastId();
        $builder->getResults()->setReadOnly(true);
        $this->results = $builder->getResults();

        $tmpList = $builder->getRetryMulticastIds();

        if ($tmpList == null) {
            $tmpList = new ArrayData('int');
        }
        $tmpList->setReadOnly(true);
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
     * @return \ArrayData
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @return \ArrayData
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

    public function __toString() {
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

        if($this->results->count() != 0) {
            $string .= 'results: ';
            $string .= $this->getResults();
        }

        return $string;
    }

}


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

    /** @var ArrayData.<Result> final */
    private $results;

    /** @var ArrayData.<long> final */
    private $retryMulticastIds;

    /**
     * @param int $success
     * @param int $failure
     * @param int $canonicalIds
     * @param int $multicastId
     */
    public function __construct($success, $failure, $canonicalIds, $multicastId)
    {

        $this->results = new ArrayData('Result');

        $this->success = intval($success);
        $this->failure = intval($failure);
        $this->canonicalIds = $canonicalIds;
        $this->multicastId = $multicastId;
    }

    /**
     * @param GcmResult $result
     * @return MulticastResultBuilder
     */
    public function addResult($result)
    {
        $this->results->add($result);
        return $this;
    }

    /**
     * @param ArrayData $retryMulticastIds
     * @return MulticastResultBuilder
     */
    public function retryMulticastIds($retryMulticastIds)
    {
        $this->retryMulticastIds = $retryMulticastIds;
        return $this;
    }

    /**
     * @return GcmMulticastResult
     */
    public function build()
    {
        return new GcmMulticastResult($this);
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
     * @return \ArrayData
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @return \ArrayData
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