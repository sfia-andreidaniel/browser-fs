/* OneDB Article Rating JQuery plugin
 *
 * USAGE  : $("div.article .rating>").OneDB_Rating(settings);
 * AUTHOR : sfia.andreidaniel@gmail.com
 * LICENCE: Do whatever yo want with this file. Also, you can take credits if you don't
 *          respect other's work. Leaving this description would be appreciated in
 *          non-minificated versions of this file.
 *
 * This work is part of OneDB - One Database to Rule them All.
 * For more info, visit: http://www.sourceforge.net/projects/jsplatform/
 *
 * @param settings, optional: Object: {
 *    "defaultValue": <int> // Optional, default 2.5, min. 1, max 5
 *    "intl": {             // Optional object, providing internalization strings for:
 *       "votes":    <str>  // Optional default:  "votes"
 *       "noVotes":  <str>  // Optional default": "Not voted yed"
 *       "error":    <str>  // Optional default: "Error voting. Please try again later"
 *    },
 *    "voteURL":     <str>  // Optional default "onedb:ratings/{article_id}";
 * }
 *
 * Minimal Markup:
 *
 * <div class="article" data-article-id="{article-id-value}" data-rating-score="{3.2;100}">
 *    This is the article content 
 *    <div class="rating"></div>
 * </div>
 *
 * $('div.article div.rating').OneDB_Rating(); // will transform the "rating" divs into rating widgets
 *                                  // which will submit their marks to the settings.voteURL
 *                                  // with POST { "articleID": <article_id>, "mark": 1 <= <float> <= 5  }
 *
 * The server side component should return a: 
 * {
 *    "value":  <int> //Actual value of the article rating
 *    "voters": <int> //Total votes on current article
 * }
 *
 * After voting, the div.rating will have an additional class: "readOnly"
 *
 **/


(function( $ ) {
    
    $.fn.OneDB_Rating = function( settings ) {

        settings = settings || {};
        settings.defaultValue = typeof settings.defaultValue ? parseFloat( settings.defaultValue ) : 2.5;
        settings.defaultValue = isNaN( settings.defaultValue ) ? 2.5 : (
            settings.defaultValue < 0 ? 0 : (
                settings.defaultValue > 5 ? 5 : settings.defaultValue
            )
        );
    
        settings.intl = settings.intl || {};
    
        settings.intl.votes = settings.intl.votes || "votes";
        settings.intl.noVotes = settings.intl.noVotes || "Not voted yet";
        settings.intl.error = settings.intl.error = "Error voting. Please try again later";
    
        settings.voteURL = settings.intl.voteURL || '/onedb:ratings/{article_id}';

        this.each( function() {
    
            $(this).html('');
    
            var article = null, cursor = this;
    
            while ( cursor.nodeName != 'BODY' ) {
                if ( $(cursor).hasClass( 'article' ) && $(cursor).attr('data-article-id') ) {
                    article = cursor;
                    break;
                }
                if (!cursor.parentNode)
                    break;
                else
                    cursor = cursor.parentNode;
            }   
    
            if ( null === article )
                return;

            /* We've got a handle to the owner article, we can now proceed further */
    
            /* Decorate the rating with the overlay div */
    
            $(this).append( '<div class="overlay"><div class="score"></div><div class="voters"></div><div class="r1 rank"><div class="r2 rank"><div class="r3 rank"><div class="r4 rank"><div class="r5 rank"></div></div></div></div></div></div>' );
    
            this.setValue = function( much ) {
    
                much = parseFloat( much );
                much = isNaN( much ) ? settings.defaultValue : (
                    much < 0 ? 0 : (
                        much > 5 ? 5 : much
                    )
                )

                $(this).css({
                    "background-position": Math.floor( much * 34.8 ) - 162  + "px 10px"
                });

                $(this).find('.score').html( much.toFixed(1) );
            }
    
            this.setNumberOfVoters = function( str ) {
                $(this).find('.voters').html( 
                    str 
                        ? ( str + ' ' + settings.intl.votes || 'votes' ) 
                        : ( settings.intl.noVotes || 'Not voted yet' ) 
                );
                if ( !str )
                    $(this).addClass('no-votes');
                else
                    $(this).removeClass('no-votes');
            }
    
            var matches;
    
            if (matches = /^([\d]+(\.[\d]+)?)\;([\d]+)$/.exec( $(article).attr( 'data-rating-score' ).toString() ) ) {
                this.setValue( matches[1] );
                this.setNumberOfVoters( matches[3] );
            } else {
                this.setValue( settings.defaultValue );
                this.setNumberOfVoters( 0 );
            }   

            $(this).find('.rank').click(function( e ) {
        
                e.preventDefault();
                e.stopPropagation();
                
                var classes = this.className.split(' '), mark = 0, matches;
                
                for ( var i=0, len=classes.length; i<len; i++ ) {
                    if ( matches = /^r([\d]{1})$/.exec( classes[i] )) {
                        mark = parseInt( matches[1] );
                    }
                }       
    
                if ( mark < 1 || mark > 5 )
                    return;
    
                var id;
    
                /* Do the voting */
                $.ajax( (settings.voteURL || '/onedb:ratings/{article_id}').replace(
                    /\{article_id\}/g,
                    id = $(article).attr('data-article-id')
                ), {
                    "type": "POST",
                    "data": {
                        "id": id,
                        "rating": mark
                    },
                    "success": function( data ) {
                        try {
    
                            if (data && data.value && data.voters && typeof data.value == 'number' && typeof data.voters == 'number') { 

                                $(article).find('div.rating').each(function() {
                                    /* Dissalow voting in all ratings from article */
                                    $(this).addClass( 'readOnly' );
                                    /* Update the ranking and the number of voters */
                                    try {
                                        this.setValue( data.value );
                                        this.setNumberOfVoters( data.voters );
                                    } catch (f){
                                        console.warn('jQuery.ratings: Failed to update rating on ', this, ', reason: ',  f );
                                    }
                                });     

                                if ( typeof settings.feedback == 'function' )
                                    settings.feedback( mark );

                            } else throw "Bad Data!";

                        } catch (e) {
                            alert( settings.intl.error || "Error voting. Please try again later\nStatus Code: " + e );
                        }
                    },
                    "error": function( data ) {
                        alert( settings.intl.error || "Error voting. Please try again later" );
                    }
                });
            });
        } );
    }

})( jQuery );