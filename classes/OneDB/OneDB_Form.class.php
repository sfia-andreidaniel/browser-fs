<?php

    require_once "OneDB_DataParser.class.php";

    class OneDB_Form extends OneDB_MongoObject {
        
        public function __construct( &$collection, $objectID = NULL, $firstLoadDataIfObjectIDWasSet = NULL ) {
        
            $this->addTrigger("method", "before", function( $newMethod, $oldMethod, $self ) {
                if ( !in_array( $newMethod, array("post", "get") ) )
                    throw new Exception("Invalid form method: $newMethod");
            });
            
            parent::__construct( $collection, $objectID, $firstLoadDataIfObjectIDWasSet );
        
        }
        
        public function submit( $_FORM, $oneDB, $forceSkipCaptcha = FALSE ) {

            if (!is_array( $_FORM ))
                throw new Exception("ERR_BAD_FORM_INPUT");

            if ($forceSkipCaptcha == FALSE) {
                $captcha = $this->captcha;
            
                if (!empty( $captcha )) {
                        
                    if (!isset( $_FORM[ $captcha ] ) )
                        throw new Exception("ERR_FORGOT_CAPTCHA");
                        
                    if (!isset($_SESSION))
                        session_start();
                    
                    if (empty( $_FORM[ $captcha ] ) || 
                        empty( $_SESSION[ $captcha ] ) ||
                        $_SESSION[ $captcha ] != $_FORM[ $captcha ]
                    ) throw new Exception("ERR_BAD_CAPTCHA");
                    
                    $_SESSION[ $captcha ] = '_EXPIRED_';
                }
                
            }
            
            /* Run the PHP part */
            
            //ob_start(); //Don't allow buffer output to be displayed on the screen
            
            $result = @eval( OneDB_EvalStripTags($this->code) );
            
            //$stdout = ob_get_contents();
            
            //ob_end_clean();
            
            if ($result === FALSE) {
                //echo $stdout;
                return;
            }
            
            /* End of run the php part */
            
            /* Determine wheather to post the content of the form into a category ! */
            
            $parentCategoryId = $this->parentCategoryId;
            
            if (!empty( $parentCategoryId )) {
            
                /* Save the form object to database */
                global $__OneDB_Default_FormObject__;
                
                $collection = $this->_collection->db->articles;
                
                $entry = new OneDB_MongoObject( $collection, NULL, $__OneDB_Default_FormObject__ );
                
                $entry->data = $_FORM;
                $entry->name = "$this->name ($this->method) " . time();
                $entry->date = time();
            
                $entry->_form = $this->_id;
            
                $_server = array();
            
                /* Save server vars */
                if (isset($_SERVER['REMOTE_ADDR']))
                    $_server['remoteAddr'] = $_SERVER['REMOTE_ADDR'];
                if (isset($_SERVER['HTTP_USER_AGENT']))
                    $_server['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
                if (isset($_SERVER['HTTP_REFERER']))
                    $_server['referer'] = $_SERVER['HTTP_REFERER'];
                /* End of saving ... */
            
                $entry->_server = $_server;
                $entry->_parent = MongoIdentifier( $parentCategoryId );
            
                $entry->type = "FormEntry";
            
                try {
                    $entry->save();
                    return TRUE;
                } catch (Exception $e) {
                    return FALSE;
                }
            
            }
        }
        
    }

?>