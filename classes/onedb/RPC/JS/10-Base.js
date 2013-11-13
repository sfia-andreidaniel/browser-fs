function OneDB_Base() {
    
    this.__class = "OneDB_Base";
    
    this.bind = function( eventName, callback ) {
        this.__events = this.__events || {};
        if ( callback ) {
            this.__events[ eventName ] = this.__events[eventName] || [];
            this.__events[ eventName ].push( callback );
        }
    }
    
    this.on = function( eventName, eventData ) {
        this.__events = this.__events || {};

        if ( this.__events[ eventName ] )
            for ( var i=0, len=this.__events[eventName].length; i<len; i++ )
                if ( this.__events[eventName][i].call( this, eventData ) === false )
                    return false;

        return true;
    }

    this.__mux = function() {
        return {
            "type": this.__class
        };
    };
    
    this.__create = function() {
        if ( typeof this.init == 'function' ) {
            
            this.init.call( this );
            
        }
    }
    
    return this;
}
