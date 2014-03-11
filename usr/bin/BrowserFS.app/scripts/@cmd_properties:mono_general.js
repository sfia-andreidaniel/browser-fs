/* This properties plugin is used to display the general properties
   for a single inode item in the itemsList
*/

var BrowserFS_cmd_properties__mono_general = function( tabPanel, itemsList ) {

    if ( !itemsList.length )
        return null;
    
    if ( itemsList.length != 1 )
        return null;
    
    if ( itemsList.item(0).inode.url == '/' )
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

    $export("0001-holder", (new DOMPlaceable({
        "caption": "General",
        "appearence": "opaque"
    })).setAttr("style", "top: 20px; left: 0px; right: 2px; position: absolute; height: 5px"));

    $export("0001-lbl", (new DOMLabel("Name:")).setAttr("style", "top: 35px; left: 10px; width: 40px; position: absolute; text-overflow: ellipsis"));

    $export("0002-lbl", (new DOMLabel("Type:")).setAttr("style", "top: 60px; left: 10px; width: 35px; position: absolute; text-overflow: ellipsis"));

    $export("0004-lbl", (new DOMLabel("Id:")).setAttr("style", "top: 90px; left: 10px; width: 25px; position: absolute; text-overflow: ellipsis"));

    $export("0002-holder", (new DOMPlaceable({
        "caption": "Indexing",
        "appearence": "opaque"
    })).setAttr("style", "top: 135px; left: 0px; right: 2px; position: absolute; height: 5px"));

    $export("0005-lbl", (new DOMLabel("Keywords:")).setAttr("style", "top: 155px; left: 10px; width: 65px; position: absolute; text-overflow: ellipsis"));

    $export("0006-lbl", (new DOMLabel("Tags:")).setAttr("style", "top: 220px; left: 10px; width: 50px; position: absolute; text-overflow: ellipsis"));

    $export("0003-holder", (new DOMPlaceable({
        "caption": "Attributes",
        "appearence": "opaque"
    })).setAttr("style", "top: 305px; left: 0px; right: 2px; position: absolute; height: 5px"));

    $export("0001-grid", (new DataGrid()).setAttr("style", "top: 320px; left: 10px; position: absolute").setAnchors({
        "width": function(w, h) {
            return w - 20 + "px";
        },
        "height": function(w, h) {
            return h - 335 + "px";
        }
    }).setProperty("selectable", true).chain(function() {
        this.colWidths = [80, 200];
        this.th(["Name", "Value"]);
        this.setProperty("delrow", function(row) {
            //What to do when deleting a row
            return false;
        });
    }));

    var name = $export("0001-text", (new TextBox("")).setAttr("style", "top: 30px; left: 95px; position: absolute; margin: 0").setAnchors({
        "width": function(w, h) {
            return w - 115 + "px";
        }
    }));

    $export("0007-lbl", (new DOMLabel("-")).setAttr("style", "top: 60px; left: 97px; right: 15px; position: absolute; text-overflow: ellipsis"));

    $export("0008-lbl", (new DOMLabel("-")).setAttr("style", "top: 90px; left: 97px; right: 10px; position: absolute; text-overflow: ellipsis"));

    $export("0001-tags", (new TagsEditor([""], {
        "sticky": []
    })).setAttr("style", "top: 150px; left: 95px; right: 10px; position: absolute; margin: 0; height: 55px"));

    $export("0002-tags", (new TagsEditor([""], {
        "sticky": []
    })).setAttr("style", "top: 220px; left: 95px; right: 10px; position: absolute; margin: 0; height: 55px"));

    $import("0001-sheet").insert($import("0001-holder"));
    $import("0001-sheet").insert($import("0001-lbl"));
    $import("0001-sheet").insert($import("0002-lbl"));
    $import("0001-sheet").insert($import("0004-lbl"));
    $import("0001-sheet").insert($import("0002-holder"));
    $import("0001-sheet").insert($import("0005-lbl"));
    $import("0001-sheet").insert($import("0006-lbl"));
    $import("0001-sheet").insert($import("0003-holder"));
    $import("0001-sheet").insert($import("0001-grid"));
    $import("0001-sheet").insert($import("0001-text"));
    $import("0001-sheet").insert($import("0007-lbl"));
    $import("0001-sheet").insert($import("0008-lbl"));
    $import("0001-sheet").insert($import("0001-tags"));
    $import("0001-sheet").insert($import("0002-tags"));
    
    return {
        
        "inputs": {
            
            "name": $import( '0001-text' ),
            "type": $import( '0007-lbl' ),
            "id"  : $import( '0008-lbl' ),
            "keywords": $import( '0001-tags' ),
            "tags": $import( '0002-tags' ),
            "attributes": $import( '0001-grid' )
        },
        
        "interface": {
            "minWidth": 400,
            "minHeight": 540
        }
    }.chain( function() {
        
        this.load = function( ) {
            
            var first = itemsList.item( 0 ).inode;
            
            // display general info
            
            this.inputs.name.value = first.name;
            this.inputs.type.label = first.type;
            this.inputs.id.label   = first.id + '';
            
            // display keywords and tags
            
            this.inputs.keywords.value = first.keywords;
            this.inputs.tags.value = first.tags;
            
            // display attributes
            
            this.inputs.attributes.tr(
                [ 'Location', first.url ]
            );
            
            this.inputs.attributes.tr(
                [ 'Created', ( new Date() ).fromString( first.ctime, '%U' ).toString('%d %M %Y %H:%i:%s') ]
            );
            
            this.inputs.attributes.tr(
                [ 'Modified', ( new Date() ).fromString( first.mtime, '%U' ).toString('%d %M %Y %H:%i:%s') ]
            );
            
            this.inputs.attributes.tr(
                [ 'Owner', first.owner + ' / ' + first.group ]
            );
            
            this.inputs.attributes.tr(
                [ 'Online', first.online ? 'Yes' : 'No' ]
            );
            
            this.inputs.attributes.tr(
                [ 'Mode', Umask.mode_to_str( first.mode ) ]
            );
            
            this.inputs.attributes.render();
            
        };
        
        this.save = function( ) {
        
        };
        
    } );
};