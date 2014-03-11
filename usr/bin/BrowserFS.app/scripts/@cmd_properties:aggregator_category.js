/* This properties plugin is used to display the general properties
   for a single inode item in the itemsList
*/

var BrowserFS_cmd_properties__aggregator_category = function( tabPanel, itemsList ) {

    if ( !itemsList.length )
        return null;
    
    if ( itemsList.length != 1 )
        return null;
    
    if ( itemsList.item(0).inode.type != 'Category_Aggregator' )
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
        "caption": "Aggregate"
    }) );

    $export("0002-holder", (new DOMPlaceable({
        "caption": "Settings",
        "appearence": "opaque"
    })).setAttr("style", "top: 20px; left: 0px; right: 2px; position: absolute; height: 5px"));

    $export("0001-lbl", (new DOMLabel("The Aggergator category is a special folder which combines items of other folders into a single location.")).setAttr("style", "top: 35px; left: 10px; right: 10px; position: absolute; text-overflow: ellipsis; white-space: normal; height: 50px"));

    $export("0002-lbl", (new DOMLabel("Aggregate paths:")).setAttr("style", "top: 120px; left: 10px; width: 100px; position: absolute; text-overflow: ellipsis"));

    $export("0001-grid", (new DataGrid()).setAttr("style", "top: 145px; left: 10px; position: absolute").setAnchors({
        "width": function(w, h) {
            return w - 75 + "px";
        },
        "height": function(w, h) {
            return h - 160 + "px";
        }
    }).setProperty("selectable", true).chain(function() {
        this.colWidths = [250];
        this.th(["Path"]);
        this.setProperty("delrow", function(row) {
            //What to do when deleting a row
            return false;
        });
    }));

    $export("0003-lbl", (new DOMLabel("Limit to:")).setAttr("style", "top: 90px; left: 10px; width: 45px; position: absolute; text-overflow: ellipsis"));

    $export("0001-spinner", (new Spinner({
        "value": 0,
        "minValue": 0,
        "maxValue": 65535
    })).setAttr("style", "top: 85px; left: 60px; position: absolute"));

    $export("0004-lbl", (new DOMLabel("items ( 0 = no limits )")).setAttr("style", "top: 90px; left: 120px; right: 10px; position: absolute; text-overflow: ellipsis"));

    $export("0001-btn", (new Button("Add", (function() {}))).setAttr("style", "top: 145px; right: 10px; position: absolute; width: 50px"));

    $export("0002-btn", (new Button("Delete", (function() {}))).setAttr("style", "top: 170px; right: 10px; position: absolute; width: 50px"));

    $import("0001-sheet").insert($import("0002-holder"));
    $import("0001-sheet").insert($import("0001-lbl"));
    $import("0001-sheet").insert($import("0002-lbl"));
    $import("0001-sheet").insert($import("0001-grid"));
    $import("0001-sheet").insert($import("0003-lbl"));
    $import("0001-sheet").insert($import("0001-spinner"));
    $import("0001-sheet").insert($import("0004-lbl"));
    $import("0001-sheet").insert($import("0001-btn"));
    $import("0001-sheet").insert($import("0002-btn"));



    return {
        
        "inputs": {
        },
        
        "interface": {
            "minWidth": 400,
            "minHeight": 300
        }
    }.chain( function() {
        
        this.load = function( ) {
            
            var first = itemsList.item( 0 ).inode;
            
        };
        
        this.save = function( ) {
        
        };
        
    } );
};