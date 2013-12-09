function BFS_IconManager() {
    
    var resources = {
        "File.svg"               : "{$include resources/File.svg}",
        "File#image.svg"         : "{$include resources/File#image.svg}",
        "File#audio.svg"         : "{$include resources/File#audio.svg}",
        "File#video.svg"         : "{$include resources/File#video.svg}",
        "Widget.svg"             : "{$include resources/Widget.svg}",
        "Poll.svg"               : "{$include resources/Poll.svg}",
        "File#text.svg"          : "{$include resources/File#text.svg}",
        "Document.svg"           : "{$include resources/Document.svg}",
        "Item.svg"               : "{$include resources/Item.svg}",
        "Category.Search.svg"    : "{$include resources/Category.Search.svg}",
        "Category.WebService.svg": "{$include resources/Category.WebService.svg}",
        "Category.svg"           : "{$include resources/Category.svg}",
        "Category.Aggregator.svg": "{$include resources/Category.Aggregator.svg}"
    };
    
    var canvases = {},
        images   = {};
    
    for ( var key in resources ) {
        
        if ( resources.propertyIsEnumerable( key ) && resources.hasOwnProperty( key ) && resources[key] )
            images[ key ] = $('img').chain( function() {
                this.resourceName = key;
            } ).setAttr('src', 'data:image/svg+xml;base64,' + resources[key]);
        
    }
    
    this.createImage = function( resourceName, width, height ) {
        
        if ( !images[ resourceName ] )
            throw Exception( 'IO', 'Bad resource name: ' + resourceName );
        
        var canvasKey = ~~width + "x" + ~~height;
        
        width = ~~width;
        height= ~~height;
        
        canvases[ canvasKey ] = canvases[ canvasKey ] || $('canvas').chain( function() {
            
            this.width = width;
            this.height= height;
            
        } );
        
        var ctx = canvases[ canvasKey ].getContext( '2d' ),
            img = images[ resourceName ];
        
        ctx.fillStyle = '#000000ff';
        ctx.fillRect( 0, 0, width, height );
        
        ctx.drawImage( img, 0, 0, img.width, img.height, 0, 0, width, height );
        
        return canvases[canvasKey].toDataURL();
    }
    
    var standardTypes = [
        'Category',
        'Category.Search',
        'Category.WebService',
        'Category.Aggregator',
        'Document',
        'Widget',
        'List',
        'Poll'
    ];
    
    this.createIcon = function( objectOneDB, width, height ) {
        
        var type = objectOneDB.type + '';
        
        //console.log( 'Create icon: ', type, width, height );
        
        switch ( true ) {
            
            case standardTypes.indexOf( type ) >= 0:
                
                var img = this.createImage( type + ".svg", width, height );
                
                //console.log( img );
                
                return img;
                break;
            
            case type == 'File':
                
                return this.createImage( 'File.svg', width, height );
                break;
            
            default:
            
                return this.createImage( 'Item.svg', width, height );
                break;
            
        }
        
    };
    
    return this;
}