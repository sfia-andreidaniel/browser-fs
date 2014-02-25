/* BFS command "cmd_connect" extender
 *
 *
 */
function BFS_Connect( app ) {
    
    var connection = null;
    
    Object.defineProperty( app, "connection", {
        "get": function() {
            return connection;
        }
    } );
    
    app.handlers.cmd_connect = function() {
    
        if ( !!!app.flags.connection ) {
    
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
                "caption": "Connect to website",
                "closeable": true,
                "height": 249,
                "maximizeable": false,
                "maximized": false,
                "minimizeable": false,
                "modal": true,
                "moveable": true,
                "resizeable": false,
                "scrollable": false,
                "visible": true,
                "width": 363,
                "childOf": app
            })).chain(function() {
                Object.defineProperty(this, "pid", {
                    "get": function() {
                        return $pid;
                    }
                });
                this.addClass("PID-" + $pid);
            }));

            $export("0001-img", (new DOMImage({
                "src": "data:image/png;base64,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",
                "displayMode": "best"
            })).setAttr("style", "top: 10px; left: 10px; width: 48px; height: 48px; position: absolute; border-color: transparent"));

            $export("0001-lbl", (new DOMLabel("Connect to website")).setAttr("style", "top: 25px; left: 65px; position: absolute; text-overflow: ellipsis; font-size: 14px; font-weight: bold").setAnchors({
                "width": function(w, h) {
                    return w - 80 + "px";
                }
            }));

            $export("0002-lbl", (new DOMLabel("Website:")).setAttr("style", "top: 80px; left: 10px; width: 60px; position: absolute; text-overflow: ellipsis"));

            $export("0003-lbl", (new DOMLabel("Username:")).setAttr("style", "top: 115px; left: 10px; width: 60px; position: absolute; text-overflow: ellipsis"));

            $export("0004-lbl", (new DOMLabel("Password:")).setAttr("style", "top: 155px; left: 10px; width: 65px; position: absolute; text-overflow: ellipsis"));

            dlg.closeCallback = function() {
                if ( connection === null ) {
                    app.forceClose = true;
                    app.close();
                }
                setTimeout( function() {
                    dlg.purge();
                }, 100 );
                return true;
            }

            $export("0001-btn", (new Button("Cancel", (function() {
            
                dlg.close();
            
            
            }))).setAttr("style", "bottom: 10px; right: 10px; position: absolute"));

            var websites = $export("0001-drop", (new DropDown(undefined)).setItems(( function() {
            
                var out = [];
                
                for ( var ws = OneDB.websites, i=0, len = ws.length; i<len; i++ ) {
                    out.push( {
                        "id": ws[i],
                        "name": ws[i]
                    } );
                }
            
                return out;
            
            } )()).setAttr("style", "top: 75px; left: 85px; position: absolute; margin: 0").setAnchors({
                "width": function(w, h) {
                    return w - 95 + "px";
                }
            }));

            var userName = $export("0001-text", (new TextBox("")).setAttr("style", "top: 110px; left: 85px; position: absolute; margin: 0").setAnchors({
                "width": function(w, h) {
                    return w - 105 + "px";
                }
            }));

            var password = $export("0002-text", (new TextBox("")).setProperty("type", "password").setAttr("style", "top: 150px; left: 85px; position: absolute; margin: 0").setAnchors({
                "width": function(w, h) {
                    return w - 105 + "px";
                }
            }));

            $export("0002-btn", (new Button("Connect", dlg.handlers.cmd_connect = (function( reuseConnection ) {
            
                var conn = reuseConnection || null;
            
                try {
                
                    if ( !conn )
                        conn = OneDB.login( websites.value, userName.value, password.value );
                    
                    connection = conn;
                    
                    dlg.close();
                    
                    app.interface.on( 'connected', connection );
                    
                } catch ( Error ) {
                    
                    DialogBox( Error + "", {
                        "caption": "Error connecting to Website",
                        "childOf": dlg
                    } );
                    
                }
                
            }))).setAttr("style", "bottom: 10px; right: 70px; position: absolute"));

            $import("0001-dlg").insert($import("0001-img"));
            $import("0001-dlg").insert($import("0001-lbl"));
            $import("0001-dlg").insert($import("0002-lbl"));
            $import("0001-dlg").insert($import("0003-lbl"));
            $import("0001-dlg").insert($import("0004-lbl"));
            $import("0001-dlg").insert($import("0002-btn"));
            $import("0001-dlg").insert($import("0001-btn"));
            $import("0001-dlg").insert($import("0001-drop"));
            $import("0001-dlg").insert($import("0001-text"));
            $import("0001-dlg").insert($import("0002-text"));
            
            Keyboard.bindKeyboardHandler( dlg, "enter", function() {
                dlg.handlers.cmd_connect();
            } );
            
            Keyboard.bindKeyboardHandler( dlg, "esc", function() {
                dlg.close();
            } );

            setTimeout(function() {
                dlg.paint();
                dlg.ready();
            }, 1);

            return dlg;
    
        } else {
            
            connection = app.flags.connection;
            
            app.location = app.flags.location || '/';
            
            return true;
        }
    
    }
}