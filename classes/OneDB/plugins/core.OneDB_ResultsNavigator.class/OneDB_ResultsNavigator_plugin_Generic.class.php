<?php
    
    /* The generic navigator plugin is usefull to impersonate methods
     * of the collection, e.g. when a collection contains
     * items of more than a type, and want to disable item-specific
     * collection plugins 
     */
    
    class OneDB_ResultsNavigator_plugin_Generic extends OneDB_ResultsNavigator {
        
        public function __construct( $items, &$server, $navigatorType = 'Generic' ) {
            parent::__construct( $items, $server, $navigatorType );
        }
        
    }

?>