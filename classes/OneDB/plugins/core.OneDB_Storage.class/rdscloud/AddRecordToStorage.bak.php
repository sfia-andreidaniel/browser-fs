<?php

require_once( dirname(__FILE__) . '/Repo.php' );

final class AddRecordToStorage extends Repo
{

    const STORAGE_SERVICE_FILE = 'http://transcoder01.rcs-rds.ro/admin/repository/service/addfiletorepository/';
    const STORAGE_SERVICE_URL = 'http://transcoder01.rcs-rds.ro/admin/repository/service/addurltorepository/';

    public function __construct()
    {
        parent::__construct();
    }

    public function uploadFile( $user, $pass, $source, $priority )
    {
        
        /*
        // $this->_setHttpCredentials( $user, $pass );

        if ( file_exists( $source ) ) {
            $url = self::STORAGE_SERVICE_FILE;
            $postData[ 'attachment' ] = '@' . $source;
        } else {
            $handlerFileRead = @fopen( $source , 'rb' );
            if ( $handlerFileRead != false ) {
                $url = self::STORAGE_SERVICE_URL;
                $postData[ 'fileUrl' ] = $source;
                fclose( $handlerFileRead );
            }
        }
        */

        if ( ( file_exists( $source ) ) && ( ! preg_match( '/^((ftp:\/\/)|(ssh:\/\/)|(sftp:\/\/))/', $source) ) ) {
            $url = self::STORAGE_SERVICE_FILE;
            $postData[ 'attachment' ] = '@' . $source;
        } else {
            $handlerFileRead = @fopen( $source , 'rb' );
            if ( $handlerFileRead != false ) {
                $url = self::STORAGE_SERVICE_URL;
                $postData[ 'fileUrl' ] = $source;
                @fclose( $handlerFileRead );
            }
        }
        
        if ( ! ( isset( $url ) ) ) {
            throw new Exception( 'Wrong source for file!' );
        }

        $postData[ 'username' ] = $user;
        $postData[ 'password' ] = $pass;
        $postData[ 'priority' ] = $priority;

        return $this->_simplePost( $url, $postData );
    }

}

function bindAddRecordToStorage( $user, $pass, $sourceFile, $priority ) {
    $service = new AddRecordToStorage;
    return $service->uploadFile( $user, $pass, $sourceFile, $priority );
}


