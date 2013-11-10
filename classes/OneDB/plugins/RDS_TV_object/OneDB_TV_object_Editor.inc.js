window['OneDB_Plugin_TV_object'] = function( app ) {
    
    app.handlers.cmd_plugin_TV_object = function( objectID ){

        var _parentID = app.tree.selectedNode.nodeID;
        
        if (!_parentID) {
            alert( "You cannot create TV events straight in ONEDB_ROOT!" );
            throw "You cannot create TV events straight in ONEDB_ROOT!";
        }

        var load = {};
        
        if (objectID) {
            (function() {
                
                var req = [];
                req.addPOST('_id', objectID );
                var rsp = app.$_PLUGIN_JSON_POST( '%plugins%/RDS_TV_object', 'get', req );
                if (rsp === null)
                    throw "Cannot load object #" + objectID;
                load = rsp;
                //console.log( load );
            })();
        }

        var dlg = new Dialog({
            "width": 600,
            "height": 400,
            "childOf": app,
            "caption": "TV Object Editor",
            "appIcon": "data:image/gif;base64,R0lGODlhEAAQAOZEAMzMzNLNxWaZzJnM/3d3d5HE91mMv0R3qkJCQm1tbXV1dU2As4CAgEpKSj1wozY2NoyMjJmZmaamprKysry8vMXFxWNjY19fXzptoDMzM729vYSEhGeazYK16D8/P2OWyXeq3Xx8fEZGRpSUlDo6Om2g01h+pGtra1WIu42NjTdqnVpaWquopHt7e01NTTk5OVVVVZGRkYKAfEd6rXJyckRERF6RxIe67VKFuE9PT1B2nUJ1qEBzpjVom6ioqD09PVyPwsTExLm5uaysrAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAEQALAAAAAAQABAAAAfCgESCRBmDg4WGhgSKiY2LRI+GLCc0LRspIwQEIykbLTQnLBYApKWmphYXFSYOC0ACAkALDiYVCBUXKxQ9A72+vT0UQRQrMBMqNwXKyzcqEwgTMDkSGCUcIB0dIBwlGBJCEjkNEQ42B+foNg4RCBENNRA8Hwb09R88EEMQNR4MO7AAAe5gQNADCQU6ZizAgQIFjgUzdCiYSOJBghAxfGggpcFHjBAJQj6Q8eDFDwQiGrhw0UAEgh8vRhIJQLOmTZtEAgEAOw=="
        });
        
        var grid = dlg.insert(
            (new PropertyGrid([
                {
                    "name"  : "category",
                    "label" : "Category",
                    "type"  : "dropdown",
                    "value" : load.category || "",
                    "values": (function() {
                        var req = [];
                        var rsp = app.$_PLUGIN_JSON_POST( '%plugins%/RDS_TV_object', 'get-categories', req );
                        
                        var out = [
                            {
                                "id": "",
                                "name": "Not Specified"
                            }
                        ];
                        
                        if ( rsp && rsp.length) {
                            for (var i=0; i<rsp.length; i++) {
                                out.push({
                                    "id": rsp[i],
                                    "name": rsp[i]
                                });
                            }
                        }
                        
                        return out;
                    })()
                },
                {
                    "name"  : "headlines",
                    "label" : "Headlines",
                    "type"  : "varchar",
                    "value" : load.headlines || ''
                },
                {
                    "name"  : "croll",
                    "label" : "Croll",
                    "type"  : "varchar",
                    "value" : load.croll || ""
                },
                {
                    "name"  : "online",
                    "label" : "Active?",
                    "type"  : "bool",
                    "value" : load.online || false
                },
                {
                    "name"  : "database",
                    "label" : "Database",
                    "expanded": false,
                    "items" : [
                        {
                            "name"  : "name",
                            "label" : "Name",
                            "type"  : "Generic",
                            "value" : load.name || null
                        },
                        {
                            "name": "_id",
                            "type": "Generic",
                            "label": "_id",
                            "value": load._id || null
                        },
                        {
                            "name": "_parent",
                            "type": "Generic",
                            "label": "_parent",
                            "value": load._parent || _parentID
                        }
                    ]
                }
            ])).setAnchors({
                    "width": function(w,h) {
                        return w - 10 + 'px';
                    },
                    "height": function(w,h) {
                        return h - 60 + 'px';
                    }
            }).setAttr(
                "style", "margin: 5px"
            )
        );
        
        grid.inputs.headlines.addCustomEventListener('change', function() {
            var len = 70 - grid.inputs.headlines.value.length;
            var newval = 
            grid.inputs.headlines.parentNode.parentNode.querySelector('div.GridRowLabel').innerHTML =
                grid.inputs.headlines.parentNode.parentNode.querySelector('div.GridRowLabel').innerHTML.replace( /^([a-z]+)([^*]+)/i, '$1 (' + len + ' chars left)' );
        });

        grid.inputs.croll.addCustomEventListener('change', function() {
            var len = 100 - grid.inputs.croll.value.length;
            var newval = 
            grid.inputs.croll.parentNode.parentNode.querySelector('div.GridRowLabel').innerHTML =
                grid.inputs.croll.parentNode.parentNode.querySelector('div.GridRowLabel').innerHTML.replace( /^([a-z]+)([^*]+)/i, '$1 (' + len + ' chars left)' );
        });
        
        grid.inputs.headlines.onCustomEvent('change');
        grid.inputs.croll.onCustomEvent('change');
        
        grid.splitPosition = 150;
        
        dlg.insert(
            (new Button( 'Ok', function() {
                dlg.save();
            } ) ).
                setAttr("style", "margin: 10px 10px 0px 5px")
        );
        
        dlg.save = function() {
        
            if (grid.values.headlines.trim() == '' && grid.values.croll.trim() == '') {
            
                DialogBox("Please input at least one of these two fields: Headlines, Croll", {
                    "type": "error",
                    "childOf": dlg
                });
                
                return;
            }
        
            if (grid.values.headlines.length > 70) {
                DialogBox("Headlines field is too long!", {
                    "type": "error",
                    "childOf": dlg
                });
                return;
            }
            
            if (grid.values.croll.length > 100) {
                DialogBox("Croll field is too long!", {
                    "type": "error",
                    "childOf": dlg
                });
                return;
            }
        
            var data = JSON.stringify( grid.values );
            var req = [];
            req.addPOST('data', data );
            var saveResult = app.$_PLUGIN_JSON_POST( '%plugins%/RDS_TV_object', 'save', req );
            
            if (saveResult) {
                grid.values.database._id = saveResult._id || '';
                grid.values.database.name= saveResult.name || '';
                app.appHandler('cmd_refresh');
                dlg.close();
                delete dlg;
            }
        }
        
    }
    
    app.registerMimeType( /^item\/TV_object$/, function( obj ) {
        /* console.log( obj ); */
        app.appHandler( 'cmd_plugin_TV_object', obj.id || obj._id );
    }, 'TV_object mime opener' );
    
    
    OneDB_RegisterMimeIcon({
        "regex": /^item\/TV_object$/,
        "icon": "data:image/gif;base64,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"
    });
    
};