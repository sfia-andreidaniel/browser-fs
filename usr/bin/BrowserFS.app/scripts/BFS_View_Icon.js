/* BrowserFS view icon */
function BFS_View_Icon( view, object ) {
    
    var holder = $('div', 'icon' ).chain( function() {
        
        var name = '';
        var selected = '';
        
        Object.defineProperty( this, "name", {
            "get": function() {
                return name;
            },
            "set": function(str) {
                name = String( str || '' );
                this.innerHTML = '';
                this.appendChild( $text( name ) );
            }
        } );
        
        Object.defineProperty( this, "selected", {
            "get": function() {
                return selected;
            },
            "set": function( boolVal ) {
                selected = !!boolVal;
                this[ selected ? 'addClass' : 'removeClass' ]( 'selected' );
            }
        } );
        
        Object.defineProperty( this, "index", {
            "get": function() {
                return view.getIconIndex( this );
            }
        } );
        
        Object.defineProperty( this, "row", {
            "get": function() {
                return view.getIconRow( this );
            }
        } );
        
        Object.defineProperty( this, "column", {
            "get": function() {
                return view.getIconColumn( this );
            }
        } );
        
        this.addEventListener( 'mousedown', function( evt ) {
    
            switch ( true ) {
                
                case evt.which == 1 && !evt.ctrlKey && !evt.shiftKey:
                    view.app.interface.selection.set( this );
                    view.activeItem = this;
                    break;
                
                case evt.which == 1 && evt.ctrlKey && !evt.shiftKey:
                    view.app.interface.selection.xor( this );
                    view.activeItem = this;
                    break;
                
            }
            
        }, false );
        
        this._paint_ = function() {
            this.style.backgroundImage = 'url(' + view.icons.createIcon( object, view.iconWidth, view.iconHeight ) + ')';
        }
        
        this.name = object.name;
        
        Object.defineProperty( this, "inode", {
            "get": function(){
                return object;
            }
        } );
        
        ( function( icon ) {
        
            var renameMode = false,
                input      = null,
                canceled   = false;
            
            Object.defineProperty( icon, "renameMode", {
                "get": function() {
                    return renameMode;
                },
                "set": function( bool ) {
                    
                    var newName;
                    
                    bool = !!bool;
                    
                    if ( bool == renameMode )
                        return;

                    renameMode = bool;
                    
                    icon[ renameMode ? 'addClass' : 'removeClass' ]( 'rename' );
                    
                    if ( bool === false ) {
                        
                        icon.parentNode.parentNode.keyboardEnabled = true;
                        
                        icon.parentNode.parentNode.focus();
                        
                        newName = input.value;
                        
                        // we set the icon name with the text value from the input
                        
                        icon.removeChild( input );
                        
                        input.purge();
                        
                        input = null;
                        
                        icon.name = icon.name;
                        
                        if ( newName != icon.name && !canceled )
                            icon.parentNode.parentNode.onCustomEvent( 'rename', { "source": icon, "old": icon.name, "new": newName } );
                        
                    } else {

                        icon.parentNode.parentNode.keyboardEnabled = false;

                        icon.innerHTML = '';
                        
                        input = icon.appendChild( new TextBox( icon.name ) );
                        
                        input.style.width = Math.min( input.value.visualWidth( input.value ), 150 ) + "px"
                        
                        input.focus();
                        input.select();
                        
                        Keyboard.bindKeyboardHandler( input, 'enter', function() {
                            canceled = false;
                            icon.renameMode = false;
                        });
                        
                        Keyboard.bindKeyboardHandler( input, 'esc', function() {
                            canceled = true;
                            icon.renameMode = false;
                        } );
                        
                        input.addEventListener( 'input', function( evt ) {
                            input.style.width = Math.min( input.value.visualWidth( input.value ), 150 ) + "px";
                        }, false );
                        
                        input.addEventListener( 'blur', function( evt ) {
                            icon.renameMode = false;
                        }, true );
                        
                    }
                    
                }
            });
            
        })( this );
        
        this._paint_();
        
    } );
    
    return holder;
    
}