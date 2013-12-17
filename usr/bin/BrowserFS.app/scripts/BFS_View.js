/* BrowserFS View panel */
function BFS_View( app ) {
    
    var panel = $('div', 'BFS_View' ),
        body  = panel.appendChild( $('div', 'body medium' ) );
    
    // link to app icons manager
    panel.icons = app.interface.iconsManager;
    panel.app   = app;
    
    var sizes = {
        "small": {
            "iconWidth": 16,
            "iconHeight": 16,
            "itemWidth": 200,
            "itemHeight": 22
        },
        "medium": {
            "iconWidth": 64,
            "iconHeight": 64,
            "itemWidth": 100,
            "itemHeight": 110
        },
        "large": {
            "iconWidth": 128,
            "iconHeight": 128,
            "itemWidth": 140,
            "itemHeight": 180
        }
    },

    size = 'medium',
    
    iconWidth    = sizes[size].iconWidth,
    iconHeight   = sizes[size].iconHeight,
    itemWidth    = sizes[size].itemWidth,
    itemHeight   = sizes[size].itemHeight,
    
    itemsPerLine = 0,
    selectionStartIndex= -1, // when the shift key has been pressed, which index was focused
    
    sortBy = '',
    
    items = [],
    
    activeItem = null;
    
    Object.defineProperty( panel, "iconWidth", {
        "get": function() {
            return iconWidth;
        }
    } );
    
    Object.defineProperty( panel, "iconHeight", {
        "get": function() {
            return iconHeight;
        }
    } );
    
    Object.defineProperty( panel, "itemWidth", {
        "get": function() {
            return itemWidth;
        }
    } );
    
    Object.defineProperty( panel, "itemHeight", {
        "get": function() {
            return itemHeight;
        }
    } );
    
    Object.defineProperty( panel, "itemsPerLine", {
        "get": function() {
            return itemsPerLine;
        }
    } );
    
    Object.defineProperty( panel, "iconSize", {
        
        "get": function() {
            return size;
        },
        "set": function( sizeStr ) {
            
            if ( [ 'small', 'medium', 'large' ].indexOf( sizeStr ) == -1 )
                throw "Bad size: Allowed: 'small', 'medium', 'large'";
            
            panel.removeClass( 'small' ).removeClass( 'medium' ).removeClass( 'large' ).addClass( size = sizeStr );
            
            iconWidth  = sizes[ size ].iconWidth;
            iconHeight = sizes[ size ].iconHeight;
            itemWidth  = sizes[ size ].itemWidth;
            itemHeight = sizes[ size ].itemHeight;
            
            app.interface.on( 'view.iconSize', size );
            
            panel._paint_();
        }
        
    } );
    
    Object.defineProperty( panel, "selectionStartIndex", {
        
        "get": function() {
            return selectionStartIndex;
        }
        
    } );
    
    Object.defineProperty( panel, "length", {
        "get": function() {
            return items.length;
        }
    } );
    
    panel.item = function( index ) {
        return items[ ~~index ];
    }
    
    Object.defineProperty( panel, "activeItem", {
        "get": function() {
            return activeItem;
        },
        "set": function( item ) {
            if ( activeItem )
                activeItem.removeClass( 'active' );
            
            activeItem = item ? item : null;
            
            if ( activeItem ) {
                activeItem.addClass( 'active' );
                activeItem.scrollIntoViewIfNeeded();
            }
        }
    } );
    
    var iconsArranger = Cowboy.debounce( 20, false, function() {
        
        var cx = 10,
            cy = 10,
            winWidth = body.offsetWidth,
            winHeight = body.offsetHeight,
            maxItemWidth = 0,
            iWidth = itemWidth,
            itemsPerLineLocal = 0;
        
        if ( size == 'small' ) {
            
            for ( var i=0, len=items.length; i<len; i++ )
                if ( maxItemWidth < items[i].offsetWidth )
                    maxItemWidth = items[i].offsetWidth;
            
            iWidth = Math.min( itemWidth, maxItemWidth );
            
        }
        
        itemsPerLineLocal = 0;
        itemsPerLine      = 0;
        
        for ( var i=0, len = items.length; i<len; i++ ) {
            items[i].style.left = cx + "px";
            items[i].style.top  = cy + "px";
            cx += ( iWidth + 10 );
            itemsPerLineLocal++;
            if ( ( cx + iWidth + 10 ) > winWidth ) {
                itemsPerLine = Math.max( itemsPerLineLocal, itemsPerLine );
                itemsPerLineLocal = 0;
                cx = 10;
                cy += ( itemHeight + 10 );
            }
        }
        
        itemsPerLine = Math.max( itemsPerLineLocal, itemsPerLine );
        
    } );
    
    var itemsSorter = Cowboy.debounce( 20, false, function() {
        items.sort( ( BFS_View_Sorters[ sortBy ] || BFS_View_Sorters.name ) );
        iconsArranger();
    } );
    
    Object.defineProperty( panel, "sortBy", {
        "get": function() {
            return sortBy;
        },
        "set": function( str ) {
            if ( !BFS_View_Sorters[ str ] )
                throw "Bad sort-by value!";
            if ( str == sortBy )
                return;
            sortBy = str;
            
            app.interface.on( 'view.sortBy', str );
            
            itemsSorter();
        }
    } );
    
    panel.addItem = function( obj ) {
        var result;
        items.push( result = body.appendChild( new BFS_View_Icon( panel, obj ) ) );
        itemsSorter();
        return result;
    };
    
    // Clears the panel
    panel.clear = function() {
        
        panel.activeItem = null;         //clear the active panel item
        
        while ( items.length )           // remove all the panel items
            body.removeChild( items.shift() ).purge();

        app.interface.selection.clear(); //clear the application selection
    };
    
    panel.setItems = function( itemsList ) {
        // Set items of the panel
        panel.clear();

        for ( var i=0, len = ( itemsList || [] ).length; i<len; i++ )
            panel.addItem( itemsList[i] );
    };
    
    panel._paint_ = function() {
        for ( var i=0, len = items.length; i<len; i++ )
            items[i]._paint_();
        iconsArranger();
    }
    
    panel.getIconIndex = function( icon ) {
        return items.indexOf( icon );
    };
    
    panel.getIconRow   = function( icon ) {
        return ~~( items.length / icon.index );
    };
    
    panel.getIconColumn= function( icon ) {
        return icon.index % itemsPerLine;
    }
    
    // add a main application resize event listener in order to resort the
    // icons to fit the viewport
    app.addCustomEventListener( 'resizeRun', function() {
        iconsArranger();
    } );
    
    panel.iconSize = 'small';
    panel.sortBy   = 'type';
    
    /* Define application handlers */
    app.handlers.cmd_view_icons_small = function() {
        panel.iconSize = 'small';
    };
    
    app.handlers.cmd_view_icons_medium = function() {
        panel.iconSize = 'medium';
    };
    
    app.handlers.cmd_view_icons_large = function() {
        panel.iconSize = 'large';
    };
    
    /* Bind panel keyboard bindings */
    
    panel.tabIndex = 0;

    panel.commands = {};

    panel.commands.keyDownHandler = function(evt) {
        if ( evt.keyCode == 16 && items.length ) {
            panel.activeItem = panel.activeItem || items[0];
            selectionStartIndex = panel.activeItem.index;
        }
    };
    
    panel.commands.keyUpHandler = function( evt ) {
        if ( evt.keyCode == 16 ) {
            selectionStartIndex = -1;
        }
    };

    panel.commands.selection_set_left = function() {
        
        app.interface.selection.clear();
        
        var index;
        
        if ( !items.length )
            return;
        
        if ( panel.activeItem === null )
            panel.activeItem = items[0];
        
        if ( ( index = panel.activeItem.index ) > 0 ) {
            panel.activeItem = items[ index - 1 ];
        }

        app.interface.selection.add( panel.activeItem );
        
    };

    panel.commands.cursor_move_left = function() {
        
        var index;
        
        if ( !items.length )
            return;
        
        if ( panel.activeItem === null )
            panel.activeItem = items[0];
        
        if ( ( index = panel.activeItem.index ) > 0 ) {
            panel.activeItem = items[ index - 1 ];
        }
        
    };
    
    panel.commands.selection_extend_left = function() {
        
        var index, previousItem;
        
        if ( !items.length )
            return;
        
        if ( panel.activeItem === null ) {
            panel.activeItem = index[0];
            selectionStartIndex = 0;
        }
        
        if ( ( index = panel.activeItem.index ) > 0 ) {
            
            previousItem = panel.activeItem;
            
            panel.activeItem = items[ index - 1 ];
            
            app.interface.selection.add( panel.activeItem );
            
            if ( selectionStartIndex < previousItem.index )
                app.interface.selection.remove( previousItem );
            else
                app.interface.selection.add( previousItem );
            
        }
        
    };

    panel.commands.selection_set_right = function() {
        
        app.interface.selection.clear();
        
        var index;
        
        if ( !items.length )
            return;
        
        if ( panel.activeItem === null ) {
            panel.activeItem = items[0];
            app.interface.selection.add( items[0] );
            return;
        }
        
        if ( ( index = panel.activeItem.index ) < items.length - 1 ) {
            panel.activeItem = items[ index + 1 ];
        }

        app.interface.selection.add( panel.activeItem );
        
    };

    panel.commands.cursor_move_right = function() {
        
        var index;
        
        if ( !items.length )
            return;
        
        if ( panel.activeItem === null )
            panel.activeItem = items[0];
        
        if ( ( index = panel.activeItem.index ) < items.length - 1 ) {
            panel.activeItem = items[ index + 1 ];
        }
        
    };

    panel.commands.selection_extend_right = function() {
        
        var index, previousItem;
        
        if ( !items.length )
            return;
        
        if ( panel.activeItem === null ) {
            panel.activeItem = items[0];
            selectionStartIndex = 0;
        }
        
        if ( ( index = panel.activeItem.index ) < items.length - 1 ) {
            
            previousItem = panel.activeItem;
            panel.activeItem = items[ index + 1 ];
            app.interface.selection.add( panel.activeItem );
            
            if ( selectionStartIndex > previousItem.index )
                app.interface.selection.remove( previousItem );
        }
        
    };

    panel.commands.selection_set_up = function() {
        
        app.interface.selection.clear();
        
        var index;
        
        if ( !items.length )
            return;
        
        if ( panel.activeItem === null )
            panel.activeItem = items[0];
        
        if ( ( index = panel.activeItem.index - itemsPerLine ) >= 0 ) {
            panel.activeItem = items[ index ];
        }

        app.interface.selection.add( panel.activeItem );
        
    };

    panel.commands.cursor_move_up = function() {
        
        var index;
        
        if ( !items.length )
            return;
        
        if ( panel.activeItem === null )
            panel.activeItem = items[0];
        
        if ( ( index = panel.activeItem.index - itemsPerLine ) >= 0 ) {
            panel.activeItem = items[ index ];
        }
        
    };

    panel.commands.selection_extend_up = function() {
        
        var index, previousItem, loopIndex;
        
        if ( !items.length )
            return;
        
        if ( panel.activeItem === null ) {
            panel.activeItem = items[0];
            selectionStartIndex = 0;
        }
        
        if ( ( index = panel.activeItem.index - itemsPerLine ) >= 0 ) {
            
            loopIndex = panel.activeItem.index;
            
            app.interface.selection.add( panel.activeItem = items[index] );
            
            do {
                
                previousItem = items[ loopIndex ];
                
                if ( selectionStartIndex >= loopIndex ) {
                    app.interface.selection.add( previousItem );
                } else {
                    app.interface.selection.remove( previousItem );
                }
                
                loopIndex--;
                
            } while ( loopIndex > index );
            
        }
        
    };

    panel.commands.selection_set_down = function() {
        
        app.interface.selection.clear();
        
        var index;
        
        if ( !items.length )
            return;
        
        if ( panel.activeItem === null )
            panel.activeItem = items[0];
        
        if ( ( index = panel.activeItem.index + itemsPerLine ) < items.length ) {
            panel.activeItem = items[ index ];
        }
        
        app.interface.selection.add( panel.activeItem );
        
    };

    panel.commands.cursor_move_down = function() {
        
        var index;
        
        if ( !items.length )
            return;
        
        if ( panel.activeItem === null )
            panel.activeItem = items[0];
        
        if ( ( index = panel.activeItem.index + itemsPerLine ) < items.length ) {
            panel.activeItem = items[ index ];
        }
        
    };
    
    panel.commands.selection_extend_down = function() {
        
        var index, previousItem, loopIndex;
        
        if ( !items.length )
            return;
        
        if ( panel.activeItem === null ) {
            panel.activeItem = items[0];
            selectionStartIndex = 0;
        }
        
        if ( ( index = panel.activeItem.index + itemsPerLine ) < items.length ) {
            
            loopIndex = panel.activeItem.index;
            
            app.interface.selection.add( panel.activeItem = items[ index ] );
            
            do {
                
                previousItem = items[ loopIndex ];
                
                if ( selectionStartIndex <= loopIndex ) {
                    app.interface.selection.add( previousItem );
                } else {
                    app.interface.selection.remove( previousItem );
                }
                
                loopIndex++;
                
            } while ( loopIndex < index );
            
        }
    };

    panel.commands.selection_toggle_current = function() {
        
        if ( !items.length )
            return;
        
        if ( panel.activeItem === null ) {
            panel.activeItem = items[0];
        }
        
        app.interface.selection.xor( panel.activeItem );
        
    };

    panel.commands.selection_set_first = function() {
        
        if ( !items.length )
            return;
        
        app.interface.selection.set( panel.activeItem = items[0] );
        
    };

    panel.commands.cursor_move_first = function() {
        if ( !items.length )
            return;
        
        panel.activeItem = items[0];
    };
    
    panel.commands.selection_set_last = function() {
        
        if ( !items.length )
            return;
        
        app.interface.selection.set( panel.activeItem = items[ items.length - 1 ] );
        
    };
    
    panel.commands.cursor_move_end = function() {
        if ( !items.length )
            return;
        
        panel.activeItem = items[ items.length - 1 ];
    };
    
    panel.commands.selection_set_all = function() {
        
        for ( var i=0, len = items.length; i<len; i++ )
            if ( !items[i].selected )
                app.interface.selection.add( items[i] );
        
    };
    
    panel.commands.selection_invert = function() {
        
        for ( var i=0, len = items.length; i<len; i++ ) {
            
            if ( !items[i].selected )
                app.interface.selection.add( items[i] );
            else
                app.interface.selection.remove( items[i] );
            
        }
        
    };
    
    panel.commands.cmd_open = function() {
        app.interface.on( 'open' );
    };
    
    ( function() {
        
        var panelKeyboardEnabled = false;
        
        Object.defineProperty( panel, "keyboardEnabled", {
            "get": function() {
                return panelKeyboardEnabled;
            },
            "set": function( bool ) {
                bool = !!bool;
                
                if ( bool == panelKeyboardEnabled )
                    return;
                
                panelKeyboardEnabled = bool;
                
                switch ( bool ) {
                    case true:
    
                        panel.addEventListener( 'keydown', panel.commands.keyDownHandler , true );
                        panel.addEventListener( 'keyup',   panel.commands.keyUpHandler,    true );

                        Keyboard.bindKeyboardHandler( panel, 'left',        panel.commands.selection_set_left );
                        Keyboard.bindKeyboardHandler( panel, 'ctrl left',   panel.commands.cursor_move_left );
                        Keyboard.bindKeyboardHandler( panel, 'shift left',  panel.commands.selection_extend_left );
                        Keyboard.bindKeyboardHandler( panel, 'right',       panel.commands.selection_set_right );
                        Keyboard.bindKeyboardHandler( panel, 'ctrl right',  panel.commands.cursor_move_right );
                        Keyboard.bindKeyboardHandler( panel, 'shift right', panel.commands.selection_extend_right );
                        Keyboard.bindKeyboardHandler( panel, 'up',          panel.commands.selection_set_up );
                        Keyboard.bindKeyboardHandler( panel, 'ctrl up',     panel.commands.cursor_move_up );
                        Keyboard.bindKeyboardHandler( panel, 'shift up',    panel.commands.selection_extend_up );
                        Keyboard.bindKeyboardHandler( panel, 'down',        panel.commands.selection_set_down );
                        Keyboard.bindKeyboardHandler( panel, 'ctrl down',   panel.commands.cursor_move_down );
                        Keyboard.bindKeyboardHandler( panel, 'shift down',  panel.commands.selection_extend_down );
                        Keyboard.bindKeyboardHandler( panel, 'ctrl space',  panel.commands.selection_toggle_current );
                        Keyboard.bindKeyboardHandler( panel, 'home',        panel.commands.selection_set_first );
                        Keyboard.bindKeyboardHandler( panel, 'ctrl home',   panel.commands.cursor_move_first );
                        Keyboard.bindKeyboardHandler( panel, 'end',         panel.commands.selection_set_last );
                        Keyboard.bindKeyboardHandler( panel, 'ctrl end',    panel.commands.cursor_move_end );
                        Keyboard.bindKeyboardHandler( panel, 'ctrl a',      panel.commands.selection_set_all );
                        Keyboard.bindKeyboardHandler( panel, 'ctrl i',      panel.commands.selection_invert );
                        Keyboard.bindKeyboardHandler( panel, 'enter',       panel.commands.cmd_open );
                        break;
                    
                    case false:
                        panel.removeEventListener( 'keydown', panel.commands.keyDownHandler , true );
                        panel.removeEventListener( 'keyup',   panel.commands.keyUpHandler,    true );
                        
                        Keyboard.unbindKeyboardHandlers( panel, [
                            'left',
                            'ctrl left',
                            'shift left',
                            'right',
                            'ctrl right',
                            'shift right',
                            'up',
                            'ctrl up',
                            'shift up',
                            'down',
                            'ctrl down',
                            'shift down',
                            'ctrl space',
                            'home',
                            'ctrl home',
                            'end',
                            'ctrl end',
                            'ctrl a',
                            'ctrl i',
                            'enter'
                        ] );
                        
                        break;
                }
            }
        });
    
    })();
    
    panel.keyboardEnabled = true;
    
    /* Panel mouse bindings */
    
    body.addEventListener( 'mousedown', function( evt ){
        
        if ( evt.target == body && evt.which == 1 && !evt.ctrlKey ) {
            app.interface.selection.clear();
        }
        
        panel.focus();
        
    }, true );
    
    body.addEventListener( 'dblclick', function( evt ){
        
        if ( evt.target && evt.target.hasClass( 'icon' ) && !evt.ctrlKey && !evt.shiftKey ) {
            app.interface.on( 'open' );
        }
        
    } );
    
    panel.addCustomEventListener( 'rename', function( info ) {
        
        if ( !info['new'] || info['new'] == info['old'] )
            return false;
        
        try {

            info.source.inode.name = info['new'];
        
            info.source.inode.save();
            
            info.source.name = info['new'];
            
        } catch ( e ) {
            
            DialogBox( e + '', {
                "caption": "Error renaming",
                "childOf": app,
                "modal": true
            });
            
        }
        
        //console.log( info );
        
    } );
    
    /* Application bidingins */
    
    app.handlers.cmd_file_rename = function() {
        
        if ( app.interface.selection.length == 1 && app.interface.view.activeItem == app.interface.selection.item(0) ) {
            
            app.interface.view.activeItem.renameMode = true;
            
        }
        
    };
    
    Keyboard.bindKeyboardHandler( app, 'f2', function() {
        app.appHandler('cmd_file_rename');
    } );
    
    new BFS_View_Mouse_Selection( body, app );
    new BFS_View_Context_Menu( body, app );
    
    return panel;
    
}