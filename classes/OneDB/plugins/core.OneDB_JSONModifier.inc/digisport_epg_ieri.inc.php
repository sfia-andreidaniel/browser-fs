<?php

    function digisport_epg_ieri() {
        $nowTime = (int)date('H') < 7 ? time() - 86400 : time();
        list($d,$m,$y) = explode(' ', date( 'j n Y', $nowTime ));
        return mktime(7, 0, 0, $m, $d, $y) - 86400;
    }

?>