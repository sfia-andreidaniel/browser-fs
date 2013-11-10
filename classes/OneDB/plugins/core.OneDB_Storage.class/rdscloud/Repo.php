<?php

class Repo
{

    protected $_ch;
    protected $_username;
    protected $_password;
    protected $_response;
    protected $_error;
    protected $_options;

    protected function __construct()
    {
        $this->_checkCurl();
        $this->_resetOptions();
    }

    protected function _checkCurl()
    {
        if  ( ! in_array( 'curl', get_loaded_extensions() ) ) {
            throw new Exception(
                'Curl extension not found on this server!'
            );
        }
    }

    protected function _setHttpCredentials( $username, $password )
    {
        $this->_username = $username;
        $this->_password = $password;
    }
    protected function _resetOptions()
    {
        $this->_options = array(
            CURLOPT_TIMEOUT =>  60 * 60 * 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_FRESH_CONNECT => true,

            CURLOPT_HEADER => defined('REPO_DEBUG') ? TRUE : FALSE,
            CURLOPT_VERBOSE => defined('REPO_DEBUG') ? TRUE : FALSE,
        );
    }

    protected function _setPostData( array $data )
    {
        $this->_setOption( CURLOPT_POST, true );
        $this->_setOption( CURLOPT_POSTFIELDS, $data );

        return $this;
    }

    protected function _setOption( $opt, $val )
    {
        if ( array_key_exists( $opt, $this->_options ) ) {
            $this->_options[ $opt ] = $val;
        } else {
            $this->_options[ $opt ] = $val;
        }
    }

    protected function _setAuthData()
    {
        // auth
        if ( ( $this->_username !== null )
             && ( $this->_password !== null )
        ) {
            $auth = $this->_username . ":" . $this->_password;
            $this->_setOption( CURLOPT_HTTPAUTH, CURLAUTH_ANY );
            $this->_setOption( CURLOPT_USERPWD, $auth );
        }

        return $this;
    }

    protected function _openRequest()
    {
        $this->_closeRequest();
        $this->_resetOptions();
        $this->_response = null;
        $this->_ch = curl_init();

        return $this;
    }

    protected function _setUrl( $url )
    {
        $this->_setOption( CURLOPT_URL, $url );

        return $this;
    }

    protected function _execRequest()
    {
        curl_setopt_array( $this->_ch, $this->_options );
        $this->_response = curl_exec( $this->_ch );

        $this->_error = null;
        if ( $this->_response == false ) {
            $this->_error = curl_error( $this->_ch );
        }

        return $this;
    }

    protected function _closeRequest()
    {
        if ( is_resource( $this->_ch ) ) {
            curl_close( $this->_ch );
        }

        return $this;
    }

    protected function _getResponse()
    {
        return $this->_response;
    }

    protected function _getLastError()
    {
        return $this->_error;
    }

    protected function _simplePost( $url, $postData ) {

        return $this->_openRequest()
                    ->_setAuthData()
                    ->_setUrl( $url )
                    ->_setPostData( $postData )
                    ->_execRequest()
                    ->_closeRequest()
                    ->_getResponse();
    }

}
