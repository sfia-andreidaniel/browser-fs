<?php
    
    class OneDB_Storage extends Object implements IDemuxable {
        
        protected $_name   = 'abstract';
        protected $_root   = NULL;
        
        static protected $_formatFallbacks = [
            
            'original'       => [
                'original'
            ],
            
            '720p.mp4'       => [
                '720p.mp4',       '480p.mp4',
                '360p.mp4',       '240p.mp4',
                'mp4',            'original if mp4'
            ],
            
            '480p.mp4'       => [
                '480p.mp4',       '360p.mp4',
                '260p.mp4',       'mp4',
                'original if mp4'
            ],
            
            '360p.mp4'       => [
                '360p.mp4',       '240p.mp4',
                'mp4',            'original if mp4'
            ],
            
            '240p.mp4'       => [
                '240p.mp4',       'mp4',
                'original if mp4'
            ],

            'mp4'            => [
                'mp4',            '360p.mp4',
                '240p.mp4',       '480p.mp4',
                '720p.mp4',       'original if mp4'
            ],
            
            'iphone.mp4'     => [
                'iphone.mp4',     '240p.mp4',
                'original if mp4'
            ],
            
            'android.mp4'    => [
                'android.mp4',    '240p.mp4',
                'original if mp4'
            ],
            
            'blackberry.mp4' => [
                'blackberry.mp4', '240p.mp4',
                'original if mp4'
            ],
            
            'webm'           => [
                'webm',           'original if webm'
            ],
            
            'ogv'            => [
                'ogv',            'original if ogv'
            ],
            
            'flv'            => [
                'flv',            'original if flv' ],
            
            'png'            => [
                'png',            'thumb.hq.jpg',
                'thumb.mq.jpg',   'thumb.lq.jpg',
                'original if png, gif, jpg'
            ],
            
            'jpg'            => [
                'jpg',            'thumb.hq.jpg',
                'thumb.mq.jpg',   'thumb.lq.jpg',
                'original if jpg, png, gif'
            ],
            
            'gif'            => [
                'gif',            'thumb.hq.jpg',
                'thumb.mq.jpg',   'thumb.lq.jpg',
                'original if gif, png, jpg'
            ],
            
            'cliche'         => [
                'cliche.jpg'
            ]
        ];
        
        public function init( OneDB_Client $client ) {

            $this->_root = $client;
            $this->_name = preg_replace( '/^OneDB_Storage_/', '', get_class( $this ) );
        
        }
        
        public function unlinkFile( $fileId ) {
            throw Object( 'Exception.Storage', 'unlinking is not implemented' );
        }
        
        /* The scope of this function is to choose the best file version of a file
           based on it's transcoded versions
           
           @return NULL when cannot determine,
           @return string <url> when a best match has been determined.
           
           Example:
           
           getBestFileMatchVersion( [
               "original" => "http://www.storage2.com/2013/11/10/123131231123.mp4",
               "240p.mp4" => "http://www.storage2.com/2013/11/10/123131231123.mp4.240p.mp4",
               "360p.mp4" => "http://www.storage2.com/2013/11/10/123131231123.mp4.240p.mp4"
           ], "480p.mp4" )
           
           will return "http://www.storage2.com/2013/11/10/123131231123.mp4.360p.mp4"
           
           NOTE: the key "original" should _ALWAYS_ be present in the $formats argument,
                 otherwise this function will return NULL
           
         */
        public function getBestFileMatchVersion( array $formats, $wantedVersion ) {
            //    /^([a-z\d\.]+)( if )?(([a-z\d\.]+)((([\s]+)?,([\s]+)?([a-z\d\.]+))+)?)?/.exec( '240p.mp4 if jpg, png, gif' )
            //    => ["240p.mp4 if jpg, png, gif", "240p.mp4", " if ", "jpg, png, gif", "jpg", ", png, gif", ", gif", undefined, " ", "gif"]
            
            // test that @arg wantedVersion is string
            if ( !is_string( $wantedVersion ) )
                throw Object( 'Exception.Storage', "The second argument of OneDB_Storage.getFileBestMatchVersions should be of type string!" );

            $wantedVersion = strtolower( trim( $wantedVersion ) );
            
            // we cannot determine a format if the $formarts[ 'original' ] is not set.
            if ( !isset( $formats[ 'original' ] ) )
                return NULL;
            
            // determine the extension of the original file format
            $originalFileNameExtension = preg_match( '/\.([a-z\d]+)/i', $formats[ $original ], $matches )
                ? strtolower( $matches[ 1 ] )
                : '';
            
            // If we don't have defined a fallback, but the original file extension
            // is the same as the wanted file version, we return the original file
            // version
            if ( !isset( self::$_formatFallbacks[ $wantedVersion ] ) )
                return $wantedVersion == $originalFileNameExtension
                    ? $formats[ 'original' ]
                    : NULL;
            
            foreach ( self::$_formatFallbacks[ $wantedVersion ] as $testVersion ) {
                
                if ( $testVersion == $wantedVersion ) {
                    
                    if ( isset( $formats[ $testVersion ] ) )
                        return $formats[ $testVersion ];
                    
                } else {
                    
                    if ( preg_match( '/^([a-z\d\.]+)( if )?(([a-z\d\.]+)((([\s]+)?,([\s]+)?([a-z\d\.]+))+)?)?/', 
                                     $testVersion, $matches )
                    ) {
                        
                        $testVersion = $matches[1];
                        
                        if ( isset( $formats[ $testVersion ] ) && isset( $matches[2] ) && $matches[ 2 ] == ' if ' ) {
                            
                            $ifOriginalVersion = isset( $matches[ 3 ] )
                                ? $matches[3]
                                : NULL;
                            
                            if ( $ifOriginalVersion !== NULL ) {
                                
                                $ifOriginalVersion = preg_split( '/[\s\,]+/', $ifOriginalVersion );
                                
                                foreach( $ifOriginalVersion as $ifVersion ) {
                                    
                                    if ( $ifVersion == $originalFileNameExtension )
                                        return $formats[ $testVersion ];
                                    
                                }
                                
                            }
                            
                        }
                        
                    }
                    
                }
                
            }
            
            return NULL;
        }
        
        public function __mux() {
            
            throw Object( 'Exception.Storage', 'demuxing is not implemented' );
            
        }
        
        static public function __demux( $data ) {
            
            throw Object( 'Exception.Storage', 'muxing not implemented!' );
            
        }
        
        
    }
    
?>