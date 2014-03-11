/* This properties plugin is used to display the general properties
   for a single inode item in the itemsList
*/

var BrowserFS_cmd_properties__webservice_category = function( tabPanel, itemsList ) {

    if ( !itemsList.length )
        return null;
    
    if ( itemsList.length != 1 )
        return null;
    
    if ( itemsList.item(0).inode.type != 'Category_WebService' )
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
        "caption": "WebService"
    }) );

    $export("0001-holder", (new DOMPlaceable({
        "caption": "Settings",
        "appearence": "opaque"
    })).setAttr("style", "top: 20px; left: 0px; right: 2px; position: absolute; height: 5px"));

    $export("0001-lbl", (new DOMLabel("A WebService category is a special folder which contains items fetched via an external http(s) location. The content should be in JSON format.")).setAttr("style", "top: 30px; left: 10px; right: 10px; position: absolute; text-overflow: ellipsis; height: 45px; white-space: normal"));

    $export("0002-lbl", (new DOMLabel("Url (absolute):")).setAttr("style", "top: 80px; left: 10px; width: 80px; position: absolute; text-overflow: ellipsis"));

    $export("0003-lbl", (new DOMLabel("Authentication:")).setAttr("style", "top: 115px; left: 10px; width: 85px; position: absolute; text-overflow: ellipsis"));

    $export("0004-lbl", (new DOMLabel("Timeout (seconds):")).setAttr("style", "top: 150px; left: 10px; width: 105px; position: absolute; text-overflow: ellipsis"));

    $export("0005-lbl", (new DOMLabel("Max. objects:")).setAttr("style", "top: 185px; left: 10px; width: 75px; position: absolute; text-overflow: ellipsis"));

    $export("0006-lbl", (new DOMLabel("Objects Array path:")).setAttr("style", "top: 220px; left: 10px; width: 110px; position: absolute; text-overflow: ellipsis"));

    $export("0007-lbl", (new DOMLabel("Request parameters:")).setAttr("style", "top: 255px; left: 10px; width: 120px; position: absolute; text-overflow: ellipsis"));

    $export("0009-lbl", (new DOMLabel("Last fetch:")).setAttr("style", "bottom: 10px; left: 10px; width: 65px; position: absolute; text-overflow: ellipsis"));

    $export("0001-text", (new TextBox("")).setAttr("style", "top: 75px; left: 135px; position: absolute; margin: 0").setProperty("placeholder", "http://www.my-company/my-webservice.json").setAnchors({
        "width": function(w, h) {
            return w - 155 + "px";
        }
    }));

    $export("0010-lbl", (new DOMLabel("user \"root\" (using password)")).setAttr("style", "top: 115px; left: 140px; right: 75px; position: absolute; text-overflow: ellipsis"));

    $export("0001-btn", (new Button("Change ...", (function() {}))).setAttr("style", "top: 110px; right: 10px; position: absolute"));

    $export("0001-spinner", (new Spinner({
        "value": 0,
        "minValue": 0,
        "maxValue": 3600
    })).setAttr("style", "top: 145px; left: 135px; position: absolute; width: 100px"));

    $export("0011-lbl", (new DOMLabel("(-1 no limit)")).setAttr("style", "top: 185px; left: 250px; right: 10px; position: absolute; text-overflow: ellipsis"));

    $export("0002-spinner", (new Spinner({
        "value": -1,
        "minValue": -1,
        "maxValue": 65535
    })).setAttr("style", "top: 180px; left: 135px; width: 100px; position: absolute"));

    $export("0002-text", (new TextBox("")).setAttr("style", "top: 215px; left: 135px; position: absolute; margin: 0").setProperty("placeholder", "meta.items (leave blank if unsure)").setAnchors({
        "width": function(w, h) {
            return w - 155 + "px";
        }
    }));

    $export("0001-grid", (new DataGrid()).setAttr("style", "top: 280px; left: 10px; position: absolute").setAnchors({
        "width": function(w, h) {
            return w - 80 + "px";
        },
        "height": function(w, h) {
            return h - 320 + "px";
        }
    }).setProperty("selectable", true).chain(function() {
        this.colWidths = [80, 80, 160];
        this.th(["Type", "Name", "Value"]);
        this.setProperty("delrow", function(row) {
            //What to do when deleting a row
            return false;
        });
        this.enableResizing();
        this.enableSorting();
    }));

    $export("0002-btn", (new Button("Add", (function() {}))).setAttr("style", "top: 280px; right: 10px; position: absolute; width: 50px"));

    $export("0003-btn", (new Button("Delete", (function() {}))).setAttr("style", "top: 305px; right: 10px; position: absolute; width: 50px"));

    $export("0012-lbl", (new DOMLabel("never")).setAttr("style", "bottom: 10px; left: 75px; right: 10px; position: absolute; text-overflow: ellipsis"));

    $import("0001-sheet").insert($import("0001-holder"));
    $import("0001-sheet").insert($import("0001-lbl"));
    $import("0001-sheet").insert($import("0002-lbl"));
    $import("0001-sheet").insert($import("0003-lbl"));
    $import("0001-sheet").insert($import("0004-lbl"));
    $import("0001-sheet").insert($import("0005-lbl"));
    $import("0001-sheet").insert($import("0006-lbl"));
    $import("0001-sheet").insert($import("0007-lbl"));
    $import("0001-sheet").insert($import("0009-lbl"));
    $import("0001-sheet").insert($import("0001-text"));
    $import("0001-sheet").insert($import("0010-lbl"));
    $import("0001-sheet").insert($import("0001-btn"));
    $import("0001-sheet").insert($import("0001-spinner"));
    $import("0001-sheet").insert($import("0011-lbl"));
    $import("0001-sheet").insert($import("0002-spinner"));
    $import("0001-sheet").insert($import("0002-text"));
    $import("0001-sheet").insert($import("0001-grid"));
    $import("0001-sheet").insert($import("0002-btn"));
    $import("0001-sheet").insert($import("0003-btn"));
    $import("0001-sheet").insert($import("0012-lbl"));

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