/* OneDB Poll JQuery plugin
 *
 * USAGE  : $("div.article .poll").OneDB_Poll(settings);
 * AUTHOR : sfia.andreidaniel@gmail.com
 * LICENCE: Do whatever yo want with this file. Also, you can take credits if you don't
 *          respect other's work. Leaving this description would be appreciated in
 *          non-minificated versions of this file.
 *
 * This work is part of OneDB - One Database to Rule them All.
 * For more info, visit: http://www.sourceforge.net/projects/jsplatform/
 *
 * @param settings, optional: Object: {
 *    "intl": {             // Optional object, providing internalization strings for:
 *       "error":    <str>  // Optional default: "Error voting. Please try again later"
 *    },
 *    "pollURL":     <str>  // Optional default "onedb:polls/{article_id}";
 * }
 *
 *
 * After voting, the div.poll will have an additional class: "readOnly"
 *
 **/

 /* <div class="poll mceNonEditable" data-poll-id="50f52e468882181b28000000">
        <h3>Care este echipa de fotbal preferata?</h3>
        <ul class="poll-type-single">
            <li data-option-id="1358245406908-5181-1647-1" data-votes="0" data-percent="0">Steaua</li>
            <li data-option-id="1358245407051-3447-9718-2" data-votes="0" data-percent="0">Dinamo</li>
        </ul>
    </div>
  */


(function( $ ) {
    
    $.fn.OneDB_Poll = function( settings ) {

        settings = settings || {};
        
        settings.getPollURL = settings.getPollURL || "/onedb/polls/get/{poll_id}";
        settings.votePollURL = settings.votePollURL || "/onedb/polls/vote/{poll_id}";
        
        settings.intl = settings.intl || {};
        settings.intl.errorEmbed = settings.intl.errorEmbed || 'Error obtaining poll embed code from server!';
        settings.intl.errorBadPollType = settings.intl.errorBadPollType || 'Unknown poll type';
        settings.intl.submit = settings.intl.submit || 'Submit';
        settings.intl.errorSubmit = settings.intl.errorSubmit || 'Error submitting your votes to server';
        
        /* Triggers when the poll has an ul element inside */
    
        var poll_id = this.attr('data-poll-id');
        
        if (!poll_id) {
            this.html('Missing attribute data-poll-id!');
            return;
        }
        
        settings.getPollURL = settings.getPollURL.replace(/\{poll_id\}/g, poll_id );
        settings.votePollURL= settings.votePollURL.replace(/\{poll_id\}/g, poll_id );
        
        // search for the poll URL
        
        var embedded = this.find('ul');
        
        var root = this;
        
        
        var onReadyState = function() {
        
            var ul = root.find( 'ul' );
            
            var pollType = null;
            
            switch (true) {
                case ul.hasClass( 'poll-type-single'):
                    pollType = 'single';
                    break;
                case ul.hasClass( 'poll-type-multiple'):
                    pollType = 'multiple';
                    break;
            }
            
            if ( pollType === null ) {
                root.html( settings.intl.errorBadPollType );
                return;
            }
            
            var pollUID = 'grp_' + ( new Date() ).getTime() + '_' + poll_id + Math.floor( Math.random() * 100000 );
            
            // setup options
            
            ul.find( 'li' ).each( function( index ) {
                
                $(this).addClass( 'option-' + index );
                
                var optionID = $(this).attr('data-option-id');
                var votes    = $(this).attr('data-votes');
                var percent  = $(this).attr('data-percent');
                
                if ( !optionID )
                    return;
                
                var input = '';
                
                switch (pollType) {
                    case 'single':
                        input = '<input type="radio" value="' + optionID + '" name="' + pollUID + '" />';
                        break;
                    
                    case 'multiple':
                        input = '<input type="checkbox" name="' + optionID + '" />';
                        break;
                }
                
                $(this).html(
                    '<label>' + input + '&nbsp;' + $(this).html() + '</label>' +
                    '<div class="holder"><div class="percent" style="width: ' + percent + '%"><span>' + parseFloat( percent ).toFixed(2) + '%, ' + votes + ' votes</span></div></div>'
                );
                
            } );
            
            root.append( '<button class="submit">' + settings.intl.submit + '</button>' );
            
            root.find( 'button' ).click( function() {
                var checkedItems = [];

                $(root).find('input').each( function() {
                    if ( this.checked ) {
                        checkedItems.push( pollType == 'single' ? this.value : this.name );
                    }
                } );
                
                if (!checkedItems.length)
                    return;
                
                $.ajax( settings.votePollURL, {
                    "type": "POST",
                    "data": {
                        "options": checkedItems.join(",")
                    },
                    "dataType": "json",
                    "success": function( rsp ) {
                        
                        switch ( true ) {
                            
                            case rsp === true:
                                // allready voted on this poll
                                $(root).addClass('readOnly');
                                break;
                            
                            case rsp === false:
                                alert( settings.intl.errorSubmit );
                                break;
                            
                            case !!rsp:
                            
                                $(root).addClass('readOnly');
                            
                                if ( !rsp.length )
                                    return;
                                
                                for ( var i=0, len=rsp.length; i<len; i++ ) {
                                
                                    (function( id, percent, votes ) {
                                
                                    $(root).find('li[data-option-id=' + id + '] div.percent').each( function() {
                                        $(this).find('span').html(
                                            parseFloat( percent ).toFixed( 2 ) + '%, ' + votes + " votes"
                                        );
                                    } ).animate({
                                        "width": Math.floor( percent ) + "%"
                                    });
                                    
                                    })( rsp[i]._id, rsp[i].percent, rsp[i].votes );
                                }
                            
                                break;
                            
                        }
                        
                    },
                    "error": function( ) {
                        alert( settings.intl.errorSubmit );
                    }
                } )
            } );
            
            if ( root.find( 'p.voted' ).length ) {
                root.addClass('readOnly');
            }

        };
        
        var submitResults = function() {
            alert('submit results!');
        }
        
        if (!embedded.length) {
            $.ajax( settings.getPollURL, {
                'type': 'POST',
                'data': {  // force request to be post, in order to skip caching mechanisms
                    "_dummy": 1
                },
                "success": function( html ) {
                    root.html( html );
                    if ( root.find( 'ul' ).length ) {
                        onReadyState();
                    } else
                        root.html( settings.intl.errorEmbed );
                },
                "error": function( ) {
                    root.html( settings.intl.errorEmbed );
                }
            })
        } else
            onReadyState();
    
    }

})( jQuery );