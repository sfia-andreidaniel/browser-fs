<?php

    require_once "OneDB_JSONCollection.class.php";

    class OneDB_Category_plugin_JSONWebserviceCategory {
        
        protected $_ = NULL;
        
        public function __construct( &$that ) {
            $this->_ = $that;
            $this->_->addTrigger("webserviceURL", "before", function( $newUrl, $oldURL, $self ) {
                
                if ( strpos("$newUrl", "?") !== FALSE)
                    throw new Exception("Please don't use a query string in the URL. Specify the query paramteres in the WebserviceConfiguration.get instead");
                
            });
        }
        
        public function applyOwnFilter( $filter ) {
            $ownFilter = $this->_->filter;
            
            if ($ownFilter === NULL || !is_array( $ownFilter ))
                return $filter;
            
            $ownFilter = OneDB_JsonModifier( $ownFilter );
            
            //remove the parent filter :)
            
            if (isset($filter['_parent']))
                unset($filter['_parent']);
            
            foreach (array_keys( $ownFilter ) as $key)
                $filter[$key] = $ownFilter[$key];
            
            return $filter;
        }
        
        public function getCollection() {
        
            $cfg = $this->_->webserviceCFG;
            $cfg = is_array( $cfg ) ? $cfg : array();
            $cfg['collectionID'] = $this->_->_id;
            
            /* If we find httpUsername and httpPassword,
               we setup authentication */
            
            $userName = $this->_->httpUsername;
            $password = $this->_->httpPassword;
            
            if (!empty( $userName ))
                $cfg["auth"] = "$userName:$password";
            
            $_col = new OneDB_JSONCollection(
                $this->_->webserviceURL,
                $this->_->webserviceTTL,
                $cfg
            );
            
            $_col->db = $this->_->_collection->db;
            
            return $_col;
        }
    }

?>