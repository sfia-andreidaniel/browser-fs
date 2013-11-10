<?php

    /* Class: OneDB_JSONCollection
       This class is intend to integrate webservices from all across the web,
       into OneDB, and to treat those as part of OneDB filesystem.
       
       @author : sfia.andreidaniel@gmail.com
       @purpose: The purpose of a OneDB_JSONCollection, is to emulate a MongoDB Collection,
                 excepting that results are took from a URL, and not from a real
                 MongoDB database.
       
       Sorting, Filtering, and other syntaxes are implemented on this collection in MongoDB style,
       but it's initialization parameters are NOT the ones from a mongoDB collection.
       
     */
     
    // OneDB functions
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "OneDB.inc.php";


    /* If running this class from command line, you should be interesetd to see
       some debug info on screen. So please uncomment the line above */
    //define("ONEDB_JSON_COLLECTION_DEBUG", 1);
    
    class OneDB_JSONCollection {
        
        protected $_url   = NULL;
        protected $_ttl   = NULL;
        protected $_items = NULL;
        protected $_cfg   = array();
        
        private   $_cache = NULL;
        private   $_file  = NULL;
        
        private   $_dataset = array();
        private   $_offset  = 0;
        
        public    $db = NULL;
        
        /* Url is the URL of the JSON collection */

        public function __construct( $url = NULL, $ttl = NULL, array $config = NULL) {
        
            $this->_debug("url: ", $url, "ttl: ", $ttl);
        
            $this->_url = $url;
            $this->_ttl = $ttl;
            $this->_cfg = $config;
            
            /* If some of the class arguments are not initialized, return an empty result set! */
            if ($url === NULL ||
                $ttl === NULL ||
                $config === NULL
            ) {
                $this->_items = array();
                $this->reset();
                return;
            }

            if (!isset( $config["collectionID"] ))
                throw new Exception("OneDB_JSONCollection::__construct(): No collection ID was specified. This is usefull to auto fill the _parent property of the results!");
        
            $this->_cache = dirname(__FILE__) . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR . "_json" . DIRECTORY_SEPARATOR;

            if (!is_writeable( $this->_cache ))
                throw new Exception("Data JSON cache directory is not writeable!");
            
            /* Remove bad characters from the file, and also .. sequences */
            $this->_file = $this->_cache . DIRECTORY_SEPARATOR . 
                           str_replace('..', '.', preg_replace('/[^a-z0-9\.\-\_]+/', '-', $url) . '-'.md5( json_encode( $config ) ) . ".cache" );
            
            if (file_exists( $this->_file )) {
                $this->_debug("File: ", $this->_file, " exists. Attempting to load JSON from it");
                /* Determine file age, compare this to the $ttl. */
                $fhandle = @fopen( $this->_file, 'r' );

                if (!is_resource( $fhandle ))
                    throw new Exception("Could not open for reading the JSONCollection file!");
                
                $cacheAge = fgets( $fhandle );
                
                if (time() - $cacheAge > $ttl) {
                    $this->_debug( "Cache file expired" );
                    fclose( $fhandle );
                } else {
                    $_json = "";
                    /* Read the ws json in memory and decode it */
                    while (!feof( $fhandle )) {
                        $chunk = fread( $fhandle, 8192 );
                        $_json .= $chunk;
                    }
                    fclose( $fhandle );

                    $this->_items = @json_decode( $_json, TRUE );
                    
                    $age = time() - $cacheAge;
                    $expires = $ttl - $age;

                    if (!is_array( $this->_items )) {
                        trigger_error( "OneDB_JsonCollection::results took from cache file $this->_file could not be deserialized as JSON. Cache ignored!", E_USER_NOTICE );
                    } else {
                        for ($i=0, $len=count($this->_items); $i<$len; $i++) {
                            $this->_items[$i]["_cache"] = array(
                                "age" => $age,
                                "expires" => $expires
                            );
                        }
                    }
                }
            }
            
            if (!is_array( $this->_items )) {
                $this->_debug("Cache file did not returned results, refreshing it" );
                $this->refresh();
            } else
                $this->_debug("Results were loaded from cache");
        }
        
        public function setDB( &$db ) {
            $this->_db = $db;
        }
        
        private function _debug( ) {
            if (!defined("ONEDB_JSON_COLLECTION_DEBUG"))
                return;
            $args = func_get_args();
            echo "Debug: ";
            for ($i=0, $len=count($args); $i<$len; $i++)
                echo $args[$i], " ";
            echo "\n";
        }
        
        private function ensureIndexes() {
            /* Step 1. Ensure that retults have names in ascii charset, and
             *         that resuts have names with only allowed characters.
             * We are assuming that the webservice is using UTF-8 charset
             */
             
            for ($i=0, $len=count($this->_items); $i<$len; $i++) {
                $this->_items[$i]['title'] = isset( $this->_items[$i]['title'] )
                    ? $this->_items[$i]['title']
                    : $this->_items[$i]['name'];
                
                //echo $this->_items[$i]['title'],"\n";
                
                $this->_items[$i]['name'] = trim(
                    preg_replace('/[\s]+/', ' ',
                        preg_replace(
                            '/[^a-z0-9\-\_\.]/i', ' ',
                            $converted = OneDB_toAscii( $this->_items[$i]['name'] )
                        )
                    )
                );
                
            }
            
            /* Step 2. Ensure that results have unique names. If collection names
               have non-unique ["name"]'s, then add a suffix to them.
               
               NOTE: The algorithm for ensuring unique name unicity is SLOW,
                     so if you have large JSON datasets, you should implement
                     a faster one.
                     
                     We are expecting decent JSON results datasets from webserver
             */
            
            $suffix = 0;
            
            $len = count($this->_items);
            
            for ($i=0; $i < $len; $i++) {
                for ($j = $i + 1; $j < $len; $j++) {
                    if ($this->_items[$j]['name'] == $this->_items[$i]['name']) {
                        $this->_items[$j]['name'] .= "-" . ( ++$suffix );
                    }
                }
            }
        }
        
        private function getWebserviceOwner() {
            $info = parse_url( $this->_url );
            return "Webservice/JSON/" . @$info['host'];
        }
        
        private function makeQueryString( $mixed ) {
            switch (TRUE) {
                case is_string( $mixed ):
                    return $mixed;
                    break;
                case is_array( $mixed ):
                    return http_build_query( OneDB_JsonModifier( $mixed ) );
                    break;
                default:
                    throw new Exception("Could not build query string from variable");
                    break;
            }
        }
        
        public function asyncRefresh() {
            
            header("X-OneDB-JSONWebserviceCategory-Daemon: started");
            
            $queryString = '';
            
            if ( isset( $this->_cfg['get'] ) )
                $queryString = $this->makeQueryString( $this->_cfg['get'] );
            
            $config = array(
                'url' => $this->_url . '?' . $queryString
            );
            
            if (
                in_array( "post", array_keys( $this->_cfg )) &&
                is_array( $this->_cfg["post"] ) && 
                count($this->_cfg["post"])
            ) {
                $config['post'] = OneDB_JsonModifier( $this->_cfg["post"] );
            }
            
            if (
                isset( $this->_cfg["auth"] ) && !empty( $this->_cfg["auth"] ) && is_string( $this->_cfg["auth"] )
            ) $config['auth'] = $this->_cfg['auth'];
            
            file_put_contents( $this->_file . '.lock', json_encode( $config ) );
            
            exec( '/usr/bin/screen -d -m /usr/bin/php ' . escapeshellarg( dirname(__FILE__) . '/components/webservice/async-daemon.php' ) . ' ' . escapeshellarg( $this->_file . '.lock' ) );
        }
        
        public function refresh() {
        
            $fakeRefresh = FALSE;
        
            if ( file_exists( $this->_file . '.async' ) /* && ( time() - filemtime( $this->_file . '.async' ) < ( 2 * ( $this->_ttl ? $this->_ttl : 3600 ) ) ) */ ) {
                // Webservice has been downloaded before.
                // We're returning the webservice download result for speed considerations.
                // If we're not finding a .lock file, we're also starting an async curl downloader.
                
                $buffer = file_get_contents( $this->_file . '.async' );
                
                if ( !file_exists( $this->_file . '.unlock' ) ) {
                
                    $fakeRefresh = TRUE;

                    if ( !file_exists( $this->_file . '.lock' ) ) {
                        $this->asyncRefresh();
                    }
                
                } else {
                    @unlink( $this->_file . '.unlock' );
                }
                
                header("X-OneDB-JSONWebserviceCategory: asynchronous-content");
                
            } else {
        
                $ch = curl_init();
            
                $queryString = "";
                
                if (isset( $this->_cfg["get"] ))
                    $queryString = $this->makeQueryString( $this->_cfg["get"] );
                
                curl_setopt( $ch, CURLOPT_URL, $fetchURL = ( $this->_url . "?$queryString" ) );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
                
                //throw new Exception( $fetchURL );
                
                if (
                    in_array( "post", array_keys( $this->_cfg )) &&
                    is_array( $this->_cfg["post"] ) && 
                    count($this->_cfg["post"])
                ) {
                    curl_setopt( $ch, CURLOPT_POSTFIELDS, OneDB_JsonModifier( $this->_cfg["post"] ) );
                    curl_setopt( $ch, CURLOPT_POST, 1 );
                }
                
                curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
                curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE );
                
                if (isset( $this->_cfg["auth"] ) && !empty( $this->_cfg["auth"] ) && is_string( $this->_cfg["auth"] ))
                    curl_setopt( $ch, CURLOPT_USERPWD, $this->_cfg["auth"] );
                
                $buffer = curl_exec( $ch );
            
            }
            
            if ($buffer === NULL || $buffer === FALSE || !strlen( $buffer )) {
                //trigger_error( "OneDB_JsonCollection::refresh() : Received no data from url: $fetchURL" );
                $this->_items = array();
            } else {
                
                $this->_items = @json_decode( $buffer, TRUE );
                
                if (!is_array( $this->_items )) {
                    $this->_items = NULL;
                    $buffer = trim("$buffer");
                    throw new Exception("Webservice '$this->_url' did not returned an array!\n$buffer");
                } else {

                    file_put_contents( $this->_file . '.async', $buffer );

                    $now = time();
                    
                    $wsOwner = $this->getWebserviceOwner();
                    
                    //Apply some meta to items
                    for ($i=0, $len=count($this->_items); $i<$len; $i++) {
                    
                        $objectHash = md5( json_encode( $this->_items[$i] ) );
                    
                        $this->_items[$i] = is_array( $this->_items[$i] ) ? $this->_items[$i] : array(
                            "value" => $this->_items[$i]
                        );
                        
                        $this->_items[$i][".collection"] = array(
                            "index" => $i
                        );
                        
                        if (!isset( $this->_items[$i]["date"] ) || !is_numeric( $this->_items[$i]["date"] ))
                            $this->_items[$i]["date"] = time();

                        $this->_items[$i]["name"] = isset( $this->_items[$i]["name"] ) ? $this->_items[$i]["name"] : "Item " . ($i+1);
                        $this->_items[$i]["owner"] = $wsOwner;
                        
                        if (!isset( $this->_items[$i]["online"] ))
                            $this->_items[$i]["online"] = TRUE;

                        $this->_items[$i]["tags"] = isset( $this->_items[$i]["tags"] ) ? $this->_items[$i]["tags"] : array( "webservice" );
                        $this->_items[$i]["keywords"] = isset($this->_items[$i]["keywords"]) ? $this->_items[$i]["keywords"] : array();
                        
                        if (!isset( $this->_items[$i]['type']) || 
                            ( !isset( $this->_items[$i]['overrideType'] ) )
                        ) $this->_items[$i]["type"] = "JSONObject";
                        
                        $this->_items[$i]["_parent"] = "{$this->_cfg['collectionID']}";

                        $this->_items[$i]["_id"] = new OneDB_FAKE_MongoId(
                            $this->_cfg["collectionID"] . "",
                            $objectHash
                        );
                        
                        $this->_items[$i]["_id"] .= '';
                    }
                    
                    /* Apply collection indexes */
                    $this->ensureIndexes();
                    
                    // We do not store results if buffer was obtained in async mode
                    if (!$fakeRefresh ) {
                        /* Ok, we have the results, we can now store them to cache */
                        if ( @file_put_contents( $this->_file, time() . "\n" . json_encode( $this->_items ) ) === FALSE) {
                            trigger_error( "OneDB_JsonCollection::refresh() : could not save JSON to cache file $this->_file" );
                        } else
                            $this->_debug("Stored results to cache");
                    }
                }
            }
        }
        
        public function reset() {
            $this->_dataset = $this->_items;
            $this->_offset = 0;
            $this->_debug("Reset");
        }
        
        public function item( $itemIndex ) {
            if ($itemIndex < 0 || $itemIndex >= count( $this->_dataset ))
                throw new Exception("Item out of range: !(0 >= $itemIndex < " . count( $this->_dataset ) . ")");
            return $this->_dataset[ $itemIndex ];
        }
        
        public function all() {
            return $this->_dataset;
        }
        
        private function returnKeys( $item, $keys ) {
            if (!is_array( $keys ) || !count( $keys )) {
                return $item;
            }
            
            $out = $item;
            
            $itemKeys = array_keys( $item );
            
            foreach (array_keys( $keys ) as $key) {
                if ($keys[ $key ])
                    $out[ $key ] = in_array( $key, $itemKeys ) ? $item[ $key ] : NULL;
                else
                    if (isset($out[ $key ]))
                        unset( $out[ $key ] );
            }
            
            return $out;
        }
        
        private function compare( $value, $with ) {
            if (is_scalar( $with ))
                return $value == $with;
            
            if (is_array( $with )) {
                /* Process all rules */
                $operators = array_keys( $with );

                foreach ($operators as $operator) {
                    $cmp = $with["$operator"].'';
                    switch (TRUE) {
                    
                        case $operator == '$eq':
                        case $operator == '=':
                        case $operator == '==':
                            if ( !count( array_intersect( (array)$value, (array)$cmp ) ) )
                                return FALSE;
                            break;
                            
                        case $operator == '$neq':
                        case $operator == '<>':
                        case $operator == '!=':
                            if ( $value == $cmp)
                                return FALSE;
                            break;
                                
                        case $operator == '$gt':
                        case $operator == '>':
                            if ( $value <= $cmp )
                                return FALSE;
                            break;
                                
                        case $operator == '$gte':
                        case $operator == '>=':
                            if ($value < $cmp)
                                return FALSE;
                            break;
                        
                        case $operator == '$lt':
                        case $operator == '<':
                            if ($value >= $cmp)
                                return FALSE;
                            break;
                        
                        case $operator == '$lte':
                        case $operator == '<=':
                            if ($value < $cmp)
                                return FALSE;
                        
                        case $operator == '$in':
                            if (!is_array( $cmp ))
                                return FALSE;
                            $found = FALSE;
                            foreach ($cmp as $eq)
                                if ("$eq" == "$value") {
                                    $found = TRUE;
                                    break;
                                }
                            if (!$found)
                                return FALSE;
                            break;
                        
                        default:
                            throw new Exception("Invalid operator: $operator");
                            break;
                    }
                }
                return TRUE;
            }
            
            $withClass = get_class( $with );
            
            switch ($withClass) {
                case 'MongoRegex':
                    return preg_match( "$with", "$value" ) ? TRUE : FALSE;
                    break;
                case 'MongoId':
                case 'OneDB_FAKE_MongoId':
                    return strtolower( "$with" ) == strtolower( "$value" );
                    break;
                default:
                    $this->_debug("Only MongoRegex and MongoId classes are allowed on filtering, but found class $withClass");
                    return FALSE;
                    break;
            }
            
            return FALSE;
        }
        
        private function matchFilter( $item, $filter ) {
            if (!is_array( $filter ) || !count($filter))
                return TRUE;
            
            $values     = array();
            $itemKeys   = array_keys( $item );
            
            foreach ( array_keys( $filter ) as $filterKey ) {
                $value = in_array( $filterKey, $itemKeys ) ? $item[ $filterKey ] : NULL;
                if (!$this->compare( $value, $filter[ $filterKey ] ))
                    return FALSE;
            }
            
            return TRUE;
        }
        
        public function find( $filter = NULL, $fields = array() ) {
            
            if (!is_array( $fields ))
                throw new Exception("Second argument should be an array!");
            
            $this->reset();
            
            $tmp = array();
            
            for ($i = 0, $len=count($this->_dataset); $i<$len; $i++) {
                if ( $this->matchFilter( $this->_dataset[ $i ], $filter ) )
                    $tmp[] =  $this->returnKeys( $this->_dataset[ $i ], $fields );
            }
            
            $this->_dataset = &$tmp;
            $this->_offset = 0;
            
            return $this;
        }
        
        public function skip( $howMany ) {
            $this->_dataset = array_slice( $this->_dataset, $howMany );
            $this->_offset = 0;
            return $this;
        }
        
        public function limit( $howMany ) {
            $this->_dataset = array_slice( $this->_dataset, 0, $howMany );
            $this->_offset = 0;
            return $this;
        }
        
        public function sort() {
            $this->_debug("Sorting is not implemented on OneDB_JSONCollection");
            $this->_offset = 0;
            return $this;
        }
        
        public function hasNext() {
            return $this->_offset < count( $this->_dataset );
        }
        
        public function getNext() {
            return $this->item($this->_offset++ );
        }
        
        public function findOne( $filter = NULL, $fields = NULL ) {
            $this->find( $filter, $fields );
            return count( $this->_dataset ) ? reset( $this->_dataset ) : NULL;
        }
        
        public function __call( $methodName, $arguments ) {
            throw new Exception("OneDB_JSONCollection::$methodName(): ERR_NOT_IMPLEMENTED");
        }
        
        public function length() {
            return count( $this->_dataset );
        }
        
        public function jsonLength() {
            return count( $this->_items );
        }
        
        public function __toString() {
            return "OneDB.JSONCollection(" . $this->_url . ")";
        }
    }

    /* The OneDB_FAKE_MongoID is a fake class, implementing
       the MongoId class, in order to privide unique _id identifiers
       of JSON items stored into a OneDB_JSONCollection.
       
       We're not using this class anywhere, excepting here.
       
     */
     
    function OneDB_EnsureFakeHashIsUnique( $str ) {
        static $hashes = array();
        
        $pad = 0;
        $padStr = "0000";
        
        while (in_array( "$str$padStr" , $hashes )) {
            $pad++;
            $padStr = str_pad("$pad", 4, '0', STR_PAD_LEFT);
        }
        
        $hashes[] = "$str$padStr";
        
        return "$str$padStr";
    }

    class OneDB_FAKE_MongoId extends MongoId {
        
        public function __construct( $collectionHashID, $index ) {
            if ($collectionHashID === NULL || !strlen($collectionHashID) )
                throw new Exception("OneDB_FAKE_MongoId: NULL identifiers are not allowed!");
            
            $this->{'$id'} = $collectionHashID . OneDB_EnsureFakeHashIsUnique( $index );
        }
        
        public function __toString() {
            return $this->{'$id'};
        }
        
    };


    /*
    
    // TEST DRIVE SECTION. UNCOMMENT THESE LINES IN ORDER TO TEST CLASS FROM RIGHT THIS FILE.
    
    define("ONEDB_JSON_COLLECTION_DEBUG", 1);
    
    $my = new OneDB_JsonCollection(
        "http://www.rcs-rds.ro/external/epg/channel-data/?channel_id=449&time_start=1333166430&time_stop=1333252829",
        10,
        array(
            "collectionID" => "NULL",
        )
    );
    
    print_r( 
        $my->find( 
                array(
                    "channel_id" => array(
                        '$gte' => 449
                    ),
                    "name" => new MongoRegex( '/^fotbal/i' ),
                    "description" => array(
                        '$eq' => "Champions League",
                        '$neq'=> "ASD"
                    )
                ),
                array(
                    "channel_id" => TRUE,
                    "name" => TRUE,
                    "description" => TRUE,
                    "logo_url" => TRUE,
                    "_id" => TRUE
                )
        )->limit(2)->length()
    );
    
    */

?>