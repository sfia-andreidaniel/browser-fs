/* System users and groups management
 */

( function() {
    
    function Sys_Security_Management( server, users, groups ) {
    
        this.__class = 'Sys_Security_Management';
        
        this.init = function() {
        
            var _root   = server || null,
                _users  = users  || {},
                _groups = groups || {};
        
            this.user = function( uidOrName ) {
                
                switch (true) {
                    case Strict.is_int( uidOrName ):
                        
                        if ( Strict.isset( _users[ uidOrName ] ) )
                            return _users[ uidOrName ]._singleton
                                ? _users[ uidOrName ]._singleton
                                : _users[ uidOrName ]._singleton = new Sys_Security_Management_User_Unauthenticated( this, _users[ uidOrName ] );
                        else
                            return null;
                        
                        break;
                    
                    case Strict.is_string( uidOrName ):
                        
                        for ( var k in _users )
                            if ( _users[k] && _users[k].name == uidOrName )
                                return _users[ k ]._singleton
                                    ? _users[ k ]._singleton
                                    : _users[ k ]._singleton = new Sys_Security_Management_User_Unauthenticated( this, _users[ k ] );
                        
                        return null;
                        
                        break;
                    default:
                        
                        throw Exception( 'Exception.Sys', '@param uidOrName must be of type int or string' );
                        
                        break;
                }
                
            }
            
            this.group= function( gidOrName ) {

                switch (true) {
                    case Strict.is_int( gidOrName ):
                        
                        if ( Strict.isset( _groups[ gidOrName ] ) )
                            return _groups[ gidOrName ]._singleton
                                ? _groups[ gidOrName ]._singleton
                                : _groups[ gidOrName ]._singleton = new Sys_Security_Management_Group( this, _groups[ gidOrName ] );
                        else
                            return null;
                        
                        break;
                    
                    case Strict.is_string( gidOrName ):
                        
                        for ( var k in _groups )
                            if ( _groups[k] && _groups[k].name == gidOrName )
                                return _groups[ k ]._singleton
                                    ? _groups[ k ]._singleton
                                    : _groups[ k ]._singleton = new Sys_Security_Management_Group( this, _groups[ k ] );
                        
                        return null;
                        
                        break;
                    default:
                        
                        throw Exception( 'Exception.Sys', '@param uidOrName must be of type int or string' );
                        
                        break;
                }
                
            }
        
        }
        
        this.__create();
        
        return this;
    
    };

    Sys_Security_Management.prototype = new OneDB_Base();

    Sys_Security_Management.prototype.__demux = function( muxedData ) {
        
        return new Sys_Security_Management( OneDB.__demux( muxedData.root), muxedData.users, muxedData.groups );
        
    }

    window.Sys_Security_Management = Sys_Security_Management;

})();