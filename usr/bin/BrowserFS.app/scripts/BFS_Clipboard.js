function _BFS_Clipboard_() {
    
    var cl;
    
    function BFS_Clipboard() {
        
        var items = [],
            effect= 'copy';
        
        Object.defineProperty( this, "data", {
            
            "get": function() {
                return items.length
                    ? {
                        "effect": effect,
                        "items" : items
                    }
                    : null;
            }
            
        } );
        
        var setClipboard = function( selectionItemsList, effectMode ) {
            items = [];
            for ( var i=0, len=selectionItemsList.length; i<len; i++ )
                items.push( selectionItemsList.item(i).inode );
            effect = effectMode;
        }
        
        this.cut = function( selectionItemsList ) {
            setClipboard( selectionItemsList, 'cut' );
        };
        
        this.copy = function( selectionItemsList ) {
            setClipboard( selectionItemsList, 'copy' );
        }
        
        return this;
    }
    
    BFS_Clipboard.prototype = new Thing();
    
    if ( typeof window.BFS_Clipboard == 'undefined' ) {
        
        cl = new BFS_Clipboard();
        
        Object.defineProperty( window, "BFS_Clipboard", {
            
            "get": function() { 
                return cl;
            }
            
        } );
    }
    
}

setTimeout( _BFS_Clipboard_, 100 );