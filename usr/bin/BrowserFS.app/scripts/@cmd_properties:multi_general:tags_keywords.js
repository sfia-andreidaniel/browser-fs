/* This properties plugin is used to display the general properties
   for multiple items in the itemsList
*/

var BrowserFS_cmd_properties__multi_general__tags_keywords = function( tabPanelSheet ) {

    // not too good, but ...
    function ucwords( s ) {
        return s.charAt(0).toUpperCase().concat( s.substr( 1 ) );
    }
    
    function objkeys( o ) {
        
        var out = [];
        
        for ( var key in o )
            if ( o.propertyIsEnumerable( key ) && o.hasOwnProperty( key ) )
                out.push( key );
            
        return out;
        
    }

    tabPanelSheet.cmd_modify = function( actualState, saveBatch, operation, propertyName, numItems ) {

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
        var $pid = getUID();
    
        var dlg = $export("0001-dlg", (new Dialog({
            "alwaysOnTop": false,
            "appIcon": "",
            "caption": ucwords( operation ) + " " + propertyName,
            "closeable": true,
            "height": 200,
            "maximizeable": false,
            "maximized": false,
            "minHeight": 50,
            "minWidth": 50,
            "minimizeable": false,
            "moveable": true,
            "resizeable": true,
            "scrollable": false,
            "visible": true,
            "width": 400,
            "childOf": getOwnerWindow( tabPanelSheet ),
            "modal": true
        })).chain(function() {
            Object.defineProperty(this, "pid", {
                "get": function() {
                    return $pid;
                }
            });
            this.addClass("PID-" + $pid);
        }));
    
        $export("0001-lbl", (new DOMLabel( ucwords( operation ) + " the following " + propertyName + " " + ( operation == 'delete' ? 'from' : 'to' ) + " all items:")).setAttr("style", "top: 10px; left: 10px; right: 10px; position: absolute; text-overflow: ellipsis"));
    
        var tags = $export("0001-tags", (new TagsEditor( objkeys( actualState ) , {
            "sticky": []
        })).setAttr("style", "top: 35px; left: 10px; right: 10px; position: absolute; margin: 0").setAnchors({
            "height": function(w, h) {
                return h - 75 + "px";
            }
        }));
    
        $export("0001-btn", (new Button("Cancel", (function() {
            
            dlg.close();
            dlg.purge();
            dlg = null;
            
        }))).setAttr("style", "right: 10px; bottom: 10px; position: absolute"));
    
        $export("0002-btn", (new Button( ucwords( operation ), (function() {
            
            
            
        }))).setAttr("style", "bottom: 10px; right: 70px; position: absolute"));
    
        $import("0001-dlg").insert($import("0001-lbl"));
        $import("0001-dlg").insert($import("0001-tags"));
        $import("0001-dlg").insert($import("0001-btn"));
        $import("0001-dlg").insert($import("0002-btn"));
    
        setTimeout(function() {
            dlg.paint();
            dlg.ready();
        }, 1);
    
        Keyboard.bindKeyboardHandler( dlg, 'esc', function() {
            
            dlg.close();
            dlg.purge();
            dlg = null;
            
        } );
    
        return dlg;

    };

};