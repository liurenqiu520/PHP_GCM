<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/11
 * Time: 11:49
 * To change this template use File | Settings | File Templates.
 */
class CertificationP12 implements ICertification
{

    private $certFile;

    private $certPassPhrase;

    private $pemFileName;

    /**
     * @param string $certFile p12形式
     * @param string $certPass
     */
    public function __construct($certFile, $certPass='') {
        $this->certFile = $certFile;
        $this->certPassPhrase = $certPass;
        $certPathInfo = pathinfo($certFile);
        $this->pemFileName = $certPathInfo['dirname'] . DIRECTORY_SEPARATOR . $certPathInfo['filename'] . '.pem';
    }

    /**
     * @throws Exception
     */
    public function createPemFile() {

        $p12 = file_get_contents($this->certFile);
        $certs = array();
        $success = openssl_pkcs12_read( $p12, $certs, $this->certPassPhrase );

        if($success) {

            openssl_pkey_export ($certs['pkey'], $pkey, $this->certPassPhrase);

            if(!openssl_pkey_export ($certs['pkey'], $pkey, $this->certPassPhrase)) {
                throw new Exception(openssl_error_string());
            }

            file_put_contents(
                $this->pemFileName,
                $certs['cert'] . $pkey
            );

        }else {
            throw new Exception(openssl_error_string());
            exit;
        }
    }

    public function getPassPhrase()
    {
        return $this->certPassPhrase;
    }

    public function getPemFileName()
    {
        if(!file_exists($this->pemFileName)) {
            $this->createPemFile();
        }
        $this->createPemFile();
        return $this->pemFileName;
    }


}
