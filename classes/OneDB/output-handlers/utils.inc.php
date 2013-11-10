<?php

    function http404() {
        header("HTTP/1.0 404 Not Found");
        header("Content-Type: text/plain");
        die("OneDB: File not found!");
    }

    function http500( $str ) {
        header("HTTP/1.0 500 Internal server error");
        header("Content-Type: text/plain");
        die( $str );
    }
    
    function http403( $str ) {
        header("HTTP/1.0 403 Forbidden");
        header("Content-Type: text/plain");
        die( strlen( $str ) ? $str : "Forbidden!" );
    }

?>