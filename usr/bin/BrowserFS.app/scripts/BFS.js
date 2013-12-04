function BFS_Backend() {

    var dlg,
        panel,
        iconsPanel;
    
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
                "caption": "BrowserFS",
                "closeable": true,
                "height": 405,
                "maximizeable": true,
                "minHeight": 50,
                "minWidth": 50,
                "minimizeable": true,
                "modal": false,
                "moveable": true,
                "resizeable": true,
                "scrollable": false,
                "visible": true,
                "width": 575
            }).chain( function() {
                
                this.addClass( 'BrowserFS-Dialog' );
                
            } );
        
            /* Initialize the interface */
        
            /* Initialize application menu */
            BFS_Menu( dlg );
        
            /* Initialize application toolbar */
            BFS_Toolbar( dlg );
        
            /* Create the app panel */
        
            panel = dlg.insert( new BFS_Panel() );
        
            Object.defineProperty( dlg, "panel", {
                "get": function() {
                    return panel;
                }
            } );                

            /* Create the app icons panel */    

            iconsPanel = dlg.insert( new BFS_IconsPanel() );
    
            Object.defineProperty( dlg, "iconsPanel", {
                "get": function() {
                    return iconsPanel;
                }
            } );
        
            return dlg;

        }

    });

}