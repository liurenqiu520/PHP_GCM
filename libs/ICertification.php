<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/11
 * Time: 16:26
 * To change this template use File | Settings | File Templates.
 */
interface ICertification
{
    /**
     * @abstract
     * @return string
     */
    public function getPassPhrase();

    /**
     * @abstract
     * @return string
     */
    public function getPemFileName();
}
