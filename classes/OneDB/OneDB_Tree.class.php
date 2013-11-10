<?php

    /* The OneDB_Tree is used to store
     * categories tree
     */

    class OneDB_Tree {

        private $_items = array();
        private $_properties = NULL;
        
        function __construct( OneDB_Category $category ) {
            $this->_properties = $category;
        }
        
        public function push( OneDB_Tree &$Tree ) {
            $this->_items[] = $Tree;
        }
        
        public function flatten() {
            $out = array();

            for ($i=0,$n=count($this->_items); $i<$n; $i++) {
                if ($this->_items[$i]->length) {
                    $out = array_merge( $out, $this->_items[$i]->flatten() );
                } else {
                    $out[] = $this->_properties;
                    $out[] = $this->_items[$i];
                }
            }
            return $out;
        }
        
        public function __get( $propertyName ) {
            switch ($propertyName) {
                case 'length':
                    return count($this->_items);
                    break;
                case 'items':
                    return $this->_items;
                    break;
                default:
                    return $this->_properties->{"$propertyName"};
                    break;
            }
        }
        
        public function item( $itemIndex ) {
            return $this->_items[ $itemIndex ];
        }
        
        public function toArray() {
            $cat = $this->_properties->toArray();
            $cat['_items'] = array();
            foreach ($this->_items as $item) {
                $cat['_items'][] = $item->toArray();
            }
            return $cat;
        }
        
        public function __call( $methodName, $args ) {
            if ($this->_properties->__has_method( $methodName ))
                return call_user_func_array( array( $this->_properties, $methodName ), $args );
            else
                throw new Exception("OneDB Tree: Method `$methodName` not implemented by category");
        }
        
        public function __set( $propertyName, $propertyValue ) {
            $this->_properties->{"$propertyName"} = $propertyValue;
        }
        
    }

?>