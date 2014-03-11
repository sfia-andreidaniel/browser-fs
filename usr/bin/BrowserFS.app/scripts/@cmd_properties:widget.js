/* This properties plugin is used to display the general properties
   for a single inode item in the itemsList
*/

var BrowserFS_cmd_properties__widget = function( tabPanel, itemsList ) {

    if ( !itemsList.length )
        return null;
    
    if ( itemsList.length != 1 )
        return null;
    
    if ( itemsList.item(0).inode.type != 'Widget' )
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
        "caption": "Widget"
    }) );

    $export("0001-lbl", (new DOMLabel("Widget type:")).setAttr("style", "top: 35px; left: 10px; width: 75px; position: absolute; text-overflow: ellipsis"));

    $export("0001-drop", (new DropDown(undefined)).setItems([{
        "id": "php",
        "name": "PHP"
    }, {
        "id": "html",
        "name": "HTML"
    }, {
        "id": "xtemplate",
        "name": "XTemplate"
    }]).setAttr("style", "top: 30px; left: 95px; position: absolute; margin: 0").setAnchors({
        "width": function(w, h) {
            return w - 105 + "px";
        }
    }));

    $export("0001-holder", (new DOMPlaceable({
        "caption": "Advanced settings",
        "appearence": "opaque"
    })).setAttr("style", "top: 20px; left: 0px; right: 2px; position: absolute; height: 5px"));

    $export("0002-lbl", (new DOMLabel("A PHP widget is an embeddable module in a website, which returns a HTML or other specific output.")).setAttr("style", "top: 75px; left: 10px; right: 10px; position: absolute; text-overflow: ellipsis; height: 75px; white-space: normal"));

    $import("0001-sheet").insert($import("0001-lbl"));
    $import("0001-sheet").insert($import("0001-drop"));
    $import("0001-sheet").insert($import("0001-holder"));
    $import("0001-sheet").insert($import("0002-lbl"));

    return {
        
        "inputs": {
        },
        
        "interface": {
            "minWidth": 400,
            "minHeight": 380
        }
    }.chain( function() {
        
        this.load = function( ) {
            
            var first = itemsList.item( 0 ).inode;
            
        };
        
        this.save = function( ) {
        
        };
        
    } );
};