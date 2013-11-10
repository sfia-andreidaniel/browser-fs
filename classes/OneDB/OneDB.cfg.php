<?php

    /* Defines section */
    
    // define('ONEDB_BACKEND',             1); // load plugins in backend mode ( + JavaScript plugin specific configuration )
    // define('ONEDB_DISABLE_AUTOPLUGINS', 1); // disable loading database specific plugins automatically after connecting to mongo database
    
    /* End of defines section */



    /* This files contains the oneDB default objects definitions
       that are used on initialization
       
       @author: sfia.andreidaniel@gmail.com
       
     */

    $__OneDB_Default_Category__ = array(
        "name"          => "",
        "url"           => "",
        "type"          => NULL,
        "date"          => NULL,
        "modified"      => NULL,
        "owner"         => "",
        "modifier"      => "",
        "description"   => "",
        "icon"          => "",
        "keywords"      => array(),
        "tags"          => array(),
        "isVirtual"     => NULL,
        "views"         => array(
            "category"  => array(),
            "item"     => array()
        )
    );
    
    $__OneDB_Default_SearchCategory__ = array(
        "isVirtual"     => TRUE,
        "filter"        => array()
    );
    
    $__OneDB_Default_JSONWebserviceCategory__ = array(
        "isVirtual"     => TRUE,
        "filter"        => array()
    );
    
    $__OneDB_Default_Article__ = array(
        "name"          => "",
        "url"           => NULL,
        "type"          => NULL,
        "date"          => NULL,
        "modified"      => NULL,
        "modifier"      => NULL,
        "keywords"      => array(),
        "tags"          => array()
    );
    
    $__OneDB_Default_User__ = array(
        "name"          => "",
        "password"      => 'd41d8cd98f00b204e9800998ecf8427e', //md5('')
        
        "icon"          => "",
        
        "firstName"     => "",
        "lastName"      => "",
        "companyName"   => "",
        
        "email"         => "",
        "phone"         => "",
        "mobile"        => "",
        
        "groups"        => array(),
        "logins"        => 0,
        "enabled"       => false
    );
    
    $__OneDB_Default_Document__ = array(
        "name"          => "",
        "title"         => "",
        "document"      => "",
        "description"   => "",
        "icon"          => "",
        "textContent"   => "",
        "revision"      => 0,
        "online"        => false,
        "posts"         => 0,
        "keywords"      => array(),
        "tags"          => array(),
        "meta"          => array()
        /* ,
        // these properties are allocated only on demand
            "statistics"    => array(
            "views"     => 0,
            "votes"     => 0,
            "comments"  => 0
        ),
        "relatedDocs"   => array() 
        */
    );
    
    $__OneDB_Default_Form__ = array(
        "name"             => "",
        "method"           => "",
        "captcha"          => "",
        "parentCategoryId" => NULL,
        "code"             => "",
        "formViewer"       => ""
    );
    
    /* Default OneDB.Article.FormObject configuration
     * @inherits: $__OneDB_Default_Article__
     */
    $__OneDB_Default_FormObject__ = array(
        "formName"      => "",
        "formId"        => "",
        "data"          => array(),
        "date"          => NULL,
        "name"          => "",
        "_server"       => array(),
        "_form"         => NULL
    );
    
    /* Default OneDB.Article.Widget configuration
     * @inherits: $__OneDB_Default_Article__
     */
    $__OneDB_Default_Widget__ = array(
        "php"             => NULL,
        "html"            => NULL,
        "css"             => NULL,
        "javascript"      => NULL,
        "templateEngine"  => "XTemplate",
        "baseURL"         => NULL,
        "developer"       => array(
            "js_inc"  => array(),
            "css_inc" => array()
        )
    );
    
    /* Default OneDB.Article.Layout configuration
     * @inerits: $__OneDB_Default_Article__
     */
    $__OneDB_Default_Layout__ = array(
        "maxItems"         => 1,          // Maximum number of items that we return
        "items"            => array(      // Here we store the layout items
        
        ),
        "acceptItemTypes"  => '/^[^*]+$/' // Regular expression for layout when adding items inside it
    );
    
    /* Default OneDB.Article.Pool configuration
     * @inherits: $__OneDB_Default_Article__
     */
    
    $__OneDB_Default_Poll__ = array(
        "title" => "",
        "options" => array(
        
        ),
        "pollType"=> "single"
    );
    
    /* This settings apply to all files that are stored 
     * via OneDB_Article('File')->storeFile method
     */
    $__OneDB_Mime_Plugins__ = array(
        '/^video\//' => array(
            "OneDB_GetVideoThumbnails"
        )
    );
    
?>