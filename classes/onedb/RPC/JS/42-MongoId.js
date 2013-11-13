/* Mongo php-type MongoID implementation */

function MongoId( id ) {
    
    this.__class = 'MongoId';
    
    this.init = function() {
        
        if ( !/^[a-f\d]{24}$/.test( id ) )
            throw "Invalid mongo id: " + id;
        
        this.$id = id;
    };
    
    this.__create();
    
    return this;
    
}

MongoId.prototype = new OneDB_Class();

MongoId.prototype.toString = function() {
    return this.$id;
};

MongoId.prototype.__mux = function() {
    return this.$id || null;
}

MongoId.prototype.__demux = function( data ) {

    return new MongoId( data );

}