<?php

require_once( 'Repo.php' );

final class AddRecordToStorage extends Repo
{

    const STORAGE_SERVICE_FILE = 'http://transcoder01.rcs-rds.ro/admin/repository/service/addfiletorepository/';
    const STORAGE_SERVICE_URL = 'http://transcoder01.rcs-rds.ro/admin/repository/service/addurltorepository/';

    const STORAGE_SERVICE_FILE_ASYNC_START = 'http://transcoder01.rcs-rds.ro/admin/repository/service/addfileasynctorepositorystart/';
    const STORAGE_SERVICE_FILE_ASYNC_USE = 'http://transcoder01.rcs-rds.ro/admin/repository/service/addfileasynctorepositoryuse/';
    const STORAGE_SERVICE_FILE_ASYNC_STOP = 'http://transcoder01.rcs-rds.ro/admin/repository/service/addfileasynctorepositorystop/';

    public function __construct()
    {
        parent::__construct();
    }

    public function uploadFile( $user, $pass, $source, $priority,
        $replace = null, $newFilename = null
    ) {
        
        clearstatcache();

        // $this->_setHttpCredentials( $user, $pass );

        if ( ( file_exists( $source ) )
            && ( ! preg_match( '/^((ftp:\/\/)|(ssh:\/\/)|(sftp:\/\/))/', $source) )
        ) {
            $fs = filesize( $source );
            if ( $fs > 1024 * 1024 * 200 ) {
                return $this->_uploadFileAsync( $user, $pass, $source,
                                                $priority, $replace, $newFilename );
            }
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
        $postData[ 'replace' ] = $replace;
        $postData[ 'newFilename' ] = $newFilename;


        return $this->_simplePost( $url, $postData );
    }

    protected function _uploadFileAsync( $user, $pass, $source, $priority,
        $replace = null, $newFilename = null
    ) {
        // $this->_setHttpCredentials( $user, $pass );

        $newFilename = ($newFilename === NULL)
                     ? pathinfo( $source, PATHINFO_BASENAME )
                     : $newFilename;

        $handlerFileRead = @fopen( $source, 'rb' );

        if ( $handlerFileRead == false ) {
            throw new Exception( 'File provided could not be opended for reading!' );
        }

        // init the tranzaction 
        $postData = array();
        $postData[ 'initTransaction' ] = TRUE;
        $postData[ 'stopTransaction' ] = NULL;
        $postData[ 'useTransaction' ] = NULL;
        $postData[ 'chunk' ] = NULL;
        $postData[ 'newFilename' ] = $newFilename;

        $postData[ 'username' ] = $user;
        $postData[ 'password' ] = $pass;
        $postData[ 'priority' ] = $priority;
        $postData[ 'replace' ] = $replace;

        $url = self::STORAGE_SERVICE_FILE_ASYNC_START;
        $rspStartJSON = $this->_simplePost( $url, $postData );

        $rspStart = @json_decode( $rspStartJSON, TRUE );
        if ( ! is_array( $rspStart )
            || ( ! isset( $rspStart[ 'ok' ] ) )
            || ( $rspStart[ 'ok' ] != TRUE )
        ) {
            throw new Exception( 'Wrong response from repository:' . $rspStartJSON );
        }

        $idTransaction = $rspStart[ 'transactionId' ];

        // use the transaction
        $i = 0;
        while ( ! feof( $handlerFileRead ) ) {

            // @NOTE!! at least 10 MB
            $chunkSize = 1024 * 1024 * 10;
            $chunk = @fread( $handlerFileRead, $chunkSize );

            $postData = array();
            $postData[ 'transactionId' ] = $idTransaction;
            $postData[ 'chunk' ] = 'CURL_CHUNK_' . $chunk;

            $postData[ 'username' ] = $user;
            $postData[ 'password' ] = $pass;
            $postData[ 'priority' ] = $priority;

            $url = self::STORAGE_SERVICE_FILE_ASYNC_USE;
            $rspUseJSON = $this->_simplePost( $url, $postData );

            $rspUse = @json_decode( $rspUseJSON, true );
            if ( ! is_array( $rspUse ) || ( ! isset( $rspUse[ 'ok' ] ) )
                || ( $rspUse[ 'ok' ] != TRUE )
            ) {
                throw new Exception( 'Wrong response from repository:' . $rspUseJSON );
            }

        }
        @fclose( $handlerFileRead );

        // close the transaction
        $postData = array();
        $postData[ 'stopTransaction' ] = TRUE;
        $postData[ 'transactionId' ] = $idTransaction;

        $postData[ 'username' ] = $user;
        $postData[ 'password' ] = $pass;
        $postData[ 'priority' ] = $priority;

        $url = self::STORAGE_SERVICE_FILE_ASYNC_STOP;
        $rsp3 = $this->_simplePost( $url, $postData );

        return $rsp3;
    }

}


function bindAddRecordToStorage( $user, $pass, $sourceFile, $priority ) {
    $service = new AddRecordToStorage;
    return $service->uploadFile( $user, $pass, $sourceFile, $priority );
}

