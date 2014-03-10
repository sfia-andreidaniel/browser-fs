function BFS_cmd_edit_paste( app ) {
    
    app.handlers.cmd_edit_paste = function() {
        
        if ( !BFS_Clipboard.length )
            return;
        
        var tasker = new Async(),
            dlg,
            lbl_from,
            progress,
            
            op_target = app.interface.location.inode;
        
        tasker.sync( function() {
            // step 1. Create the copy dialog
            
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
        
            dlg = $export("0001-dlg", (new Dialog({
                "alwaysOnTop": true,
                "appIcon": "",
                "caption": "%FileOperation% %count% items...",
                "closeable": true,
                "height": 168,
                "maximizeable": false,
                "maximized": false,
                "minHeight": 50,
                "minWidth": 50,
                "minimizeable": false,
                "modal": true,
                "moveable": true,
                "resizeable": false,
                "scrollable": false,
                "visible": true,
                "width": 370,
                "childOf": app
            })).chain(function() {
                Object.defineProperty(this, "pid", {
                    "get": function() {
                        return $pid;
                    }
                });
                this.addClass("PID-" + $pid);
            }));
        
            lbl_from = $export("0001-lbl", (new DOMLabel("From:")).setAttr("style", "top: 10px; left: 10px; position: absolute; text-overflow: ellipsis").setAnchors({
                "width": function(w, h) {
                    return w - 20 + "px";
                }
            }));
        
            progress = $export("0001-progress", (new ProgressBar({
                "value": 0,
                "minValue": 0,
                "maxValue": 100,
                "captionFormat": "/v%"
            })).setAttr("style", "top: 70px; left: 10px; position: absolute; height: 16px").setAnchors({
                "width": function(w, h) {
                    return w - 20 + "px";
                }
            }));
        
            $export("0001-btn", (new Button("Abort", (function() {}))).setAttr("style", "bottom: 10px; right: 10px; position: absolute"));
        
            $import("0001-dlg").insert($import("0001-lbl"));
            $import("0001-dlg").insert($import("0001-progress"));
            $import("0001-dlg").insert($import("0001-btn"));
        
            setTimeout(function() {
                dlg.paint();
                dlg.ready();
            }, 1);
        
            this.on( 'success' );
        } );

        // add the tasks to the tasker ...
        for ( var i=0, len = BFS_Clipboard.length; i<len; i++ ) {
            
            ( function( inode, iprogress, mode ) {
                
                tasker.sync( function() {
                    
                    ( function( task ) {
                        
                        lbl_from.caption = inode.name;
                        
                        try {
                        
                            op_target[ mode == 'cut' ? 'appendChild' : 'copyChild' ]( inode );
                        
                            progress.value = iprogress;
                        
                            task.on( 'success' );
                        
                        } catch ( error ) {
                            
                            task.on( 'error', error + '' );
                            
                        }
                        
                    } )( this );
                    
                } );
                
            } )( 
                BFS_Clipboard.data.items[i],
                Math.floor( ( 100 / BFS_Clipboard.length ) * i ),
                BFS_Clipboard.effect
            );
            
        }

        if ( BFS_Clipboard.effect == 'cut' ) {
            BFS_Clipboard.copy([]);
        }

        tasker.run( function() {
            console.log( 'Operation completed successfully' );
            // success
        }, function( reason ) {
        
            if ( dlg ) {
                dlg.close();
            }
        
            DialogBox( 'An error occured: ' + reason, {
                
                "childOf": app,
                "type": "error",
                "modal": true
                
            } );
        
            // error
        }, function() {
            // complete
            
            lbl_from = null;
            lbl_to   = null;
            progress = null;
            
            dlg.close();
            dlg.purge();
            dlg = null;
            
            console.log( 'complete tasker' );
            
            app.appHandler( 'cmd_refresh' );
            
        } );

    };

}