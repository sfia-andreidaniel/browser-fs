/* System groups
 */

( function() {
    
    function Sys_Security_Management_Group( sys, props ) {
    
        this.__class = 'Sys_Security_Management_Group';
        
        this.init = function() {
            
            this.id = props._id;
            
            Object.defineProperty( this, 'gid', {
                "get": function() {
                    return props._id;
                }
            });
            
            Object.defineProperty( this, 'name', {
                "get": function() {
                    return props.name;
                }
            });
            
            Object.defineProperty( this, 'flags', {
                "get": function() {
                    return props.flags;
                }
            });
            
            var members = null;
            
            Object.defineProperty( this, 'users', {
                
                "get": function() {
                    if ( members === null ) {
                        var member;
                            members = [];
                        for ( var i=0, len = props.members.length; i<len; i++ ) {
                            if ( member = sys.user( props.members[i] ) )
                                members.push( member );
                        }
                    }
                    return members;
                }
                
            });
            
        }
        
        this.__create();
        
        return this;
    
    };

    Sys_Security_Management_Group.prototype = new OneDB_Base();
    Sys_Security_Management_Group.prototype.toString = function() {
        return this.name;
    }

    window.Sys_Security_Management_Group = Sys_Security_Management_Group;

})();