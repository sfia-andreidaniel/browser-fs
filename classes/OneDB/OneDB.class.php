<?php

    /* OneDB is a framework with a backend intended for rapid developing
       of websites, data storage and indexing, and much more.
       
       This File is the only require you should make in order to use
       OneDB into your projects
       
     */

    /* Setting include paths ... */
    set_include_path( get_include_path() . ":" . dirname(__FILE__) . ":" . dirname(__FILE__) . DIRECTORY_SEPARATOR . "plugins/*" );
    
    /* AutoStart */
    require_once dirname(__FILE__) . "/_autostart.php";
    
    /** Connectors **/
    require_once "OneDB_CategoryConnector.class.php";
    require_once "OneDB_UsersConnector.class.php";

    /* In OneDB.inc.php we declared some useful functions */
    require_once "OneDB.inc.php";
    
    /* In OneDB.cfg.php, we defined the Mongo class patters for each object type */
    require_once "OneDB.cfg.php";
    
    /* We require the widget cache class in order to speed up the widget generation.
       Requires: memcached
     */
    require_once "OneDB_WidgetCache.class.php";
    
    /* If cfg file found in "<DOCUMENT_ROOT>/cfg/onedb.cfg.php", include it.
     * In that file you can declare a variable $_ONEDB_CFG_ and if you
     *   call OneDB constructor without the cfg parameters, it will
     *   load it's configuration from that global variable */
    if (file_exists( ($include_cfg_file = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
            "conf" . DIRECTORY_SEPARATOR . "onedb.cfg.php" ) ) ) require_once $include_cfg_file;
    
    /* Passing an optional "instance.name" into
     * OneDB initialization ( default instance name that is '_default')
     * will store that instance into this array.
     * Later, we can select that instance with "OneDB::get( [<instanceName>] )
     */
    $__ONEDB_INSTANCES__ = array();

    
    /* OneDB class definition
       @author: sfia.andreidaniel@gmail.com
       
       This software is OpenSource licensed !!!
     */

    class OneDB {
        
        /* Instance configuration */
        protected $_cfg = array(
            
            /* MongoDB Database configuration 
             */
            'db.host'               => 'localhost',
            'db.database'           => 'onedb',
            'db.user'               => '',
            'db.pass'               => '',
            'db'                    => NULL,         //Auto initialized
            
            /* Caching options. OneDB stores version of files
               into a cache folder on disk. Files generated are
               then dumped from that cache after that. If you want
               to disable this behaviour, set 'cache.enabled' to FALSE
             */
            'cache.enabled'         => TRUE,
            'cache.spanDirectories' => 128,
            'cache.dir'             => "",
            
            /* self instance name */
            'instance.name'         => "_default",



            /* A plugin should be in format:
               ~/plugins/foo
               where the path should be a directory on disk. Do not include the DIRECTORY_SEPARATOR
               sign at the end of the path.
               OneDB will load the <plugin_path>/plugin.php
             */
            'plugins'               => array(),
            'loadedPlugins'         => array()
        );
        
        /* BEGIN CLASS METHODS */
        
        /* OneDB Constructor
           
           OneDB will try to load it's configuration from the following 
           places, in the following order:
           
           1) $config parameter from the __constructor
           2) $_ONEDB_CFG_ (global variable)
           3) $_SESSION['onedb_connection']
           4) $_GET[ONEDB_AUTH_TOKEN] ***
           
           *** A OneDB authorization token is a md5 hash that OneDB generates when
           it loads it configuration, and store it's configration into a directory
           on disk with the hash name ( public-auth/token_md5_hash.auth )
           
           You can obtain the authorization token with the class method getExternalAuth()
           
           This is usefull if for example you wish to implement multiple instances of OneDB,
           send them to a javascript client, and send commands to individual OneDB instances
           through ajax ( pass instance token to each request via $_GET )

         */
           
        public function __construct( array $config = array() ) {
        
            if (time() < 1331233236)
                throw new Exception("Error: Bad date / time on server");
        
            if (is_array($config)) {
            
                //Preload the $config from other sources if necesarry
                if (!count( $config )) {
                     switch (TRUE) {
                        case isset($GLOBALS['_ONEDB_CFG_']) && is_array( $GLOBALS['_ONEDB_CFG_'] ):
                            $config = $GLOBALS['_ONEDB_CFG_'];
                            break;
                        case isset($_SESSION['onedb_connection']):
                            $config = $_SESSION['onedb_connection'];
                            break;
                        case isset($_GET['ONEDB_AUTH_TOKEN']):
                            $token = trim( $_GET['ONEDB_AUTH_TOKEN'], ' ()');
                            
                            $jsonAuthBuffer = NULL;
                            
                            if (preg_match('/^[a-f0-9]+$/', $token) && file_exists(
                                $authTokenFilePath =
                                dirname(__FILE__) . DIRECTORY_SEPARATOR .
                                "public-auth" . DIRECTORY_SEPARATOR .
                                "$token.auth"
                            )) {
                                $jsonAuthBuffer = @file_get_contents( $authTokenFilePath );
                            }
                            
                            if (empty( $jsonAuthBuffer ))
                                throw new Exception("OneDB: Invalid token!");
                            
                            $config = @json_decode( $jsonAuthBuffer, TRUE );
                            if (empty( $config ))
                                throw new Exception("OneDB: Invalid token file content!");
                            break;
                    }
                }
                
                //Load the predefined values that EXIST in $this->_cfg from the $config ...
                foreach (array_keys( $this->_cfg ) as $defaultKey ) {
                    if (in_array( $defaultKey , array_keys( $config )))
                        $this->_cfg[ $defaultKey ] = $config[ $defaultKey ];
                }
            } else throw new Exception( "OneDB::_construct: Invalid config parameter" );
            
            if ($this->_cfg['db'] === NULL) {
                
                /* Check if config database / hostname names are valid */
                if (!preg_match('/^[0-9\.a-z_\-]+(\:[\d]+)?$/', $this->_cfg['db.host']) || strpos($this->_cfg['db.host'], '..') !== FALSE)
                    throw new Exception('HostName seems to be invalid!');
            
                if (!preg_match('/^[0-9a-z_]+$/', $this->_cfg['db.database']) || strpos($this->_cfg['db.database'], '..') !== FALSE)
                    throw new Exception('DatabaseName seems to be invalid!');
            
                $mongo = empty( $this->_cfg['db.user'] ) ? 
                    //connect to database without authentication
                    new Mongo( $this->_cfg['db.host'], array(
                        'connect' => TRUE,
                        'db' => $this->_cfg['db.database']
                    ) ) :
                    //connect to database WITH authentication
                    new Mongo( $this->_cfg['db.host'], array(
                        'username' => $this->_cfg['db.user'],
                        'password' => $this->_cfg['db.pass'],
                        'connect' => TRUE,
                        'db' => $this->_cfg['db.database']
                    ));
                
                $this->_cfg['db'] = $mongo->{ $this->_cfg['db.database'] };
                $this->_cfg['db']->onedb = $this;
                
            }
            
            if ($this->_cfg['cache.enabled'])
                $this->_cfg['cache.dir'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . "cache/" .
                    str_replace(':', '_', $this->_cfg['db.host']) . DIRECTORY_SEPARATOR .
                    $this->_cfg['db.database'];
            
            /* Load plugins from configuration setting */
            foreach ($this->_cfg['plugins'] as $plugin)
                $this->loadPlugin( $plugin );
            
            if (defined('ONEDB_BACKEND') && isset($_SESSION['ONEDB_PLUGINS'])) {
                foreach (array_keys( $_SESSION['ONEDB_PLUGINS'] ) as $pluginPath )
                    $this->loadPlugin( $pluginPath );
            }

            /* Load user-specific plugins from database */
            
            if ( isset($_SESSION) && isset( $_SESSION['UNAME'] ) && !defined('ONEDB_DISABLE_AUTOPLUGINS') ) {

                $cursor = $this->_cfg['db']->plugins_users->find(
                    array(
                        'user' => $_SESSION['UNAME']
                    ), 
                    array('name' => TRUE)
                );

                foreach ($cursor as $dbPlugin) {
                    $this->loadPlugin( $dbPlugin['name'] );
                }
            }
            
            /* Load plugins from database ( all users ) */
            if (!defined('ONEDB_DISABLE_AUTOPLUGINS')) {
                
                $cursor = $this->_cfg['db']->plugins->find(array(), array('name' => TRUE));
                
                foreach ($cursor as $dbPlugin) {
                    $this->loadPlugin( $dbPlugin['name'] );
                }
            }
            
            
            /* Save class instance */
            global $__ONEDB_INSTANCES__;
            
            if (!isset($__ONEDB_INSTANCES__[ $this->_cfg['instance.name'] ] ))
                $__ONEDB_INSTANCES__[ $this->_cfg['instance.name'] ] = &$this;
        }
        
        /* loads a custom written plugin from a specific path */
        public function loadPlugin( $pluginFilePath ) {
            
            $pluginName = $pluginFilePath;
            
            if (isset( $this->_cfg['loadedPlugins'][ $pluginName ] ))
                return $this->_cfg['loadedPlugins'][ $pluginName ];
        
            $pluginFilePath = str_replace('%plugins%', dirname(__FILE__) . DIRECTORY_SEPARATOR . "plugins", $pluginFilePath);
            
            if (file_exists( $pluginFilePath . DIRECTORY_SEPARATOR . "plugin.php" ))
                require_once $pluginFilePath . DIRECTORY_SEPARATOR . "plugin.php";
            else
                return FALSE;

            if (!defined('ONEDB_BACKEND'))
                return TRUE;
            
            /* Fetch all backend javascript ... */
            $jsFiles = @scandir( $pluginFilePath );
            $out = array();
            foreach ($jsFiles as $js) {
                if (!preg_match('/\.js(on)?$/i', $js))
                    continue;
                $buffer = @file_get_contents( $pluginFilePath . DIRECTORY_SEPARATOR . $js );
                if (!empty( $buffer ))
                    $out[ $js ] = $buffer;
            }
            
            $this->_cfg['loadedPlugins'][ $pluginName ] = $out;
            
            return $out;
        }
        
        public function execPluginBackendHandler( $pluginFilePath ) {
            if (isset( $this->_cfg['loadedPlugins'][ $pluginFilePath ] ) ) {
                
                $pluginFilePath = str_replace('%plugins%', dirname(__FILE__) . DIRECTORY_SEPARATOR . "plugins", $pluginFilePath );
                
                if (file_exists( $includeFile = ( $pluginFilePath . DIRECTORY_SEPARATOR . "handler.php" ) ) ) {
                    require_once $includeFile;
                } else
                    throw new Exception( "OneDB::execPluginBackendHandler: Handler file '$includeFile' was not found!" );
                
            } else {
                // print_r( $this->_cfg['loadedPlugins'] );
                throw new Exception( "OneDB::execPluginBackendHandler: Plugin '$pluginFilePath' is not loaded in OneDB!" );
            }
        }
        
        /* Class getter */
        public function __get( $propertyName ) {
            if (isset($this->_cfg[ $propertyName ]))
                return $this->_cfg[ $propertyName ];
            
            switch ($propertyName) {
                case "categories":
                    return new OneDB_CategoryConnector( $this );
                    break;
                case "users":
                    return new OneDB_UsersConnector( $this );
                    break;
                case "defaultStorageType":
                    if (isset( $this->_cfg['defaultStorageType'] ) )
                        return $this->_cfg['defaultStorageType'];
                    else {
                        $registryStorageType = $this->registry()->{"OneDB.DefaultStorageType"};
                        $this->_cfg['defaultStorageType'] = empty( $registryStorageType ) ? 'database' : $registryStorageType;
                        return $this->_cfg['defaultStorageType'];
                    }
                    break;
            }
            
            return NULL;
        }
        
        /* Morphs $this to a $what type class ( that is implementing a specific
           class plugin method */
        
        private function morphTo( $what ) {
            $className = "OneDB_plugin_$what";
    
            $classPath = dirname(__FILE__). DIRECTORY_SEPARATOR . 
                         "plugins" . DIRECTORY_SEPARATOR . 
                         "core.OneDB.class" . DIRECTORY_SEPARATOR . 
                         $className . ".class.php";
    
            if (!class_exists( $className )) {
                if (file_exists( $classPath ) ) {
                    require_once $classPath;
                }
            }
            
            if (!class_exists( $className ))
                throw new Exception("OneDB plugin class $className not found (in $classPath)!");
            return new $className( $this->_cfg );
        }
        
        /* Any undeclared method calls are passed to the magic method __call,
           in order to load apropriate plugins that are implementing that method
         */
        public function __call( $methodName, $args ) {
            return call_user_func_array( array( $this->morphTo( $methodName ), $methodName ), $args );
        }
        
        /* Obtains a JSON version of OneDB configuration */
        public function config() {
            $cfg = $this->_cfg;
            unset( $cfg['db'] );
            return json_encode( $cfg );
        }
        
        /* Obtains the path to a root of the cache directory. If an ID is specified,
           it will point to the cache_directory/span_directory_index, determined
           for that ID.
           
           If no chaching is enabled via the config['cache.enabled'], this function
           will return NULL.
         */
        public function temp( $ID = NULL ) {
            if ($this->_cfg['cache.enabled']) {
                if ($ID === NULL)
                    return $this->_cfg['cache.dir'] . DIRECTORY_SEPARATOR;
                else
                    return $this->_cfg['cache.dir'] . DIRECTORY_SEPARATOR . abs( md5( $ID ) % $this->_cfg['cache.spanDirectories'] ) . DIRECTORY_SEPARATOR;
            } else return NULL;
        }
        
        /* Returns the OneDB external auth key. 
           Read more @ class constructor about that 
         */
        public function getExternalAuth() {
        
            //if allready calculated that key, return it.
            // WARNING: Working with this mechanism, you won't be
            // able to use multiple OneDB authentications with external authentication.
            if (defined("ONEDB_EXTERNAL_AUTH"))
                return ONEDB_EXTERNAL_AUTH;
        
            $out = array();
            
            foreach (array_keys( $this->_cfg ) as $key ) {
                if ($key != 'db')
                    $out["$key"] = $this->_cfg["$key"];
            }
            
            $cfg = json_encode( $out );
            
            $authName = md5( $cfg );
            
            if (!file_exists( 
                    $authFilePath = 
                        dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 
                        "public-auth" . DIRECTORY_SEPARATOR . 
                        "$authName.auth" 
                )
            ) {
                if (@file_put_contents( $authFilePath, $cfg ) === FALSE)
                    throw new Exception("Error creating auth token in $authFilePath!");
            }
            
            define("ONEDB_EXTERNAL_AUTH", $authName);
            
            return $authName;
        }
        
        /* Each time OneDB is initialized, it registers it's instance into global
           variable __ONEDB_INSTANCES__. After that, you can obtain that instance
           with the static method get. This is usefull, because you should not
           keep a global $OneDB variable after it's initialization, but obtain
           it with OneDB::get( instanceName )
         */
        public static function get( $instanceName = "_default" ) {
            global $__ONEDB_INSTANCES__;
            
            if (!isset($__ONEDB_INSTANCES__[ $instanceName ]))
                throw new Exception("Cannot get OneDB instance '$instanceName'");
            
            return $__ONEDB_INSTANCES__[ $instanceName ];
        }
    
    }
    
    /* Shorthand to OneDB::get(...) */
    /* works if php >= 5.3 */
    function OneDB() {
        $args = func_get_args();
        return forward_static_call_array( array( 'OneDB', 'get' ), $args );
    }
?>