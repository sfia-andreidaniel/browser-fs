<?php

    function digisport_epg_vineri() {
        $nowTime = (int)date('H') < 7 ? time() - 86400 : time();
        list($d,$m,$y) = explode(' ', date( 'j n Y', $nowTime ));
        $theTime = mktime(7, 0, 0, $m, $d, $y);
        while (date('w', $theTime) != 5)
            $theTime += 86400;
        return $theTime;
    }
    
    //echo date('H:i:s j/n/Y', digisport_epg_vineri());
    
?>