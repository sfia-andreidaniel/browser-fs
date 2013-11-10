<?php

    function Mongo_NameSpaces( $mongo ) {
        $result = $mongo->system->namespaces->find();
        $out = array();
        while ($result->hasNext()) {
            $ns = $result->getNext();
            $out[] = 'db.' . implode('.', array_slice(explode('.', $ns['name']), 1));
        }
        return $out;
    }
    
    function Mongo_GetStorage( $mongo ) {
        $out = array();
        
        $sumTotal = 0;
        $sumData = 0;
        $sumStorage = 0;
        
        $row = array();
        
        foreach (Mongo_Namespaces( $mongo ) as $nsName ) {

            $size = $mongo->execute("return $nsName.totalSize()");
            
            if (is_array( $size ) && $size['ok'] == 1) {
                $sumTotal += ($row["totalSize"] = $size['retval']);
                
                $size = $mongo->execute("return $nsName.storageSize()");
                
                if (is_array( $size ) && $size['ok'] == 1) {
                    $sumStorage += ($row["storageSize"] = $size['retval']);
                    
                    $size = $mongo->execute("return $nsName.dataSize()");
                
                    if (is_array( $size ) && $size['ok'] == 1) {
                        $sumData += ($row["dataSize"] = $size['retval']);
                        
                        $out["$nsName"] = $row;
                    }
                }
            }
        }
        
        $out['_total'] = array(
            'totalSize'   => $sumTotal,
            'dataSize'    => $sumData,
            'storageSize' => $sumStorage
        );
        
        return $out;
    }
    
    function Mongo_RepairDatabase( $mongo ) {
        //operation should not take more than 10 minutes
        set_time_limit( 600 );
        
        $ret = $mongo->execute( "return db.repairDatabase();" );
        
        return $ret['ok'] == 1 ? TRUE : FALSE;
    }
    
    function Mongo_Exec( $mongo, $code ) {

        $code = trim( $code, ' ;' );

        $ret = $mongo->execute("
            var dumpifier = dumpifier || function( o ) {
                switch (true) {
                    case ((o && o.hasNext && o.next) ? true : false) :
                        var out = [];
                        while ( o.hasNext() ) out.push( JSON.parse( JSON.stringify( o.next( ) ) ) );
                        return JSON.stringify( out.slice( 0, 100 ) );
                        break;
                    default:
                        return JSON.stringify( o );
                }
            };

            return dumpifier( $code );"
        );
        
        if (is_array( $ret ) && isset( $ret['ok'] )) {

            if ($ret['ok'] == 1)
                return json_decode( $ret['retval'], TRUE );
            else
                return $ret;

        } return $ret;
    }

?>