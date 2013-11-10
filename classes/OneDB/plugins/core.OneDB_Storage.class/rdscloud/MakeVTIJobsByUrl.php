<?php


require_once( dirname(__FILE__) . '/Repo.php' );

final class MakeVTIJobsByUrl extends Repo
{
    const JOB_SERVICE_URL = 'http://transcoder01.rcs-rds.ro/admin/repository/service/makevtijobsbyurl/';

    public function __construct()
    {
        parent::__construct();
    }

    public function makeJobs( $user, $pass, $source, $priority, array $snapshots )
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
        $postData[ 'snapshots' ] = serialize( $snapshots );

        return $this->_simplePost( $url, $postData );
    }

}


function bindMakeVTIJobsByUrl( $user, $pass, $sourceFile, $priority, $snapshots = array() ) {
    $service = new MakeVTIJobsByUrl;
    return $service->makeJobs( $user, $pass, $sourceFile, $priority, $snapshots );
}




