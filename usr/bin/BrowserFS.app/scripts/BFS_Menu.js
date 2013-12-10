/* BrowserFS menu */

function BFS_Menu( app ) {
    
    app.menu = [{
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
                "id"     : "cmd_create_Category",
                "handler": app.appHandler,
                "items": [{
                        "caption": "- Other folder types -",
                        "enabled": false
                    },
                    {
                        "caption": "Search Folder",
                        "icon"   : app.interface.iconsManager.createImage( 'Category.Search.svg', 16, 16 ),
                        "enabled": true,
                        "id"     : "cmd_create_Category_Search",
                        "handler": app.appHandler
                    }, {
                        "caption": "WebService Folder",
                        "icon"   : app.interface.iconsManager.createImage( 'Category.WebService.svg', 16, 16 ),
                        "enabled": true,
                        "id"     : "cmd_create_Category_WebService",
                        "handler": app.appHandler
                    }, {
                        "caption": "Aggregator Folder",
                        "icon"   : app.interface.iconsManager.createImage( 'Category.Aggregator.svg', 16, 16 ),
                        "enabled": true,
                        "id"     : "cmd_create_Category_Aggregator",
                        "handler": app.appHandler
                    }
                ]
            }, {
                "caption": "Document",
                "icon"   : app.interface.iconsManager.createImage( 'Document.svg', 16, 16 ),
                "enabled": true,
                "id"     : "cmd_create_Document",
                "handler": app.appHandler
            }, {
                "caption": "List",
                "icon"   : app.interface.iconsManager.createImage( 'List.svg', 16, 16 ),
                "enabled": true,
                "id"     : "cmd_create_List",
                "handler": app.appHandler
            }, {
                "caption": "Widget",
                "icon"   : app.interface.iconsManager.createImage( 'Widget.svg', 16, 16 ),
                "enabled": true,
                "id"     : "cmd_create_Widget",
                "handler": app.appHandler
            }, {
                "caption": "File",
                "icon"   : app.interface.iconsManager.createImage( 'File.svg', 16, 16 ),
                "enabled": true,
                "id"     : "cmd_create_File",
                "handler": app.appHandler
            }]
        }, null, {
            "caption": "Rename",
            "icon": "data:image/png;base64,{$include resources/menu/rename.png}",
            "enabled": true,
            "id": "cmd_file_rename",
            "handler": app.appHandler
        }, {
            "caption": "Delete",
            "icon": "data:image/png;base64,{$include resources/menu/delete.png}",
            "id": "cmd_file_delete",
            "handler": app.appHandler
        }, {
            "caption": "Copy To...",
            "icon": "data:image/png;base64,{$include resources/menu/copy-to.png}",
            "enabled": true,
            "id": "cmd_file_copy_to",
            "handler": app.appHandler
        }, {
            "caption": "Move To...",
            "icon": "data:image/png;base64,{$include resources/menu/move-to.png}",
            "enabled": true,
            "id": "cmd_file_move_to",
            "handler": app.appHandler
        }, null, {
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
            "icon": "",
            "shortcut": "Ctrl + X",
            "enabled": true,
            "handler": app.appHandler
        }, {
            "caption": "Copy",
            "id": "cmd_edit_copy",
            "icon": "",
            "shortcut": "Ctrl + C",
            "enabled": true,
            "handler": app.appHandler
        }, {
            "caption": "Paste",
            "id": "cmd_edit_paste",
            "icon": "",
            "shortcut": "Ctrl + V",
            "enabled": true,
            "handler": app.appHandler
        }, null, {
            "caption": "Select All",
            "id": "cmd_select_all",
            "icon": "",
            "shortcut": "Ctrl + A",
            "enabled": true,
            "handler": app.appHandler
        }, {
            "caption": "Invert Selection",
            "id": "cmd_select_invert",
            "icon": "",
            "shortcut": "Ctrl + I",
            "enabled": true,
            "handler": app.appHandler
        }, null, {
            "caption": "Find",
            "id": "cmd_search",
            "icon": "",
            "shortcut": "Ctrl + F",
            "enabled": true,
            "handler": app.appHandler
        }, null, {
            "caption": "Properties",
            "id": "cmd_properties",
            "icon": "",
            "shortcut": "Alt + Enter",
            "enabled": true,
            "handler": app.appHandler
        }]
    }, {
        "caption": "View",
        "enabled": true,
        "items": [{
            "caption": "Small Icons",
            "id": "cmd_view_icons_small",
            "icon": "",
            "shortcut": "",
            "input": "radio:view",
            "enabled": true,
            "handler": app.appHandler,
            "checked": function() {
                return app.interface.view.iconSize == 'small';
            }
        }, {
            "caption": "Normal Icons",
            "id": "cmd_view_icons_medium",
            "icon": "",
            "shortcut": "",
            "input": "radio:view",
            "enabled": true,
            "handler": app.appHandler,
            "checked": function() {
                return app.interface.view.iconSize == 'medium';
            }
        }, {
            "caption": "Large Icons",
            "id": "cmd_view_icons_large",
            "icon": "",
            "shortctut": "",
            "input": "radio:view",
            "enabled": true,
            "handler": app.appHandler,
            "checked": function() {
                return app.interface.view.iconSize == 'large';
            }
        }, null, {
            "caption": "Tasks Panel",
            "id": "cmd_view_tasks_panel",
            "icon": "",
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
            "icon": "",
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
            "icon": "",
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
            "icon": "",
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
            "icon": "",
            "shortcut": "F1",
            "enabled": true
        } , null, {
            "caption": "About",
            "id": "cmd_help_about",
            "icon": "",
            "shortcut": "",
            "enabled": true
        }]
    }];
    
}