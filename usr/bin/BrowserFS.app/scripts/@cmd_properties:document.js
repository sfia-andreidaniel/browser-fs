/* This properties plugin is used to display the general properties
   for a single inode item in the itemsList
*/

var BrowserFS_cmd_properties__document = function( tabPanel, itemsList ) {

    if ( !itemsList.length )
        return null;
    
    if ( itemsList.length != 1 )
        return null;
    
    if ( itemsList.item(0).inode.type != 'Document' )
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
        "caption": "Document"
    }) );

    $export("0001-holder", (new DOMPlaceable({
        "caption": "Publishing",
        "appearence": "opaque"
    })).setAttr("style", "top: 20px; left: 0px; right: 2px; position: absolute; height: 5px"));

    $export("0001-lbl", (new DOMLabel("Title:")).setAttr("style", "top: 45px; left: 10px; width: 30px; position: absolute; text-overflow: ellipsis"));

    $export("0002-lbl", (new DOMLabel("Description:")).setAttr("style", "top: 75px; left: 10px; width: 70px; position: absolute; text-overflow: ellipsis"));

    $export("0003-lbl", (new DOMLabel("Language:")).setAttr("style", "top: 145px; left: 10px; width: 65px; position: absolute; text-overflow: ellipsis"));

    $export("0004-lbl", (new DOMLabel("Scheduling:")).setAttr("style", "top: 180px; left: 10px; width: 75px; position: absolute; text-overflow: ellipsis"));

    $export("0002-holder", (new DOMPlaceable({
        "caption": "Manual relationing",
        "appearence": "opaque"
    })).setAttr("style", "top: 265px; left: 0px; right: 2px; position: absolute; height: 5px"));

    $export("0006-lbl", (new DOMLabel("Online")).setAttr("style", "top: 215px; left: 30px; width: 45px; position: absolute; text-overflow: ellipsis"));

    $export("0001-grid", (new DataGrid()).setAttr("style", "top: 300px; left: 10px; position: absolute").setAnchors({
        "width": function(w, h) {
            return w - 150 + "px";
        },
        "height": function(w, h) {
            return h - 315 + "px";
        }
    }).setProperty("selectable", true).chain(function() {
        this.colWidths = [240];
        this.th(["Document"]);
        this.setProperty("delrow", function(row) {
            //What to do when deleting a row
            return false;
        });
    }));

    $export("0007-lbl", (new DOMLabel("You can manually choose a list of related items with this one:")).setAttr("style", "top: 280px; left: 10px; right: 10px; position: absolute; text-overflow: ellipsis"));

    $export("0001-text", (new TextBox("")).setAttr("style", "top: 40px; left: 95px; position: absolute; margin: 0").setProperty("placeholder", "Can contain special characters").setAnchors({
        "width": function(w, h) {
            return w - 245 + "px";
        }
    }));

    $export("0001-textArea", (new TextArea("")).setAttr("style", "top: 70px; left: 95px; position: absolute; height: 50px").setProperty("placeholder", "A short plain-text description of the document.").setAnchors({
        "width": function(w, h) {
            return w - 245 + "px";
        }
    }));

    $export("0002-text", (new TextBox("")).setAttr("style", "top: 140px; left: 95px; position: absolute; margin: 0; width: 80px").setProperty("placeholder", "2 code format"));

    $export("0008-lbl", (new DOMLabel("24.01.2014 - 30.12.2014")).setAttr("style", "top: 180px; left: 95px; right: 225px; position: absolute; text-overflow: ellipsis"));

    $export("0001-btn", (new Button("Schedule...", (function() {}))).setAttr("style", "top: 175px; right: 140px; position: absolute"));

    $export("0001-check", (new DOMCheckBox({
        "valuesSet": "two-states",
        "checked": "false"
    })).setAttr("style", "top: 210px; left: 5px; position: absolute"));

    $export("0009-lbl", (new DOMLabel("Icon:")).setAttr("style", "top: 40px; right: 60px; width: 50px; position: absolute; text-overflow: ellipsis"));

    $export("0001-img", (new DOMImage({
        "src": "",
        "displayMode": "best"
    })).setAttr("style", "top: 60px; right: 10px; width: 100px; height: 150px; position: absolute; background-color: #333"));

    $export("0002-btn", (new Button("Set", (function() {}))).setAttr("style", "top: 220px; right: 65px; position: absolute; width: 45px"));

    $export("0003-btn", (new Button("Clear", (function() {}))).setAttr("style", "top: 220px; right: 10px; position: absolute; width: 45px"));

    $export("0003-holder", (new DOMPlaceable({
        "caption": "",
        "appearence": "opaque"
    })).setAttr("style", "top: 30px; right: 125px; position: absolute; height: 225px; width: 5px"));

    $export("0004-btn", (new Button("Add ...", (function() {}))).setAttr("style", "top: 300px; right: 60px; position: absolute; width: 70px"));

    $export("0005-btn", (new Button("Remove", (function() {}))).setAttr("style", "top: 325px; right: 60px; position: absolute; width: 70px"));

    $export("0002-check", (new DOMCheckBox({
        "valuesSet": "two-states",
        "checked": "false"
    })).setAttr("style", "top: 210px; left: 85px; position: absolute"));

    $export("0011-lbl", (new DOMLabel("This is a documente template")).setAttr("style", "top: 215px; left: 110px; right: 150px; position: absolute; text-overflow: ellipsis"));

    $import("0001-sheet").insert($import("0001-holder"));
    $import("0001-sheet").insert($import("0001-lbl"));
    $import("0001-sheet").insert($import("0002-lbl"));
    $import("0001-sheet").insert($import("0003-lbl"));
    $import("0001-sheet").insert($import("0004-lbl"));
    $import("0001-sheet").insert($import("0002-holder"));
    $import("0001-sheet").insert($import("0006-lbl"));
    $import("0001-sheet").insert($import("0001-grid"));
    $import("0001-sheet").insert($import("0007-lbl"));
    $import("0001-sheet").insert($import("0001-text"));
    $import("0001-sheet").insert($import("0001-textArea"));
    $import("0001-sheet").insert($import("0002-text"));
    $import("0001-sheet").insert($import("0008-lbl"));
    $import("0001-sheet").insert($import("0001-btn"));
    $import("0001-sheet").insert($import("0001-check"));
    $import("0001-sheet").insert($import("0009-lbl"));
    $import("0001-sheet").insert($import("0001-img"));
    $import("0001-sheet").insert($import("0002-btn"));
    $import("0001-sheet").insert($import("0003-btn"));
    $import("0001-sheet").insert($import("0003-holder"));
    $import("0001-sheet").insert($import("0004-btn"));
    $import("0001-sheet").insert($import("0005-btn"));
    $import("0001-sheet").insert($import("0002-check"));
    $import("0001-sheet").insert($import("0011-lbl"));

    return {
        
        "inputs": {
        },
        
        "interface": {
            "minWidth": 600,
            "minHeight": 540
        }
    }.chain( function() {
        
        this.load = function( ) {
            
            var first = itemsList.item( 0 ).inode;
            
        };
        
        this.save = function( ) {
        
        };
        
    } );
};