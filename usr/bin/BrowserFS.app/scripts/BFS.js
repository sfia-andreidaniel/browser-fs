/* @param: dialogFlags {
       // the application mode
       "applicationMode": nullable <string> enum( "shell", "desktop", "open", "save", "folder" ) default null
                          // when "shell"   - an explorer like interface will be created. Default
                          // when "desktop" - needed for future integration BrowserFS with BrowserCL
                          // when "open"    - an "open file" dialog will be created
                          // when "save"    - a "save file" dialog will be created
                          // when "folder"  - a "browse for folder" dialog will be created
       
       // use an existing connection ( needed for applicationMode "open", "save", "folder" )
       "connection"     : nullable <OneDB_Client>, default null
       
       // success callback, that will be triggered when the application is in a ready state first time
       "success"        : nullable <function( appInstance )>
       
       // error callback, that will be triggered when the application is in an error state first time
       "error"          : nullable <function( errorMessage )>
       
       // multiple selection allowed. ignored if the "applicationMode" is "shell", "desktop"
       "multiple"       : nullable <boolean>
       
       // allowed type of files in the interface validator function.
       // Ignored if the applicationMode is "shell", "desktop", "folder"
       "filters"        : nullable Object{ "name": <string>, "type": <string> }[]
       
       
   }
*/

function BFS( dialogFlags ) {

    var dlg;
    
    dialogFlags = dialogFlags || {};
    
    dialogFlags.applicationMode = dialogFlags.applicationMode || 'open';
    
    /* Test if dialogFlags.applicationMode is valid */
    if ( [ 'shell', 'desktop', 'open', 'save', 'folder' ].indexOf( dialogFlags.applicationMode ) == -1 )
        throw Exception('BrowserFS.Interface', 'Invalid application mode. Allowed: "shell", "desktop", "open", "save", "folder"' );
    
    // The browserFS startup process
    BFS_Startup( function( error ) {
        
        if ( error ) {
            DialogBox( error, {
                "caption": "Failed to load BrowserFS"
            } );
        } else {
            
            dlg = new Dialog({
                "alwaysOnTop": false,
                "appIcon": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAAXNSR0IArs4c6QAAAAZiS0dEAP8A/wD/oL2nkwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB90MBA0iFJ1W3gkAAAI2SURBVDjLlZPBS5RhEMZ/78z77eeWuh0ShNIgskgCS5EIw6OBWkS3oCA8RAUhIR2L/Sc8eTDwYCcDD4qHCr0FukohVKag0CJulOu26Od+774dRLMu6nMaBmbmmWfmMX3PHneu5zdGymUfcgRsl0rR7OynW3Y9vzEyk5kPjTFHqccYExYKxVG7M9ngvQdAVfHec5iGIhLactlTU3OS9vZ2VJXBwUGqq6sPzcTGLiadTuOcw1qLqjI8PIyqHljsvceqCLFzJCsqUFXiON5rdBBUFSsiyH/7WmsRkYNEREQQESGZTBIEAUEQEIYhqoqqYozBOYdzjnK5jLUW59w/Q2wikaC/v5+Ojg6stUxMTNDQ0EA2m+Xc+QvcaarFxSWKseH15EeedLWysLTM2NwKq6uCtrRcTt+7/4C6ujrq6+uJooienh6am5spBZXcnX/O5+NXWP5Z5GHbGdYmBkhdvcmvLcPXxcWdFYIgIIoiMpkMU1NT5HK5HXqqsL1FV53nUu0x3nwpcv20cIMM4qO/Gqgqzjmampro7u6mqqqKVCpFWFEBNkH6/SoDkwvU6BYfWl8y/26MvrZaPAaxQcD09AyqSj6f31N5aGiIt+OjfLv2gpUNR3FrG5IpTs29IjzbytPx74SBxfT2PvILCyuICMaYPaWDIEBEKLkyCbvzE3HsqEydwADFQp6lpSVsLvcjymaze2ba9cT+e+/m9sciwtraWmQaGy92Fgq/R0TkSHb23kebm5u3/wAHzt/be1PtCwAAAABJRU5ErkJggg==",
                "caption": "BrowserFS - Connecting",
                "closeable": true,
                "height": dialogFlags.height || getMaxY(),
                "maximizeable": true,
                "minHeight": 200,
                "minWidth": 300,
                "minimizeable": true,
                "modal": false,
                "moveable": true,
                "resizeable": true,
                "scrollable": false,
                "visible": true,
                "width": dialogFlags.width || getMaxX(),
                "childOf": dialogFlags.childOf || null
            }).chain( function() {
                this.addClass( 'BrowserFS-Dialog' );
            } );
            
            Object.defineProperty( dlg, "flags", {
                "get": function() {
                    return dialogFlags;
                }
            });
            
            BFS_Shared( dlg );
            
            /* Initialize the interface */
            
            BFS_Interface( dlg );
            
            /* Initialize the application handlers */
            BFS_Connect( dlg );
            BFS_Common_Commands( dlg );
            BFS_Creator( dlg );
            
            dlg.appHandler( 'cmd_connect', dialogFlags.connection || null );
            
            if ( dialogFlags.applicationMode == 'shell' )
                window.bfs = dlg;
            
            return dlg;

        }

    });

}