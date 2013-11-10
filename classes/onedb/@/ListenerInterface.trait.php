<?php

    trait ListenerInterface {
        
        private    $__PARENT_NODE_ = NULL;
        private    $__NODE_NAME_   = '';
        private    $__EVENT_STACK_ = array();
        
        
        public function __setObjectName( $name ) {
            $this->__NODE_NAME_ = $name;
            return $this;
        }
        
        public function __setParentObject( $obj ) {
            if (is_object( $obj ) && ( $obj instanceof Object ) )
                $this->__PARENT_NODE_ = $obj;
        }
        
        /* Creates an event instance */
        private function createEvent( $eventName, $args = array() ) {
            return array( 
                'event' => $eventName,
                'path'  => $this->__NODE_NAME_,
                'args'  => $args,
                'source'=> $this 
            );
        }
        
        /* fires an event on $this object
         */
        private function onOwnEvent( &$evt ) {

            if ( isset( $this->__EVENT_STACK_[ $evt['event'] ] ) ) {

                $result = TRUE;

                foreach ( $this->__EVENT_STACK_[ $evt['event'] ] as $callback ) {
                    if ( !call_user_func_array( @Closure::bind( $callback, $this, $this ), [ $evt ] ) )
                        $result = FALSE;
                }
                
                return $result;
                
            } else {

                /* Check in the parent node */
                if ( is_object( $this->__PARENT_NODE_ ) )
                    return $this->__PARENT_NODE_->onChildEvent( $evt );
                else
                    return TRUE;
                
            }
            
        }
        
        /* handles an event fired from a child object */
        
        public function onChildEvent( &$evt ) {
            
            $evt['path'] = 
                ( $this->__PARENT_NODE_ !== NULL 
                    ? $this->__NODE_NAME_
                    : '' )
                . '/'
                . $evt['path'];

            if ( isset( $this->__EVENT_STACK_[ $evt['event'] ] ) ) {

                $result = TRUE;

                foreach ( $this->__EVENT_STACK_[ $evt['event'] ] as $callback ) {
                    if ( !call_user_func_array( @Closure::bind( $callback, $this, $this ), [ $evt ] ) )
                        $result = FALSE;
                }
                
                return $result;
                
            } else {

                /* Check in the parent node */
                if ( is_object( $this->__PARENT_NODE_ ) )
                    return $this->__PARENT_NODE_->onChildEvent( $evt );
                else
                    return TRUE;

            }
        }
        
        public function on( $eventName, $subProperty = NULL, $data = NULL ) {
        
            $evt = $this->createEvent( $eventName, array_slice( func_get_args(), 2 ) );

            if ( $subProperty !== NULL && isset( $evt['path'] ) ) {
                $evt['path'] .= "/$subProperty";
            }
            
            $data = $data === NULL ? array() : (array)$data;
            
            foreach ( array_keys( $data ) as $key )
                $evt[$key] = $data[ $key ];
            
            return $this->onOwnEvent( $evt );
        }
        
        public function addEventListener( $eventName, $listenerFunction ) {
            if (!is_callable( $listenerFunction ))
                throw new Exception("Fatal: The listenerFunction (2nd arg) should be callable!");
            if ( !isset( $this->__EVENT_STACK_[ $eventName ] ))
                $this->__EVENT_STACK_[ $eventName ] = [];
            $this->__EVENT_STACK_[ $eventName ][] = $listenerFunction;
        }
        
        public function removeEventListeners( $eventName ) {
            if ( isset( $this->__EVENT_STACK_[ $eventName ] ) )
                $this->__EVENT_STACK_[ $eventName ] = [];
        }
    }

?>