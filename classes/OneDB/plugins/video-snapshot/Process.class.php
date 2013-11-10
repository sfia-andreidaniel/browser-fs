<?php
    
    class Process {
        
        protected $_bin = NULL;        // process executable path
        protected $_args= array();     // process aditional arguments
        protected $_pid = NULL;        // process ID
        
        protected $_buffers = array(   // process stream buffers
            'stdin' => array(
                'buffer' => '',
                'closed' => TRUE,
                'index'  => 0
            ),
            'stdout' => array(
                'buffer' => '',
                'closed' => FALSE,
                'index'  => 1
            ),
            'stderr' => array(
                'buffer' => '',
                'closed' => FALSE,
                'index'  => 2
            )
        );
        
        protected $_descriptorSpec = array(
            0 => array(         //stdin
                'pipe', 'r'
            ),
            1 => array(         //stdout
                'pipe', 'w'
            ),
            2 => array(         //stderr
                'pipe', 'w'
            )
        );
        
        protected $_listeners = array(
            'stdin' => array(),
            'stdout'=> array(),
            'stderr'=> array()
        );
        
        protected $_pipes;
        
        public function __construct( $bin, $which = FALSE ) {
            // if using the 'which' command, then we try to determine where the 
            // process binary (executable) file is located
            if ($which) {
                $findBinCmd = "/usr/bin/which " . escapeshellarg( $bin );
                $binPath = trim( `$findBinCmd` );
            } else 
                $binPath = $bin;
            
            // does binary file exists?
            if (!file_exists( $binPath ) )
                throw new Exception("File '$binPath' was not found!");
            else
                $this->_bin = $binPath;
        }
        
        public function addArgument( $argument ) {
            $this->_args[] = escapeshellarg( $argument );
        }
        
        public function addEventListener( $pipeName, $methodName ) {
            if (!method_exists( $this, $methodName ) )
                throw new Exception("method $methodName is not implemented in this class, so it cannot be added as an event listener");

            if (!isset( $this->_listeners[ $pipeName ] ) )
                throw new Exception("Cannot add listener for pipe `$pipeName`: Invalid pipe name");
                
            $this->_listeners[ $pipeName ][] = $methodName;
        }
        
        public function run() {
            $this->_pid = proc_open( 
                $cmdLine = implode(' ', array_merge( array( $this->_bin ), $this->_args ) ),
                $this->_descriptorSpec,
                $this->_pipes
            );
            
            // echo $cmdLine, "\n";
            
            if (!is_resource( $this->_pid ))
                throw new Exception("Cannot create process: $cmdLine");
            
            foreach (array_keys( $this->_buffers ) as $pipeName ) {
                stream_set_timeout( $this->_pipes[ $this->_buffers[ $pipeName ]['index'] ], 10 );
            }
            
            $terminated = FALSE;
            
            while (!$terminated) {
                
                $terminated = TRUE;
                
                foreach (array_keys( $this->_buffers ) as $pipeName ) {
                    
                    if ( !$this->_buffers[ $pipeName ]['closed']  && 
                         feof( $this->_pipes[ $this->_buffers[ $pipeName ]['index'] ] )
                    ) {
                        
                        $this->_buffers[ $pipeName ]['closed'] = TRUE;

                    } else 
                    
                    if (!$this->_buffers[ $pipeName ]['closed'] ) {
                        
                        $pipeResponse = fread( $this->_pipes[ $this->_buffers[ $pipeName ]['index'] ], 10 );
                        
                        if ($pipeResponse !== FALSE) {
                            
                            $this->_buffers[ $pipeName ]['buffer'] .= $pipeResponse;
                            
                            //execute the listeners for that pipe
                            
                            for( $i=0, $len=count($this->_listeners[ $pipeName ]); $i<$len; $i++ ) {
                                $callable = $this->_listeners[ $pipeName ][ $i ];
                                $this->{"$callable"}( $pipeResponse );
                            }
                            
                        } else echo "FALSE\n";
                        
                        $terminated = FALSE;
                    }
                    
                }
                
            }
            
            foreach (array_keys( $this->_buffers ) as $pipeName ) {
                // echo "fclose( $pipeName )\n";
                fclose( $this->_pipes[ $this->_buffers[ $pipeName ]['index'] ] );
            }
            
            proc_close( $this->_pid );
        }
        
        public function __get( $propertyName ) {
            if (isset( $this->_buffers[ $propertyName ] ))
                return $this->_buffers[ $propertyName ]['buffer'];
            else
                return NULL;
        }
    }
    
    /*
    $myProcess = new Process('ls', FALSE);
    $myProcess->addArgument('/tmp');
    $myProcess->run();
    
    echo "stdin: \n", $myProcess->stdin, "\n";
    echo "stdout: \n", $myProcess->stdout, "\n";
    echo "stderr: \n", $myProcess->stderr, "\n";
    */
?>