<?php
    
    /* The role of this class is to implement an automatic auto-increment
       value for the mongo database.
     */
    
    class Mongo_Counter extends Object {
        
        protected $_collection = NULL;
        protected $_counterName= NULL;
        
        public function init( $mongoDatabase, $counterName ) {
            $this->_collection  = $mongoDatabase->counters;
            $this->_counterName =  $counterName;
        }
        
        // obtain the next auto increment counter value.
        // returns NULL on error
        public function getNext() {
            try {
                /* Assume the counter is created, and fetch it */
                $result = $this->_collection->findAndModify(
                    [
                        '_id' => $this->_counterName
                    ],
                    [
                        '$inc' => [
                            'seq' => 1
                        ]
                    ],
                    [
                        'seq' => TRUE
                    ],
                    [
                        'new' => TRUE
                    ]
                );

                if ( is_array( $result ) && isset( $result[ 'seq' ] ) && is_int( $result[ 'seq' ] ) )
                    return $result['seq'];
            
                // insert the new counter and return 1;
                $this->_collection->save( [ '_id' => $this->_counterName, 'seq' => 1 ] );

                return 1;
            } catch ( Exception $e ) {
                return FALSE;
            }
        }
        
        // sets the counter to a specific value.
        // this value will be returned on the next getNext() call.
        // returns FALSE on error
        public function setCounter( $counterValue ) {
            
            try {
                
                if ( !is_int( $counterValue ) || $counterValue < 1 )
                    return FALSE;
                
                $this->_collection->save( [ '_id' => $this->_counterName, 'seq' => $counterValue - 1 ] );
                
            } catch ( Exception $e ) {
                return FALSE;
            }
            
        }
    }
    
?>