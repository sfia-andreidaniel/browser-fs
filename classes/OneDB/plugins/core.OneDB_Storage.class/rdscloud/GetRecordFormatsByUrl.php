<?php

require_once( dirname(__FILE__) . '/Repo.php' );

final class GetRecordFormatsByUrl extends Repo
{
    const JOB_SERVICE_URL = 'http://transcoder01.rcs-rds.ro/admin/repository/service/getrecordformats/';

    public function __construct()
    {
        parent::__construct();
    }

    public function getFormats( $user, $pass, $source )
    {
        $handlerFileRead = @fopen( $source , 'rb' );
        if ( $handlerFileRead != false ) {
            $url = self::JOB_SERVICE_URL;
            $postData[ 'fileUrl' ] = $source;
            @fclose( $handlerFileRead );
        }

        if ( ! ( isset( $url ) ) ) {
            throw new Exception( 'Wrong source for file!' );
        }
        
        $postData[ 'username' ] = $user;
        $postData[ 'password' ] = $pass;

        return $this->_simplePost( $url, $postData );
    }
}


function bindGetRecordFormatsByUrl( $user, $pass, $sourceFile ) {
    $service = new GetRecordFormatsByUrl;
    return $service->getFormats( $user, $pass, $sourceFile );
}



