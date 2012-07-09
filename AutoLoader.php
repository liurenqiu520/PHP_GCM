<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/06
 * Time: 16:44
 * To change this template use File | Settings | File Templates.
 */
class AutoLoader
{
    public static function addIncludePath( array $dirs )
    {
        $currentPath = ini_get( 'include_path' );
        if ( $currentPath ) {
            $currentPath = explode( PATH_SEPARATOR, $currentPath );
            $dirs = array_merge( $currentPath, $dirs );
            $dirs = array_unique( $dirs );
        }
        ini_set( 'include_path', implode( PATH_SEPARATOR, $dirs ) );
    }

    public static function registerAutoLoad()
    {
        spl_autoload_register( array( 'AutoLoader', 'loadClass' ) );
    }

    public static function loadClass($className, $dir = null)
    {

        if ( class_exists( $className, false ) || interface_exists( $className, false ) ) {
            return;
        }

        if ( (null !== $dir) && !is_string( $dir ) ) {
            throw new Exception( '$dir must be a string' );
        }
        $file = $className . '.php';
        if ( !empty( $dir ) ) {
            $file = $dir . DIRECTORY_SEPARATOR . $file;
        }
        self::checkFileName( $file );

        include_once $file;

        if ( !class_exists( $className, false ) && !interface_exists( $className, false ) ) {
            throw new Exception( $className . ' not Exist in load file' );
        }
    }

    /**
     * ファイル文字列が安全か
     *
     * @param string $filename
     * @throws Exception
     */
    private static function checkFileName($filename)
    {
        if ( !is_string( $filename ) ) {
            throw new Exception( '$filename must be a string' );
        }
        if ( preg_match( '/[^a-z0-9\\/\\\\_.-]/i', $filename ) ) {
            throw new Exception( 'Load Error: Illegal Filename' );
        }
    }
}
