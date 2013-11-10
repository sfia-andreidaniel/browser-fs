<?php

    require_once "OneDB.class.php";

    class OneDB_plugin_prepareServer extends OneDB {
        
        public function prepareServer( ) {
        
            $this->_cfg['db']->categories->ensureIndex(
                array(
                    '_parent' => TRUE,
                    'name'   => TRUE
                ),
                array(
                    'unique' => TRUE
                )
            );
            
            try {
            
            $this->_cfg['db']->categories->insert(
                array(
                    "_parent" => NULL,
                    "name"    => "video snapshots",
                    "hidden"  => TRUE,
                    "type"    => NULL
                )
            );
            
            } catch (Exception $e) {}
            
            try {
            
            $this->_cfg['db']->categories->insert(
                array(
                    "_parent" => NULL,
                    "name"    => "metafiles",
                    "hidden"  => TRUE,
                    "type"    => NULL
                )
            );
            
            } catch (Exception $e) {}
            
            $this->_cfg['db']->articles->ensureIndex(
                array(
                    '_parent' => TRUE,
                    'name'   => TRUE
                ),
                array(
                    'unique'  => TRUE
                )
            );
            
            $this->_cfg['db']->users->ensureIndex(
                array(
                    'name' => TRUE
                ),
                array(
                    'unique' => TRUE
                )
            );
            
            $this->_cfg['db']->users->ensureIndex(
                array(
                    'email' => TRUE
                ),
                array(
                    'unique' => TRUE
                )
            );
            
            $this->_cfg['db']->forms->ensureIndex(
                array(
                    'name' => TRUE,
                    'method' => TRUE
                ),
                array(
                    'unique' => TRUE
                )
            );
            
            $this->_cfg['db']->config->ensureIndex(
                array(
                    'name' => TRUE
                ),
                array(
                    'unique' => TRUE
                )
            );

            $cacheDir = $rootDir = dirname(__FILE__) . "/../../cache/".str_replace(':','_',$this->_cfg['db.host']);
            
            if (!is_dir( $cacheDir )) {
               if (!@mkdir( $cacheDir ) )
                  throw new Exception("Could not create cache root directory: $cacheDir");
            }
        
            $cacheDir = realpath( $cacheDir );
            
            $folders = array();
            $folders[] = $cacheDir;
            $folders[] = $rootDBDir = "$cacheDir". DIRECTORY_SEPARATOR . $this->_cfg['db.database'];
            
            for ($i=0, $len=$this->_cfg['cache.spanDirectories']; $i<$len; $i++)
                $folders[] = "$cacheDir". DIRECTORY_SEPARATOR . $this->_cfg['db.database'] . DIRECTORY_SEPARATOR . "/$i";
            
            foreach ($folders as $folder) {
                if (!is_dir( $folder )) {
                    if (!@mkdir( $folder ))
                        throw new Exception("Could not create folder: '$folder'");
                }
            }
            
            return TRUE;
        }
        
    }
    
?>