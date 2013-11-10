window.OneDB_Storage_Plugin_Panel = function( tabSheet ) {
    
    var dlg = getOwnerWindow( tabSheet );
    
    var grid = tabSheet.insert(
        (new DataGrid()).setAnchors({
            "width": function(w,h) {
                return w - 10 + 'px';
            },
            "height": function(w,h) {
                return h - 50 + 'px';
            }
        }).setAttr(
            "style", "margin: 4px"
        )
    );
    
    grid.colWidths = [ 150, 300, 100, 100 ];
    grid.th([ 'ID', 'Name', 'Mime', 'StorageType', 'Status' ]);
    
    grid.enableSorting();
    grid.enableResizing();
    grid.selectable = true;
    
    var isWorking = false;
    
    tabSheet.addSelection = function( newSelection ) {
        
        var resolveIndexes = [];
        
        for (var i=0; i<newSelection.length; i++) {
            if (/^item\/File($| )/.test( newSelection[i].type )) {
                (function( item ) {
                    var row = grid.tr([
                        item.id,
                        item.path,
                        item.type.substr( 9 ),
                        '',
                        ''
                    ]);
                    
                    resolveIndexes.push( row.primaryKey = item.id );
                    row.waitStorage = true;
                    
                } )( newSelection[i] );
            }
        }
        
        if (resolveIndexes.length) {
            
            (function() {
                
                var req = [];
                req.addPOST('items', JSON.stringify( resolveIndexes ) );
                
                var rsp = dlg.$_PLUGIN_JSON_POST( '%plugins%/storage', 'get-storage', req );
                
                rsp = rsp || {};
                var row;
                
                for ( var i=0; i<grid.tbody.rows.length; i++) {
                    row = grid.tbody.rows[i];
                    if (row.waitStorage) {
                        
                        if ( typeof rsp[ row.primaryKey ] != 'undefined' ) {
                            delete row.waitStorage;
                            row.cells[3].value = rsp[ row.primaryKey ];
                        }
                        
                    }
                }
                
            })();
            
        }
        
        grid.render();
        
        return true;
    };
    
    MakeDropable( tabSheet );
    
    tabSheet.addCustomEventListener('drop', function( data ) {
        data = data || {};
        
        if (data.dataType == 'OneDB.items' && data.data && data.data.selection)
            tabSheet.addSelection( data.data.selection );
    } );
    
    tabSheet.insert(
        (new Button('Add Selection', function() {
            tabSheet.addSelection( app.view.selection );
        } )).setAttr(
            "style", "position: absolute; bottom: 14px; left: 4px; display: block;"
        )
    );

    tabSheet.insert(
        (new Button('Clear List', function() {
            if ( isWorking ) {
                alert("Cannot clear list while migration in progress. Wait until completed or stop it, then retry again!");
                return;
            }
            grid.clear();
            grid.render();
        } )).setAttr(
            "style", "position: absolute; bottom: 14px; left: 104px; display: block;"
        )
    );
    
    tabSheet.insert(
        (new DOMLabel('New storage type:')).setAttr(
            'style', 'bottom: 20px; left: 210px; position: absolute;'
        )
    );
    
    tabSheet.destinationStorage = tabSheet.insert(
        (new DropDown( (function() {
            var rsp = dlg.$_PLUGIN_JSON_POST( '%plugins%/storage', 'get-storage-types', [] );
            rsp = rsp || [];
            var out = [];
            
            for (var i=0; i<rsp.length; i++) {
                out.push({
                    "id": rsp[i],
                    "name": rsp[i]
                });
            }
            
            return out;
        })() )).setAttr(
            'style', 'display: block; position: absolute; bottom: 14px; left: 320px'
        ).setAnchors({
            "width": function(w,h) {
                return w - 450 + 'px';
            }
        })
    );
    
    tabSheet.destinationStorage.onchange = function() {
        for (var i=0; i<grid.tbody.rows.length; i++)
            grid.tbody.rows[i].cells[4].value = '';
    };
    
    tabSheet.destinationStorage.value =
        dlg.getEnv('OneDB.DefaultStorageType');
    
    EnableCustomEventListeners( tabSheet );
    
    tabSheet.migrateNext = function() {
        var row = false;
        
        // alert('start');
        
        for (var i=0; i < grid.tbody.rows.length; i++) {
            if (grid.tbody.rows[i].cells[4].value == '') {
                row = grid.tbody.rows[i];
                row.selected = true;
                break;
            }
        }
        
        // alert('here!');
        
        if (row === false) {
            tabSheet.onCustomEvent('process-ready');
            return;
        }
        
        row.cells[4].value = 'Processing...';
        
        var req = [];
        req.addPOST( '_id', row.primaryKey );
        req.addPOST( 'type', tabSheet.destinationStorage.value );
        
        
        dlg.$_PLUGIN_JSON_POST( '%plugins%/storage', 'move-to-storage', req, function( rsp ) {
            if (!rsp) {
                tabSheet.onCustomEvent('process-error', rsp );
                return;
            }

            row.cells[4].value = rsp;
            
            if (rsp != 'ok') {
                tabSheet.onCustomEvent('process-error', rsp);
            } else {
                row.cells[3].value = tabSheet.destinationStorage.value;
                tabSheet.onCustomEvent('process-success');
            }
        });

    }
    
    grid.delrow = function( primaryKey ) {
        for (var i=0; i<grid.tbody.rows.length; i++)
            if (grid.tbody.rows[i].primaryKey == primaryKey)
                return ['ok', ''].indexOf( grid.tbody.rows[i].cells[4].value ) != -1;
        return true;
    }
    
    var stopProcess = tabSheet.insert(
        (new Button('<b>Stop</b>', function() {
            tabSheet.onCustomEvent('process-abort');
        } ) ).setAttr(
            "style", "position: absolute; bottom: 14px; right: 4px; display: block"
        ).setAttr(
            "disabled", "disabled"
        )
    );
    
    migrateTo = tabSheet.insert(
        (new Button('<b>Migrate</b>', function() {
            if (isWorking)
                return;
            isWorking = true;
            tabSheet.destinationStorage.disabled = true;
            migrateTo.disabled = true;
            stopProcess.disabled = false;
            tabSheet.migrateNext();
        } ) ).setAttr(
            "style", "position: absolute; bottom: 14px; right: 64px; display: block"
        )
    );

    
    
    tabSheet.addCustomEventListener('process-error', function( data ) {
        alert("Storage migration error!\n\n" + (data ? data : "NULL RESPONSE FROM SERVER!") );
        tabSheet.onCustomEvent('process-abort');
        return true;
    });
    
    tabSheet.addCustomEventListener('process-abort', function() {
        isWorking = false;
        tabSheet.destinationStorage.disabled = false;
        migrateTo.disabled = false;
        stopProcess.disabled = true;
        return true;
    } );
    
    tabSheet.addCustomEventListener('process-ready', function() {
        isWorking = false;
        tabSheet.destinationStorage.disabled = false;
        migrateTo.disabled = false;
        stopProcess.disabled = true;
        
        return true;
    });
    
    tabSheet.addCustomEventListener('process-success', function() {
        if (isWorking) {
            setTimeout( function() {
                if (isWorking)
                    tabSheet.migrateNext();
            }, 100 );
        }
        return true;
    });
}