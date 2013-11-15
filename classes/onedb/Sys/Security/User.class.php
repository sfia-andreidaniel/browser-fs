<?php
    
    /*
    
        User Struc:
            {
                "_id"     : <int>,           // user id
                "type"    : "user",          // constant, = 'user'
                "name"    : <string>,        // user name
                "members" : [ 2, 4, 8, 9 ],  // groups id apartenence list
                "umask"   : <int>            // default user mask
                "flags"   : <int>            // other user flags
            }
    
     */
    
    class Sys_Security_User extends Object {
        
    }
    
?>