<?php

    /**
     * Name        : OneDB.inc.php
     * Description : Various functions required by the OneDB
     * Author      : sfia.andreidaniel@targujiu.rcs-rds.ro
     **/


    /* JSON Functions */

    $__ONEDB_JSON_FUNCTIONS__ = array(
        "now" => function() {
            return time();
        },
        "today" => function() {
            return mktime( 0, 0, 0 );
        },
        "this morning" => function() {
            if (@date( 'H', time() ) > 7)
                return mktime( 7, 0, 0 ); //today at 7 o clock
            else
                return mktime( 7, 0, 0 ) - 86400;
        },
        "yesterday" => function() { //yesterday at 0:00
            return mktime( 0, 0, 0 ) - 86400;
        },
        "yesterday morning" => function() { //yesterday at 7 o clock
            if (@date( 'H', time() ) > 7)
                return mktime( 7, 0, 0 ) - 86400;
            else
                return mktime( 7, 0, 0 ) - ( 86400 * 2 );
        },
        "tomorrow morning" => function() {
            if (@date('H', time() ) > 7)
                return mktime( 7, 0, 0 ) + 86400;
            else
                return mktime( 7, 0, 0 );
        },
        "day after tomorrow morning" => function() {
            if (@date('H', time() ) > 7)
                return mktime( 7, 0, 0 ) + 172800;
            else
                return mktime( 7, 0, 0 ) + 86400;
        },
        "after one day" => function() {
            return time() + 86400;
        },
        "this week" => function() {
            return mktime( 0, 0, 0 ) - ( @date("N") - 1 ) * 86400;
        },
        "this month" => function() {
            return mktime( 0, 0, 0 ) - ( @date("j") - 1 ) * 86400;
        },
        "one day ago" => function() {
            return time() - 86400;
        },
        "one week ago" => function() {
            return time() - 7 * 86400;
        },
        "one month ago" => function() {
            return time() - 31 * 86400;
        }
    );

    /* OneDB Backend File->New foreign entries */
    
    $__OneDB_Backend_Objects__ = array(
        
    );

    function OneDB_RegisterBackendObject( $objectConfig ) {
        global $__OneDB_Backend_Objects__;
        
        if (!is_array( $objectConfig ))
            throw new Exception("OneDB_RegisterBackendObject:: Could not register object, expected array!");
        
        if (!isset( $objectConfig['backendName'] ) )
            throw new Exception("OneDB_RegisterBackendObject:: objectConfig[backendName] not defined!");

        if (!isset( $objectConfig['docType'] ) )
            throw new Exception("OneDB_RegisterBackendObject:: objectConfig[docType] not defined!");

        if (!isset( $objectConfig['icon'] ) )
            throw new Exception("OneDB_RegisterBackendObject:: objectConfig[icon] not defined!");
        
        $__OneDB_Backend_Objects__[ "$objectConfig[docType]" ] = $objectConfig;
    }

    /**
     * Adds a custom OneDB JSON Modifier function ... 
     **/
    function OneDB_AddJSONFunction( $funcName, $funcClosure ) {
        global $__ONEDB_JSON_FUNCTIONS__;
        
        if (isset($__ONEDB_JSON_FUNCTIONS__[ $funcName ]))
            throw new Exception("Could not add function $funcName because it is allready defined!");
        
        if (!is_callable( $funcClosure ))
            throw new Exception("Function $funcName could not be added because it is not callable!");
        
        $__ONEDB_JSON_FUNCTIONS__[ $funcName ] = $funcClosure;
    }

    /**
     * This is a hardcoded ascii table, that is used to manually convert
     * special characters to their ascii corresponding ones. Use this
     * if OneDB_toAscii() does not properly convert chars 
     **/
    $__ONEDB_ASCII_TABLE__ = array(
        '/ä|æ|ǽ/' => 'ae',
        '/ö|œ/' => 'oe',
        '/ü/' => 'ue',
        '/Ä/' => 'Ae',
        '/Ü/' => 'Ue',
        '/Ö/' => 'Oe',
        '/À|Á|Â|Ã|Ä|Å|Ǻ|Ā|Ă|Ą|Ǎ/' => 'A',
        '/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª/' => 'a',
        '/Ç|Ć|Ĉ|Ċ|Č/' => 'C',
        '/ç|ć|ĉ|ċ|č/' => 'c',
        '/Ð|Ď|Đ/' => 'D',
        '/ð|ď|đ/' => 'd',
        '/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě/' => 'E',
        '/è|é|ê|ë|ē|ĕ|ė|ę|ě/' => 'e',
        '/Ĝ|Ğ|Ġ|Ģ/' => 'G',
        '/ĝ|ğ|ġ|ģ/' => 'g',
        '/Ĥ|Ħ/' => 'H',
        '/ĥ|ħ/' => 'h',
        '/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ/' => 'I',
        '/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı/' => 'i',
        '/Ĵ/' => 'J',
        '/ĵ/' => 'j',
        '/Ķ/' => 'K',
        '/ķ/' => 'k',
        '/Ĺ|Ļ|Ľ|Ŀ|Ł/' => 'L',
        '/ĺ|ļ|ľ|ŀ|ł/' => 'l',
        '/Ñ|Ń|Ņ|Ň/' => 'N',
        '/ñ|ń|ņ|ň|ŉ/' => 'n',
        '/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ/' => 'O',
        '/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º/' => 'o',
        '/Ŕ|Ŗ|Ř/' => 'R',
        '/ŕ|ŗ|ř/' => 'r',
        '/Ś|Ŝ|Ş|Š/' => 'S',
        '/ś|ŝ|ş|š|ſ/' => 's',
        '/Ţ|Ť|Ŧ/' => 'T',
        '/ţ|ť|ŧ/' => 't',
        '/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ/' => 'U',
        '/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ/' => 'u',
        '/Ý|Ÿ|Ŷ/' => 'Y',
        '/ý|ÿ|ŷ/' => 'y',
        '/Ŵ/' => 'W',
        '/ŵ/' => 'w',
        '/Ź|Ż|Ž/' => 'Z',
        '/ź|ż|ž/' => 'z',
        '/Æ|Ǽ/' => 'AE',
        '/ß/'=> 'ss',
        '/Ĳ/' => 'IJ',
        '/ĳ/' => 'ij',
        '/Œ/' => 'OE',
        '/ƒ/' => 'f'
    );
    
    /**
     * Converts a string into a seo-friendly string.
     *
     *  @param $str -- should be an utf8 encoded string
     *  @param $maxLength -- should be the maximum url length
     *  @param $options -- should be an array containing options to be passed
     *         to the function (JSON example):
     *         {
     *            "minWordLength"     : 1,   // Default minimum word length,
     *            "charset"           : NULL // if other than NULL is present, an iconv will be applied
     *                                       // to the string as iconv( $inputCharacterSet, 'ASCII//TRANSLIT', $str )
     *            "maxLength"         : 60   // Max 60 characters in seo URL string
     *         }
     **/
     
    function OneDB_SeoURL( $str, array $options = array() ) {
        
        if (!is_array($options))
            throw new Exception("Bad $options for function OneDB_SeoURL (Expected array at 2nd argument)");
        
        $str = OneDB_toAscii( $str );
        
        $strConv = preg_replace(
            '/[^a-z0-9]/i', 
            '-',
            isset($options['charset']) ? iconv( $options['charset'], 'ASCII//TRANSLIT', $str ) : $str
        );
        
        $words = explode( '-', $strConv );
        
        $minWordLength = isset($options['minWordLength']) ? $options['minWordLength'] : 1;
        $maxLength     = isset($options['maxLength'])     ? $options['maxLength']     : 60;
        
        $outString = "";
        
        $wl = 0; //Current word length
        
        for ($i=0, $len=count($words); $i<$len; $i++) {
            
            // Ignore following steps if word length is less than required 
            // minimum word length and proceed to the following word
            if ( ($wl = strlen($words[$i])) < $minWordLength )
                continue;
            
            // If current word length + the length of the allready formed URL exceeds
            // the maxLength of the url, return the url allready formed here if the 
            // formed URL is not empty
            if ( $wl + strlen( $outString ) > $maxLength) {
                if (strlen($outString))
                    return $outString;
                else 
                    continue;
            }
            
            $outString = ($outString == '') ? $words[$i] : ( $outString . '-' . $words[$i] );
        }
        
        return $outString;
        
    }

    /* Checks to see if @email is a valid representation of an email address */
    function is_email( $email ) {
        return preg_match('|^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]{2,})+$|i', $email);
    }
    
    $_ONEDB_ORDER_ID_ = 0;
    
    /* Generates an automatically unique 32bit integer, based on time + a random number,
       this is used in order to guarantee an automatically number */
    function OneDB_OrderID() {
        global $_ONEDB_ORDER_ID_;
        $_ONEDB_ORDER_ID_++;
        
        $t = (int) ( (time() - 1331233236) . str_pad("$_ONEDB_ORDER_ID_", 4, '0', STR_PAD_LEFT) );
        
        if ($t < 0)
            throw new Exception( "Bad OneDB_OrderID() result!" );
        
        return $t;
    }
    
    /* Removes <?php and ?> tags from the beginning and ending of a php @code */
    function OneDB_EvalStripTags( $code ) {
        $code = preg_replace("/^([\\s]+)?<\?php([\\s]+)/", "", $code );
        $code = preg_replace("/([\\s]+)?\?>([\\s]+)$/", "", $code);
        return $code;
    }


    /* 
    print_r( OneDB_JsonModifier( array(
        "time" => "OneDB( now )",
        "today"=> "OneDB( today )",
        "one week ago" => "OneDB( one week ago )",
        "this week" => "OneDB( this week )",
        "this month" => "OneDB( this month )",
        "recursive" => array(
            "OneDB( now )",
            array(
                "ttt" => "OneDB( this month )"
            )
        )
    ) ) );
    */

    function OneDB_JsonModifier( $arr ) {

        global $__ONEDB_JSON_FUNCTIONS__;

        if (!is_array( $arr ))
            return $arr;

        foreach (array_keys( $arr ) as $key) {
            $value = $arr[$key];
            switch (TRUE) {
                case 
                    is_string( $value ) && 
                    preg_match( "/^OneDB\(([\s]+)?([\S\s]+)([\s]+)?\)$/", $value, $matches ):
                    
                    $functionName = trim($matches[2]);
                    
                    if (isset($__ONEDB_JSON_FUNCTIONS__[ $functionName ])) {
                        $arr[ $key ] = $__ONEDB_JSON_FUNCTIONS__[ $functionName ]();
                    } else {

                        //Try to auto-load the function
                        if (function_exists( $functionName )) {
                             $arr[ $key ] = $functionName();
                        } else {
                            if (file_exists( $includeFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR .
                                "core.OneDB_JSONModifier.inc" . DIRECTORY_SEPARATOR . "$functionName.inc.php"
                                )
                            ) {
                                require_once $includeFile;

                                if (function_exists( $functionName )) {
                                    $arr[ $key ] = $functionName();

                                } else
                                    throw new Exception("OneDB_jsonModifier:: Function '$functionName' is not defined (auto-load file found, but no function detected)");
                            } else
                                throw new Exception("OneDB_jsonModifier:: Function '$functionName' is not defined (auto-load file not found)");
                        }
                    }
                    
                    break;
                case is_array( $arr[ $key ] ):
                    $arr[ $key ] = OneDB_jsonModifier( $arr[ $key ] );
                    break;
            }
        }

        return $arr;
    }
    
    /* Compiles a php code stored in a string @phpCode, and returns TRUE if no syntax errors
       were found, otherwise returns compiler output errors */
    
    function OneDB_PHPCompiler( $phpCode ) {
        $php = trim(`which php`);
        
        $tmp = sys_get_temp_dir();
        
        if ( file_put_contents( $diskFile = $tmp . DIRECTORY_SEPARATOR . uniqid() . ".php", $phpCode ) === FALSE )
            return "ERROR: Could not write temp file: $diskFile";
        
        if (!file_exists( $diskFile ))
            return "ERROR: PHP cli file not found!";
        
        $cmdLine = "$php -l $diskFile";
        
        /* Create a process to php, and read stream of php process compilation */
        
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("pipe", "w")   // stderr is a pipe that the child will write to
        );
        
        $process = proc_open($cmdLine, $descriptorspec, $pipes );
        
        if (is_resource( $process )) {
            
            $stdout = stream_get_contents( $pipes[ 1 ] );
            @fclose( $pipes[ 1 ] );
            
            $stderr = stream_get_contents( $pipes[ 2 ] );
            @fclose( $pipes[ 2 ] );
            
            @fclose( $pipes[ 0 ] );
            
            $output = trim( $stderr.$stdout );
            
            proc_close( $process );
        }
        
        @unlink( $diskFile );
        
        $pregTmp = addcslashes( $tmp, DIRECTORY_SEPARATOR . ".!@#$%^&*()_-=+[\{]\};:\"':;<>,.\/?" );
        
        return preg_match('/^No syntax errors detected in/', $stdout ) ? TRUE : preg_replace("/in $pregTmp\/[a-f0-9]+.php on line /", ", line ", $stderr );
    }
    
    /* generates a string, which if would be evaluated, registers
       the keys from @array as local variables in current scope */
    function OneDB_ImportArgumentsToLocalScope( $array, $arrayName ) {
        $code = "";
        
        foreach (array_keys( $array ) as $argName) {
            if (preg_match('/^[_a-z][a-z0-9_]+$/i', $argName)) {
                $code .= "if (!isset( \$$argName ))\n" .
                         "    \$$argName = \$$arrayName" . "[ " . "'$argName' ];\n".
                         "else\n" . 
                         "    throw new Exception(\"Invalid argument [\$$argName]: Allready declared!\");\n\n";
            }
        }
        
        return $code;
    }
    
    /* modifies the url of a picture, in order to get the picture resized, for example
     */
    function OneDB_picture( $src, array $config ) {
        if (!count($config) || empty($src))
            return $src;
        else {
            $o = array();

            foreach (array_keys( $config ) as $param )
                $o[] = "$param=" . $config[$param];

            $o = "(" . implode(',', $o ) . ")";

            $src = preg_replace('/onedb(\\:|\\/)picture(\\:|\\/|\([\S]+\)(\\:|\\/))/', 'onedb/picture' . $o . '/', $src, 1 );

            return $src;
        }
    }

    /* Truncates a string to $howMany $units, where $units can be 'words', or 'chars' */
    
    function OneDB_truncateText( $str, $howMany, $units, $addMore = '' ) {
        
        $implodeStr = '';
        $added = '';
        $len = 0;
        
        switch (strtolower($units)) {
            case 'letters':
            case 'chars':
            case 'characters':
                $implodeStr = '';
                $len = strlen( $str );
                break;
            case 'word':
            case 'words':
                $str = explode(' ', $repl = trim( preg_replace('/[\s]+/', ' ', preg_replace('/([\s]+)?([\,\.\?\:\;\"\+]+)/', '$2 ', $str) ) ) );
                $implodeStr = ' ';
                $len = count( $str );
                break;
            default:
                throw new Exception('OneDB_truncateText: unknown unit ' . $unit);
                break;
        }
        
        for ($i=0, $len = ( $max = min( $len, $howMany ) ); $i<$len; $i++) {
            $added .= ( $str[ $i ] . $implodeStr );
        }
        
        return ( $added = trim( $added ) ) . ( ($len > $max && strlen( $added ) > 0 ) ? $addMore : '' );
    }
    
    function OneDB_toAscii( $str ) {
        
        global $__ONEDB_ASCII_TABLE__;
        
        foreach ( array_keys( $__ONEDB_ASCII_TABLE__ ) as $regex )
            $str = preg_replace( $regex, $__ONEDB_ASCII_TABLE__[ $regex ], $str );
        
        // $encoding = mb_detect_encoding( $str, 'utf-8, iso-8859-1', TRUE );
        return iconv( 'utf-8', 'ascii//translit//ignore', $str );
        
    }
    
    /* If we resolvechildof for a category-related search, field should be _id,
       if we resolvechildof for a article-related search, field should be _parent
     */
    
    function OneDB_resolveChildOf( &$arrayObject, &$OneDBServer, $fieldName = '_id' ) {
        
        if (!isset( $arrayObject[ '_childOf' ] ))
            return $arrayObject;
        
        if (isset( $arrayObject['selector'] ) || isset($arrayObject[$fieldName]) )
            throw new Exception("The _childOf magic key cannot be used in combination with search criteria 'selector' or 'parent'!");
        
        $query = array( 
            '$in' => array(
                $rootID = $OneDBServer->getElementByPath(
                    $arrayObject['_childOf']
                )->_id
            )
        );

        $parents = $OneDBServer->categories(
            array('selector' => $arrayObject[ '_childOf' ] . ' *' )
        )->flatten();
        
        for ($i=0, $len=$parents->length; $i<$len; $i++)
            $query['$in'][] = $parents->get($i)->_id;
        
        unset( $arrayObject[ '_childOf' ] );
        
        $arrayObject[$fieldName] = $query;
        
        return $arrayObject;
    }
    

    /* Returns the real IP address of the client. Takes in consideration proxies. */
    function OneDB_RemoteAddr() {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }
    }
    
    /* Eliminates NON-UTF8 bad characters from a string */
    function OneDB_EnsureUTF8( $str ) {

        $charsList = array(
            array(
                "char" => "&laquo;",
                "replaceWith" => "&quot;"
            ),
            array(
                "char" => "&raquo;",
                "replaceWith" => "&quot;"
            ),
            array(
                "char" => "&acute;",
                "replaceWith" => "`"
            )
        );
        
        for ( $i=0,$n=count($charsList); $i<$n; $i++ )
            $str = str_replace( $charsList[$i]['char'], $charsList[$i]['replaceWith'], $str );
        
        return $str;
    }
    
?>