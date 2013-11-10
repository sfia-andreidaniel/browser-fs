<?php

require_once( 'Repo.php' );

final class SetBytesOnStorage extends Repo
{

    const STORAGE_SERVICE_BYTES = 'http://transcoder01.rcs-rds.ro/admin/repository/service/addbytestorepository/';

    public function __construct()
    {
        parent::__construct();
    }

    public function uploadBytes( $user, $pass, $source, $priority, $replace,
        $newFilename
    ) {
        // $this->_setHttpCredentials( $user, $pass );

        $url = self::STORAGE_SERVICE_BYTES;
        $postData[ 'fileBytes' ] = $source;
        

        if ( ! ( isset( $source ) ) ) {
            throw new Exception( 'Wrong source for file!' );
        }

        $postData[ 'username' ] = $user;
        $postData[ 'password' ] = $pass;
        $postData[ 'priority' ] = $priority;
        $postData[ 'replace' ] = $replace;
        $postData[ 'newFilename' ] = $newFilename;

        return $this->_simplePost( $url, $postData );
    }

}

/**
 * Add bytes to storage
 *
 * @param string $user - repo username
 * @param string $pass - repo password
 * @param string $bytes - bytes file to upload
 * @param integer $priority - priotity (0 - 100)
 * @param string $targetURL - full url to repo file in order to overwrite
 * @param string $newFilename - experimental, new filename on repo
 * @return string
 */

function bindSetBytesOnStorage( $user, $pass, $bytes, $targetURL ) {

    $priority = 50;
    $newFileName = NULL;

    $service = new SetBytesOnStorage;
    return $service->uploadBytes( $user, $pass, $bytes, $priority,
        $targetURL, $newFilename
    );
}
