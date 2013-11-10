<?php

    require_once "Process.class.php";
    
    class VideoParser extends Process {
        
        protected $path;
        private   $section = NULL;
        
        private   $_read = 0;
        private   $_buff = '';
        
        protected $_properties = array(
            'video'  => array(),
            'audio'  => array()
        );
        
        public function __construct( $videoFilePath ) {
            $this->path = $videoFilePath;
            parent::__construct( 'ffmpeg', TRUE );
            $this->addArgument('-i');
            $this->addArgument($videoFilePath);
            $this->parse();
        }
        
        public function onStdErr( $str ) {
            // echo $str;
            $buff = substr( $this->stderr, $this->_read );
            while (preg_match('/([\S\s]+?)(\r)?\n/', $buff, $matches)) {
                $this->_read += ( $len = strlen( $matches[0] ) ); //got new line
                $line = $matches[1];
                $buff = substr( $buff, trim( strlen($matches[0]) ) );
                $this->parseLine( $line );
            }
        }
        
        public function parseVideoLine( $line ) {
            if (preg_match( '/[\s]+([\d]+x[\d]+)(([\s]+)?\[[\s\S]+?\]([\s+])?)?,/', $line, $matches ) ) {
                list( $width, $height ) = explode( 'x', $matches[1] );
                $this->_properties['video']['width'] = $width;
                $this->_properties['video']['height'] = $height;
                // $this->_properties['video']['pixelsPerFrame'] = $width * $height;
            }
            if (preg_match( '/,[\s]+([\d]+) kb\/s(\,|$)?/', $line, $matches ) ) {
                $this->_properties['video']['bitrate'] = $matches[1];
            }
            if (preg_match( '/,[\s]+([\d]+(\.[\d]+)?)[\s]+fps(\,|$)/', $line, $matches ) ) {
                switch ($matches[1]) {
                    case '23.98':
                    case '23.97':
                    case '23.976':
                        // more accurate (more decimals)
                        $this->_properties['video']['fps'] = 24000 / 1001;
                        break;
                    
                    case '29.98':
                    case '29.97':
                    case '29.970':
                        $this->_properties['video']['fps'] = 30000 / 1001;
                        break;
                    
                    default:
                        $this->_properties['video']['fps'] = (float)$matches[1];
                        break;
                }
            }
            //echo "video: ", $line, "\n";
        }
        
        public function parseAudioLine( $line ) {
            if (preg_match( '/,[\s]+([\d]+) Hz,/', $line, $matches ) ) {
                $this->_properties['audio']['samplerate'] = $matches[1];
            }
            if (preg_match( '/,[\s]+([\d]+) kb\/s(\,|$)?/', $line, $matches ) ) {
                $this->_properties['audio']['bitrate'] = $matches[1];
            }
        }
        
        public function parseDurationLine( $line ) {
            //  Duration: 00:01:19.07, start: 0.000000, bitrate: 470 kb/s
            // echo "\n\nDDDDD: $line\n\n\n";
            
            if (preg_match('/^([\d]+)\:([\d]+)\:([\d]+)\.([\d]+),/', $line, $matches )) {
                list( $dummy, $hh, $mm, $ss, $s100 ) = $matches;
                $hh = (int)$hh;
                $mm = (int)$mm;
                $ss = (int)$ss;
                $s100 = (int)$s100;
                
                $total = (float) ( ($secDuration = ( ( $hh * 3600 ) + ( $mm * 60 ) + $ss ) ) . '.' . $s100 );
                
                $this->_properties['duration'] = $total;
            }
        }
        
        public function parseLine( $line ) {
            switch (TRUE) {
                case preg_match( '/^[\s]+Stream #[\d].[\d](\([a-z]+\))?: (Video|Audio):([^*]+)/', $line, $matches ):
                    $sectionType = strtolower( $matches[2] ); // strtolower ( Video | Audio )
                    $this->section = $sectionType;
                    switch ($this->section) {
                        case 'video': 
                            $this->parseVideoLine( $matches[3] );
                            break;
                        case 'audio':
                            $this->parseAudioLine( $matches[3] );
                            break;
                    }
                    break;
                case preg_match( '/^[\s]+Duration\:[\s]+([^*]+)/', $line, $matches ):
                    $this->parseDurationLine( $matches[1] );
                    break;
            }
        }

        public function parse() {
            $this->addEventListener( 'stderr', 'onStdErr' );
            $this->run();
            if ( !count( $this->_properties['video'] ) ) {
                throw new Exception("File does not contain a video stream!");
            } else {
                
                $a16_9 = 1.777777778;
                $a4_3  = 1.333333333;
                
                // determine video file aspect
                
                $aspect = $this->_properties['video']['width'] / $this->_properties['video']['height'];
                
                if ( abs( $aspect - $a16_9 ) <= abs( $aspect - $a4_3 ) )
                    $this->_properties['video']['aspect'] = '16:9';
                else
                    $this->_properties['video']['aspect'] = '4:3';
                
            }
            
            if (isset( $this->_properties['duration'] ) && isset( $this->_properties['video']['fps'] ) ) {
                $this->_properties['video']['total_frames'] = $this->_properties['video']['fps'] * $this->_properties['duration'];
            }
            
            // print_r( $this->_properties );
        }
        
        public function properties( ) {
            return $this->_properties;
        }

    }

    function parse_video_file( $filePath ) {
        try {
            $parser = new VideoParser( $filePath );
            return $parser->properties();
        } catch (Exception $e) {
            // echo $e->getMessage(), "\n";
            return NULL;
        }
    }
    
    // print_r( parse_video_file( '/srv/jsplatform/tmp/mov.mp4' ) );
    // var_dump( parse_video_file( '../movie.sadasdasdasdadsadsas.mp4' ) );

?>