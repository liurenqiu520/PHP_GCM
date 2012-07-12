<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/11
 * Time: 16:25
 * To change this template use File | Settings | File Templates.
 */
class CertificationManager
{

    /** @var string */
    private $rootCertFileName;

    /** @var ICertification */
    private $localCert;

    /**
     * @param string $rootCertFileName
     * @param ICertification $localCert
     */
    public function __construct(ICertification $localCert, $rootCertFileName = '') {
        $this->localCert = $localCert;
        $this->rootCertFileName = $rootCertFileName;
    }

    /**
     * @return string
     */
    public function getLocalCertAuthFile() {
        return $this->localCert->getPemFileName();
    }

    /**
     * @return string
     */
    public function getLocalCertPassPhrase() {
        return $this->localCert->getPassPhrase();
    }

    /**
     * @return string
     */
    public function getRootCertAuthFile() {
        return $this->rootCertFileName;
    }

    /**
     * @return string
     */
    public function getRootCertPassPhrase() {
        return $this->rootCert->getPassPhrase();
    }

    /**
     * @param \ICertification $rootCert
     */
    public function setRootCert($rootCert)
    {
        $this->rootCert = $rootCert;
    }

    /**
     * @param \ICertification $localCert
     */
    public function setLocalCert($localCert)
    {
        $this->localCert = $localCert;
    }
}
