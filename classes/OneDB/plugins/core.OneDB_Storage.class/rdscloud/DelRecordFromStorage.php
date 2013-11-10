<?php

require_once( 'Repo.php' );
final class DelRecordFromStorage extends Repo
{

    const STORAGE_SERVICE_URL = 'http://transcoder01.rcs-rds.ro/admin/repository/service/delurlfromstorage/';

    public function __construct()
    {
        parent::__construct();
    }

    public function deleteFile( $user, $pass, $source )
    {
        $handlerFileRead = @fopen( $source , 'rb' );
        if ( $handlerFileRead != false ) {
            $url = self::STORAGE_SERVICE_URL;
            $postData[ 'fileUrl' ] = $source;
            fclose( $handlerFileRead );
        }

        if ( ! ( isset( $url ) ) ) {
            throw new Exception( 'Wrong source for file!' );
        }

        $postData[ 'username' ] = $user;
        $postData[ 'password' ] = $pass;

        return $this->_simplePost( $url, $postData );
    }

}

function bindDelRecordFromStorage( $user, $pass, $sourceFile ) {
    $service = new DelRecordFromStorage;
    return $service->deleteFile( $user, $pass, $sourceFile );
}