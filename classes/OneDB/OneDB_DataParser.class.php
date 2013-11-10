<?php
    
    /* The goal of this class is to perform data-type checking, against
       different array structures of data, by using standard SQL data syntax
       
       A dataType can be defined as follows:
       
            [OPTIONAL] varName 
            DATATYPE(dataLength)
            [MIN value]
            [MAX value]
            [NOT EMPTY] 
            [NOT NULL] 
            [DEFAULT value] 
            [VALIDATE function_name]
            [CONVERT function_names]
            [ERROR label_name] 
       
       Where:
            OPTIONAL: - means that data can be missing, implicit DEFAULT value must be present
            DATATYPE: - [UNSIGNED] INT | [UNSIGNED] FLOAT | VARCHAR | DATE | DATETIME
            DEFAULT : - default value
            VALIDATE: specify a list of function names to be pass data through for validation purposes
            CONVERT : specify a list of function names to be pass through for conversion purposes
            ERROR   : if data is missing, an error to be thrown with the label_name
       
    */
    
    class OneDB_DataParser {

        private $_defs = array();
        private $_properties = array();
        private $_output = 'slashes';
        
        public function __construct($stringDefines, $sourceVar = array(), $settings = array()) {
            $this->compileDefs($stringDefines);
            if (!$sourceVar || !is_array($sourceVar))
                throw new Exception("Error: \$sourceVar not defined");
            $this->_properties = $sourceVar;
            
            foreach (array_keys($settings) as $setting_name) {
                $this->{$setting_name} = $settings["$setting_name"];
            }
            
            $this->compileData();
        }

        private function isOptional(&$str) {
            if (preg_match('/^optional([\s]+)/i', $str, $matches)) {
                $str = trim(substr($str, strlen($matches[0])));
//                 echo "ISOPTIONAL: TRUE\n";
                return true;
            } else {
//                 echo "ISOPTIONAL: FALSE\n";
                return false;
            }
                
        }
        
        private function readInt(&$str) {
            if (preg_match('/^(\+|-)?\d+/', $str, $matches)) {
                $str = trim(substr($str, strlen($matches[0])));
                return $matches[0];
            } else throw new Exception("PARSER: Expected integer value: ^".substr($str, 0, 128));
        }
        
        private function readFloat(&$str) {
            if (preg_match('/^(\+|-)?((\d+(\.\d+)?)|(\.\d+))/', $str, $matches)) {
                $str = trim(substr($str, strlen($matches[0])));
                return $matches[0];
            } else throw new Exception("PARSER: Expected float value: ^".substr($str, 0, 128));
        }
        
        private function readNull(&$str) {
            if (preg_match('/^null/i', $str, $matches)) {
                $str = trim(substr($str, strlen($matches[0])));
                return null;
            } else throw new Exception("PARSER: Expected null value: ^".substr($str, 0, 128));
        }
        
        private function getOpenBracket(&$str) {
            if (preg_match('/^\(/', $str)) {
                $str = trim(substr($str, 1));
//                 echo "OPENBRACKET\n";
                return true;
            } else throw new Exception("PARSER: Expected '(' ^".substr($str, 0, 128));
        }
        
        private function getCloseBracket(&$str) {
            if (preg_match('/^\)/', $str)) {
                $str = trim(substr($str, 1));
//                 echo "CLOSEBRACKET\n";
                return true;
            } else throw new Exception("PARSER: Expected ')' ^".substr($str, 0, 128));
        }
        
        private function readString(&$str, $quoteCharacter = '"') {
            $result = '';
            $i = 1;
            
            $isEscapedCharacter = false;
            
            do {
                switch (true) {
                    case $str[$i] == "\\":
                        if ($isEscapedCharacter) {
                            $result .= "\\";
                            $isEscapedCharacter = false;
                        } else $isEscapedCharacter = true;
                        break;
                    case $str[$i] == $quoteCharacter:
                        if ($isEscapedCharacter) {
                            $isEscapedCharacter = false;
                            $result .= $quoteCharacter;
                        } else break 2;
                        break;
                    default:
                        $result .= $str[$i];
                        break;
                }
                $i++;
            } while ($i < strlen($str));
            
            if ($i > strlen($str))
                throw new Exception("PARSER: unterminated string: ^$str");
            
            $str = trim(substr($str, strlen($result)+2));
            return $result;
        }
        
        private function readVarContents(&$str) {
        
            if ($str[0] == '"' || $str[0] == "'")
                return $this->readString($str, $str[0]);
            $saveStr = $str;
            try {
                $asFloat = $this->readFloat($str);
                return (float)$asFloat;
            } catch (Exception $e) {
                $str = $saveStr;
            }
            
            try {
                $asInt = $this->readInt($str);
                return (int)$asInt;
            } catch (Exception $e) {
                $str = $saveStr;
            }
            
            try {
                $asNull = $this->readNull($str);
                return null;
            } catch (Exception $e) {
                $str = $saveStr;
            }
            
            throw new Exception("Error reading variable contents: ^".substr($str, 0, 128));
        }
        
        private function getMinValue(&$str) {
            if (!preg_match("/^min([\s]+)/i", $str))
                return null;
            $str = trim(substr($str, 3));
            return $this->readVarContents($str);
        }
        
        private function getMaxValue(&$str) {
            if (!preg_match("/^max([\s]+)/i", $str))
                return null;
            $str = trim(substr($str, 3));
            return $this->readVarContents($str);
        }
        
        private function getDataType(&$str) {
            $_vartype  = null;
            $_unsigned = false;
            $_length   = false;
            
            if (preg_match('/^(unsigned)?([\s]+)?(int|float|varchar|datetime|date)/i', $str, $matches)) {
                $_unsigned = $matches[1] ? true : false;
//                 echo $_unsigned ? "UNSIGNED: TRUE\n" : "UNSIGNED: FALSE\n";
                $_vartype  = strtolower($matches[3]);
//                 echo "VARTYPE: $_vartype\n";
                $str = trim(substr($str, strlen($matches[0])));
            } else throw new Exception("PARSER: Expected data type definition: ^".substr($str, 0, 128));
            
            if ($_unsigned) {
                //allow only unsigned int and unsigned float
                if ($_vartype != 'int' && $_vartype != 'float') {
                    throw new Exception("PARSER: Invalid variable type: unsigned $_vartype: ^".substr($str, 0, 128));
                }
            }
            
            //read data length
            $this->getOpenBracket($str);
            $_length = $this->readInt($str);
            $this->getCloseBracket($str);
            
            return array(
                'type'=> ($_unsigned ? "unsigned_": "") . $_vartype,
                'length'  => $_length
            );
        }
        
        private function getVarName(&$str) {
            if (preg_match('/^([a-z_])([a-z_\.0-9]+)?/i', $str, $matches)) {
                $str = trim(substr($str, strlen($matches[0])));
//                 echo "GETVARNAME: ",$matches[0],"\n";
                return $matches[0];
            } else throw new Exception("Expected variable name definition: ^".substr($str, 0, 128));
        }
        
        private function getNull(&$str) {
            if (preg_match('/^not([\s]+)null([\s]+)/i', $str, $matches)) {
                $str = substr($str, strlen($matches[0]));
                return true;
            } else return false;
        }
        
        private function getEmpty(&$str) {
            if (preg_match('/^not([\s]+)empty([\s]+)/i', $str, $matches)) {
                $str = substr($str, strlen($matches[0]));
                return false;
            } else return true;
        }
        
        private function getDefault(&$str) {
            if (!preg_match('/^default([\s]+)/i', $str, $matches))
                throw new Exception("PARSER: Expected DEFAULT value: ^".substr($str, 0, 128));
            $str = trim(substr($str, strlen($matches[0])));
            return $this->readVarContents($str);
        }
        
        private function readComma(&$str) {
            if (!strlen($str)) return false;
            if ($str[0] == ',') {
                $str = trim(substr($str, 1));
                return true;
            } else return false;
        }

        private function getValidateFunctions(&$str) {
            if (!preg_match('/^validate([\s]+)/i', $str, $matches))
                return false;
            $str = trim(substr($str, strlen($matches[0])));
            $functions = array();
            do {
                $funcName = $this->getVarName($str);
                if (!function_exists($funcName))
                    throw new Exception("PARSER: specified filter function \"$funcName\", but function is not found!");
                $functions[] = $funcName;
            } while ($this->readComma($str));
            return $functions;
        }
        
        private function getConvertFunctions(&$str) {
            if (!preg_match('/^convert([\s]+)/i', $str, $matches))
                return false;
            $str = trim(substr($str, strlen($matches[0])));
            $functions = array();
            do {
                $funcName = $this->getVarName($str);
                if (!function_exists($funcName))
                    throw new Exception("PARSER: specified convert function \"$funcName\", but function is not found!");
                $functions[] = $funcName;
            } while ($this->readComma($str));
            return $functions;
        }
        
        private function getError(&$str) {
            if (!preg_match('/^error([\s]+)/i', $str))
                return false;
            $str = trim(substr($str, 5));
            return $this->readVarContents($str);
        }
        
        private function nextStatement(&$str) {
            if (!strlen(trim($str))) return false;
            if ($str[0] != ';')
                throw new Exception("PARSER: Unexpected token, expected ';' at: ^".substr($str, 0, 128));
            $str = trim(substr($str, 1));
            if ($str != '') return true;
            else return false;
        }
        
        private function compileDefs($str) {
        
            $copy = trim($str);
        
            do {
            
//                 echo "DEBUG: $copy\n";
                
                $_optional = false;
                $_name     = false;
                $_type     = false;
                $_length   = false;
                $_min      = null;
                $_max      = null;
                $_default  = null;
                $_error    = null;
                $_functs   = null;
                $_empty    = true;
                
                $_optional = $this->isOptional($copy) ? true : false;
                $_name     = $this->getVarName($copy);
                $_type     = $this->getDataType($copy);
                $_length   = $_type['length'];
                $_type     = $_type['type'];
                $_min      = $this->getMinValue($copy);
                $_max      = $this->getMaxValue($copy);
                $_null     = !$this->getNull($copy);
                $_empty    = $this->getEmpty($copy);
                $_default  = $this->getDefault($copy);
                $_funcs    = $this->getValidateFunctions($copy);
                $_convert  = $this->getConvertFunctions($copy);
                $_error    = $this->getError($copy);
            
                //Check for dup defs...
                if (isset($this->_defs["$_name"]))
                    throw new Exception("Duplicate definition for variable \"$_name\"");
                    
                $this->_defs["$_name"] =
                array(
                    'name'      => $_name,
                    'optional'  => $_optional,
                    'type'      => $_type,
                    'length'    => $_length,
                    'min'       => $_min,
                    'max'       => $_max,
                    'null'      => $_null,
                    'empty'     => $_empty,
                    'default'   => $_default,
                    'functions' => $_funcs,
                    'convert'   => $_convert,
                    'error'     => $_error
                );
                
            } while ($this->nextStatement($copy));
        }
        
        public function __get($propertyName) {
            if (!in_array($propertyName, array_keys($this->_properties)))
                throw new Exception("Invalid getter property name: $propertyName!");
            
            switch ($this->_output) {
                case 'raw':
                    return $this->_properties["$propertyName"];
                    break;
                case 'json':
                    return json_encode($this->_properties["$propertyName"]);
                    break;
                case 'mysql':
                case 'mssql':
                    switch (true) {
                        case ($this->_defs["$propertyName"]['null'] && $this->isNull($this->_properties["$propertyName"])):
                            return 'NULL';
                        case (in_array($this->_defs["$propertyName"]['type'], array( 'int', 'unsigned_int' ))):
                            return (int)$this->_properties["$propertyName"];
                            break;
                        case (in_array($this->_defs["$propertyName"]['type'], array( 'float', 'unsigned_float' ))):
                            return (float)$this->_properties["$propertyName"];
                            break;
                        default:
                            if ($this->_output == 'mysql')
                                return '"'.mysql_escape_string($this->_properties["$propertyName"]).'"';
                            else return "'".str_replace("'", "''", $this->_properties["$propertyName"])."'";
                            break;
                    }
                    break;
                case 'slashes':
                    return addslashes($this->_properties["$propertyName"]);
                    break;
                default:
                    throw new Exception("Panic: Unknown encoder output!");
                    break;
            }
            
        }
        
        public function __set($propertyName, $propertyValue) {
        
//             echo "debug: set $propertyName to $propertyValue\n";
        
            switch ($propertyName) {
                case 'output':
                    switch (true) {
                        case strtolower($propertyValue) == 'mysql':
                            if (!function_exists('mysql_escape_string'))
                                throw new Exception("mysql_escape_string function not found!");
                            else $this->_output = 'mysql';
                            break;
                        case strtolower($propertyValue) == 'mssql':
                            $this->_output = 'mssql';
                            break;
                        case strtolower($propertyValue) == 'json':
                            if (!function_exists('json_encode'))
                                throw new Exception('json_encode function not found!');
                            else $this->_output = 'json';
                            break;
                        case strtolower($propertyValue) == 'slashes':
                            $this->_output = 'slashes';
                            break;
                        case strtolower($propertyValue) == 'raw':
                            $this->_output = 'raw';
                            break;
                        default:
                            throw new Exception("Invalid output filter '$propertyValue', allowed only: 'mysql', 'mssql', 'json', 'slashes', 'raw'");
                            break;
                    }
                    break;
                    
                case 'trim':
                    if ($propertyValue) {
                        foreach (array_keys($this->_properties) as $propertyName) {
                            if (is_string($this->_properties["$propertyName"]))
                                $this->_properties["$propertyName"] = trim($this->_properties["$propertyName"]);
                        }
                    }
                    break;
                    
                default:
                    throw new Exception("Invalid setter property name: $propertyName");
            }
        }
        
        private function isInt($var, $allowNull = false) {
            if ($allowNull && $this->isNull($var)) return true;
            return preg_match('/^(\+|-)?\d+$/', $var);
        }
        
        private function isUnsignedInt($var, $allowNull = false) {
            if ($allowNull && $this->isNull($var)) return true;
            return preg_match('/^\d+$/', $var);
        }
        
        private function isFloat($var, $allowNull = false) {
            if ($allowNull && $this->isNull($var)) return true;
            return preg_match('/^(\+|-)?((\d+(\.\d+)?)|(\.\d+))$/', $var);
        }
        
        private function isUnsignedFloat($var, $allowNull = false) {
            if ($allowNull && $this->isNull($var)) return true;
            return preg_match('/^((\d+(\.\d+)?)|(\.\d+))$/', $var);
        }
        
        private function isNull($var) {
            return ($var === null || $var === '') ? true : false;
        }
        
        private function compileData() {
            foreach (array_keys($this->_defs) as $varName) {
                if (!in_array($varName, array_keys($this->_properties))) {
                    if (!$this->_defs["$varName"]['optional'])
                        throw new Exception($this->_defs["$varName"]['error'] ? $this->_defs["$varName"]['error'] : "Variable \"$varName\" not found");
                    else
                        $this->_properties["$varName"] = $this->_defs["$varName"]['default'];
                }
                
                if (!$this->_defs["$varName"]['empty'] && !strlen($this->_properties["$varName"])) {
                    throw new Exception($this->_defs["$varName"]['error'] ? $this->_defs["$varName"]['error'] : "\"$varName\" cannot be empty");
                }
                
                //Check for data types ...
                switch ($this->_defs["$varName"]['type']) {
                    case 'int':
                        if (!$this->isInt($this->_properties["$varName"], $this->_defs["$varName"]['null']))
                            throw new Exception(($this->_defs["$varName"]['error'] ? ($this->_defs["$varName"]['error']."\n") : '') ."Variable \"$varName\" is not an integer!");
                        break;
                    case 'unsigned_int':
                        if (!$this->isUnsignedInt($this->_properties["$varName"], $this->_defs["$varName"]['null']))
                            throw new Exception(($this->_defs["$varName"]['error'] ? ($this->_defs["$varName"]['error']."\n") : '') ."Variable \"$varName\" is not an unsigned integer!");
                        break;
                    case 'float':
                        if (!$this->isFloat($this->_properties["$varName"], $this->_defs["$varName"]['null']))
                            throw new Exception(($this->_defs["$varName"]['error'] ? ($this->_defs["$varName"]['error']."\n") : '') ."Variable \"$varName\" is not a float!");
                        break;
                    case 'unsigned_float':
                        if (!$this->isUnsignedFloat($this->_properties["$varName"], $this->_defs["$varName"]['null']))
                            throw new Exception(($this->_defs["$varName"]['error'] ? ($this->_defs["$varName"]['error']."\n") : '') ."Variable \"$varName\" is not an unsigned float!");
                        break;
                }
                
                //Check for variable length ...
                $lengthOk = false;
                
                switch ($this->_defs["$varName"]['type']) {
                    case 'unsigned_int':
                    case 'int':
                        if (strlen(abs($this->_properties["$varName"])) <= $this->_defs["$varName"]['length'])
                            $lengthOk = true;
                        break;
                    case 'unsigned_float':
                    case 'float':
                        if (strlen(abs($this->_properties["$varName"])) <= $this->_defs["$varName"]['length'])
                            $lengthOk = true;
                        break;
                    case 'date':
                    case 'datetime':
                    case 'varchar':
                        if (strlen($this->_properties["$varName"]) <= $this->_defs["$varName"]['length'])
                            $lengthOk = true;
                        break;
                    default:
                        throw new Exception("DEBUG: Unknown var type!");
                        break;
                }
                
                if ($lengthOk === false)
                    throw new Exception(
                        ($this->_defs["$varName"]['error'] ? ($this->_defs["$varName"]['error']."\n") : '') .
                        "Data length check failed for variable \"$varName\" (should be less than ".
                        $this->_defs["$varName"]['length']
                    );
                
                //Check for min / max bounds ...
                if ($this->_defs["$varName"]['min'] !== null) {
                    if ($this->_properties["$varName"] < $this->_defs["$varName"]['min'])
                        throw new Exception(($this->_defs["$varName"]['error'] ? ($this->_defs["$varName"]['error']."\n") : '') ."Variable \"$varName\" should be >= than ".$this->_defs["$varName"]['min']);
                }
                
                if ($this->_defs["$varName"]['max'] !== null) {
                    if ($this->_properties["$varName"] > $this->_defs["$varName"]['max'])
                        throw new Exception(($this->_defs["$varName"]['error'] ? ($this->_defs["$varName"]['error']."\n") : '') ."Variable \"$varName\" should be <= than ".$this->_defs["$varName"]['max']);
                }
                
                //Apply conversion filters...
                if (is_array($this->_defs["$varName"]['convert'])) {
                    foreach ($this->_defs["$varName"]['convert'] as $functionName) {
                        $this->_properties["$varName"] = $functionName($this->_properties["$varName"]);
                    }
                }
                
                //Check for custom validation functions ...
                if (is_array($this->_defs["$varName"]['functions'])) {
                    foreach ($this->_defs["$varName"]["functions"] as $functionName) {
                        if (!$functionName($this->_properties["$varName"], $this->_defs["$varName"]['null']))
                            throw new Exception(($this->_defs["$varName"]['error'] ? ($this->_defs["$varName"]['error']."\n") : '') ."Failed custom data filter \"$functionName\" for variable \"$varName\"");
                    }
                }
            }
        }
        
    }
    
    /*
    
    function myCustomIntegerCheck($variable, $allowNull = false) {
        return true;
    }
    
    try {
        $my = new DataParser("
            int             INT(5)                  MIN 1 MAX 2 DEFAULT 1 VALIDATE myCustomIntegerCheck ERROR 'Please input your correct age';
            uint            UNSIGNED INT(5)         MIN 1 MAX 2 DEFAULT 1;
            float           FLOAT(5)                MIN 1 MAX 2 DEFAULT 1;
            ufloat          UNSIGNED FLOAT(5)       MIN -3 MAX 2 DEFAULT 1;
            optional str    VARCHAR(32) NOT NULL    DEFAULT '';
            password        VARCHAR(32) NOT EMPTY   DEFAULT 'A' CONVERT md5 ERROR 'Please input your password';
            date            DATE(10)                DEFAULT '1980-01-01';
            datetime        DATETIME(19)            DEFAULT '1980-01-01 10:20:45';
            
        ", array(
            'int' => '1',
            'uint' => 1,
            'float' => 1,
            'ufloat'=> 1,
            'password' => "pass",
            'date' => '  23-01-1980  ',
            'datetime'=> '23-01-1980 00:00:00'
        ), array(
            'output' => 'mysql',
            'trim'   => true
        ));
        
//         $my->output = 'mysql';
        
        echo "$my->password\n$my->date\n";
    } catch (Exception $e) {
        die($e->getMessage()."\n");
    }
    
    */
    
?>