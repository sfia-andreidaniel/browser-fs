function OneDB_Path( ) {
    
    this.__class = "OneDB_Path";
    
    this.init = function() {
        
    }
    
    this.__create();
    
    return this;
}

OneDB_Path.prototype = new OneDB_Class();

OneDB_Path.prototype.isAbsolute = function( strPath ) {
    
    if ( !Strict.is_string( strPath ) )
        throw Exception("Exception.Parser", "Argument should be of type string!" );
    
    return !strPath.length || strPath.charAt( 0 ) != '/'
        ? false
        : true;
}

OneDB_Path.prototype.resolve = function( strPath ) {
    
    if ( !Strict.is_string( strPath ) )
        throw Exception("Exception.Parser", "Argument should be of type string!" );
    
    var arg   = strPath.replace( /(^[\/]+|[\/]+$)/g, '' ),
        parts = arg.split( /[\/]+/ ),
        len   = parts.length,
        i     = 0;
    
    while ( i < len ) {
        
        if ( len < 0 )
            return false;
        
        if ( i < 0 )
            i = 0;
        
        if ( len == 0 )
            return '/';
        
        switch ( parts[i] ) {
            
            case '.':
                
                parts.splice( i, 1 );
                len--;
                
                break;
            
            case '..':
                
                if ( i == 0 )
                    return false;
                
                parts.splice( i - 1, 2 );
                
                i -= 2;
                len -= 2;
                
                break;
            
            default:
            
                i++;
                break;
        }
        
    }
    
    return '/' + parts.join( '/' );
    
}

OneDB_Path.prototype.append = function( currentPath, fragment ) {
    
    return this.isAbsolute( fragment )
        ? this.resolve( fragment )
        : this.resolve( currentPath + '/' + fragment );
    
}

OneDB_Path.prototype.substract = function( path, segments ) {
    
    segments = segments || 0;
    
    if ( !Strict.is_int( segments ) || segments < 0 )
        return false;
    
    for ( var i=0; i<segments; i++ ) {
        path += '/..';
    }
    
    return this.resolve( path );
    
}

OneDB_Path.prototype.basename = function( path ) {
    
    var matches,
        result;
    
    path = this.resolve( path );
    
    if ( path === false )
        return false;
    
    if ( ( matches = /\/([^\/]+)$/.exec( path ) ) ) {
        
        result = matches[1].replace( /\+/g, '%20' );
        
        return decodeURIComponent( result );
    
    } else return false;
    
}