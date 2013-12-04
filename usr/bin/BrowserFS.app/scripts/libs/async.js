function Thing() {
    
    var events      = {},
        intervals   = {},
        me          = this;
    
    /* Event listeners interface */
    
    me.bind = function (eventName, handlerFunction ) {
        events[ eventName ] = events[ eventName ] || [];
        events[ eventName ].push( handlerFunction );
    }
    
    me.blind = function( handlerFunction ) {
        events[ '*' ] = events[ '*' ] || [];
        events[ '*' ].push( handlerFunction );
    }
    
    me.on = function( eventName, eventData ) {
        if (events[ eventName ] instanceof Array ) {
            for ( var i=0, len = events[eventName].length; i<len; i++ ) {
                if ( events[ eventName ][i]( eventData ) === false ) {
                    return false;
                    break;
                }
            }
        }
        
        else
        
        if ( events[ '*' ] instanceof Array ) {
            for ( var i=0, len = events['*'].length; i<len; i++ ) {
                if ( events[ '*' ][i]( eventData ) === false ) {
                    return false;
                    break;
                }
            }
        }
        
        return true;
    }
    
    me.remove = function( eventName, handlerFunction ) {
        handlerFunction = handlerFunction || null;
        if ( events[ eventName ] instanceof Array ) {
            if ( !handlerFunction ) {
                delete events[ eventName ];
            } else {
                for ( var i=0, len=events[ eventName ].length; i<len; i++ ) {
                    if ( events[ eventName ][i] == handlerFunction ) {
                        events[eventName].splice( i, 1 );
                        return true;
                    }
                }
            }
        }
        return true;
    }
    
    me.interval = function( intervalName, intervalFunction, durationMs ) {
        
        if ( typeof intervals[ intervalName ] != 'undefined' )
            throw "Failed to register interval!";
        
        intervals[ intervalName ] = setInterval( intervalFunction, durationMs ).unref();
    }
    
    return me;
}

function TaskGroup( init ) {
    
    Object.defineProperty( init, "allAre", {
        "get": function( ) {
            
            return function( propertyName, isPropertyValue ) {
            
                var value;
                
                for ( var i = 0, len = init.length; i<len; i++ ) {
                    value = init[i][ propertyName ];
                    
                    if ( value != isPropertyValue )
                        return false;
                }
            
                return true;
            };
        }
    } );
    
    Object.defineProperty( init, "first", {
        
        "get": function( ) {
            return init.length 
                ? init[0] 
                : null;
        }
        
    } );
    
    Object.defineProperty( init, "last", {
        
        "get": function() {
            
            return init.length > 0 
                ? init[ init.length - 1 ] 
                : null;

        }
        
    } );
    
    return init;
}

function Task( async, index, callback, success, error, taskOwner ) {
    
    var me = new Thing();
    
    me.async = !!async;

    me.complete = false;
    me.started  = false;
    me.error    = null;
    me.success  = null;
    me.prev     = null;
    me.next     = null;
    me.syncRes  = undefined;
    
    me.index    = index;
    
    Object.defineProperty( me, "siblingTasks", {
        
        "get": function() {
            
            if ( !me.async )
                return [];
            else {
                
                var out = [],
                    cursorNext = me.next,
                    cursorPrev = me.prev;
                
                while ( cursorPrev && cursorPrev.async == me.async ) {
                    out.push( cursorPrev );
                    cursorPrev = cursorPrev.prev;
                }
                
                while ( cursorNext && cursorNext.async == me.async ) {
                    out.push( cursorNext );
                    cursorNext = cursorNext.next
                }
                
                return new TasksGroup( out );
                
            }
        },
        "set": function() {
            throw "siblingTasks of a task are read-only!";
        }

    } );
    
    me.bind( 'complete', function() {
        
        if ( me.complete || me.success || me.error || !me.started ) {
            return;
        }
        
        me.complete = true;
        
        // If the task encountered an error, we don't complete
        // the last sibling next task start, but we rather
        // announce the task owner of the failure
        
        if ( me.error ) {
            
            taskOwner.on( 'error', me.error );
            
            return;
        }
        
        var myGroup = me.siblingTasks,
            allComplete = true;
            
        for ( var i=0, len=myGroup.length; i<len; i++ ) {
                
            if ( !myGroup[i].complete ) {
                return;
            }
        }
        
        // If all my sibling tasks are complete, and myGroup.last has a next task,
        // and that task is not started we start that task
        
        if ( ( myGroup && 
               myGroup.allAre && 
               myGroup.allAre('complete', true ) && 
               myGroup && myGroup.last
                ? (
                   myGroup.last &&
                   myGroup.last.next && 
                   !myGroup.last.next.started
                )
                : (
                  me.next &&
                  !me.next.started
                )
            )
        )
        
        setTimeout( function() {
            myGroup && myGroup.last && myGroup.last.next
                ? myGroup.last.next.on( 'start' )
                : me.next.on( 'start' );
            
        }, 10 );

        else

            if ( me.next && !me.async )
                me.next.on( 'start' );
        
    } );
    
    me.bind( 'start', function() {
        
        if ( me.started || me.error || me.success ) {
            return;
        }
        
        me.started = true;
        
        if ( !me.async ) {
            
            try {
                
                try {
                    if ( !taskOwner.ignoreFurtherCommands )
                        var result = callback.call( me );
                    else
                        throw "Task Owner is ignoring further commands";
                
                } catch ( f ) {
                
                    throw f;
                
                }
                
            } catch ( e ) {
                
                me.on( 'error', e + '' );
                
            }
            
        } else {
            
            try {
                
                if ( !taskOwner.ignoreFurtherCommands )
                    callback.call( me );
                else
                    throw "TaskOwner is ignoring further commands";
                
                if ( !me.error ) {
                
                    if ( me.next && me.next.async == me.async )
                        me.next.on( 'start' );
                    
                }
                
            } catch ( e ) {
                
                me.on( 'error', e + '' );
                
            }
            
        }
        
    } );
    
    me.bind( 'success', function( result ) {
        
        if ( !me.complete )
            me.on( 'complete' );
        
        if ( me.error || me.success ) {
            return;
        }
        
        me.success = result || true;
        
        taskOwner.on( 'complete' );

        if ( success ) {
            
            try {
                
                if ( !taskOwner.ignoreFurtherCommands )
                    success.call( me, result || true );
                else
                    throw "TaskOwner is ignoring further commands";
                
            } catch ( e ) {
                
                me.on( 'error', e + '' );
                
                return;
            }
            
        }
        
    } );
    
    me.bind( 'error', function( reason ) {
        
        me.error = reason || "Unknown error";
        
        if ( !me.complete )
            me.on( 'complete' );
        
        
        if ( me.success ) {
            return;
        }
        
        if ( error ) {
            
            try {
                if ( !taskOwner.ignoreFurtherCommands )
                    error.call( me, reason || null );
                else
                    throw "Task owner is ignoring further commands";
            } catch ( e ) {}
            
        }
        
        taskOwner.on( 'error', reason || 'unknown error' );
        
    } );
    
    me.bind( 'reset', function() {
        
        me.error = false;
        me.success = false;
        me.complete = false;
        me.started = false;
        me.prev = null;
        me.next = null;
        
    } );
    
    return me;
}

function Async( ) {
    var me = new Thing(),

        events = {},

        length = 0,
        
        error      = false,
        complete   = false,
        success    = false,
        
        callbackSuccess  = false,
        callbackError    = false,
        callbackComplete = false;
    
    Object.defineProperty( me, "ignoreFurtherCommands", {
        "get": function( ) {
            return complete || error || success;
        }
    } );
    
    me.sync = function( callback, success, error ) {
        
        events [ length ] = new Task( false, length, callback, success, error, me );
        
        length++;
        
        return me;
        
    };
    
    me.async = function( callback, success, error ) {
        
        events[ length ] = new Task( true, length, callback, success, error, me );
        
        length++;
        
        return me;
        
    };
    
    me.bind( 'complete', function() {
        
        // The complete
        
        if ( error || success || complete )
            return; // If any of the finally events occured, we abort.
        
        // Test if all the tasks are completed.
        // If not all of the tasks are completed, abort
        
        for ( var i = 0; i<length; i++ ) {
            if ( !events[i].complete )
                return;
        }
        
        complete = true;
        
        if ( callbackComplete ) {
            
            try {
            
                callbackComplete();
            
            } catch ( e ) {
                
                me.on( 'error', e + '' );
                
                return;
            }
        }
        
        if ( !me.error )
            me.on( 'success' );
        else
            me.on( 'error' );
        
    } );
    
    me.bind( 'error', function( reason ) {
        
        if ( error || success )
            return;
        
        error = true;
        
        if ( callbackError ) {
            callbackError.call( me, reason || 'unknown error' );
        }
        
    } );
    
    me.bind( 'success', function( data ) {
        
        if ( error || success )
            return;
        
        if ( callbackSuccess ) {
            try { 
                callbackSuccess.call( data || true );
            } catch ( e ) {
                me.on( 'error' );
                return;
            }
        }
        
        success = true;

    } );
    
    me.run = function( success, error, complete ) {

        callbackSuccess = success || false;
        callbackError   = error   || false;
        callbackComplete= complete|| false;
        
        success  = false;
        error    = false;
        complete = false;
        
        for ( var i=0; i<length; i++ ) {
            
            events[i].on( 'reset' );
            
        }
        
        for ( var i=1; i<length; i++ ) {
            
            events[i - 1].next = events[i];
            events[i].prev = events[i - 1];
            
        }
        
        if ( length ) {
            
            events[0].on('start');
            
        } else {
            
            me.on( 'success' );
            
        }
        
        return me;
    }
    
    return me;
}