jQuery.fn.OneDB_Gallery = function( settings ) {

    settings = settings || {};

    this.each( function() {

        if ( this.patched )
            return;
        
        this.patched = true;

        var gallery = {
            "title"     : $(this).attr('data-gallery-title') || settings.title,
            "type"      : settings.type || $(this).attr('data-gallery-type') || 'OneDB',
            "theme"     : settings.theme || $(this).attr('data-gallery-theme') || '',
            "autoScroll": typeof settings.autoScroll == 'undefined' ? $(this).attr('data-gallery-autoscroll') : !!settings.autoScroll,
            "items"     : [],
            "node"      : this
        };
        
        $(this).find('li').each( function() {
            try {
                var item = JSON.parse( $(this).attr('data-item-data') );
                gallery.items.push( item );
            } catch (e) {}
        } );
        
        $(this).html('');
        
        var init = function( cfg, Node ) {
            // console.log( cfg );
            
            $(Node).addClass( cfg.type );
            
            if ( cfg.theme ) {
                $(Node).addClass( cfg.theme );
            }
            
            if (cfg.theme)
                $(Node).addClass( cfg.theme );
                
            $(Node).append( '<div class="overlay"><div class="captions"></div><div class="prev-item"></div><div class="next-item"></div><div class="content"></div></div><div class="track"><div><div class="left"></div><div class="right"></div><div class="mobile"></div></div></div>' );
                
            var trackWidth = 0;
                
            for (var i=0; i<cfg.items.length; i++) {
        
                (function( item ) {
                
                    var el = document.createElement('div');
                    $(el).addClass( item.type );
                    
                    $(el).append( '<div class="img"></div><div class="cap"><span></span></div><div class="sticker"></div>' );
                    
                    $(el).find('div.cap > span').get(0).appendChild( document.createTextNode(
                        item.title
                    ) );
                
                    var img = document.createElement( 'img' );
                    img.src = item.srcSmall;
            
                    $(el).find('div.img').append( img );
                
                    // el.tabIndex = 0;
            
                    $(Node).find('div.mobile').append( el );
                
                    trackWidth += $(el).width();
                    $(el).find('img').each(
                        function() {
                            $(this).scaleMax( null );
                        }
                    );
                        
                    el.data = item;
                    
                    $(el).find('div.cap > span').verticalCenter();
                    
                })( cfg.items[i] );
                
            }
            
            Node.maintainVisible = function( slideNode ) {
                var xTrack = $(Node).find('div.mobile').get(0).offsetLeft;
                var xNode  = slideNode.offsetLeft;
                var wTrack = $(Node).find('div.mobile').parent().width();
            
                if (xNode < -xTrack) {
                    $(Node).find('div.mobile').animate({
                        "left": -xNode + "px"
                    }, 'fast');
                }
                
                var tmp;
                
                if ( xTrack + wTrack < xNode + slideNode.offsetWidth ) {
                    $(Node).find('div.mobile').animate({
                        "left": - (xNode + slideNode.offsetWidth - wTrack ) + 'px'
                    }, 'fast');
                }
            }
            
            Node.setSlide = function( slideNode ) {
                $(slideNode).parent().find("div").removeClass("current");
                $(slideNode).addClass("current");
                
                Node.maintainVisible( slideNode );
                
                var theContent = $(Node).find('div.content');
                var theCaption = $(Node).find('div.captions');
                $(theCaption).toggle();
                    
                $(theContent).fadeOut( 'slow', function() {
                    $(theContent).addClass('loading');
                    /* Load next content */
                    
                    switch ( slideNode.data.type ) {
                        case 'picture':
                            var img = new Image();
                            
                            $(img).addClass('vertical-center scale-max');
                            
                            img.onload = function() {
                                $(theContent).html('');
                                $(theContent).append( img );
                                $(theContent).removeClass('loading');
                                $(img).scaleMax( theContent, { "height": 20, "width": 20 } );
                                $(img).verticalCenter();
                                
                                $(theCaption).html('');
                                if ( slideNode.data.title ) {
                                    $(theCaption).append('<h3></h3>').find('h3').append( document.createTextNode( slideNode.data.title ));
                                }
                                
                                if ( slideNode.data.description ) {
                                    $(theCaption).append('<p></p>').find('p').append( document.createTextNode( slideNode.data.description ));
                                }
                                
                                if (slideNode.data.title || slideNode.data.description)
                                    $(theCaption).fadeIn('slow');
                            };
                            
                            img.src = slideNode.data.srcLarge;
                        
                            if (slideNode.data.url) {
                                $(img).css({"cursor": "pointer"});
                                $(img).attr("title", slideNode.data.url);
                                $(img).click(function(){
                                    window.location = slideNode.data.url;
                                });
                            }
                            
                            break;
                                
                        case 'video':
                        
                            var video = document.createElement('video');
                            
                            video.src = slideNode.data.srcLarge;
                            video.preload = 'none';
                            video.controls = true;
                            
                            $(video).css({
                                "width": '100%',
                                "height": $(theContent).height()
                            });
                            
                            $(theCaption).html('');
                                
                            if ( slideNode.data.title ) {
                                $(theCaption).append('<h3></h3>').find('h3').append( document.createTextNode( slideNode.data.title ));
                            }
                                    
                            if ( slideNode.data.description ) {
                                $(theCaption).append('<p></p>').find('p').append( document.createTextNode( slideNode.data.description ));
                            }
                                
                            if (slideNode.data.title || slideNode.data.description)
                                $(theCaption).fadeIn('slow');                           

                            $(theContent).html('<div class="video-holder"><div></div></div>');
                            $(theContent).find('.video-holder > div').append( video );
                            
                            $(theContent).removeClass('loading');
                            
                            break;
            
                        case 'article':
                            break;
                    }
                        
                    $(theContent).fadeIn('fast');
                    
                } );
            };
            
            $(Node).on("click", "div.mobile > div", function( e ) {
                Node.setSlide( this );
            } );
            
            $(Node).find( 'div.mobile' ).css({
                "width": trackWidth + 'px'
            });
            
            Node.slideLeft = function() {
                var itemWidth     = 0;
                var itemsPerSlide = Math.floor( $(Node).find('div.track').width() /
                                        ( itemWidth = $(Node).find('div.mobile > div').width() )
                                    );
                
                var slideLeft = itemsPerSlide * itemWidth;
                
                var mobile = $(Node).find( 'div.mobile').get(0);
                
                var diff = mobile.offsetLeft - ( itemWidth * itemsPerSlide );
                
                if (diff + ( mobile.offsetWidth - mobile.parentNode.offsetWidth ) < 0 ) {
                    diff = mobile.parentNode.offsetWidth - mobile.offsetWidth;
                }
                
                $(mobile).animate(
                    {
                        "left": diff
                    },
                    "slow"
                );
                
            };          

            Node.slideRight = function() {
                var itemWidth     = 0;
                var itemsPerSlide = Math.floor( $(Node).find('div.track').width() /
                                        ( itemWidth = $(Node).find('div.mobile > div').width() )
                                    );
                $(Node).find('div.track').width( itemWidth * itemsPerSlide );
                
                
                var slideLeft = itemsPerSlide * itemWidth;
                
                var mobile = $(Node).find( 'div.mobile').get(0);
                
                var diff = ( mobile.offsetLeft + ( itemWidth * itemsPerSlide ) );
                if (diff > 0) diff = 0;
                
                $(mobile).animate(
                    {
                        "left": diff
                    },
                    "slow"
                );
                
            };
            
            Node.resizeGallery = function() {
                $(Node).find('div.track').css({
                    "left": '5px',
                    "right": '5px'
                }).each( function() {
                    this.style.width = '';
                    this.style.height= '';
                } );
                
                var itemWidth     = 0;
                var itemsPerSlide = Math.floor( 
                    $(Node).find('div.track').width() /
                    ( itemWidth = $(Node).find('div.mobile > div').width() )
                );
                
                var w = Math.floor( ( Node.offsetWidth - ( itemWidth * itemsPerSlide ) ) / 2 ) + 'px';
                
                try {
                    $(Node).find('div.track').css({
                        "left": w,
                        "right": w
                    });
                
                } catch (e) { }
                
                $(Node).find('.scale-max').scaleMax( null, { "height": 20, "width": 20 } );
            
                $(Node).find('.vertical-center').verticalCenter();
                
                $(Node).find('.next-item, .prev-item').each(function() {
                    $(this).css({
                        'top': Math.floor( ( $(this).parent().height() / 2 )-($(this).height()/2) ) + 'px'
                    })
                });
                    
                var tmp;
                
                /* AutoHide the left and right page controls if not needed */
                $(Node).find('div.left, div.right').css({
                      "display": tmp = ( $(Node).find('div.mobile > div').length <= itemsPerSlide ) ? 'none' : ''
                });
                    
                if (tmp)
                  $(Node).find('.mobile').css({
                    "left": "0px"
                  });
            };
            
            $(window).resize( Node.resizeGallery );
            
            Node.resizeGallery();
            
            $(Node).find('div.left').click(
                function() {
                    Node.slideRight();
                }
            );          

            $(Node).find('div.right').click(
                function() {
                    Node.slideLeft();
                }
            );
            
            Node.previousItem = function() {
                var current = $(Node).find('div.mobile').find('div.current');
                if (current.length) {
                    current = current.get(0);
                    if (current.previousSibling) {
                        Node.setSlide( current.previousSibling );
                    } else {
                        Node.setSlide( $(Node).find('div.mobile > div').last().get(0) );
                    }
                }
            }           

            Node.nextItem = function() {
                var current = $(Node).find('div.mobile').find('div.current');
                if (current.length) {
                    current = current.get(0);
                    if (current.nextSibling) {
                        Node.setSlide( current.nextSibling );
                    } else {
                        Node.setSlide( $(Node).find('div.mobile > div').first().get(0) );
                    }
                }
            }
            
            $(Node).find('div.prev-item').click(function() {
                Node.previousItem();
            });         
    
            $(Node).find('div.next-item').click(function() {
                Node.nextItem();
            });
            
            try {
                Node.setSlide( $(Node).find('div.mobile > div').get(0) );
            } catch (e) {}
            
            /* console.log( trackWidth , 'pixels for track' );
               console.log( cfg ); 
             */
        };
    
        init( gallery, this );
        
    });
    
    return this;
};
