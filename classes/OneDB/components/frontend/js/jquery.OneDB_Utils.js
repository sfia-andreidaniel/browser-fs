jQuery.fn.scaleMax = function ( container, preserve ) {

    preserve = preserve || {};
    
    preserve.width  = preserve.width || 0;
    preserve.height = preserve.height || 0;

    container = container || $(this).parent();

    var oH = $(container).innerHeight() - preserve.height;
    var oW = $(container).innerWidth() - preserve.width;
    
    var iH = this.outerHeight();
    var iW = this.outerWidth();
    
    if(oH/iH > oW/iW){
        this.css("width", oW);
        this.css("height", iH*(oW/iW))
    } else {
        this.css("height", oH);
        this.css("width", iW*(oH/iH));
    }
    return this;
};

jQuery.fn.verticalCenter = function( ) {
    
    var parentHeight = $(this).parent().innerHeight();
    var elHeight     = $(this).outerHeight();
    
    $(this).css({
        "margin-top": Math.floor( ( parentHeight - elHeight ) / 2 ) + 'px'
    });
    return this;
}