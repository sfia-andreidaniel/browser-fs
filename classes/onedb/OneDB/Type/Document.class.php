<?php

    require_once __DIR__ . '/../Type.class.php';

    Object( 'Vendor.PhpQuery' );
    Object( 'Vendor.HTML2Text' );
    
    class OneDB_Type_Document extends OneDB_Type {
        
        static protected $_isContainer = FALSE;
        static protected $_isReadOnly  = FALSE;
        
        protected $_document           = '';
        protected $_title              = '';
        protected $_textContent        = '';
        protected $_isDocumentTemplate = FALSE;
        
        public function exportOwnProperties( array &$properties ) {
            
            $properties[ 'document' ]           = $this->_document;
            $properties[ 'title' ]              = $this->_title;
            $properties[ 'textContent' ]        = $this->_textContent;
            $properties[ 'isDocumentTemplate' ] = $this->_isDocumentTemplate;
        }
        
        public function importOwnProperties( array $properties ) {
            
            $this->_document = isset( $properties['document'] )
                ? $properties['document']
                : '';
            
            $this->_title = isset( $properties['title'] ) 
                ? $properties['title']
                : '';
            
            $this->_textContent = isset( $properties['textContent'] )
                ? $properties[ 'textContent' ]
                : '';
            
            $this->_isDocumentTemplate = isset( $properties[ 'isDocumentTemplate' ] )
                ? $properties[ 'isDocumentTemplate' ]
                : FALSE;
            
        }
        
        public function __mux() {
            
            return [
                "document" => $this->_document,
                "title" => $this->_title,
                "textContent" => $this->_textContent,
                "isDocumentTemplate" => $this->_isDocumentTemplate
            ];
            
        }
    }
    
    OneDB_Type_Document::prototype()->defineProperty( 'dom', [
        "get" => function() {
            $result = phpQuery::newDocument( $this->_document );
            return $result;
        }
    ] );
    
    OneDB_Type_Document::prototype()->defineProperty( 'document', [
        "get" => function() {
            return $this->_document;
        },
        "set" => function( $html ) {
        
            $this->_document = $html;
            
            // Automatically determine article's title
            $newTitle = '';
            
            pq( $this->dom )->find( 'head > title, h1, h2, h3, h4, h5, h6' )->each( function( $heading ) use ( &$newTitle ) {
                if ( empty( $newTitle ) )
                    $newTitle = trim( pq( $heading )->text() );
            } );
            
            $this->_title = $newTitle;
            
            // Automatically determine article's text content
            $newTextContent = @html2text( trim( preg_replace('/([^*]+)?<body>([^*]+)<\/body>([^*]+)?/i', '$2', $html ) ) );
            $this->_textContent = $newTextContent;
            
            $this->_root->_change( 'document', $this->_document );
            $this->_root->_change( 'title',    $this->_title );
            $this->_root->_change( 'textContent', $this->_textContent );
        }
    ] );
    
    OneDB_Type_Document::prototype()->defineProperty( 'title', [
        "get" => function() {
            return $this->_title;
        },
        "set" => function( $str ) {
            $this->_title = trim( $str . '' );
            $this->_root->_change( 'title', $this->_title );
        }
    ] );
    
    OneDB_Type_Document::prototype()->defineProperty( 'textContent', [
        "get" => function() {
            return $this->_textContent;
        }
    ] );
    
    OneDB_Type_Document::prototype()->defineProperty( 'isDocumentTemplate', [
        "get" => function() {
            return $this->_isDocumentTemplate;
        },
        "set" => function( $bool ) {
            $this->_isDocumentTemplate = $bool ? TRUE : FALSE;
            $this->_root->_change( 'isDocumentTemplate', $this->_isDocumentTemplate );
        }
    ] );
    
?>