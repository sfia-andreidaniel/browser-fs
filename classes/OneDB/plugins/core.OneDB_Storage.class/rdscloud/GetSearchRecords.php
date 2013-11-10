<?php

require_once( dirname(__FILE__) . '/Repo.php' );
final class GetSearchRecords extends Repo
{
    const JOB_SERVICE_URL = 'http://transcoder01.rcs-rds.ro/admin/repository/service/searchrecords/';

    public function __construct()
    {
        parent::__construct();
    }

    public function getRecords( $user, $pass, $relUrlLike, $extra = null,
        $limit = null, $offset = null
    ) {
        $url = self::JOB_SERVICE_URL;

        $postData[ 'username' ] = $user;
        $postData[ 'password' ] = $pass;

        $postData[ 'relUrl' ] = $relUrlLike;

        $postData[ 'extra' ] = serialize( $extra );

        $postData[ 'limit' ] = $limit;
        $postData[ 'offset' ] = $offset;

        return $this->_simplePost( $url, $postData );
    }
}


function bindGetSearchRecords( $user, $pass, $relUrlLike, $extra = null,
    $limit = null, $offset = null
) {
    $service = new GetSearchRecords();

    return $service->getRecords( $user, $pass, $relUrlLike, $extra, $limit,
        $offset
    );
}



