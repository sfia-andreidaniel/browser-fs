function OneDB_Type_Category( ) {
    
    console.log( "new category: ", arguments );
    
    this.__class = 'OneDB_Type_Category';
    
    this.init.apply( this, Array.prototype.slice.call( arguments, 0 ) );
    
    return this;
};

OneDB_Type_Category.prototype = new OneDB_Type();
