<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/03
 * Time: 15:58
 * To change this template use File | Settings | File Templates.
 */
class Logger
{
    private static $instancePool = array();

    private $logfile;

    const FORMAT_LOG  = '[LOG][%s] %s';
    const FORMAT_WARN = '[WARN][%s] %s';
    const FORMAT_INFO = '[INFO][%s] %s';

    private function __construct($logfile) {
        $this->logfile = $logfile;
    }

    /**
     * @static
     * @param $logfile
     * @return Logger
     */
    public static function getLogger($logfile) {
        if (!array_key_exists($logfile, self::$instancePool)) {
            self::$instancePool[$logfile] = new Logger($logfile);
        }
        return self::$instancePool[$logfile];
    }

    private $log = '';

    public function put($log){
        $this->log .= $log . PHP_EOL;
    }

    public function warn($log){
        $this->log .= sprintf(self::FORMAT_WARN, date(DATE_ATOM)) . PHP_EOL;
    }

    public function log($log){
        $this->log .= sprintf(self::FORMAT_LOG, date(DATE_ATOM)) . PHP_EOL;
    }

    public function info($log){
        $this->log .= sprintf(self::FORMAT_INFO, date(DATE_ATOM)) . PHP_EOL;
    }

    public function get(){
        return $this->log;
    }

    public function flush() {

        $dirName = dirname($this->logfile);

        if (!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }

        file_put_contents($this->logfile, $this->log, FILE_APPEND | LOCK_EX);

	    $this->log = '';
    }

}
