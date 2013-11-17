/* System users
 */

( function() {
    
    function Sys_Security_Management_User_Unauthenticated( sys, props ) {
    
        this.__class = 'Sys_Security_Management_User_Unauthenticated';
        
        this.init = function() {
            
            //console.log( 'new user: ', props );
            
            this.id = props._id;
            
            Object.defineProperty( this, "name", {
                "get": function() {
                    return props.name;
                }
            } );
            
            Object.defineProperty( this, "uid", {
                "get": function() {
                    return props._id;
                }
            });
            
            Object.defineProperty( this, "flags", {
                "get": function() {
                    return props.flags;
                }
            });
            
            Object.defineProperty( this, "umask", {
                "get": function() {
                    return props.umask;
                }
            });
            
            var members = null;
            
            Object.defineProperty( this, 'groups', {
                "get": function() {
                    if ( members === null ) {
                        var member;
                            members = [];
                        for ( var i=0, len = props.members.length; i<len; i++ )
                            if ( member = sys.group( members[i] ) )
                                members.push( member );
                    }
                    return members;
                }
            });
            
            Object.defineProperty( this, 'str_umask', {
                "get": function() {
                    return Umask.mode_to_str( props.umask, Umask.MASK_OCTAL );
                }
            });
        }
        
        this.__create();
        
        return this;
    
    };

    Sys_Security_Management_User_Unauthenticated.prototype = new OneDB_Base();
    Sys_Security_Management_User_Unauthenticated.prototype.toString = function() {
        return this.name;
    }

    window.Sys_Security_Management_User_Unauthenticated = Sys_Security_Management_User_Unauthenticated;

})();