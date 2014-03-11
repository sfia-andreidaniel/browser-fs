/* This properties plugin is used to display the general properties
   for a single inode item in the itemsList
*/

var BrowserFS_cmd_properties__search_category = function( tabPanel, itemsList ) {

    if ( !itemsList.length )
        return null;
    
    if ( itemsList.length != 1 )
        return null;
    
    if ( itemsList.item(0).inode.type != 'Category_Search' )
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
        "caption": "Searching"
    }) );

    $export("0001-holder", (new DOMPlaceable({
        "caption": "Settings",
        "appearence": "opaque"
    })).setAttr("style", "top: 20px; left: 0px; right: 2px; position: absolute; height: 5px"));

    $export("0002-lbl", (new DOMLabel("A search category is a special folder which contains items obtained by a search in database, based on a search criteria.")).setAttr("style", "top: 30px; left: 10px; right: 10px; position: absolute; text-overflow: ellipsis; height: 40px; white-space: normal"));

    $export("0003-lbl", (new DOMLabel("Search criteria (JSON format):")).setAttr("style", "top: 75px; left: 10px; width: 165px; position: absolute; text-overflow: ellipsis"));

    $export("0001-source", (new AceEditor()).setAnchors({
        "width": function( w,h ) {
            return w - 20 + "px";
        }
    }).setAttr("style", "top: 100px; left: 10px; right: 10px; bottom: 10px; height: auto; position: absolute").chain(function() {
        this.syntax = "javascript";
    }));

    $import("0001-sheet").insert($import("0001-holder"));
    $import("0001-sheet").insert($import("0002-lbl"));
    $import("0001-sheet").insert($import("0003-lbl"));
    $import("0001-sheet").insert($import("0001-source"));

    return {
        
        "inputs": {
            "source": $import( '0001-source' )
        },
        
        "interface": {
            "minWidth": 400,
            "minHeight": 300
        }
    }.chain( function() {
        
        this.load = function( ) {
            
            var first = itemsList.item( 0 ).inode;
            
            this.inputs.source.value = JSON.beautify( JSON.stringify( first.data.query ) ).replace(/^\[\]$/, '{}');
            
        };
        
        this.save = function( ) {
        
        };
        
    } );
};