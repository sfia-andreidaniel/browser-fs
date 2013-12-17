/* Actions that can be executed on selection items
 */
function BFS_FilesAssociations( app ) {
    
    var actions = [],
        flags   = {
            "SINGLE"           : 2,
            "MULTIPLE"         : 4,
            "ALL_DIRS"         : 8,
            "ALL_ITEMS"        : 16,
            "SAME_TYPE"        : 32,
            "SAME_TYPE_STRICT" : 64
            
        };
    
    Object.defineProperty( this, "flags", {
        "get": function() {
            return flags;
        }
    });
    
    Object.defineProperty( this, "actions", {
        "get": function() {
            return actions;
        }
    });
    
    /* @param: defaultAction    : <boolean>
       @param: itemType         : nullable string                        // eg: "Category"
       @param: handlerFunction  : function( <BFS_Selection> selection )
       @param: selectionFlag    : <int>                                  // bitmask from flags
       @param: filterFunction   : nullable function( inode )
       @param: actionPriority   : nullable <int>
    */
    this.registerAction = function( actionName, defaultAction, itemType, handlerFunction, selectionFlag, filterFunction, actionPriority ) {
        
        actionName = actionName || '';
        
        defaultAction = !!defaultAction;
        
        itemType = itemType || null;
        
        if ( itemType !== null && !Strict.is_string( itemType ) )
            throw "3nd argument should be a nullable string!";
        
        if ( !Strict.is_callable( handlerFunction ) )
            throw "4rd argument should be a callback";
        
        selectionFlag = ~~selectionFlag;
        
        filterFunction = filterFunction || null;
        
        if ( filterFunction !== null && !Strict.is_callable( filterFunction ) )
            throw "6th argument should be a nullable callback";
        
        actionPriority = ~~actionPriority;
        
        actions.push( {
            "name": actionName,
            "default": defaultAction,
            "type": itemType,
            "handler": handlerFunction,
            "flags": selectionFlag,
            "filter": filterFunction,
            "priority": actionPriority
        } );
        
    };
    
    this.getActions = function( selection ) {
        
        var mask = 0,
            fl = {
                "SINGLE": 0,
                "MULTIPLE": 0,
                "ALL_DIRS": 0,
                "ALL_ITEMS": 0,
                "SAME_TYPE": 0,
                "SAME_TYPE_STRICT": 0
            },
            numDirs = 0,
            numItems= 0,
            firstType = null,
            firstSType = null,
            out = [],
            valid = false;
        
        fl.SINGLE   = ( selection.length == 1 ) ? 1 : 0;
        fl.MULTIPLE = ( selection.length > 1 ) ? 1 : 0;

        for ( var i=0, len = selection.length; i<len; i++ ) {

            if ( selection.item(0).inode.has_flag( 'container' ) )
                numDirs++;
            else
                numItems++;
            
            if ( i == 0 ) {
                
                firstType = selection.item(0).inode.type.toLowerCase().split( '_' )[0];
                firstSType= selection.item(0).inode.type;
                
            } else {
                
                if ( firstType && selection.item(i).inode.type.toLowerCase().split( '_' )[0] != firstType )
                    firstType = null;
                
                if ( firstSType && selection.item(i).inode.type != firstSType )
                    firstSType = null;
                
            }
        }
        
        fl.ALL_DIRS  = ( numItems == 0 && numDirs > 0 ) ? 1 : 0;
        fl.ALL_ITEMS = ( numDirs == 0 && numItems > 0 ) ? 1 : 0;
        fl.SAME_TYPE = ( firstType !== null ) ? 1 : 0;
        fl.SAME_TYPE_STRICT = ( firstSType !== null ) ? 1 : 0;
        
        mask = 0;
        
        for ( var flag in flags ) {
            
            if ( flags.hasOwnProperty( flag ) && flags.propertyIsEnumerable( flag ) )
                mask += ( fl[ flag ] * flags[ flag ] );
            
        }
        
        for ( var i=0, n = actions.length; i<n; i++ ) {

            valid = true;
            
            for ( var j=0, len = selection.length; j<len; j++ ) {
                
                if ( actions[ i ].flags > 0 && ( ( mask & actions[i].flags ) != actions[i].flags ) ) {
                    valid = false; break;
                }
                
                if ( actions[ i ].type && actions[i].type != firstSType ) {
                    valid = false; break;
                }
                
                if ( actions[i].filter && !actions[i].filter( selection.item(j).inode ) ) {
                    valid = false; break;
                }
            }
            
            if ( valid ) {
                
                out.push( {
                    "name": actions[i].name,
                    "handler": actions[i].handler,
                    "default": actions[i]['default'],
                    "priority": actions[i].priority
                } );
                
            }
        }
        
        return out.sort( function( a, b ) {
            return a.priority == b.priority
                ? a.name.toLowerCase().strcmp( b.name.toLowerCase() )
                : a.priority - b.priority;
        } );
        
        return out;
    };
    
    return this;
}