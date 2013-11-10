<?php


require_once( dirname(__FILE__) . '/Repo.php' );

final class MakeVTVJobsByUrl extends Repo
{
    const JOB_SERVICE_URL = 'http://transcoder01.rcs-rds.ro/admin/repository/service/makevtvjobsbyurl/';

    public function __construct()
    {
        parent::__construct();
    }

    public function makeJobs( $user, $pass, $source, $priority, array $versions )
    {
        $handlerFileRead = @fopen( $source , 'rb' );
        if ( $handlerFileRead != false ) {
            $url = self::JOB_SERVICE_URL;
            $postData[ 'fileUrl' ] = $source;
            fclose( $handlerFileRead );
        }

        if ( ! ( isset( $url ) ) ) {
            throw new Exception( 'Wrong source for file!' );
        }

        $postData[ 'username' ] = $user;
        $postData[ 'password' ] = $pass;
        $postData[ 'priority' ] = $priority;
        $postData[ 'versions' ] = serialize( $versions );

        return $this->_simplePost( $url, $postData );
    }

}


function bindMakeVTVJobsByUrl( $user, $pass, $sourceFile, $priority, $versions = array() ) {
    $service = new MakeVTVJobsByUrl;
    return $service->makeJobs( $user, $pass, $sourceFile, $priority, $versions );
}





