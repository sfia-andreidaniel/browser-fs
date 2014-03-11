/* This properties plugin is used to display the general properties
   for a single inode item in the itemsList
*/

var BrowserFS_cmd_properties__list = function( tabPanel, itemsList ) {

    if ( !itemsList.length )
        return null;
    
    if ( itemsList.length != 1 )
        return null;
    
    if ( itemsList.item(0).inode.type != 'List' )
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
        "caption": "List"
    }) );

    $export("0001-holder", (new DOMPlaceable({
        "caption": "List settings",
        "appearence": "opaque"
    })).setAttr("style", "top: 20px; left: 0px; right: 2px; position: absolute; height: 5px"));

    $export("0001-lbl", (new DOMLabel("Accept item type(s):")).setAttr("style", "top: 35px; left: 10px; width: 115px; position: absolute; text-overflow: ellipsis"));

    $export("0002-lbl", (new DOMLabel("Return max items:")).setAttr("style", "top: 65px; left: 10px; width: 110px; position: absolute; text-overflow: ellipsis"));

    $export("0001-text", (new TextBox("")).setAttr("style", "top: 30px; left: 130px; position: absolute; margin: 0").setAnchors({
        "width": function(w, h) {
            return w - 145 + "px";
        }
    }));

    $export("0001-spinner", (new Spinner({
        "value": 0,
        "minValue": -1,
        "maxValue": 65535
    })).setAttr("style", "top: 60px; left: 130px; position: absolute"));

    $export("0003-lbl", (new DOMLabel("( -1 all items )")).setAttr("style", "top: 65px; left: 195px; right: 10px; position: absolute; text-overflow: ellipsis"));

    $import("0001-sheet").insert($import("0001-holder"));
    $import("0001-sheet").insert($import("0001-lbl"));
    $import("0001-sheet").insert($import("0002-lbl"));
    $import("0001-sheet").insert($import("0001-text"));
    $import("0001-sheet").insert($import("0001-spinner"));
    $import("0001-sheet").insert($import("0003-lbl"));

    return {
        
        "inputs": {
        },
        
        "interface": {
            "minWidth": 400,
            "minHeight": 220
        }
    }.chain( function() {
        
        this.load = function( ) {
            
            var first = itemsList.item( 0 ).inode;
            
        };
        
        this.save = function( ) {
        
        };
        
    } );
};