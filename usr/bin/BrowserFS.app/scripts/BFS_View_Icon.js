/* BrowserFS view icon */
function BFS_View_Icon( view, object ) {
    
    var holder = $('div', 'icon' ).chain( function() {
        
        var name = '';
        
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