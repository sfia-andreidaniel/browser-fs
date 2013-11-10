<?php

    class OneDB_DocSplit {
        
        protected $_file     = NULL;
        protected $_docsplit = NULL;
        protected $_outDir   = NULL;
        protected $_pdf      = NULL;
        protected $_package  = NULL;
        
        private   $_dbg      = FALSE;
        private   $_unlink   = array();
        
        function __construct( $fileName, $docSplitBinPath = NULL, $debug = FALSE ) {
        
            $this->_dbg = $debug;
            
            $ensureLocal = $this->ensureFileIsLocal( $fileName );

            if ($ensureLocal != $fileName) {
                $fileName = $ensureLocal;
            }
            
            if (!file_exists( $fileName ))
                throw new Exception("File $fileName was not found!");
        
            if (!is_readable( $fileName ))
                throw new Exception("File $fileName is not readable!");
        
            if ( $docSplitBinPath === NULL ) {
                $cmd = "which docsplit";
                $docSplitBinPath = trim( `$cmd` );
            }
        
            if (!file_exists( $docSplitBinPath ))
                throw new Exception("DocSplit executable not found!");
        
            $this->_file = $fileName;
            $this->_docsplit = $docSplitBinPath;
        
            $this->debug("File: ", $this->_file, ", DocSplit: ", $this->_docsplit );
        
            $dir = sys_get_temp_dir();
            $prefix = '';
            $ddir = '';
        
            while ( TRUE ) {
                $ddir = $dir . DIRECTORY_SEPARATOR . "docsplit" . ( $prefix == '' ? '' : "-$prefix" );

                if (!is_file( $ddir ) && !is_dir( $ddir )) 
                {
                    if ( @mkdir( $ddir ) && is_dir( $ddir ) ) {
                        $this->_outDir = $ddir;
                        break;
                    } else
                        throw new Exception("Could not create output directory: $ddir!");
                }
        
                $prefix = ( $prefix === '' ? 1 : $prefix + 1 );

                if ($prefix === 1000)
                    throw new Exception("Could not create output directory: Too many tries!");
            }
            
            $this->debug("Output Dir: ", $this->_outDir );
            
            $this->_package = $this->_outDir . DIRECTORY_SEPARATOR . preg_replace('/([\S\s]+)\.[a-z0-9]+$/i', '$1', basename( $this->_file ) ) . ".docsplit";
            $this->debug("Package: ", $this->_package );
            
            $info = pathinfo( $this->_file );
            $extension = isset( $info['extension'] ) ? strtolower( $info['extension'] ) : '';
            
            if ($extension != 'pdf') {
                /* Creates the PDF version of the $fileName inside the temporary folder */
                $result = $this->shell( array(
                    $this->_docsplit,
                    'pdf',
                    $this->_file,
                    '-o', $this->_outDir
                ) );
            
                if ( !(($this->_pdf = $this->findOne( $this->_outDir, '/\.pdf$/i' )) !== NULL ) )
                    $this->debug("Info: Could not find a docsplit pdf file!");
            } else {
                $this->_pdf = $this->_file;
                $this->debug("Using the source file as the PDF file too!");
            }
            
        }
        
        private function ensureFileIsLocal( $filePath ) {
            $info = @parse_url( $filePath );
            
            $isURL = (bool)$info;
            
            if (!$isURL)
                return $filePath;
            
            $name = basename( isset( $info['path'] ) ? $info['path'] : 'unknown-file' );
            
            $info = pathinfo( $name );
            
            $extension = isset( $info['extension'] ) ? $info['extension'] : '';
            
            if ($extension != '')
                $name = substr( $name, 0, strlen( $name ) - 1 - strlen( $extension ) );
            
            $name = str_replace('%20', '-', $name);
            $name = str_replace('+', '-', $name);
            
            $tries = '';
            $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $name;
            
            while ( TRUE ) {
                $checkName = $base . ( $tries == '' ? '' : "-$tries" ) . ( strlen( $extension ) ? ".$extension" : "" );
                
                if (!file_exists( $checkName )) {

                    if ( @file_put_contents( $checkName, '' ) === FALSE )
                        throw new Exception( "Could not create file: $checkName on local disk!" );
                    
                    break;
                }
                
                $tries = $tries == '' ? 1 : $tries + 1;
                
                if ($tries > 1000)
                    throw new Exception("Could not fetch remote file locally: Could not generate a local file on temp dir!");
            }
            
            //die("NAME=$name, EXTENSION=$extension, LOCAL=$checkName\n");
            
            $this->_unlink[] = $checkName;
            
            $remote = @fopen( $filePath, 'r' );

            if (!is_resource( $remote ))
                throw new Exception("Could not open remote url: $filePath for reading");
            
            $local  = @fopen( $checkName, 'w' );
            
            if (!is_resource( $local ))
                throw new Exception("Could not open local temp file: $checkLocal for writing" );
            
            while (!feof( $remote )) {
                $buffer = @fread( $remote, 8192 );

                if ($buffer === FALSE) {
                    break;
                } else 
                    @fwrite( $local, $buffer );
            }
            
            fclose( $local );
            fclose( $remote );
            
            if ( filesize( $checkName ) == 0)
                throw new Exception("Local stored file is 0 bytes length!");
            
            return $checkName;
        }
        
        function _text( $forceOCR = FALSE ) {
            if ($this->_pdf === NULL) {
                $this->debug("->text: Could not extract text because no PDF was generated!");
                return NULL;
            }
            
            $cmd = $this->shell( array(
                $this->_docsplit,
                'text',
                ($forceOCR ? '--ocr' : ''),
                '-o', $this->_outDir,
                $this->_pdf
            ) );
            
            $textFile = $this->findOne( $this->_outDir, '/\.txt$/i' );
            
            if ($textFile)
                return file_get_contents( $textFile );
            else 
                return NULL;
        }
        
        function _images( ) {
            if ($this->_pdf === NULL) {
                $this->debug("->images: Could not extract images from document because no PDF was generated!");
                return NULL;
            }
            
            $cmd = $this->shell( array(
                $this->_docsplit,
                'images',
                '-o', $this->_outDir,
                '-d', '300',
                $this->_pdf
            ) );
            
            return $this->findAll( $this->_outDir, '/\.png$/i' );
        }
        
        function _all() {
            
            if ($this->_pdf === NULL)
                return NULL;
            
            $archive = new ZipArchive();
            
            if ( $archive->open( $this->_package, ZipArchive::CREATE) !== TRUE )
                throw new Exception("Could not create archive: $this->_package");
            
            $textContent = $this->_text();
            
            $pictures    = $this->_images();
            
            $pages       = 0;
            
            $archive->addFromString( 'text', "$textContent" );

            if ( is_array( $pictures ) ) {

                foreach ($pictures as $picture) {
                    $archiveName = preg_replace( '/^[\S\s]+_([\d]+)\.png$/i', '$1', $picture );
                    $archive->addFromString( $archiveName, file_get_contents( $picture ) );
                    $this->debug("AddFile: ", $archiveName, "=>", $picture );
                    $pages++;
                }
                
            }
            
            $archive->addFromString( 'pages', "$pages" );
            
            $archive->addFile( $this->_pdf, 'pdf' );
            
            $ok = $archive->close();
            
            return $ok ? $this->_package : NULL;
        }
        
        function debug() {

            if (!$this->_dbg)
                return true;

            echo "Debug: @", date('d-M-Y, H:i:s > ');
            foreach (func_get_args() as $arg) {
                echo $arg, " ";
            }
            echo "\n";
        }
        
        function shell( array $arguments ) {
            $out = array();
            $cmdLine = '';
            
            foreach ($arguments as $argument) {
                if (strlen( $argument ))
                $out[] = escapeshellarg( $argument );
            }
            
            $cmdLine = implode(' ', $out);
            
            $this->debug("Exec: ", $cmdLine );
            
            $out = `$cmdLine`;
            
            $this->debug("Exec return");
            
            return $out;
        }
        
        function findAll( $dir, $pattern ) {
            $files = @scandir( $dir );
            $out = array();
            if (is_array( $files ) ) {
                foreach ($files as $file) {
                    if (preg_match( $pattern, $file )) {
                        $path = realpath( $dir . DIRECTORY_SEPARATOR . $file );
                        if ($path)
                            $out[] = $path;
                    }
                }
            }
            return $out;
        }
        
        function findOne( $dir, $pattern ) {
            $files = @scandir( $dir );
            if (is_array( $files ) ) {
                foreach ($files as $file) {
                    if (preg_match( $pattern, $file )) {
                        $path = realpath( $dir . DIRECTORY_SEPARATOR . $file );
                        if ($path)
                            return $path;
                    }
                }
            }
            return NULL;
        }
        
        function __destruct( ) {
            $this->debug("Destruct...");

            $this->shell(array( 'rm' , '-rf', $this->_outDir ));

            foreach ($this->_unlink as $file) {
                $this->debug("@unlink: $file");
                @unlink( $file );
            }
        }
        
        function __get( $propertyName ) {
            switch ($propertyName) {
                case 'pdf':
                    return $this->_pdf;
                    break;
                case 'text':
                    return $this->_text();
                    break;
                case 'images':
                    return $this->_images();
                    break;
                case 'all':
                    return $this->_all();
                    break;
                default:
                    throw new Exception("Invalid property name: $propertyName");
                    break;
            }
        }
    }

    /*

    try {

        $docsplit = new OneDB_DocSplit( 'http://static.digi24.ro/~cache-0~width-640~crop-1/504/mihai%20bucurenciu%20portbagajul-36809.jpg?query=24' );
        
        $theFile = $docsplit->all;
        
        echo $theFile, "\n";
        
        //if ($theFile) {
        //    copy( $theFile, basename( $theFile ) );
        // }
        
        // echo $docsplit->text, "\n";
        // print_r( $docsplit->images );
    } catch (Exception $e) {
        echo $e->getMessage(),"\n";
    }

    */

?>