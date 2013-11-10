window.OneDB_CategoryOrder_Plugin_Panel = function( tabSheet ) {
    
    var lnk = $('link');
    lnk.rel = 'stylesheet';
    lnk.type = 'text/css';
    lnk.href = 'classes/OneDB/plugins/order/style.css?time=' + (new Date()).getTime();
    
    document.getElementsByTagName('head')[0].appendChild( lnk );
    
    tabSheet.dlg = dlg = getOwnerWindow( tabSheet );
    
    var lastLoadedNode;
    var overlay;
    
    var orderItems = [{
        "id": "sortOrder",
        "name": "Default"
    }];
    
        (function(){
            try {
                var items = JSON.parse(tabSheet.dlg.getEnv('Plugin.Order.ItemsList'));
                if (items instanceof Array && items.length ) {
                    for ( var i=0,n=items.length; i<n; i++ ) {
                        orderItems.push( items[i] );
                    }
                }
            } catch (e){
            }
        })();
    
    var currentOrder = tabSheet.insert( 
        (new DropDown( ) )
            .setItems( orderItems )
            .setAttr("style", "position: absolute; left: 5px; top: -25px")
            .setAnchors({
                "width": function(w,h){
                    return w - 10 + "px";
                }
            })
    );
    
    currentOrder.addEventListener('change', function() {
        reloadPanel( lastLoadedNode );
    });
    
    tabSheet.insert(
        overlay = $('div', 'OneDB_CategoryOrder_Overlay').setAnchors({
            "width": function(w,h) {
                return w + 'px';
            },
            "height": function(w,h) {
                return h - 30 + 'px';
            }
        }).setAttr(
            "style", "overflow-x: hidden; overflow-y: auto; margin-top: 30px; position: relative;"
        )
    );
    
    MakeDropable( overlay );
    
    overlay.addCustomEventListener( 'dragenter', function() {
        addStyle( overlay, 'dragenter');
        return true;
    } );
    
    overlay.addCustomEventListener( 'dragleave', function() {
        removeStyle( overlay, 'dragenter');
        return true;
    } );
    
    overlay.addItem = function( item, appendAlways, ignoreType ) {
    
        item = item || {};

        if (!item.id)
            return false;
        
        if (!!!ignoreType) {
            if (/^category(\/|$)/.test( item.type ) )
                return false;
        }
        
        var d = document.createElement('div');
        
        d.nodeID = item.id;
        d.itemName = item.name;
        d.itemType = item.type;
        
        var iconHolder = d.appendChild( $('div', 'icon') );
        var textHolder = d.appendChild( $('div', 'text') );
        var orderHolder= d.appendChild( $('div', 'order') );
        
        disableSelection( iconHolder );
        disableSelection( textHolder );
        
        item.icon = '';
        
        d.setOrder = function( order ) {
            orderHolder.innerHTML = '';
            orderHolder.appendChild( $text( order ) );
        }
        
        if (typeof item.type != 'undefined')
            iconHolder.appendChild(
                $('img').setAttr(
                    'src',
                        OneDB_Navigator_GetIcon(
                            item,
                            false
                        )
                ).setAttr(
                    'dragable', '1'
                )
            );
        
        iconHolder.setAttr('dragable', '1');
        textHolder.setAttr('dragable', '1');
        
        textHolder.appendChild( $text( item.name ) );
        
        var first = overlay.firstChild;
        
        var indexPos = 0;
        
        while (first) {
            indexPos++;
            
            if (first.nodeID == item.id) {
                
                ( function( node ) {
                
                DialogBox("\"" + d.itemName + "\" is allready added in this list at position " + indexPos + "\n\nWhat do you want to do?", {
                    
                    "type": "question",
                    "childOf": getOwnerWindow( tabSheet ),
                    "buttons": {
                        "Move to first position": function() {
                            node.parentNode.firstChild.addBefore( node );
                            saveCurrentPanel();
                        },
                        "Cancel": function() {
                            
                        }
                    },
                    "caption": "What do you want to do?"
                    
                });
                
                })( first );
                
                return false;
            }
            first = first.nextSibling;
        }
        
        if (overlay.firstChild && !!!appendAlways) {
            overlay.insertBefore( d, overlay.firstChild );
        } else
            overlay.appendChild( d );
            
        (function() {
            d.addContextMenu([
                {
                    "caption": "Open",
                    "default": true,
                    "handler": function() {
                        // console.log( "Open: ", window.openNode = d );
                        var ow = getOwnerWindow( d );
                        ow.view.onCustomEvent(
                            'on-view-doubleclick',
                            {
                                "id": d.nodeID,
                                "name": d.itemName,
                                "type": d.itemType,
                                "path": ow.tree.realPath( ow.tree.selectedNode ) + d.itemName + (
                                    /^category(\/|$)/.test( d.itemType ) ? '/' : ""
                                )
                            }
                        );
                    }
                },
                null,
                {
                    'caption': "Delete",
                    "handler": function() {
                        d.parentNode.removeChild( d );
                        saveCurrentPanel();
                    }
                }
            ]);
        })();
        
        return true;
    };
    
    overlay.addCustomEventListener( 'drop', function( e ) {
        
        e = e || {};
        if (e.dataType != 'OneDB.items')
            return false;
        
        e.data = e.data || {};
        e.data.selection = e.data.selection || [];
        
        var needSave = false;
        
        for (var i=0; i<e.data.selection.length; i++) {
            if (overlay.addItem( e.data.selection[i] ))
                needSave = true;
        }
        
        if (needSave)
            saveCurrentPanel();
        
        
        return true;
    } );
    
    MakeSortable( overlay );
    
    var reloadPanel = function( nodeID ) {
    
        // if (typeof lastLoadedNode != 'undefined') {
        //     saveCurrentPanel();
        // }
    
        nodeID = nodeID || null;

        lastLoadedNode = nodeID;
    
        var req = [];
        req.addPOST('categoryID', nodeID );
        req.addPOST('viewName', currentOrder.value );
        
        if ( dlg.destroyed )
            return;
        
        var rsp = dlg.$_PLUGIN_JSON_POST( '%plugins%/order', 'load', req );
        // console.error( 'Node Orders [ ', nodeID, ']: ', rsp );
        overlay.innerHTML = '';
        
        rsp = rsp || [];
        
        // console.log( 'Loaded: ', rsp );
        
        for (var i=0; i<rsp.length; i++) {
            if (
                overlay.addItem(
                    rsp[i], true, true
                )
            ) {
                overlay.lastChild.setOrder( overlay.childNodes.length );
            }
        }
        
    };
    
    var saveCurrentPanel = function() {
    
        console.info('Saving panel');
    
        var items = [];
        var first = overlay.firstChild;
        var i = 1;
        
        while (first) {
            items.push({
                "id": first.nodeID,
                "order": i
            });
            first.setOrder( i );
            i++;
            first = first.nextSibling;
        }
        
        var req = [];
        req.addPOST( 'categoryId', lastLoadedNode );
        req.addPOST( 'items', JSON.stringify( items ) );
        req.addPOST( 'viewName', currentOrder.value );
        
        dlg.$_PLUGIN_JSON_POST( '%plugins%/order', 'save-order', req );
        
        // console.error( 'saved current panel', lastLoadedNode );
    }
    
    dlg.addCustomEventListener('path-changed', function( pathID ) {
        if (!dlg.destroyed)
            reloadPanel( pathID || null);
    } );
    
    overlay.addCustomEventListener('change', function() {
        //console.log('changed...');
        if (!dlg.destroyed)
            saveCurrentPanel();
    } );
    
    reloadPanel();
}