<?php
    
    /* The role of a view is to bind an object with a widget
     *
     * The views in OneDB are inheritable, so that if we require a view
     * that is not defined on an object, a search will be made in the
     * reversed object path for that view
     *
     *
     */
    
    class OneDB_Object_Views extends Object {
        
        protected $_properties = [];
        private   $_object     = NULL;
        private   $_type       = NULL;
        
        public function init( OneDB_Object $object, $properties ) {
            
            $this->_object = $object;
            $this->_type   = $object->type;
            
            if ( is_array( $properties ) )
                $this->_properties = $properties;
            
        }
        
        /* Returns a view ( widget instance ) of type $viewType with the name
           $viewName. If the view is not found, an exception will be thrown
         */
        protected function fetch( $viewType, $viewName ) {
            
            if ( !is_string( $viewType ) || !is_string( $viewName ) )
                throw Object( 'Exception.OneDB', 'arguments should be of type string!' );
            
            if ( $viewType != 'item' && $viewType != 'category' )
                throw Object( 'Exception.OneDB', 'first argument should be of type string enum [ "item", "category" ]' );
            
            $genericViewKey = NULL;
            $dedicatedViewKey = NULL;
            $itemType = $this->_type;
            $escViewName = addcslashes( $viewName, '*/\\.#{}+?%()^:' );
            
            $regex = '/^' . $viewType . '\.' . $escViewName . '(\#(.*))?$/';
            
            foreach ( array_keys( $this->_properties ) as $key ) {
                if ( preg_match( $regex, $key, $matches ) ) {
                    if ( isset( $matches[2] ) ) {
                        if ( $matches[2] != $this->_type ) continue;
                        else {
                            $dedicatedViewKey = $this->_properties[ $key ];
                            break;
                        }
                    } else $genericViewKey = $this->_properties[ $key ];
                }
            }
            
            $result = $dedicatedViewKey
                ? $dedicatedViewKey
                : ( $genericViewKey
                    ? $genericViewKey
                    : NULL
                );
            
            if ( $result !== NULL )
                return $result;
            
            if ( ($parent = $this->_object->parent ) !== NULL )
                return $parent->views->fetch( $viewType, $viewName );
            else
                return NULL;
        }
        
        public function getView( $viewType, $viewName ) {
            
            $result = $this->fetch( $viewType, $viewName );
            
            if ( $result === NULL )
                throw Object( 'Exception.OneDB', 'Failed to get object view "' . ( $viewType . '.' . $viewName ) . '" for object "' . $this->_object->url . '"' );
            
            else return Object( 'OneDB.Object.View', $result, $this->_object );
        }
        
        public function setView( $viewType, $viewName, OneDB_Object $widget, $justForType = NULL ) {
            
            if ( !is_string( $viewType ) || !is_string( $viewName ) )
                throw Object( 'Exception.OneDB', 'argument #1 and #2 should be of type string!' );
            
            if ( $viewType != 'item' && $viewType != 'category' )
                throw Object( 'Exception.OneDB', 'argument #1 should be of type string enum( "item", "category" )' );
            
            if ( $widget->type != 'Widget' )
                throw Object( 'Exception.OneDB', 'argument #3 should implement a Widget type!' );
            
            if ( preg_match( '/[\.\#]/', $viewName ) || $viewName == '' )
                throw Object( 'Exception.OneDB', 'illegal view name!' );
            
            if ( $justForType !== NULL && ( !is_string( $justForType ) || !strlen( $justForType ) ) )
                throw Object( 'Exception.OneDB', 'argument #4 should be of type nullable non-empty string!' );
            
            $viewKey = $viewType . '.' . $viewName . ( $justForType === NULL ? '' : ( '#' . $justForType ) );
            
            $this->_properties[ $viewKey ] = ( $widget->id . '' );
            
            $this->_object->_change( '_views_', $this->_properties );
        }
        
        public function enumerateViews() {
            $out = [];

            foreach ( array_keys( $this->_properties ) as $key ) {
                if ( preg_match( '/^(item|category)\.([^\#]+)(\#(.*))?$/i', $key, $matches ) ) {
                    $out[] = [
                        'type' => $matches[1],
                        'name' => $matches[2],
                        'for'  => isset( $matches[4] ) ? $matches[4] : NULL,
                        'id'   => $key
                    ];
                }
            }
            
            return $out;
        }
        
        public function deleteView( $viewId ) {
            
            if ( !is_string( $viewId ) )
                throw Object( 'Exception.OneDB', 'argument #1 should be of type string!' );
            
            if ( isset( $this->_properties[ $viewId ] ) ) {
                
                unset( $this->_properties[ $viewId ] );
                
                $this->_object->_change( '_views_', $this->_properties );
                
            }
        }
        
        public function toObject() {
            return $this->_properties;
        }
        
    }
    
?>