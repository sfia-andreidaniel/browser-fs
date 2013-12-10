function BFS_IconManager( app ) {
    
    var resources = {
        "File.svg"               : "{$include resources/mime/File.svg}",
        "File#image.svg"         : "{$include resources/mime/File#image.svg}",
        "File#audio.svg"         : "{$include resources/mime/File#audio.svg}",
        "File#video.svg"         : "{$include resources/mime/File#video.svg}",
        "Widget.svg"             : "{$include resources/mime/Widget.svg}",
        "Poll.svg"               : "{$include resources/mime/Poll.svg}",
        "File#text.svg"          : "{$include resources/mime/File#text.svg}",
        "Document.svg"           : "{$include resources/mime/Document.svg}",
        "Item.svg"               : "{$include resources/mime/Item.svg}",
        "Category.Search.svg"    : "{$include resources/mime/Category.Search.svg}",
        "Category.WebService.svg": "{$include resources/mime/Category.WebService.svg}",
        "Category.svg"           : "{$include resources/mime/Category.svg}",
        "Category.Aggregator.svg": "{$include resources/mime/Category.Aggregator.svg}",
        "List.svg"               : "{$include resources/mime/List.svg}"
    }, 
    
    numResources = ( function() {
        
        var len = 0;
        
        for ( var k in resources )
            if ( resources.hasOwnProperty( k ) && resources.propertyIsEnumerable( k ) )
                len++;
            
        return len;
        
    } )(),
    
    loadedResources = 0;
    
    var canvases = {},
        images   = {};
    
    for ( var key in resources ) {
        
        if ( resources.propertyIsEnumerable( key ) && resources.hasOwnProperty( key ) && resources[key] )
            images[ key ] = $('img').chain( function() {
                this.resourceName = key;
                this.onload = function() {
                    loadedResources++;
                    if ( loadedResources == numResources )
                        app.interface.on( 'resources-loaded' );
                }
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
        
        ctx.globalAlpha = 0;
        
        ctx.fillStyle = '#ffff';
        ctx.fillRect( 0, 0, width, height );
        //ctx.globalCompositeOperation = 'source-over';
        
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