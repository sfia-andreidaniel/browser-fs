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
                
                case evt.which == 1 && !evt.ctrlKey && evt.shiftKey:
                    
                    
                    
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
        
        this._paint_();
        
    } );
    
    return holder;
    
}