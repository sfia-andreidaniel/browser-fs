<?php

    require_once "utils.inc.php";
    
    error_reporting( E_ALL );
    ini_set('display_errors', 'on');
    
    $FORM_NAME = isset($_GET['__FORM_NAME__']) && !empty($_GET['__FORM_NAME__']) ? $_GET['__FORM_NAME__'] : http500("Which form name?");
    define('FORM_NAME', $FORM_NAME);
    
    $FORM_PROTO= isset($_GET['__FORM_PROTOCOL__']) && !empty($_GET['__FORM_PROTOCOL__']) ? strtolower($_GET['__FORM_PROTOCOL__']) : http500("Which form protocol?");

    if (!in_array( $FORM_PROTO, array('get', 'post')))
        http500("Invalid form protocol: $FORM_PROTO");

    define('FORM_PROTO', $FORM_PROTO);
    
    if (isset($_GET['ONEDB_AUTH_TOKEN']) && empty($_GET['ONEDB_AUTH_TOKEN']))
        unset($_GET['ONEDB_AUTH_TOKEN']);
    
    chdir( dirname(__FILE__)."/.." );
    
    if (!isset($_SESSION))
        session_start();
    
    require_once "OneDB.class.php";
    
    header("Content-Type: text/plain");
    
    try {
    
        $my = new OneDB();
    
        $form = $my->forms( array(
            "name" => FORM_NAME,
            "method" => FORM_PROTO
            )
        )->get(0);
        
        switch (FORM_PROTO) {
            case 'post':
                $_FORM = $_POST;
                break;
                
            case 'get':
                $_FORM = $_GET;
                unset($_FORM['ONEDB_AUTH_TOKEN']);
                unset($_FORM['__FORM_NAME__']);
                unset($_FORM['__FORM_PROTOCOL__']);
                break;
            case 'any':
                $_FORM = $_REQUEST;
                unset($_FORM['ONEDB_AUTH_TOKEN']);
                unset($_FORM['__FORM_NAME__']);
                unset($_FORM['__FORM_PROTOCOL__']);
                break;
            default:
                http500("Invalid FORM_PROTO: ". FORM_PROTO);
                break;
        }
        
        $form->submit( $_FORM, $my );
        
    } catch (Exception $e) {
        http500( $e->getMessage()."\n\n" . $e->getFile() . "\n\n" . $e->getLine() );
    }
    

?>