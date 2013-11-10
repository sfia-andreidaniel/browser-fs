<?php

    class OneDB_Category_plugin_SearchCategory {
        
        protected $_ = NULL;
        
        public function __construct( &$that ) {
            $this->_ = $that;
        }
        
        public function applyOwnFilter( $filter ) {
            $ownFilter = $this->_->filter;
            
            if ($ownFilter === NULL || !is_array( $ownFilter ))
                return $filter;
            
            if (!count( $ownFilter ))
                $ownFilter = array(
                    "disabledFilter" => "disabledFilter"
                );
            
            $ownFilter = OneDB_JsonModifier( $ownFilter );
            
            //remove the parent filter :)
            
            if (isset($filter['_parent']))
                unset($filter['_parent']);
            
            foreach (array_keys( $ownFilter ) as $key)
                $filter[$key] = $ownFilter[$key];
            
            return $filter;
        }
        
        public function getCollection() {
            return $this->_->_collection->db->articles;
        }
    }

?>