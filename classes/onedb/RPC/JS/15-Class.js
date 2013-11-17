function OneDB_Class( ) {
    
    this.__class = "OneDB_Class";
    
    /* Adds a remote server method on the current object instance.
       This method will be used on class ancestors.
     */
    
    this.addServerMethod = function( methodName, methodArgs ) {
        
        ( function( me ) {
            me[ methodName ] = function() {
                
                //console.log( "run: ", methodName, Array.prototype.slice.call( arguments, 0 ) );
                
                return OneDB.runEndpointMethod( me, methodName, Array.prototype.slice.call( arguments, 0 ) );
                
            };
        })(this);
        
    }
    
}

OneDB_Class.prototype = new OneDB_Base();