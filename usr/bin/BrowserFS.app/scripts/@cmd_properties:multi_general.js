/* This properties plugin is used to display the general properties
   for multiple items in the itemsList
*/

var BrowserFS_cmd_properties__multi_general = function( tabPanel, itemsList ) {

    if ( !itemsList.length )
        return null;
    
    if ( itemsList.length <= 1 )
        return null;

    var $namespace = {};

    var $export = function(objectID, objectData) {
        $namespace[objectID] = objectData;
        return objectData;
    };
    var $import = function(objectID) {
        return $namespace[objectID] || (function() {
            throw "Namespace " + objectID + " is not defined (yet?)";
        })();
    };

    var sheet = $export("0001-sheet", tabPanel.addTab({
        "caption": "General"
    }) );

    // used to initialize the initial statistics of keywords and tags counts
    function indexProperty( objectsList, propertyName, placeInto ) {
        
        var num = 0;
        
        for ( var i=0, len = objectsList.length; i<len; i++ ) {
            
            for ( var j=0, props = objectsList.item(i).inode[ propertyName ], lej = props.length; j < lej; j++ ) {
                
                placeInto[ props[ j ] ] = ( placeInto[ props[j] ] || 0 ) + 1;
                
                num++;
                
            }
            
        }
        
        return num;
    }
    
    // used to convert statistics to strings
    function objstats( statObject ) {
        
        var out = [];
        
        for ( var k in statObject )
            if ( statObject.propertyIsEnumerable( k ) && statObject.hasOwnProperty( k ) )
                out.push( '"' + k + '" (' + statObject[k] + ')' );
        
        return out.join( ', ' );
        
    }
    
    // we want to apply the keywords and the tags settings
    // only when the user clicks ok, but not before that.
    var keywordsState = null,
        tagsState     = null,
        saveBatch     = [];
    
    indexProperty( itemsList, 'keywords', keywordsState );
    indexProperty( itemsList, 'tags',     tagsState );

    $export("0001-holder", (new DOMPlaceable({
        "caption": "General",
        "appearence": "opaque"
    })).setAttr("style", "top: 20px; left: 0px; right: 2px; position: absolute; height: 5px"));

    $export("0001-lbl", (new DOMLabel("Selection:")).setAttr("style", "top: 35px; left: 10px; width: 60px; position: absolute; text-overflow: ellipsis"));

    $export("0002-lbl", (new DOMLabel("Types:")).setAttr("style", "top: 60px; left: 10px; width: 50px; position: absolute; text-overflow: ellipsis"));

    $export("0002-holder", (new DOMPlaceable({
        "caption": "Indexing",
        "appearence": "opaque"
    })).setAttr("style", "top: 135px; left: 0px; right: 2px; position: absolute; height: 5px"));

    $export("0003-lbl", (new DOMLabel("Keywords:")).setAttr("style", "top: 155px; left: 10px; width: 65px; position: absolute; text-overflow: ellipsis"));

    $export("0004-lbl", (new DOMLabel("Tags:")).setAttr("style", "top: 250px; left: 10px; width: 35px; position: absolute; text-overflow: ellipsis"));

    $export("0005-lbl", (new DOMLabel("0 items ( 0 folders, 0 files )")).setAttr("style", "top: 35px; left: 75px; right: 10px; position: absolute; text-overflow: ellipsis"));

    $export("0006-lbl", (new DOMLabel("2 Categories, 2 Folder categories, 3 Documents, 8 Files, 10 Pictures")).setAttr("style", "top: 60px; left: 75px; right: 10px; position: absolute; text-overflow: ellipsis; height: 45px; white-space: normal"));

    $export("0007-lbl", (new DOMLabel("\"dogs\" (3), \"cats\" (45), \"mices skeletons\" (87)")).setAttr("style", "top: 155px; left: 80px; right: 70px; position: absolute; text-overflow: ellipsis; height: 80px; white-space: normal"));

    $export("0008-lbl", (new DOMLabel("No tags are set.")).setAttr("style", "top: 250px; left: 80px; right: 70px; position: absolute; text-overflow: ellipsis; white-space: normal; height: 80px"));

    $export("0001-btn", (new Button("Add", (function() {
        
        sheet.cmd_modify( keywordsState, saveBatch, 'add', 'keywords', itemsList.length );
        
    }))).setAttr("style", "top: 155px; right: 10px; position: absolute; padding: 1px 5px; height: 20px; width: 55px; text-align: left"));

    $export("0002-btn", (new Button("Delete", (function() {

        sheet.cmd_modify( keywordsState, saveBatch, 'delete', 'keywords', itemsList.length );
    
    }))).setAttr("style", "top: 175px; right: 10px; position: absolute; padding: 1px 5px; height: 20px; width: 55px; text-align: left"));

    $export("0003-btn", (new Button("Set", (function() {

        sheet.cmd_modify( keywordsState, saveBatch, 'set', 'keywords', itemsList.length );

    }))).setAttr("style", "top: 195px; right: 10px; position: absolute; padding: 1px 5px; height: 20px; width: 55px; text-align: left"));

    $export("0004-btn", (new Button("Add", (function() {
        
        sheet.cmd_modify( tagsState, saveBatch, 'add', 'tags', itemsList.length );
        
    }))).setAttr("style", "top: 250px; right: 10px; position: absolute; padding: 1px 5px; height: 20px; width: 55px; text-align: left"));

    $export("0005-btn", (new Button("Delete", (function() {
        
        sheet.cmd_modify( tagsState, saveBatch, 'delete', 'tags', itemsList.length );
        
    }))).setAttr("style", "top: 270px; right: 10px; position: absolute; padding: 1px 5px; height: 20px; width: 55px; text-align: left"));

    $export("0006-btn", (new Button("Set", (function() {
        
        sheet.cmd_modify( tagsState, saveBatch, 'set', 'tags', itemsList.length );
        
    }))).setAttr("style", "top: 290px; right: 10px; position: absolute; padding: 1px 5px; height: 20px; width: 55px; text-align: left"));

    $export("0003-holder", (new DOMPlaceable({
        "caption": "Attributes",
        "appearence": "opaque"
    })).setAttr("style", "top: 350px; left: 0px; right: 2px; position: absolute; height: 5px"));

    $export("0001-grid", (new DataGrid()).setAttr("style", "top: 360px; left: 10px; position: absolute").setAnchors({
        "width": function(w, h) {
            return w - 20 + "px";
        },
        "height": function(w, h) {
            return h - 375 + "px";
        }
    }).setProperty("selectable", true).chain(function() {
        this.colWidths = [80, 300];
        this.th(["Name", "Value"]);
        this.setProperty("delrow", function(row) {
            //What to do when deleting a row
            return false;
        });
        
        this.enableResizing();
        
    }));

    $import("0001-sheet").insert($import("0001-holder"));
    $import("0001-sheet").insert($import("0001-lbl"));
    $import("0001-sheet").insert($import("0002-lbl"));
    $import("0001-sheet").insert($import("0002-holder"));
    $import("0001-sheet").insert($import("0003-lbl"));
    $import("0001-sheet").insert($import("0004-lbl"));
    $import("0001-sheet").insert($import("0005-lbl"));
    $import("0001-sheet").insert($import("0006-lbl"));
    $import("0001-sheet").insert($import("0007-lbl"));
    $import("0001-sheet").insert($import("0008-lbl"));
    $import("0001-sheet").insert($import("0001-btn"));
    $import("0001-sheet").insert($import("0002-btn"));
    $import("0001-sheet").insert($import("0003-btn"));
    $import("0001-sheet").insert($import("0004-btn"));
    $import("0001-sheet").insert($import("0005-btn"));
    $import("0001-sheet").insert($import("0006-btn"));
    $import("0001-sheet").insert($import("0003-holder"));
    $import("0001-sheet").insert($import("0001-grid"));

    BrowserFS_cmd_properties__multi_general__tags_keywords( sheet );

    return {
        
        "inputs": {
            
            "selection": $import( '0005-lbl' ),
            "types": $import( '0006-lbl' ),
            "keywords": $import( '0007-lbl' ),
            "tags": $import( '0008-lbl' ),
            "attributes": $import( '0001-grid' )
        },
        
        "interface": {
            "minWidth": 400,
            "minHeight": 620
        }
    }.chain( function() {
        
        this.load = function( ) {
            
            // compute the number of folders and the number of files *from selection* (NO RECURSIVITY)
            
            var nfolders = 0, nfiles = 0;
            
            for ( var i=0, len = itemsList.length; i<len; i++ ) {
                
                if ( itemsList.item(i).inode.has_flag( 'container' ) )
                    nfolders++;
                else nfiles++;
                
            }
            
            // set the "selection" label
            this.inputs.selection.label = [].chain( function() {
                
                if ( nfolders > 0 )
                    this.push( nfolders + ' Categor' + ( nfolders > 1 ? 'ies' : 'y' ) );
                
                if ( nfiles > 0 )
                    this.push( nfiles + ' Aricle' + ( nfiles > 1 ? 's' : '' ) );
                
            } ).join( ' and ' );
            
            // set the "types" label
            this.inputs.types.label = ( function() {
                
                var stats = {},
                    _type,
                    out = [];
                
                for ( var i=0, len = itemsList.length; i<len; i++ ){
                    
                    _type = itemsList.item(i).inode.type;
                    
                    stats[ _type ] = ( stats[ _type ] || 0 ) + 1;
                    
                }
                
                for ( var k in stats )
                    if ( stats.hasOwnProperty( k ) && stats.propertyIsEnumerable( k ) )
                        out.push( stats[ k ] + ' ' + k );
                
                return out.join( ', ' );
                
            } )();
            
            this.inputs.keywords.label = objstats( keywordsState ) || 'No keywords are set to any items';
            this.inputs.tags.label     = objstats( tagsState ) || 'No tags are set to any items';
            
            // compute the oldest and the newest creation dates
            this.inputs.attributes.tr( [
                
                'Created', ( function() {
                    
                    var max = 0,
                        min = ~~( ( new Date() ).getTime() / 1000 ),
                        ctime;
                    
                    for ( var i=0, len = itemsList.length; i<len; i++ ) {
                        
                        ctime = itemsList.item(i).inode.ctime;
                        
                        if ( max < ctime )
                            max = ctime;
                        
                        if ( min > ctime )
                            min = ctime;
                    }
                    
                    return ( ( new Date() ).fromString( min, '%U' ).toString( window.DEFAULT_DATE_FORMAT ) ) + ' -> ' +
                           ( ( new Date() ).fromString( max, '%U' ).toString( window.DEFAULT_DATE_FORMAT ) );
                    
                } )()
                
            ] );

            this.inputs.attributes.tr( [
                
                'Modified', ( function() {
                    
                    var max = 0,
                        min = ~~( ( new Date() ).getTime() / 1000 ),
                        ctime;
                    
                    for ( var i=0, len = itemsList.length; i<len; i++ ) {
                        
                        ctime = itemsList.item(i).inode.mtime;
                        
                        if ( max < ctime )
                            max = ctime;
                        
                        if ( min > ctime )
                            min = ctime;
                    }
                    
                    return ( ( new Date() ).fromString( min, '%U' ).toString( window.DEFAULT_DATE_FORMAT ) ) + ' -> ' +
                           ( ( new Date() ).fromString( max, '%U' ).toString( window.DEFAULT_DATE_FORMAT ) );
                    
                } )()
                
            ] );
            
            this.inputs.attributes.tr( [
                
                'User(s):', ( function(){
                    
                    var users = {},
                        out = [],
                        user;
                    
                    for ( var i=0, len = itemsList.length; i<len; i++ ) {
                        user = itemsList.item(i).inode.owner + '';
                        users[ user ] = ( users[ user ] || 0 ) + 1;
                    }
                    
                    for ( var k in users )
                        if ( users.hasOwnProperty( k ) && users.propertyIsEnumerable( k ) )
                            out.push( k + ' (' + users[ k ] + ')' );
                    
                    return out.join( ', ' );
                    
                })()
                
            ] );

            this.inputs.attributes.tr( [
                
                'Group(s):', ( function(){
                    
                    var groups = {},
                        out = [],
                        group;
                    
                    for ( var i=0, len = itemsList.length; i<len; i++ ) {
                        group = itemsList.item(i).inode.group + '';
                        groups[ group ] = ( groups[ group ] || 0 ) + 1;
                    }
                    
                    for ( var k in groups )
                        if ( groups.hasOwnProperty( k ) && groups.propertyIsEnumerable( k ) )
                            out.push( k + ' (' + groups[ k ] + ')' );
                    
                    return out.join( ', ' );
                    
                })()
                
            ] );
            
            this.inputs.attributes.render();
            
        };
        
        this.save = function( ) {
        
        };
        
    } );
};