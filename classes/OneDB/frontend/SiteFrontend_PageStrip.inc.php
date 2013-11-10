<?php

    /* Strips a html buffer for tags contained in the tagsList */

    function OneDB_PageStrip( $htmlBuffer, $tagsList = array() ) {
        
        $dom = new DOMDocument();
        @$dom->loadHTML( $htmlBuffer );
        
        foreach ($tagsList as $tag) {
            $elements = $dom->getElementsByTagName( $tag );
            for ($i=$elements->length - 1; $i>=0; $i--) {
                $elements->item( $i )->parentNode->removeChild(
                    $elements->item( $i )
                );
            }
        }
        
        return $dom->saveHTML();
    }

?>