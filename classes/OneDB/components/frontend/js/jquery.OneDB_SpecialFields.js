jQuery.fn.OneDB_SpecialFields = function() {

    var self = this;

    this.find('.special-field').removeClass('special-field').removeClass('mceNonEditable').css({
        "background-color": ""
    }).each(function() {
        $(this).text( $(self).attr('data-' + ( $(this).attr('data-special-field') ) ) );
    });
    
    console.log( this );
    
    return this;
};
