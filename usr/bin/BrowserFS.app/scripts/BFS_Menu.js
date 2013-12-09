/* BrowserFS menu */

function BFS_Menu( app ) {
    
    app.menu = [{
        "caption": "File",
        "enabled": true,
        "items": [{
            "caption": "Open Address",
            "icon": "",
            "shortcut": "Ctrl + E",
            "enabled": true,
            "handler": app.appHandler,
            "id": "cmd_open_address"
        } , {
            "caption": "New",
            "icon": "",
            "shortcut": "",
            "enabled": true,
            "items": [{
                "caption": "Folder",
                "icon"   : "",
                "enabled": true,
                "id"     : "cmd_create_Category",
                "handler": app.appHandler,
                "items": [{
                        "caption": "- Other folder types -",
                        "enabled": false
                    },
                    {
                        "caption": "Search Folder",
                        "icon"   : "",
                        "enabled": true,
                        "id"     : "cmd_create_Category_Search",
                        "handler": app.appHandler
                    }, {
                        "caption": "WebService Folder",
                        "icon"   : "",
                        "enabled": true,
                        "id"     : "cmd_create_Category_WebService",
                        "handler": app.appHandler
                    }, {
                        "caption": "Aggregator Folder",
                        "icon"   : "",
                        "enabled": true,
                        "id"     : "cmd_create_Category_Aggregator",
                        "handler": app.appHandler
                    }
                ]
            }, {
                "caption": "Document",
                "icon"   : "",
                "enabled": true,
                "id"     : "cmd_create_Document",
                "handler": app.appHandler
            }, {
                "caption": "List",
                "icon"   : "",
                "enabled": true,
                "id"     : "cmd_create_List",
                "handler": app.appHandler
            }, {
                "caption": "Widget",
                "icon"   : "",
                "enabled": true,
                "id"     : "cmd_create_Widget",
                "handler": app.appHandler
            }, {
                "caption": "File",
                "icon"   : "",
                "enabled": true,
                "id"     : "cmd_create_File",
                "handler": app.appHandler
            }]
        }, null, {
            "caption": "Rename",
            "icon": "",
            "enabled": true,
            "id": "cmd_file_rename",
            "handler": app.appHandler
        }, {
            "caption": "Delete",
            "icon": "",
            "id": "cmd_file_delete",
            "handler": app.appHandler
        }, {
            "caption": "Copy To...",
            "icon": "",
            "enabled": true,
            "id": "cmd_file_copy_to",
            "handler": app.appHandler
        }, {
            "caption": "Move To...",
            "icon": "",
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
            "id": "cmd_view_small",
            "icon": "",
            "shortcut": "",
            "input": "radio:view",
            "enabled": true
        }, {
            "caption": "Normal Icons",
            "id": "cmd_view_normal",
            "icon": "",
            "shortcut": "",
            "input": "radio:view",
            "enabled": true
        }, {
            "caption": "Large Icons",
            "id": "cmd_view_large",
            "icon": "",
            "shortctut": "",
            "input": "radio:view",
            "enabled": true
        }, null, {
            "caption": "Tasks Panel",
            "id": "cmd_view_tasks_panel",
            "icon": "",
            "shortcut": "",
            "input": "checkbox",
            "enabled": true
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