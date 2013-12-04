function OneDB_Frontend( name, buffer, blocks ) {
    
    this.__class = "OneDB_Frontend";
    
    /* @param branch <string>
     * @param tpl    <XTemplate>
     */
    var createSection = ( function() {
        
        return function( branch, _tpl, root ) {
        
            var _branch_   = branch,
                _tpl_      = _tpl,
                _needParse = false;
            
            this.add = function( type, value, inline ) {
                
                if ( root.done )
                    throw Exception( 'Exception.Frontend', "tried to add something after the .getText() method of the frontend has been called. You need to call the .restart() method to be able to add again!" );
                
                inline = !!inline;
                
                if ( !Strict.is_string( type ) || !Strict.is_string( value ) )
                    throw Exception( 'Exception.Frontend', "arguments #1 and #2 should be strings!" );
                
                var blockName = '';
                
                switch ( type ) {
                    
                    case 'script':
                        blockName = '_' + ( inline ? 'inline' : '' ) + 'script_';
                        break;
                    
                    case 'css':
                        blockName = '_' + ( inline ? 'inline' : '' ) + 'css_';
                        break;
                    
                    case 'code':
                        blockName = '_code_';
                        break;
                    
                    default:
                        throw Exception( 'Exception.Frontend', 'argument #1 should be "script", "css", or "code"' );
                        break;
                    
                }
                
                _tpl_.assign( 'src', value );
                _tpl_.parse( _branch_ + '.' + blockName );
                _needParse = true;
            };
            
            this.assign = function( propertyName, propertyValue ) {

                if ( root.done )
                    throw Exception( 'Exception.Frontend', "tried to assign something after the .getText() method of the frontend has been called. You need to call the .restart() method to be able to add again!" );
                
                if ( Strict.is_string( propertyName ) && Strict.is_string( propertyValue ) ) {
                    
                    _tpl_.assign( propertyName, propertyValue );
                    _needParse = true;
                    
                } else throw Exception( 'Exception.Frontend', 'arguments should be of type string!' );
            }
            
            this.parse = function() {
                if ( _needParse ) {
                    _tpl_.parse( _branch_ );
                }
            }
            
            this.restart = function() {
                _needParse = false;
                _tpl_      = root._tpl_;
            }
            
            return this;
        };
        
    })();
    
    var createBlock = ( function() {
        
        return function( branch, _tpl, root ) {
            
            var _branch_ = branch,
                _tpl_    = _tpl;
            
            this.add = function( code ) {
                
                if ( root.done )
                    throw Exception( 'Exception.Frontend', "tried to add something after the .getText() method of the frontend has been called. You need to call the .restart() method to be able to add again!" );

                if ( !Strict.is_string( code ) )
                    throw Exception( 'OneDB.Frontend', 'Argument should be of type string' );
                
                _tpl_.assign( 'value', code );
                _tpl_.parse( _branch_ );
                
            };
            
            this.restart = function() {
                _tpl_ = root._tpl_;
            }
            
            return this;
            
        };
        
    } )();
    
    this.init = function() {
        
        var _name   = name,
            _buffer = buffer,
            _blocks = blocks,
            _tpl    = new XTemplate( _buffer ),
            _begin  = new createSection( 'main._begin_', _tpl, this ),
            _end    = new createSection( 'main._end_',   _tpl, this ),
            _theText= null
        ;
        
        Object.defineProperty( this, 'name', {
            "get": function() {
                return _name;
            }
        });
        
        Object.defineProperty( this, "buffer", {
            "get": function() {
                return _buffer;
            }
        });
        
        Object.defineProperty( this, "blocks", {
            "get": function() {
                return _blocks;
            }
        } );
        
        Object.defineProperty( this, "_tpl_", {
            "get": function() {
                return _tpl;
            }
        });
        
        Object.defineProperty( this, "begin", {
            "get": function() {
                return _begin;
            }
        });

        Object.defineProperty( this, "end", {
            "get": function() {
                return _end;
            }
        });
        
        Object.defineProperty( this, "done", {
            "get": function() {
                return _theText !== null;
            }
        } );
        
        /* Define frontend blocks */
        
        for ( var i=0, len = _blocks.length; i<len; i++ ) {
            ( function( block, me ) {
                
                var _inst = new createBlock( 'main.' + block, _tpl, me );
                
                Object.defineProperty( me, block, {
                    "get": function() {
                        return _inst;
                    }
                });
                
            } )( _blocks[i], this );
        }
        
        this.restart = function() {
            _tpl = new XTemplate( _buffer );
            _begin.restart();
            _end.restart();
            
            for ( var i=0, len = _blocks.length; i<len; i++ ) {
                this[ _blocks[i] ].restart();
            }
            
            _theText = null;
        }
        
        this.assign = function( propertyName, propertyValue ) {
            _begin.assign( propertyName, propertyValue );
        };
        
        this.getText = function() {
            
            if ( _theText === null ) {
                
                _begin.parse();
                _end.parse();
                
                _tpl.parse( 'main' );
                _tpl.parse();
                
                _theText = _tpl.text + '';
            }
            
            return _theText;
        };
        
    }
    
    this.__create();
    
    return this;
}

OneDB_Frontend.prototype = new OneDB_Class();

OneDB_Frontend.prototype.__demux = function( muxedData ) {
    return new OneDB_Frontend( muxedData[0], muxedData[1], muxedData[2] );
}