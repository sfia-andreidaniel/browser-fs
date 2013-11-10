<?php

    function http404() {
        header("HTTP/1.0 404 Not Found");
        die("OneDB: File not found!");
    }

    function http500( $str ) {
        header("HTTP/1.0 500 Internal server error");
        die( $str );
    }

?>