<?php

    function DS_Entitlement( $IP ) {
        $buffer = @file_get_contents( "http://cdnapi.rcs-rds.ro/api/ds_entitlement.php?ip=" . $IP );
        return strlen( "$buffer" ) == 2 ? strtolower( $buffer ) : '-';
    }
    
?>