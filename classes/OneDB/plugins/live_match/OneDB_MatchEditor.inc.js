window['OneDB_Plugin_Match'] = function( app ) {
    
    app.handlers.cmd_plugin_Match = function( matchID ){

        var _parentID = app.tree.selectedNode.nodeID;
        
        if (!_parentID) {
            alert( "You cannot create matches straight in ONEDB_ROOT!" );
            throw "You cannot create matches straight in ONEDB_ROOT!";
        }

        (function() {
            var req = [];
            req.addPOST('_parent', _parentID);
            if (app.$_PLUGIN_JSON_POST( '%plugins%/live_match', 'validate-parent', req ) != 'ok')
                throw "Cannot edit / create matches in this location";
        })();

        var load = {};
        
        if (matchID) {
            (function() {
                
                var req = [];
                req.addPOST('_id', matchID );
                var rsp = app.$_PLUGIN_JSON_POST( '%plugins%/live_match', 'load-match', req );
                if (rsp === null)
                    throw "Cannot load match #" + matchID;
                load = rsp;
                console.log( load );
            })();
        }

        var dlg = new Dialog({
            "width": 600,
            "height": 400,
            "childOf": app,
            "caption": "Live Match Editor"
        });
        
        dlg.loadMatchByID = function( mongoID ) {
            var req = [];
            req.addPOST('_id', mongoID);
            var rsp = app.$_PLUGIN_JSON_POST( '%plugins%/live_match', 'load-match', req );
            return rsp;
        }
        
        var grid = dlg.insert(
            (new PropertyGrid([
                {
                    "name"  : "online",
                    "label" : "Active",
                    "type"  : "bool",
                    "value" : !!load.online
                },
                {
                    "name"  : "soccerwayPath",
                    "label" : "SoccerWay Path",
                    "type"  : "varchar",
                    "value" : load.soccerwayPath || ""
                },
                {
                    "name"  : "streamerScope",
                    "label" : "Streamer Scope",
                    "type"  : "varchar",
                    "value" : load.streamerScope || ""
                },
                {
                    "name"  : "externalURL",
                    "label" : "External Match URL",
                    "type"  : "varchar",
                    "value" : load.externalURL || ""
                },
                {
                    "name"  : "startTime",
                    "label" : "Start Date and Time",
                    "type"  : "date",
                    "value" : !load.startTime ? ( new Date() ).toString('%Y-%m-%d %H:%i:%s') : 
                                ( (new Date()).fromString( load.startTime, '%U' ).toString('%Y-%m-%d %H:%i:%s') ),
                    "valueFormat": "%Y-%m-%d %H:%i:%s",
                    "displayFormat": "%Y-%m-%d %H:%i:%s"
                },
                {
                    "name"  : "stopTime",
                    "label" : "Stop Date and Time",
                    "type"  : "date",
                    "value" : !load.stopTime ? ( new Date() ).fromString( parseInt( ( ( new Date() ).toString('%U') ) ) + 6900 , '%U' ).toString('%Y-%m-%d %H:%i:%s') :
                                ( new Date()).fromString( load.stopTime, '%U' ).toString('%Y-%m-%d %H:%i:%s'),
                    "valueFormat": "%Y-%m-%d %H:%i:%s",
                    "displayFormat": "%Y-%m-%d %H:%i:%s"
                },
                {
                    "name"  : "team_A_Name",
                    "label" : "Team A Name",
                    "type"  : "Generic",
                    "value" : load.team_A_Name || ''
                },
                {
                    "name"  : "team_A_id",
                    "label" : "Team A ID",
                    "type"  : "Generic",
                    "value" : load.team_A_id || ''
                },
                {
                    "name"  : "team_B_Name",
                    "label" : "Team B",
                    "type"  : "Generic",
                    "value" : load.team_B_Name || ''
                },
                {
                    "name"  : "team_B_id",
                    "label" : "Team B ID",
                    "type"  : "Generic",
                    "value" : load.team_B_id || ''
                },
                {
                    "name"  : "match_id",
                    "label" : "SoccerWay Match ID",
                    "type"  : "Generic",
                    "value" : load.match_id || ''
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
                            "value" : load.name || ''
                        },
                        {
                            "name": "_id",
                            "type": "Generic",
                            "label": "_id",
                            "value": load._id || ''
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
        
        if (grid.values.soccerwayPath) {
            grid.inputs.soccerwayPath.disabled = true;
            grid.inputs.soccerwayPath.readOnly = true;
        }
        
        grid.splitPosition = 150;
        
        dlg.insert(
            (new Button( 'Ok', function() {
                dlg.saveMatch();
            } ) ).
                setAttr("style", "margin: 10px 10px 0px 5px")
        );
        
        var onPathFocus = function() {
            grid.inputs.soccerwayPath._previous = grid.values.soccerwayPath;
        }
        
        var onPathBlur = function() {
            if (typeof grid.inputs.soccerwayPath._previous != 'undefined' &&
                grid.inputs.soccerwayPath._previous == grid.values.soccerwayPath
            ) return;
            var req = [];
            req.addPOST('soccerwayPath', grid.values.soccerwayPath);
            var rsp = app.$_PLUGIN_JSON_POST( '%plugins%/live_match', 'get-match-details', req );

            if (rsp !== null)
                grid.inputs.soccerwayPath._previous = grid.values.soccerwayPath;
            else
                return;
            
            grid.values.match_id = rsp.match_id || '';
            grid.values.team_A_Name = rsp.team_A_name || '';
            grid.values.team_B_Name = rsp.team_B_name || '';
            grid.values.team_A_id = rsp.team_A_id || '';
            grid.values.team_B_id = rsp.team_B_id || '';
            
            /* Parse match start date and time */
            if (rsp.match_date) {
                grid.values.startTime = rsp.match_date;
                grid.values.stopTime  = (new Date()).fromString( parseInt( ( new Date() ).fromString(rsp.match_date, '%Y-%m-%d %H:%i:%s').toString('%U') ) + 6900, '%U' ).toString('%Y-%m-%d %H:%i:%s');
            }
        }
        
        grid.inputs.soccerwayPath.addEventListener('focus', onPathFocus, true);
        grid.inputs.soccerwayPath.addEventListener('blur',  onPathBlur,  true);
        
        dlg.saveMatch = function() {
            var data = JSON.stringify( grid.values );
            var req = [];
            req.addPOST('data', data );
            var saveResult = app.$_PLUGIN_JSON_POST( '%plugins%/live_match', 'save-match', req );
            
            if (saveResult) {
                grid.values.database._id = saveResult._id || '';
                grid.values.database.name= saveResult.name || '';
                app.appHandler('cmd_refresh');
                dlg.close();
                delete dlg;
            }
        }
        
    }
    
    app.registerMimeType( /^item\/Match$/, function( obj ) {
        /* console.log( obj ); */
        app.appHandler( 'cmd_plugin_Match', obj.id || obj._id );
    }, 'Match mime opener' );
    
    
    OneDB_RegisterMimeIcon({
        "regex": /^item\/Match$/,
        "icon": "data:image/png;base64,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"
    });
    
    
};