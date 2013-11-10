<?php

    /** This class implements a filesystem wrapper in format
        onedb://path/to/something for OneDB files and categories
     **/
    
    class OneDB_MySQL_Wrapper {
        
        protected static $_con    = array();
        protected static $_dir    = array();
        protected static $_offset = 0;
        
        protected $_stream = NULL;
        protected $_statFile= NULL;
        
        static protected $_matches = array(
            'database' => '/^[a-z0-9\_]+$/i',
            'table'    => '/^[a-z0-9\_]+\.(table|sql|count|[\d]+\-[\d]+)+$/i'
        );
        
        static function _get_con( $connectionURL ) {
            $info = parse_url( $connectionURL );

            if ( isset( self::$_con[ $key = "$info[scheme]://$info[user]:$info[pass]@$info[host]" ] ) )
                return self::$_con[ $key ];
            else {
                $conn = @mysql_connect( $info['host'], $info['user'], $info['pass'] );
                if ( is_resource( $conn ) ) {
                    return self::$_con[ $key ] = $conn;
                } else return FALSE;
            }
        }
        
        static function _path( $path ) {
            $info = parse_url( $path );
            $info['path'] = isset( $info['path'] ) ? $info['path'] : '/';
            $info['path'] = preg_replace('/[\/]+/', '/', $info['path'] );
            return trim( $info['path'], '/');
        }
        
        static function _context( $path ) {
            $path = self::_path( $path );
            $parts = explode('/', $path );

            // echo "path = $path\n";
            // echo "parts = ", implode(' / ', $parts ), "\n";

            switch ( TRUE ) {
                case in_array($path, array('', '/') ):
                    return array('type' => 'server' );
                    break;
                    
                case count( $parts ) == 1 && !in_array( $parts[0], array('.dump', '.queries') ) && 
                     !empty( $parts[0]) &&
                     preg_match( self::$_matches['database'], $parts[0] ):
                    return array('type' => 'database', 'name' => $parts[0] );
                    break;
                    
                case count( $parts ) == 2 && 
                     !in_array( $parts[1], array('.dump', '.queries', '.triggers') ) &&
                     preg_match( self::$_matches['database'], $parts[0] ) &&
                     preg_match( self::$_matches['table'], $parts[1] ):
                    return array('type' => 'table', 'name' => $parts[0] . '.' . $parts[1] );
                    break;
                    
                case in_array( $parts[1], array( '.dump' ) ) && count( $parts ) == 2 &&
                     preg_match( self::$_matches['database'], $parts[0] ):
                    return array('type' => 'dump','name' => $parts[0]);
                    break;
                    
                case in_array( $parts[0], array( '.queries' ) ):
                    return array('type' => 'query', 'name' => count( $parts ) == 1 ? NULL : urldecode( base64_decode( $parts[1] ) ) );
                    break;
                    
                case in_array( $parts[1], array('.triggers') ) &&
                     preg_match( self::$_matches['database'], $parts[0] ):
                    return array(
                        'type' => 'trigger', 
                        'name' => count( $parts ) == 2 
                                    ? $parts[0]
                                    : ( count( $parts ) == 3 
                                         ? $parts[0] . '.' .  $parts[2]
                                         : NULL
                                      )
                        );
                    break;
                    
                default:
                    return array('type' => 'unknown');
                    break;
            }
        }
        
        // directories
        public function dir_opendir( $url, $options ) {
            $con = self::_get_con( $url );
            
            if (!is_resource( $con ))
                return FALSE;
            
            $context = self::_context( $url );
            
            // print_r( $context );
            // die();
            
            self::$_offset = 0;
            
            switch ($context['type']) {
            
                case 'server':
                    $sql = "SHOW databases";
                    
                    $result = @mysql_query( $sql, $con );
                    
                    if (!$result) {
                        trigger_error("mysql_error: " . mysql_error( $con ), E_WARNING );
                        return FALSE;
                    }
                    
                    self::$_dir = array();
                    
                    while ($row = mysql_fetch_row( $result ) )
                        self::$_dir[] = reset( $row );
                    
                    return TRUE;
                    
                    break;
                    
                case 'database':
                    $sql = "SHOW tables FROM `" . $context['name'] . "`";
                    
                    $result = @mysql_query( $sql, $con );
                    
                    if (!$result) {
                        trigger_error("mysql_error: " . @mysql_error( $con ), E_WARNING );
                        return FALSE;
                    }
                    
                    self::$_dir = array(
                        '.dump',
                        '.triggers'
                    );
                    
                    while ($row = @mysql_fetch_row( $result ) ) {
                        self::$_dir[] = reset( $row ) . ".table";
                        self::$_dir[] = reset( $row ) . ".sql";
                        //self::$_dir[] = reset( $row ) . ".0-100";
                    }
                    
                    return TRUE;
                    
                    break;
                    
                case 'table':
                    /* describe table, in order to fetch it's primary or unique keys */
                    
                    if (!preg_match('/\.table$/', $context['name'])) {
                        trigger_error("bad table name!");
                        return FALSE;
                    }
                    
                    $context['name'] = preg_replace('/\.table$/', '', $context['name'] );
                    
                    $sql = "DESCRIBE " . $context['name'];
                    
                    $result = mysql_query( $sql, $con );
                    
                    if (!$result) {
                        trigger_error( "mysql_error: " . mysql_error( $con ), E_WARNING );
                        return FALSE;
                    }
                    
                    $keys = array();
                    $rows = array();
                    
                    while ($row = mysql_fetch_array( $result, MYSQL_ASSOC ) )
                        $rows[] = $row;
                    
                    /* Find the keys */
                    
                    // - by primary keys
                    foreach( $rows as $row ) {
                        if ($row['Key'] == 'PRI')
                            $keys[] = $row['Field'];
                    }
                    
                    // - by unique indexes
                    if (!count( $keys ) ) {
                        foreach ($rows as $row) {
                            if ($row['Key'] == 'UNI')
                                $keys[] = $row['Field'];
                        }
                    }
                    
                    /* Now select from the table... */
                    
                    $sql = "SELECT " . ( count( $keys ) ? "`" . implode( '`, `', $keys ) . "`" : "*" ) . " FROM " . $context['name'];
                    
                    $result = mysql_query( $sql, $con );
                    
                    if (!$result) {
                        trigger_error("mysql_error: " . mysql_error( $con ), E_WARNING );
                        return FALSE;
                    }
                    
                    self::$_dir = array();
                    
                    $keysCount = count( $keys );
                    
                    $i = 0;
                    
                    while ($row = mysql_fetch_array( $result, MYSQL_ASSOC ) ) {
                        if ( $keysCount ) {
                            
                            $out = array();
                            
                            foreach ( $keys as $key )
                                $out[ $key ] = $row[ $key ];
                            
                            self::$_dir[] = urlencode( json_encode( $out ) );
                        } else {
                            self::$_dir[] = ("row-" . $i);
                            $i++;
                        }
                    }
                    
                    return TRUE;
                    
                    break;
                
                case 'dump':
                    return FALSE;
                    break;
                
                case 'trigger':
                    if (empty( $context['name'] ) )
                        return FALSE;
                    
                    if ( strpos( $context['name'], '.' ) !== FALSE ) {
                        trigger_error( "invalid trigger specification!", E_WARNING );
                        return FALSE;
                    }
                    
                    $sql = "SHOW triggers FROM $context[name]";
                    $result = @mysql_query( $sql, $con );
                    if (!$result) {
                        trigger_error( "mysql_error: " . mysql_error( $con ), E_WARNING );
                        return FALSE;
                    }
                    
                    while ($row = mysql_fetch_array( $result, MYSQL_ASSOC ) ) {
                        self::$_dir[] = reset( $row );
                    }
                    
                    return TRUE;
                
                case 'query':
                    if ( !empty( $context['name'] ) ) {
                        $sql = $context['name'];
                    } else return FALSE;
                    
                    $result = mysql_query( $sql, $con );
                    
                    if (!$result) {
                        trigger_error( "mysql_error: " . mysql_error( $con ), E_WARNING );
                        return FALSE;
                    }
                    
                    $i = 0;
                    self::$_dir[] = array();
                    
                    while ($row = mysql_fetch_row( $result ) ) {
                        self::$_dir[] = "row-" . $i;
                        $i++;
                    }
                    
                    return TRUE;
                    
                    break;
                    
                case 'unknown':
                default:
                    trigger_error('malformed url!', E_WARNING);
                    return FALSE;
                    break;
            }
        }
        
        function dir_readdir( ) {
            if ( self::$_offset < count( self::$_dir ) ) {
                return self::$_dir[ self::$_offset++ ];
            } else return FALSE;
        }
        
        function dir_rewinddir( ) {
            self::$_offset = 0;
            return TRUE;
        }
        
        function dir_closedir( ) {
            self::$_offset = 0;
            self::$_dir = array();
            return TRUE;
        }
        
        
        function rename( $oldName, $newName ) {
            $con = self::_get_con( $oldName );
            
            if (!is_resource( $con ))
                return FALSE;
            
            $contextOld = self::_context( $oldName );
            $contextNew = self::_context( $newName );
            
            if ($contextOld['type'] != $contextNew['type']) {
                trigger_error("Rename failed: tried to rename a $contextOld[type] into a $contextNew[type]!", E_WARNING);
                return FALSE;
            }
            
            switch ( $contextNew['type'] ) {

                case 'table':
                    $sql = "RENAME TABLE $contextOld[name] TO $contextNew[name]";
                    
                    $result = @mysql_query( $sql, $con );
                    
                    if (!$result)
                        trigger_error( "mysql_error: " . mysql_error( $con ), E_WARNING );
                    return !$result ? FALSE : TRUE;
                    break;
                    
                default:
                    trigger_error("Context $contextNew[type] cannot be renamed through this wrapper!", E_WARNING);
                    return FALSE;
                    break;
            }
        }
        
        function rmdir( $dirName ) {
            return FALSE;
        }
        
        // streams
        function stream_open( $url, $mode, $options, $opened_path ) {
        
            if (!in_array( $mode, array('r', 'r+') ) ) {
                trigger_error("This is a read-only wrapper", E_WARNING);
                return FALSE;
            }
        
            $con = self::_get_con( $url );

            if (!is_resource ($con) ) {
                trigger_con("Cannot obtain a connection to specified path!", E_WARNING);
                return FALSE;
            }
            
            $context = self::_context( $url );
            
            switch ($context['type']) {
                case 'table':

                    switch (TRUE) {
                        case preg_match('/\.([\d]+)\-([\d]+)$/', $context['name'], $matches):
                            
                            $start = $matches[1];
                            $len  = $matches[2];
                            
                            $context['name'] = preg_replace( '/\.([\d]+)\-([\d]+)$/', '', $context['name'] );
                            
                            $sql = "SELECT * FROM $context[name] LIMIT $start,$len";
                            $out = array();
                            
                            $result = @mysql_query( $sql, $con );
                            
                            if (!$result) {
                                trigger_error('mysql_error: ' . mysql_error());
                                return FALSE;
                            }
                            
                            while ($row = mysql_fetch_array( $result, MYSQL_ASSOC ) ) {
                                $out[] = $row;
                            }

                            require_once dirname(__FILE__) . "/../core.OneDB_FileSystem_Wrapper.class/OneDB_FileSystem_Wrapper_JSON.class.php";
                            $dummy = FALSE;
                            $this->_stream = new OneDB_FileSystem_Wrapper_JSON( $out, $dummy );
                            return TRUE;
                            
                            break;
                        case preg_match('/\.count$/', $context['name']):
                            $context['name'] = preg_replace('/\.count$/', '', $context['name'] );
                            $sql = "SELECT COUNT(*) FROM $context[name]";
                            $result = @mysql_query( $sql, $con );
                            if (!$result) {
                                trigger_error("mysql_error: " . mysql_error( $con ), E_WARNING );
                                return FALSE;
                            }
                            list( $count ) = mysql_fetch_row( $result );
                            require_once dirname(__FILE__) . "/../core.OneDB_FileSystem_Wrapper.class/OneDB_FileSystem_Wrapper_JSON.class.php";
                            $dummy = FALSE;
                            $out = array(
                                'count' => $count
                            );
                            $this->_stream = new OneDB_FileSystem_Wrapper_JSON( $out, $dummy );
                            return TRUE;
                            
                        case preg_match('/\.table$/', $context['name']):
                            $context['name'] = preg_replace('/\.table$/', '', $context['name'] );
                            
                            $sql = "DESCRIBE $context[name]";
                            $result = @mysql_query( $sql, $con );
                            
                            if (!$result) {
                                trigger_error("mysql_error: " . mysql_error( $con ), E_WARNING );
                                return FALSE;
                            }
                            
                            $out = array(
                            );
                            
                            while ($row = mysql_fetch_array( $result, MYSQL_ASSOC ))
                                $out[] = $row;
                            
                            require_once dirname(__FILE__) . "/../core.OneDB_FileSystem_Wrapper.class/OneDB_FileSystem_Wrapper_JSON.class.php";
                            $dummy = FALSE;
                            
                            $this->_stream = new OneDB_FileSystem_Wrapper_JSON( $out, $dummy );
                            return TRUE;
                            
                            break;
                            
                        case preg_match('/\.sql$/', $context['name']):
                            
                            $context_name = preg_replace( '/\.sql$/', '', $context['name'] );
                            
                            $cmdLine = trim( `/usr/bin/which mysqldump` ) . ' ';
                            
                            $info = parse_url( $url );
                            
                            if (isset( $info['user'] ) )
                                $cmdLine .= escapeshellarg( "--user=" . $info['user'] ) . ' ';
                                
                            if (isset( $info['pass'] ) )
                                $cmdLine .= escapeshellarg( "--pass=" . $info['pass'] ) . ' ';
                            
                            if (isset( $info['host'] ) )
                                $cmdLine .= escapeshellarg( "--host=" . $info['host'] ) . ' ';
                            
                            if (isset( $info['port'] ) )
                                $cmdLine .= escapeshellarg( "--port=" . $info['port'] ) . ' ';
                            
                            @list( $dbName, $tableName ) = explode( '.', $context['name'] );
                            
                            if (empty( $dbName ) || empty( $tableName ) ) {
                                trigger_error("Could not determine database name and table!", E_WARNING);
                                return FALSE;
                            }
                            
                            $cmdLine .= escapeshellarg( $dbName ) . ' ';
                            $cmdLine .= escapeshellarg( $tableName ) . ' ';
                            
                            require_once dirname(__FILE__) . "/../core.OneDB_FileSystem_Wrapper.class/OneDB_FileSystem_Wrapper_Process.class.php";
                            
                            $this->_stream = new OneDB_FileSystem_Wrapper_Process( $cmdLine );
                            
                            return TRUE;
                            
                            break;
                            
                        default: return FALSE;
                    }
                    
                    break;
                
                case 'trigger':
                    
                    @list( $db_name, $trigger_name ) = explode( '.', $context['name'] );
                    
                    if (empty( $db_name ) || empty( $trigger_name )) {
                        trigger_error("Could not determine trigger path!");
                        return FALSE;
                    }
                    
                    $sql = "SHOW CREATE TRIGGER " . $context['name'];
                    
                    $result = @mysql_query( $sql, $con );
                    
                    if (!$result) {
                        trigger_error("mysql_error: " . mysql_error($con), E_WARNING );
                        return FALSE;
                    }
                    
                    $row = mysql_fetch_array( $result, MYSQL_ASSOC );
                    
                    require_once dirname(__FILE__) . "/../core.OneDB_FileSystem_Wrapper.class/OneDB_FileSystem_Wrapper_String.class.php";

                    $dummy = NULL;

                    $this->_stream = new OneDB_FileSystem_Wrapper_String( $row['SQL Original Statement'], $dummy );
                    
                    return TRUE;
                    
                    break;
                
                case 'dump':
                    $context_name = preg_replace( '/\.sql$/', '', $context['name'] );
                    
                    $cmdLine = trim( `/usr/bin/which mysqldump` ) . ' ';
                    
                    $info = parse_url( $url );
                    
                    if (isset( $info['user'] ) )
                        $cmdLine .= escapeshellarg( "--user=" . $info['user'] ) . ' ';
                    
                    if (isset( $info['pass'] ) )
                        $cmdLine .= escapeshellarg( "--pass=" . $info['pass'] ) . ' ';
                    
                    if (isset( $info['host'] ) )
                        $cmdLine .= escapeshellarg( "--host=" . $info['host'] ) . ' ';
                    
                    if (isset( $info['port'] ) )
                        $cmdLine .= escapeshellarg( "--port=" . $info['port'] ) . ' ';
                    
                    $dbName = $context['name'];
                    
                    if (empty( $dbName )) {
                        trigger_error("Could not determine database name and table!", E_WARNING);
                            return FALSE;
                    }
                    
                    $cmdLine .= escapeshellarg( $dbName );
                    
                    require_once dirname(__FILE__) . "/../core.OneDB_FileSystem_Wrapper.class/OneDB_FileSystem_Wrapper_Process.class.php";
                    
                    $this->_stream = new OneDB_FileSystem_Wrapper_Process( $cmdLine );
                    
                    return TRUE;
                
                default: 
                    trigger_error("Invalid context: $context[type]", E_WARNING );
                    return FALSE;
                    break;
            }
        }

        function stream_close( ) {
            return $this->_stream->close();
        }

        function stream_read( $count ) {
            return $this->_stream->read( $count );
        }

        function stream_write( $data ) {
            return $this->_stream->write( $data );
        }

        function stream_eof( ) {
            return $this->_stream->eof( );
        }

        function stream_tell( ) {
            return $this->_stream->tell( );
        }

        function stream_seek( $offset, $whence = SEEK_SET ) {
            return $this->_stream->seek( $offset, $whence );
        }

        function unlink( $path ) {
            return FALSE;
        }

        
        function url_stat( $url, $flags = STREAM_URL_STAT_LINK ) {
        
            $con = self::_get_con( $url );

            if (!is_resource ($con) ) {
                trigger_con("Cannot obtain a connection to specified path!", E_WARNING);
                return FALSE;
            }
            
            $context = self::_context( $url );
            
            switch ($context['type']) {
                case 'server':
                    return stat( sys_get_temp_dir() );
                    break;
                
                case 'database':
                    $sql = "SHOW TABLES FROM " . $context['name'];
                    $result = @mysql_query( $sql, $con );
                    
                    if (!$result) {
                        trigger_error( 'mysql_error: ' . mysql_error( $con ) );
                        return FALSE;
                    }
                    
                    while ($row = mysql_fetch_row( $result )) {}
                    
                    return stat( sys_get_temp_dir() );
                    break;
                

                case 'table':

                    switch (TRUE) {
                        case preg_match('/\.([\d]+)\-([\d]+)$/', $context['name'], $matches):
                            
                            $start = $matches[1];
                            $len  = $matches[2];
                            
                            $context['name'] = preg_replace( '/\.([\d]+)\-([\d]+)$/', '', $context['name'] );
                            
                            $sql = "SELECT * FROM $context[name] LIMIT $start,$len";
                            $out = array();
                            
                            $result = @mysql_query( $sql, $con );
                            
                            if (!$result) {
                                trigger_error('mysql_error: ' . mysql_error());
                                return FALSE;
                            }
                            
                            while ($row = mysql_fetch_array( $result, MYSQL_ASSOC ) ) {
                                $out[] = $row;
                            }

                            require_once dirname(__FILE__) . "/../core.OneDB_FileSystem_Wrapper.class/OneDB_FileSystem_Wrapper_JSON.class.php";
                            $dummy = FALSE;
                            $stream = new OneDB_FileSystem_Wrapper_JSON( $out, $dummy );
                            return $stream->stat();
                            
                            break;
                        case preg_match('/\.count$/', $context['name']):
                            $context['name'] = preg_replace('/\.count$/', '', $context['name'] );
                            $sql = "SELECT COUNT(*) FROM $context[name]";
                            $result = @mysql_query( $sql, $con );
                            
                            if (!$result) {
                                trigger_error("mysql_error: " . mysql_error( $con ), E_WARNING );
                                return FALSE;
                            }
                            list( $count ) = @mysql_fetch_row( $result );
                            require_once dirname(__FILE__) . "/../core.OneDB_FileSystem_Wrapper.class/OneDB_FileSystem_Wrapper_JSON.class.php";
                            $dummy = FALSE;
                            $out = array(
                                'count' => $count
                            );
                            $stream = new OneDB_FileSystem_Wrapper_JSON( $out, $dummy );
                            return $stream->stat();
                            break;
                            
                        case preg_match('/\.table$/', $context['name']):
                            $context['name'] = preg_replace('/\.table$/', '', $context['name'] );
                            
                            $sql = "DESCRIBE $context[name]";
                            $result = @mysql_query( $sql, $con );
                            
                            if (!$result) {
                                trigger_error("mysql_error: " . mysql_error( $con ), E_WARNING );
                                return FALSE;
                            }
                            
                            $out = array(
                            );
                            
                            while ($row = mysql_fetch_array( $result, MYSQL_ASSOC ))
                                $out[] = $row;
                            
                            require_once dirname(__FILE__) . "/../core.OneDB_FileSystem_Wrapper.class/OneDB_FileSystem_Wrapper_JSON.class.php";
                            $dummy = FALSE;
                            
                            $stream = new OneDB_FileSystem_Wrapper_JSON( $out, $dummy );
                            return $stream->stat();
                            
                            break;
                            
                        case preg_match('/\.sql$/', $context['name']):
                            
                            $context_name = preg_replace( '/\.sql$/', '', $context['name'] );
                            
                            $cmdLine = trim( `/usr/bin/which mysqldump` ) . ' ';
                            
                            $info = parse_url( $url );
                            
                            if (isset( $info['user'] ) )
                                $cmdLine .= escapeshellarg( "--user=" . $info['user'] ) . ' ';
                                
                            if (isset( $info['pass'] ) )
                                $cmdLine .= escapeshellarg( "--pass=" . $info['pass'] ) . ' ';
                            
                            if (isset( $info['host'] ) )
                                $cmdLine .= escapeshellarg( "--host=" . $info['host'] ) . ' ';
                            
                            if (isset( $info['port'] ) )
                                $cmdLine .= escapeshellarg( "--port=" . $info['port'] ) . ' ';
                            
                            @list( $dbName, $tableName ) = explode( '.', $context['name'] );
                            
                            if (empty( $dbName ) || empty( $tableName ) ) {
                                trigger_error("Could not determine database name and table!", E_WARNING);
                                return FALSE;
                            }
                            
                            $cmdLine .= escapeshellarg( $dbName ) . ' ';
                            $cmdLine .= escapeshellarg( $tableName ) . ' ';
                            
                            require_once dirname(__FILE__) . "/../core.OneDB_FileSystem_Wrapper.class/OneDB_FileSystem_Wrapper_Process.class.php";
                            
                            $stream = new OneDB_FileSystem_Wrapper_Process( $cmdLine );
                            return $stream->stat();
                            
                            break;
                            
                        default: return FALSE;
                    }
                    
                case 'trigger':
                    
                    @list( $db_name, $trigger_name ) = explode( '.', $context['name'] );
                    
                    if (empty( $db_name )) {
                        trigger_error("Could not determine trigger path!");
                        return FALSE;
                    }
                    
                    if (!empty( $trigger_name )) {
                    
                        $sql = "SHOW CREATE TRIGGER " . $context['name'];
                        
                        $result = @mysql_query( $sql, $con );
                        
                        if (!$result) {
                            trigger_error("mysql_error: " . mysql_error($con), E_WARNING );
                            return FALSE;
                        }
                    
                        $stat = stat( __FILE__ );
                        
                        $row = mysql_fetch_row( $result, MYSQL_ASSOC );
                        
                        $stat['size'] = $stat[ 7 ] = strlen( $row['SQL Original Statement'] );
                        
                        return $stat;
                    
                    } else return stat( sys_get_temp_dir( ) );
                    
                    break;
                
                case 'dump':
                    $dbName = $context['name'];
                    $sql = "SHOW TABLES FROM " . $dbName;
                    
                    $result = @mysql_query( $sql, $con );

                    if (!$result)
                        return FALSE;
                    
                    while ($row = mysql_fetch_row( $result, MYSQL_ASSOC )) {
                    }
                    
                    $stat = stat( __FILE__ );
                    
                    return $stat;
                    
                    break;
                
                default:
                    return FALSE;
                    break;
            }
        }

        function __destruct() {
        }
    }

    if (!stream_wrapper_register('mysql', 'OneDB_MySQL_Wrapper', STREAM_IS_URL))
        throw new Exception("Could not register protocol mysql!");

    //echo var_dump( stat("mysql://root:traktopel@localhost/jsplatform/admin_auth.0-100") );

?>