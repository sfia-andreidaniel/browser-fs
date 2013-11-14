<?php

    /* Unix file inspired permissions security module */
    
    /*
    
    //               0 1 2 3 4 5 6 7 8 9
    ///////////////////////////////////////////////////
    
    [ur] => 512   // 1 0 0 0 0 0 0 0 0 0  | USER   READ
    [uw] => 256   // 0 1 0 0 0 0 0 0 0 0  | USER   WRITE
    [ux] => 128   // 0 0 1 0 0 0 0 0 0 0  | USER   EXEC
    [gr] => 64    // 0 0 0 1 0 0 0 0 0 0  | GROUP  READ
    [gw] => 32    // 0 0 0 0 1 0 0 0 0 0  | GROUP  WRITE
    [gx] => 16    // 0 0 0 0 0 1 0 0 0 0  | GROUP  EXEC
    [or] => 8     // 0 0 0 0 0 0 1 0 0 0  | OTHER  READ
    [ow] => 4     // 0 0 0 0 0 0 0 1 0 0  | OTHER  WRITE
    [ox] => 2     // 0 0 0 0 0 0 0 0 1 0  | OTHER  EXEC
    [s] => 1      // 0 0 0 0 0 0 0 0 0 1  | STICKY BIT
    [n] => 0      // 0 0 0 0 0 0 0 0 0 0  | NO PERMISSIONS
    
    MODE INT      // PERMISSIONS MODE - COMBINATIONS OF BITS ABOVE
    GID  INT      // GROUP ID
    UID  INT      // USER  ID
    
    SPECIAL USERS AND THEIR ROLES:
    0 // NOBODY
    1 // ROOT
    
    SPECIAL GROUPS AND THEIR ROLES:
    0 // NOBODY
    1 // ROOT
    
    DEFAULT USER CREATION MASK:
    ROOT   : rwx------- // what creates root, stays to root
    NOBODY : rwxrwxrwx- // what creates nobody, stays to anyone
    
    
    
    */
    
    class OneDB_Security extends Object {
        
        
        
    }


?>