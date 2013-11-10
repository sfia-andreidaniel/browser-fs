<?php

    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "OneDB_DummyClass.class.php";

    class OneDB_ResultsNavigator {
        
        protected $_items  = array();
        protected $_type   = 'Generic';
        private   $_iBasic = TRUE;
        protected $_svr    = NULL;
        
        public function __construct( $arr, &$server, $navigatorType = 'generic' ) {
            $this->_items = $arr;
            $this->_type  = $navigatorType;
            $this->_svr   = $server;
        }
        
        /* When the items from this results naviagtor are represented as a tree,
           the flatten() method is generating another result navigator set,
           with items represented into a 2d array instead of a tree structure
         */
        
        public function flatten() {
            $out = array();
            
            foreach ($this->_items as $item) {
                $out[] = $item;
                if (method_exists( $item, 'flatten' ))
                    $out = array_merge( $out, $item->flatten() );
            }
            
            return new OneDB_ResultsNavigator($out, $this->_svr, $this->_type);
        }
        
        /* The here concept is very usefull for debugging. We break the chain commands,
           execute the code for $func(), and continue the chain.
         */
        
        public function here( $func ) {
            $func( $this );
            return $this;
        }
        
        /* Applies a user function to each result navigator element,
           and returns the same result navigator */
        
        public function each( $func = NULL ) {
            if ($func) {
                for ($i=0, $length = count( $this->_items ); $i < $length; $i++) {
                    $func( $this->_items[$i], $i, $this );
                }
            }
            return $this;
        }
        
        /* Returns a result navigator only with items that
           pass the $func($item) as TRUE
         */
        
        public function filter( $func ) {
            $out = array();
            for ($i=0, $length = count( $this->_items ); $i < $length; $i++) {
                if ($func( $this->_items[$i] )) {
                    $out[] = $this->_items[$i];
                }
            }
            return new OneDB_ResultsNavigator( $out, $this->_svr, $this->_type );
        }
        
        /* Returns a result navigator with sorted elements,
           based on a user sort function.
           
           Prototype of $func should be: function($a, $b)
           which should return: negative values if a < b, 0 if a == b, positive values if a > b
         */
        
        public function sort( $func ) {
            $out = array();
            for ($i=0, $length = count( $this->_items ); $i < $length; $i++) {
                $out[] = $this->_items[$i];
            }
            usort( $out, $func );
            return new OneDB_ResultsNavigator( $out, $this->_svr, $this->_type );
        }
        
        /* Returns a result navigator with items from
           this one, in a reversed order */
        
        public function reverse() {
            return new OneDB_ResultsNavigator( 
                array_reverse( $this->_items ), 
                $this->_svr, 
                $this->_type 
            );
        }
        
        /* Returns a result navigator with items starting
           from current result $howMany index */
        
        public function skip( $howMany ) {
            return new OneDB_ResultsNavigator( /* Fix: It was new 'OneDB_ResultsPaginator' ???? */
                array_slice( $this->_items, $howMany ),
                $this->_svr,
                $this->_type
            );
        }
        
        /* Returns a result navigator with maximum
           $howMany items */
        
        public function limit( $howMany ) {
            return new OneDB_ResultsNavigator(
                array_slice( $this->_items, 0, $howMany ),
                $this->_svr,
                $this->_type
            );
        }
        
        /* Returns a result navigator whose items are unique by the
           $byPropertyName key */
        
        public function unique( $byPropertyName ) {
            if (count($this->_items) < 2)
                return new OneDB_ResultsNavigator( $this->_items, $this->_svr, $this->_type );
            
            $arr = array();
            
            for ($i=0, $length = count( $this->_items ); $i < $length; $i++) {
                $s = $this->_items[$i]->{"$byPropertyName"};
                $arr[ "$s" ] = $i;
            }

            $out = array();
            $_values = array_values( $arr );
            foreach ($_values as $index) {
                $out[] = $this->_items[ $index ];
            }
            
            return new OneDB_ResultsNavigator( $out, $this->_svr, $this->_type );
        }
        /* Get element #$index from the result set. Throws exception if
           index is invalid */
        
        public function get( $index ) {
            if ($index < 0 || $index > count($this->_items) - 1)
                throw new Exception("Index $index out of range");
            return $this->_items[ $index ];
        }
        
        /* Merge results from another ResultsNavigator with
           this one, and returns a Generic ResultsNavigator
           containing both result sets */
        
        public function join( $resultSet ) {
            for( $i=0, $length = $resultSet->length; $i < $length; $i++ ) {
                $this->_items[] = $resultSet->get( $i );
            }
            return $this->morphTo('Generic');
        }
        
        /* If the boolOrCallable is boolean and TRUE, or $boolOrCallable is
           a function and $boolOrCallable($this) evaluates as TRUE, contine the chain,
           otherwise returns a DummyClass which will cancel the chain execution */

        public function continueIf( $boolOrCallable ) {
            
            $testResult = FALSE;
            
            switch (TRUE) {
                case is_callable( $boolOrCallable ):
                    $testResult = $boolOrCallable( $this );
                    break;
                case is_bool( $boolOrCallable ):
                    $testResult = $bool;
                    break;
                default:
                    throw new Exception("continueIf accepts as argument either a function f(\$this), either a boolean");
                    break;
            }
            
            return $testResult ? $this : new OneDB_DummyClass();
        }

        /* Applies a custom sorting order */
        public function applySortOrder( $sortOrder = NULL ) {

            $sortOrderCount = count($sortOrder);

            if ( !is_array( $sortOrder ) || !$sortOrderCount )
                return $this;

            $sortHash = array();

            // create a hash with orders
            for ($i=0; $i < $sortOrderCount; $i++)
                $sortHash[ $sortOrder[$i][ 'id' ] ] = $sortOrder[$i]['order'];

            $orderItem = count( $sortHash ) + 1;
            $globalHash= array();

            // create a hash with order for this navigator
            foreach ($this->_items as $item) {
                $globalHash[ "$item->_id" ] = isset( $sortHash[ "$item->_id" ] ) ? $sortHash[ "$item->_id" ] : ($orderItem++);
            }

            usort( $this->_items, function( $a, $b ) use ($globalHash) {
                return $globalHash[ "$a->_id" ] - $globalHash[ "$b->_id" ];
            } );

            return $this;
        }

        /* Morphs this Results Navigator to another type. Usefull
           for plugin specific results navigators types (we're using the
           decorator pattern here) */
        
        private function morphTo( $ResultsNavigatorType ) {
            $classNeeded = "OneDB_ResultsNavigator_plugin_$ResultsNavigatorType";
            $classPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR .
                         "core.OneDB_ResultsNavigator.class" . DIRECTORY_SEPARATOR .
                         "$classNeeded.class.php";
            
            if (!class_exists( $classNeeded )) {
                if (file_exists( $classPath )) 
                    require_once $classPath;
                else
                    throw new Exception( "Could not morph to a $classNeeded: required file not found ( $classPath )" );
            }
            
            return new $classNeeded( $this->_items, $this->_svr, $ResultsNavigatorType );
        }
        
        /* Class magic method getter */
        public function __get( $propertyName ) {
            switch ($propertyName) {
                case 'items':
                    return $this->_items;
                    break;
                case 'length':
                    return count($this->_items);
                    break;
                default:
                    if (isset( $this->_iBasic ))
                        return $this->morphTo( $this->_type )->{"$propertyName"};
                    else
                        throw new Exception("Invalid property '$propertyName'");
                    break;
            }
        }
        
        /* Wrapper for plugin method calls */
        public function __call( $methodName, $args ) {
            if (!method_exists( $this, $methodName ) && isset( $this->_iBasic ))
                return call_user_func_array( array($this->morphTo( $this->_type ) , $methodName), $args);
            else
                throw new Exception("Invalid method name: '$methodName'!");
        }
        
    }

?>