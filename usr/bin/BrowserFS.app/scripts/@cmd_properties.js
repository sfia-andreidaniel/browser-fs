function BFS_cmd_properties( app ) {
    
    app.handlers.cmd_properties = function( itemsList ) {
        
        itemsList = itemsList || ( app.interface.selection.length ? app.interface.selection : null ) || [ { "inode": app.interface.location.inode } ].chain( function() {
            this.item = function(index) {
                return this[ index ];
            };
        } );
        
        if ( !itemsList.length )
            return;
        
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
        
        var dlgWidth  = 450;
        var dlgHeight = 300;
        
        var $pid = getUID();
    
        var dlg = $export("0001-dlg", (new Dialog({
            "alwaysOnTop": false,
            "appIcon": "",
            "caption": "Properties",
            "closeable": true,
            "height": 300,
            "maximizeable": true,
            "maximized": false,
            "minHeight": 50,
            "minWidth": 50,
            "minimizeable": true,
            "modal": false,
            "moveable": true,
            "resizeable": true,
            "scrollable": false,
            "visible": true,
            "width": 450,
            "childOf": app
        })).chain(function() {
            Object.defineProperty(this, "pid", {
                "get": function() {
                    return $pid;
                }
            });
            this.addClass("PID-" + $pid);
        }));
    
        var tabs = $export("0001-tabs", (new TabPanel({
            "initTabs": []
        })).setAttr("style", "top: 10px; left: 10px; right: 10px; bottom: 40px; position: absolute"));
    
        $export("0001-btn", (new Button("Cancel", (function() {
            
            dlg.close();
            dlg.purge();
            
        }))).setAttr("style", "bottom: 10px; right: 10px; position: absolute"));
        
        $export("0002-btn", (new Button("Apply", (function() {
            
            dlg.appHandler( 'cmd_save', true );
            
        }))).setAttr("style", "bottom: 10px; right: 65px; position: absolute"));
        
        $export("0003-btn", (new Button("Ok", (function() {
            
            dlg.appHandler( 'cmd_save' );
            
        }))).setAttr("style", "bottom: 10px; right: 113px; position: absolute"));
    
        Keyboard.bindKeyboardHandler( dlg, 'esc', function() {
            
            dlg.close();
            dlg.purge();
            
        } );
    
        $import("0001-dlg").insert($import("0001-tabs"));
        $import("0001-dlg").insert($import("0001-btn"));
        $import("0001-dlg").insert($import("0002-btn"));
        $import("0001-dlg").insert($import("0003-btn"));
        
        var loaders = [],
            plugins = [
                'BrowserFS_cmd_properties__mono_general',
                'BrowserFS_cmd_properties__multi_general',
                'BrowserFS_cmd_properties__search_category',
                'BrowserFS_cmd_properties__webservice_category',
                'BrowserFS_cmd_properties__aggregator_category',
                'BrowserFS_cmd_properties__widget',
                'BrowserFS_cmd_properties__list',
                'BrowserFS_cmd_properties__document'
            ];
        
        for ( var i=0, len = plugins.length; i<len; i++ ) {
            
            ( function( plugin ) {
                
                var result = window[ plugin ]( $import( '0001-tabs' ), itemsList );
                
                if ( result )
                    loaders.push( result );
                
            } )( plugins[i] );
            
        }
        
        if ( loaders.length ) {

            for ( var i=0, len = loaders.length; i<len; i++ ) {
                
                loaders[i].load();
                
                if ( dlgHeight < loaders[i].interface.minHeight )
                    dlgHeight = loaders[i].interface.minHeight;
                
                if ( dlgWidth < loaders[i].interface.minWidth )
                    dlgWidth = loaders[i].interface.minWidth;
                
            }

            dlg.height = dlgHeight;
            dlg.width = dlgWidth;
            
            dlg.center();

            setTimeout(function() {
                dlg.paint();
                dlg.ready();
            }, 1);
        
            return dlg;
        
        } else {
            
            dlg.close();
            
            DialogBox( "There are no properties to be shown for this item or selection of items", {
                "childOf": app,
                "modal": true,
                "type": "error"
            } );
            
        }
        
    }

}