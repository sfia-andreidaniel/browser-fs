/* BrowserFS menu */

function BFS_Menu( app ) {
    
    app.shared.menu = {
        "view": {
            "caption": "Icon Size",
            "items": []
        }
    };
    
    [{
        "caption": "File",
        "enabled": true,
        "items": [{
            "caption": "Open Address",
            "icon": "data:image/png;base64,{$include resources/menu/open.png}",
            "shortcut": "Ctrl + E",
            "enabled": true,
            "handler": app.appHandler,
            "id": "cmd_open_address"
        } , {
            "caption": "New",
            "icon": "data:image/png;base64,{$include resources/menu/new.png}",
            "shortcut": "",
            "enabled": true,
            "items": [{
                "caption": "Folder",
                "icon"   : app.interface.iconsManager.createImage( 'Category.svg', 16, 16 ),
                "enabled": true,
                "id"     : "",
                "handler": function() { app.appHandler( 'cmd_create', 'Category' ); },
                "items": [{
                        "caption": "- Other folder types -",
                        "enabled": false
                    },
                    {
                        "caption": "Search Folder",
                        "icon"   : app.interface.iconsManager.createImage( 'Category_Search.svg', 16, 16 ),
                        "enabled": true,
                        "id"     : "",
                        "handler": function() { app.appHandler( 'cmd_create', 'Category.Search' ); }
                    }, {
                        "caption": "WebService Folder",
                        "icon"   : app.interface.iconsManager.createImage( 'Category_WebService.svg', 16, 16 ),
                        "enabled": true,
                        "id"     : "",
                        "handler": function(){ app.appHandler( 'cmd_create', 'Category.WebService' ); }
                    }, {
                        "caption": "Aggregator Folder",
                        "icon"   : app.interface.iconsManager.createImage( 'Category_Aggregator.svg', 16, 16 ),
                        "enabled": true,
                        "id"     : "",
                        "handler": function() { app.appHandler( 'cmd_create', 'Category.Aggregator' ); }
                    }
                ]
            }, {
                "caption": "Document",
                "icon"   : app.interface.iconsManager.createImage( 'Document.svg', 16, 16 ),
                "enabled": true,
                "id"     : "cmd_create_Document",
                "handler": function() { app.appHandler( 'cmd_create', 'Document' ); }
            }, {
                "caption": "List",
                "icon"   : app.interface.iconsManager.createImage( 'List.svg', 16, 16 ),
                "enabled": true,
                "id"     : "cmd_create_List",
                "handler": function() { app.appHandler( 'cmd_create', 'List' ); }
            }, {
                "caption": "Widget",
                "icon"   : app.interface.iconsManager.createImage( 'Widget.svg', 16, 16 ),
                "enabled": true,
                "id"     : "cmd_create_Widget",
                "handler": function() { app.appHandler( 'cmd_create', 'Widget' ); }
            }, {
                "caption": "File",
                "icon"   : app.interface.iconsManager.createImage( 'File.svg', 16, 16 ),
                "enabled": true,
                "id"     : "cmd_create_File",
                "handler": function() { app.appHandler( 'cmd_create', 'File' ); }
            }]
        }.chain( function() {
            app.shared.menu.create = this;
        } ), null, {
            "caption": "Rename",
            "icon": "data:image/png;base64,{$include resources/menu/rename.png}",
            "enabled": true,
            "id": "cmd_file_rename",
            "handler": app.appHandler,
            "shortcut": "F2"
        }.chain( function() {
            app.shared.menu.menu_rename = this;
        } ), {
            "caption": "Delete",
            "icon": "data:image/png;base64,{$include resources/menu/delete.png}",
            "id": "cmd_file_delete",
            "handler": app.appHandler
        }.chain( function() {
            app.shared.menu.menu_delete = this;
        } ), {
            "caption": "Copy To...",
            "icon": "data:image/png;base64,{$include resources/menu/copy-to.png}",
            "enabled": true,
            "id": "cmd_file_copy_to",
            "handler": app.appHandler
        }.chain( function() {
            app.shared.menu.menu_copy_to = this;
        } ), {
            "caption": "Move To...",
            "icon": "data:image/png;base64,{$include resources/menu/move-to.png}",
            "enabled": true,
            "id": "cmd_file_move_to",
            "handler": app.appHandler
        }.chain( function() {
            app.shared.menu.menu_move_to = this;
        } ), null, {
            "caption": "Exit",
            "icon": "",
            "enabled": true,
            "id": "cmd_exit",
            "handler": app.appHandler
        }]
    }, {
        "caption": "Edit",
        "enabled": true,
        "items": [{
            "caption": "Cut",
            "id": "cmd_edit_cut",
            "icon": "data:image/png;base64,{$include resources/menu/edit-cut.png}",
            "shortcut": "Ctrl + X",
            "enabled": true,
            "handler": app.appHandler
        }.chain( function() {
            app.shared.menu.menu_cut = this;
        } ), {
            "caption": "Copy",
            "id": "cmd_edit_copy",
            "icon": "data:image/png;base64,{$include resources/menu/edit-copy.png}",
            "shortcut": "Ctrl + C",
            "enabled": true,
            "handler": app.appHandler
        }.chain( function() {
            app.shared.menu.menu_copy = this;
        } ), {
            "caption": "Paste",
            "id": "cmd_edit_paste",
            "icon": "data:image/png;base64,{$include resources/menu/edit-paste.png}",
            "shortcut": "Ctrl + V",
            "enabled": true,
            "handler": app.appHandler,
            "disabled": function() {
                return BFS_Clipboard.length == 0;
            }
        }.chain( function() {
            
            app.shared.menu.pasteMenu = this;
            
        } ), null, {
            "caption": "Select All",
            "id": "cmd_select_all",
            "icon": "data:image/png;base64,{$include resources/menu/edit-select-all.png}",
            "shortcut": "Ctrl + A",
            "enabled": true,
            "handler": app.appHandler
        }, {
            "caption": "Invert Selection",
            "id": "cmd_select_invert",
            "icon": "data:image/png;base64,{$include resources/menu/edit-select-invert.png}",
            "shortcut": "Ctrl + I",
            "enabled": true,
            "handler": app.appHandler
        }, null, {
            "caption": "Find",
            "id": "cmd_search",
            "icon": "data:image/png;base64,{$include resources/menu/edit-find.png}",
            "shortcut": "Ctrl + F",
            "enabled": true,
            "handler": app.appHandler
        }, null, {
            "caption": "Properties",
            "id": "cmd_properties",
            "icon": "data:image/png;base64,{$include resources/menu/edit-properties.png}",
            "shortcut": "Alt + Enter",
            "enabled": true,
            "handler": app.appHandler
        }.chain( function() {
            app.shared.menu.properties = this;
        } )]
    }, {
        "caption": "View",
        "enabled": true,
        "items": [{
            "caption": "Small Icons",
            "id": "cmd_view_icons_small",
            "icon": "data:image/png;base64,{$include resources/menu/small-icons.png}",
            "shortcut": "",
            "input": "radio:view",
            "enabled": true,
            "handler": app.appHandler,
            "checked": function() {
                return app.interface.view.iconSize == 'small';
            }
        }.chain( function() {
            
            app.shared.menu.view.items.push( this );
            
        } ), {
            "caption": "Normal Icons",
            "id": "cmd_view_icons_medium",
            "icon": "data:image/png;base64,{$include resources/menu/medium-icons.png}",
            "shortcut": "",
            "input": "radio:view",
            "enabled": true,
            "handler": app.appHandler,
            "checked": function() {
                return app.interface.view.iconSize == 'medium';
            }
        }.chain( function() {
            
            app.shared.menu.view.items.push( this );
            
        } ), {
            "caption": "Large Icons",
            "id": "cmd_view_icons_large",
            "icon": "data:image/png;base64,{$include resources/menu/large-icons.png}",
            "shortctut": "",
            "input": "radio:view",
            "enabled": true,
            "handler": app.appHandler,
            "checked": function() {
                return app.interface.view.iconSize == 'large';
            }
        }.chain( function() {
            
            app.shared.menu.view.items.push( this );
            
        } ), null, {
            "caption": "Tasks Panel",
            "id": "cmd_view_tasks_panel",
            "icon": "data:image/png;base64,{$include resources/menu/task-panel.png}",
            "shortcut": "",
            "input": "checkbox",
            "enabled": true,
            "handler": function( visibility ) {
                app.interface.panel.visible = visibility;
            },
            "checked": function() {
                return app.interface.panel.visible;
            }
        } , {
            "caption": "Search Bar",
            "id": "cmd_view_search_bar",
            "icon": "data:image/png;base64,{$include resources/menu/search-bar.png}",
            "shortcut": "",
            "input": "checkbox",
            "enabled": true,
            "handler": function( visibility ) {
                app.interface.search.visible = visibility;
            },
            "checked": function() {
                return app.interface.search.visible;
            }
        } , {
            "caption": "Address Bar",
            "id": "cmd_view_address_bar",
            "icon": "data:image/png;base64,{$include resources/menu/address-bar.png}",
            "shortcut": "",
            "input": "checkbox",
            "enabled": true,
            "handler": function( visibility ) {
                app.interface.location.visible = visibility;
            },
            "checked": function() {
                return app.interface.location.visible;
            }
        }]
    }, {
        "caption": "Tools",
        "enabled": true,
        "items": [{
            "caption": "Options",
            "id": "cmd_tools_options",
            "icon": "data:image/png;base64,{$include resources/menu/tools-preferences.png}",
            "shortcut": "",
            "input": "",
            "enabled": true
        }]
    }, {
        "caption": "Help",
        "enabled": true,
        "items": [{
            "caption": "Online Help",
            "id": "cmd_help",
            "icon": "data:image/png;base64,{$include resources/menu/help-online.png}",
            "shortcut": "F1",
            "enabled": true
        } , null, {
            "caption": "About",
            "id": "cmd_help_about",
            "icon": "",
            "shortcut": "",
            "enabled": true
        }]
    }].chain( function() {
        
        if ( app.flags.applicationMode == 'shell' )
            app.menu = this;
        
    } );
    
}