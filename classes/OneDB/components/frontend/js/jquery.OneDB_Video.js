/* OneDB video JQuery plugin
 *
 *
 **/

window.createVideoID = ( function() {
    
    var uid = 0;
    
    return ( function() {
        uid++;
        return 'video_' + uid;
    } );
    
} )();

window.ensureVideoURL = function( str ) {
    var ret = str.replace(new RegExp('[\/]+', 'g'), '/').replace( new RegExp('^([a-z\d]+)\:\/'), '$1://').replace(/(\/)?onedb\/([a-f\d]{24})(\.|$)/, '$1onedb/transcode/$2$3');
    // console.log('ensureVideoURL: ', str, '=>', ret );
    return ret;
}

Array.prototype.intersect = function( otherArray ) {
    otherArray = otherArray || [ ];
    var out = [];
    for ( var i=0, n=otherArray.length; i<n; i++ ) {
        for ( var j=0, m = this.length; j<m; j++ ) {
            if ( this[j] == otherArray[i] &&
                 out.indexOf( this[j] ) == -1
               ) {
                out.push( this[j] );
                break;
            }
        }
    }
    return out;
};

if ( typeof Array.prototype.indexOf == 'undefined' ) {
    Array.prototype.indexOf = Array.prototype.indexOf || function(value) {
        for ( var i=0, len = this.length; i<len; i++ ) {
            if ( this[i] == value )
                return i;
        }
        return -1;
    }
}

(function( $ ) {
    
    $.fn.OneDB_Video = function( settings ) {

        var timeToSec = function( floatVal ) {

            var secs = floatVal % 60;
            var mins = Math.floor( ( floatVal - secs ) / 60 );
            
            secs = secs >> 0;
            
            return ( mins < 10 ? '0' + mins : mins ) + ':' + ( secs < 10 ? '0' + secs : secs );
        };

        settings = settings || {};
        
        var browser = BrowserDetect.browser;
        var version = BrowserDetect.version;
        var os      = BrowserDetect.OS;
        
        var platform = (function() {
            
            var pc = ['unknown', 'linux', 'windows', 'mac'];
            
            return pc.indexOf( os ) >= 0 ? 'pc' : 'mobile';
            
        })();
        
        var logDebug = function( arg ) {
            if ( settings.debug ) {
                console.log( 'LogDebug: ', arg );
            }
            return arg;
        }
    
        var platformFormats = {
            "pc": [
                "mp4", "240p.mp4", "360p.mp4", "480p.mp4", "720p.mp4"
            ],
            "android": [
                "mp4", "android.mp4"
            ],
            "iphone": [
                "mp4", "iphone.mp4"
            ],
            "ipad": [
                "mp4", "iphone.mp4"
            ],
            "blackberry": [
                "mp4", "blackberry.mp4"
            ]
        };
        
        var strToTime = function( str ) {
            if (!str) return null;
            var matches;
            if ( matches = /^([\d]{4})\/([\d]{2})\/([\d]{2}) ([\d]{2})\:([\d]{2})\:([\d]{2})$/.exec( str ) ) {
                
                var d = new Date();
                
                d.setFullYear( parseInt( matches[1] ) );
                d.setMonth   ( parseInt( matches[2] ) - 1 );
                d.setDate    ( parseInt( matches[3] ) );
                
                d.setHours   ( parseInt( matches[4] ) );
                d.setMinutes ( parseInt( matches[5] ) );
                d.setSeconds ( parseInt( matches[6] ) );

                var ret = ( ( d.getTime() / 1000 ) >> 0 );
                
                return ret;
            } else return null;
        }
        
        var serverTime = function( ) {

            var time = null;

            var a = $.ajax( settings.serverTime || '/onedb/time', {
                "async": false,
                "type": 'POST',
                "success": function( unixTimestamp ) {
                    time = unixTimestamp;
                },
                "error": function() {
                    time = ( ( ( new Date() ).getTime() / 1000 ) >> 0 );
                }
            });
            
            return time;
        };
        
        this.each( function() {
        
            try {
        
            var stretching = $(this).attr('data-stretching') || settings.stretching || null;
        
            // assetType can be: static, live 
            var assetType = $(this).attr('data-asset-type') || settings.assetType || 'static';

            // a video that will be playing before the video
            var preroll   = $(this).attr('data-preroll') || settings.preroll || '';
            
            // a video that will be palying after the video
            var postroll  = $(this).attr('data-postroll') || settings.postroll || '';
        
            // a picture that will be displayed instead on the video when not playing
            var poster = $(this).attr('data-poster') || settings.poster || '';

            // video source
            var source = ensureVideoURL( $(this).attr('data-src') || $(this).attr('src') || settings.src || '' );
            
            // width and height of the video
            var width = $(this).attr('data-width') || settings.width || $(this).attr('width') || '';
            var height= $(this).attr('data-height') || settings.height || $(this).attr('height') || '';
            
            // an edge server is a server that is caching the static assets, and serves
            // them instead of onedb (simply change the domain of current site with the
            // domain of the edge server)
        
            var edgeServers = settings.edgeServers || $(this).attr('data-edge-servers') || '';
            
            var interfaceType = $(this).attr('data-interface-type') || settings.interfaceType || 'flash';
            
            var startTime = strToTime( $(this).attr('data-start-time') || settings.startTime || '' );
            var stopTime  = strToTime( $(this).attr('data-stop-time')  || settings.stopTime  || '' );
            
            //console.log( startTime, stopTime );
            
            
            // parse edge servers
            switch (true) {
                case ( edgeServers.length == 0 ):
                    edgeServers = [];
                    break;
                    
                case typeof edgeServers == 'string' && edgeServers.length > 0:
                    edgeServers = (function(str) {
                        var arr = str.split(/\,/g);
                        var out = [];
                        
                        for( var i=0, len = arr.length; i<len; i++ ) {
                            arr[i] = arr[i].replace(/(^[\s]+|[\s]+$)/g, '');
                            if ( arr[i] )
                                out.push( arr[i] );
                        }
                        return out;
                    })( edgeServers );
                    
                    break;
                    
                case edgeServers instanceof Array && edgeServers.length > 0:
                    break;
                default:
                    edgeServers = [];
                    break;
            }
            
            
            // parse available video versions for static assets
            var versions = $(this).attr('data-versions') || settings.versions || '';
            
            switch (true) {
                case versions.length == 0:
                    versions = ['mp4'];
                    break;
                case typeof versions == 'string' && versions.length > 0:
                    versions = (function(str) {
                        
                        var arr = versions.split(/\,/g);
                        var out = [];
                        
                        for ( var i=0, len = arr.length; i<len; i++ ) {
                            arr[i] = arr[i].replace(/^([\s]+|[\s]+$)/g, '');
                            if ( arr[i] )
                                out.push( arr[i] );
                        }
                        
                        // the versions from the data-versions must be intersected with
                        // the settings.versions
                        
                        if ( settings.versions )
                            out = out.intersect( settings.versions );
                        
                        
                        return out;
                        
                    })( versions );
                    break;
                
                case versions instanceof Array && versions.length > 0:
                    break;
                
                default:
                    versions = ['mp4'];
                    break;
            }

            
            //everything will be placed inside this holder
            
            var holder;
        
            // if the input element is a "video", enclose it between a div.video
            if (this.nodeName.toLowerCase() == 'video') {
                holder = document.createElement('div');
                $(holder).addClass('video');
                $(this).before( holder );
                holder.appendChild( this );
            } else {
            
                if ( this.nodeName.toLowerCase() != 'div')
                    return;
            
                var video = document.createElement( 'video' );
                
                if ( $(this).attr('data-preroll') )
                    $(video).attr( 'data-preroll', $(this).attr('data-preroll') );
                
                if ( $(this).attr('data-type') )
                    $(video).attr( 'data-type', $(this).attr('data-type') );
    
                if ( $(this).attr('data-edge-servers') )
                    $(video).attr( 'data-edge-servers', $(this).attr('data-edge-servers') );
                
                this.innerHTML = '';
                this.appendChild( video );
                
                holder = this;
            }
            
            if (holder.parentNode.tagName.toLowerCase() == 'p') {
                $(holder).parent().after( holder );
            }
            
            $(holder).addClass('interface-' + interfaceType);
            
            /* A little browser detection */
            $(holder).addClass('browser-' + browser.toLowerCase());
            $(holder).addClass('os-' + os.toLowerCase());
            
            // put some debug information if settings.debug is TRUE inside the video player
            if ( settings.debug ) {
                $(holder).append('<div class="debugger"></div>').find('div.debugger').text( 'User Agent: ' + navigator.userAgent );
                $(holder).find('div.debugger').append('<br /><br />');
                $(holder).find('div.debugger').append("<span class='detect'></span>").find('span.detect').text(
                    "OS: " + os + ', Browser: ' + browser + ', Version: ' + version
                );
                $(holder).find('div.debugger').append('<br /><br />');
                $(holder).find('div.debugger').append("<span class='formats'></span>").find('span.formats').text(
                    "Available media formats: " + versions.join(', ')
                );
            }
            
            // the holder has 3 classes: is-stopped, is-playing, is-paused
            $(holder).addClass('is-stopped');
            
            // dump some more debug information in console
            if (!!settings.debug){
                console.log( holder );
                console.log( "Edge Servers: ", edgeServers );
                console.log( "AssetType: ", assetType );
                console.log( "Preroll: ", preroll );
                console.log( "Postroll: ", postroll );
                console.log( "Poster: ", poster );
                console.log( "---\n" );
            }
            
            // a custom event listener is setup to the holder.
            holder.events = {};

            holder.onCustomEvent = function( eventName, params ) {
                if (!!settings.debug )
                    console.log( "player.onCustomEvent( ", eventName, ")( ", arguments, ")" );
                var queue = holder.events[ eventName ] || [];
                for ( var i=0, len = queue.length; i<len; i++ ) {
                    if ( queue[i]( params ) == false )
                        return false;
                }
                return true;
            }
            
            holder.addCustomEventListener = function( eventName, callback ) {
                holder.events[ eventName ] = holder.events[ eventName ] || [];
                holder.events[ eventName ].push( callback || ( function() { return true; } ) );
            }
            
            holder.removeCustomEventListener = function( eventName, callback ) {
                holder.events[ eventName ] = holder.events[ eventName ] || [];

                for ( var i=0, len = holder.events[ eventName ].length; i<len; i++ ) {
                    if ( holder.events[ eventName ][i] == callback ) {
                        holder.events[ eventName ].splice( i, 1 );
                        if ( !!settings.debug ) {
                            console.log("removed custom event: ", eventName );
                        }
                        break;
                    }
                }
            }
            
            
            $(holder).addClass( assetType );
            $(holder).append("<div class='badge'></div>");
            $(holder).append("<div class='status'></div>");
            
            /* get the settings for the platform */
            
            var getAvailableVersionsForCurrentPlatform = function() {
                if ( platform == 'pc' ) {
                    return platformFormats.pc.intersect( versions );
                } else {
                    switch ( os ) {
                        case 'symbian':
                            return platformFormats.blackberry.intersect( versions );
                            break;
                        case 'android':
                            return platformFormats.android.intersect( versions );
                            break;
                        case 'iphone':
                        case 'ipad':
                            return platformFormats[ os ].intersect( versions );
                            break;
                        default:
                            return ['mp4'];
                    }
                }
            }
            
            var platformVersions = getAvailableVersionsForCurrentPlatform() ;
            
            /* Add controll bar */
            
            $(holder).append("<div class='controllbar'><div class='button play'></div><div class='button pause'></div><div class='button stop'></div><div class='button settings'><ul></ul></div><div class=\"time\">--:--</div></div>");
            $(holder).append("<div class='seek'></div><div class='buffer'></div><div class='play-overlay'></div>");
            
            $(holder).find('.button.settings').click( function() {
                if ( $(this).hasClass( 'focused' ) ) {
                    $(this).removeClass( 'focused' );
                } else
                    $(this).addClass('focused');
            } );
            
            $(holder).find('.button.settings').on('click', 'li', function() {
                setTimeout( function() {
                    $(holder).find('.button.settings').removeClass('focused');
                    $(holder).find('.button.settings').toggle();
                    $(holder).find('.button.settings').toggle();
                }, 40);
            });
            
            ( function( formats ) {
                if (settings.debug)
                    console.log( 'Formats: ', formats );
                for ( var i=0, len=formats.length; i<len; i++ ) {
                    (function( format ) {
                        var li = document.createElement('li');
                        li.appendChild( document.createTextNode(/^([a-z\d]+)/.exec(format)[1]) );
                        li.formatName = formats[i];
                        $(li).addClass( 'v' + li.formatName.replace(/\.[a-z\d]+$/, '') );
                        $(holder).find('div.controllbar > .button.settings > ul').append( li );
                        $(li).click( function() {
                            holder.onCustomEvent('change-format', li.formatName );
                        });
                    })( formats[i] );
                }
            } )( platformVersions );
            
            // setup the seeker
            
            (function() {
                
                holder.seeker = $(holder).find('div.seek').get(0);
                
                holder.seeker.lastCurrent = 0;
                holder.seeker.lastMax = 0;
                
                holder.seeker.setValue = function (current, max ) {
                    var bgPos = 0;
                    
                    holder.seeker.lastCurrent = current;
                    holder.seeker.lastMax = max;
                    
                    if ( !!max ) {
                        
                        var percent = holder.seeker.offsetWidth * ( current / max );
                        holder.seeker.style.backgroundPosition = ( percent >> 0 ) + 'px' + ' 0px';
                        
                        holder.seeker.max = max;
                    
                    } else {
                        holder.seeker.style.backgroundPosition = '0px 0px';
                        holder.seeker.max = 0;
                    }
                };
                
                holder.addCustomEventListener('resize', function() {

                    holder.seeker.setValue( holder.seeker.lastCurrent, holder.seeker.lastMax );
                    
                    try {
                        jwplayer( holder.videoID ).resize(
                            $(holder).width(),
                            $(holder).height()
                        );
                    } catch (e) {}
                    
                    return true;
                });
                
                $(holder.seeker).mouseup( function( evt ) {
                
                    if ( !holder.seeker.max )
                        return;
                
                    var percent = (100 * evt.offsetX / holder.seeker.offsetWidth);
                    var pos = ( ( holder.seeker.max / 100 ) * percent ) >> 0;
                    
                    holder.seekTo({
                        "percent": percent,
                        "time"   : pos
                    });

                } );
                
            })();
            
            // depending on edgeServers we've setup, this function will return
            // a random edge server for prepending on static assets
            var getEdgePrefix = function() {
                if ( edgeServers.length ) {
                    return 'http://' + edgeServers[ Math.floor( Math.random() * edgeServers.length ) ] + '/';
                } else
                    return '';
            }
            
            // setup the player poster
            holder.setPoster = function( href ) {
            
                if ( href && /^http(s)?\:/.test( href ) ) {
                    holder.style.backgroundImage = 'url(' + href + ')';
                } else {
            
                    if ( href ) {
                        holder.style.backgroundImage = 'url(' + getEdgePrefix() + href + ')';
                    } else
                        holder.style.backgroundImage = 'none';
                
                }
            }
            
            // setup the source of the asset
            holder.setSource = function( href ) {
                holder.onCustomEvent('source', href );
            };
            
            // setup the width of the player.
            holder.setWidth = function( cssDimension ) {
                var str = cssDimension.toString(), matches;
                if ( matches = /^([\d]+)(px|\%)?/.exec( str )) {
                    matches[2] = matches[2] || 'px';
                    holder.style.width = matches[1] + matches[2];
                    
                    holder.onCustomEvent('resize', {
                        "width": holder.offsetWidth,
                        "height": holder.offsetHeight
                    });
                    
                }
            }
            
            // setup the height of the player.
            holder.setHeight = function( cssDimension ) {
                var str = cssDimension.toString(), matches;
                if ( matches = /^([\d]+)(px|\%)?/.exec( str )) {
                    matches[2] = matches[2] || 'px';
                    holder.style.height = matches[1] + matches[2];
                    
                    holder.onCustomEvent('resize', {
                        "width": holder.offsetWidth,
                        "height": holder.offsetHeight
                    });
                }
            }
            
            // setup the status text of the player
            holder.setStatus = function( str ) {
                $(holder).find('.status').html(str);
            }
            
            // do some basic initializing
            holder.setPoster( poster );
            
            if ( width )
                holder.setWidth( width );
            
            if ( height )
                holder.setHeight( height );
            
            // remove eventually video, embed or objects tag inside the holder
            $(holder).find('video, embed, object').each( function() {
                this.parentNode.removeChild( this );
            } );
            
            // if width or height are present, we apply them to player
            if ( width || height ) {
                $(window).resize( function() {
                
                    if ( width ) 
                        holder.setWidth( width );
                        
                    if ( height ) 
                        holder.setHeight( height );
                        
                } );
            }
            
            // do some patching to the player container ...
            $(holder).append( '<div class="player" id="' + ( holder.videoID = createVideoID() ) + '" data-is-video-id="yes"></div>' );
            $(holder).append( '<div class="position"></div>' );
            
            // reset status text
            holder.setStatus('');
            
            holder.addCustomEventListener('error', function( reason ) {
                holder.setStatus( reason || 'error' );
                $('#' + holder.videoID ).addClass('error');
                $(holder).removeClass('is-playing').removeClass('is-paused').addClass('is-stopped');
                $(holder).removeClass('live').removeClass('phase-preroll').removeClass('phase-main').removeClass('phase-postroll');
                
                return true;
            } );
            
            // check additionally init-vars for the asset.
            if ( assetType == 'live') {
                
                var balancerURL = $(this).attr('data-balancer-url') || settings.balancerURL || '';
                
                if ( !!!balancerURL ) {
                    holder.onError( 'Missing config: settings.balancerURL || attr[data-balancer-url]!');
                    return;
                }
                
                var balancerScopeName = $(this).attr('data-balancer-scope-name') || settings.balancerScopeName || '';

                if ( !!!balancerScopeName ) {
                    holder.onError( 'Missing config: settings.balancerScopeName || attr[data-balancer-scope-name]!');
                    return;
                }
                
                var balancerKey = $(this).attr('data-balancer-key') || settings.balancerKey || '';
                if ( !!!balancerKey ) {
                    holder.onError( 'Missing config: settings.balacerKey || attr[data-balancer-key]!' );
                    return;
                }
                
                if ( settings.debug ) {
                    console.log("Balancer URL: ", balancerURL, "\nBalancer Scope Name: ", balancerScopeName, "\nBalancer Key: ", balancerKey );
                }
                
                var $_JSONP = function( url, args, callback, errorCallback, errorTimeout ) {
                
                    var script = document.createElement('script');
                    var data   = [];
                    for ( var key in args ) {
                        if ( args.propertyIsEnumerable( key ) ) {
                            data.push( key + '=' + encodeURIComponent( args[key] ) );
                        }
                    }
                    
                    // add a random query string, in order to avoid caching
                    data.push('_random=' + ( new Date() ).getTime() + '-' + ( ( Math.random() * 1000000 ) >> 0 ) );
                    
                    var windowCallbackName = 'callback_' + window.createVideoID();
                    
                    window[ windowCallbackName ] = function() {
                        
                        var returnValue = callback.apply( holder, Array.prototype.slice.call( arguments, 0 ) );
                        // cleanup ...
                        setTimeout( function() {
                            if ( typeof window[ windowCallbackName ] != 'undefined' ) {
                                try {
                                    delete window[windowCallbackName];
                                } catch (e) {}
                            }
                        }, 5 );
                        // even more cleanup
                        script.parentNode.removeChild( script );
                        
                        setTimeout( function() {
                            try { script.parentNode.removeChild( script ); delete script; } catch (e) {}
                        }, 10000 );
                        
                        return returnValue;
                    };
                    
                    if ( typeof errorCallback != 'undefined' ) {
                        setTimeout( function() {
                            if ( script.parentNode ) {
                                errorCallback();
                                script.parentNode.removeChild( script );
                                try { delete script; } catch (e) {}
                                delete window[ windowCallbackName ]
                            }
                        }, errorTimeout || 5000 );
                    }
                    
                    data.push('callback=' + windowCallbackName );
                    
                    script.src = url + '?' + data.join('&');
                    
                    document.getElementsByTagName('head')[0].appendChild( script );
                }
            }
            
            // setup the time holder, the place where we update the time progress of the media (PC mode)
            (function() {
                var timeHolder = $(holder).find('div.time');
                
                holder.addCustomEventListener('time', function( timeObject ) {
                    holder.lastTimeObject = timeObject;
                    timeHolder.html( 
                        timeObject.phase == 'main' 
                            ? timeToSec( timeObject.position || timeObject.offset ) + ' / ' + timeToSec( timeObject.duration )
                            : timeObject.phase + ' (' + timeToSec( timeObject.duration - ( timeObject.position || timeObject.offset ) ) + ')'
                    );
                    holder.seeker.setValue( ( timeObject.position || timeObject.offset ), timeObject.duration );
                    return true;
                } );
                
                holder.addCustomEventListener( 'reset-time', function() {
                    delete holder.lastTimeObject;
                    timeHolder.html('');
                    holder.seeker.setValue( 0, 0 );
                } );
                
            })();

            // event triggered when media is starting to play
            holder.addCustomEventListener('playing', function() {
                $(holder).removeClass('is-stopped').removeClass('is-paused').addClass('is-playing');
                return true;
            });
            
            // event triggered when media is stopping
            holder.addCustomEventListener('stopping', function() {
                $(holder).removeClass('is-paused').removeClass('is-playing').addClass('is-stopped');
                $(holder).find('.time').html('');
                return true;
            });
            
            // event triggered when media is pausing
            holder.addCustomEventListener('pausing', function() {
                $(holder).removeClass('is-playing').removeClass('is-stopped').addClass('is-paused');
                return true;
            });

            // sets the percent of the loaded media in internal player buffer.
            // @param percent should be an <int> between 0..100
            holder.setBufferPercent = function( percent ) {
                $(holder).find('.buffer').css({
                    "width": percent + "%"
                });
            };
            
            // set the play phase of the media.
            // @param str should be in: ["main", "preroll", "postroll"]
            holder.setPlayPhase = function( str ) {
                $(holder).removeClass('phase-preroll').removeClass('phase-main').removeClass('phase-postroll').
                    addClass('phase-' + str );
            };
            
            // returns the index of the version of currently static asset
            // e.g. will return 0 for "mp4", 1 for "240p.mp4", etc.
            // the index is took from the settings gear dropdown
            holder.getActiveFormatIndex = function() {
            
                var versionName;
                
                if (!(versionName = $(holder).attr('data-version')))
                    return 0;
                else {
                    var returnIndex = 0;
                
                    $(holder).find('div.controllbar > div.button.settings > ul > li').each(function( index ) {
                        if ( $(this).hasClass( versionName ) ) {
                            returnIndex = index;
                        }
                    });
                    
                    return returnIndex;
                }
            };
            
            ( function() {
    
                var embedded = false;
                var liveEmbedSettings = null;
                
                holder.isReady = function() {
                    return embedded;
                }
                
                $(holder).find('#' + holder.videoID ).click( function() {
                    if ( !holder.isReady() )
                        holder.play();
                } );
                
                holder.getPlayer = function() {
                    return null;
                }
            
                var staticEmbedPC = function() {
                
                    /* the queue of files to be played */
                    
                    var queue = [];
                    
                    if ( preroll )
                        queue.push({
                            "type": "preroll",
                            "file": preroll
                        });
                    
                    queue.push({
                        "type": "main",
                        "file": source
                    });
                    
                    if ( postroll )
                        queue.push({
                            "type": "postroll",
                            "file": postroll
                        });
                    
                    var changeFormatFunc = function( format ) {
                        if ( settings.debug )
                            console.log( 'change format: ', format );
                        holder.getPlayer().changeFormat( format );
                        return true;
                    };
                    
                    var nextFunc = function() {
                        queueIndex++;
                        if ( queueIndex >= queue.length )
                            return false;
                        else {
                            holder.setPlayPhase( queue[ queueIndex ].type );
                            holder.getPlayer().player().load({
                                "file": getEdgePrefix() + queue[ queueIndex ].file.replace(/^onedb(\:|\/)/, '/onedb$1') + "." + platformVersions[ holder.getActiveFormatIndex() ]
                            });
                            holder.getPlayer().player().play( true );
                            return true;
                        }
                    }
                    
                    holder.addCustomEventListener('next', nextFunc );
                    
                    var unloadFunc = function() {
                        try { 
                            try {
                                jwplayer( holder.videoID ).stop();
                            } catch( e ) {
                                console.trace( e );
                            }
                            jwplayer.prototype.constructor.api.destroyPlayer( holder.videoID );
                        } catch (e) {}
                        
                        holder.getPlayer = function() {
                            return null;
                        };
                        
                        embedded = false;
        
                        $(holder).find('#' + holder.videoID ).addClass('player').click(function(){
                            if ( !holder.isReady())
                                holder.play();
                        });
                        
                        holder.seeker.setValue( 0, 0 );
                        holder.setBufferPercent( 0 );
                        
                        holder.removeCustomEventListener('change-format', changeFormatFunc );
                        holder.removeCustomEventListener('unload', unloadFunc );
                        holder.removeCustomEventListener('next', nextFunc );
                        
                        $(holder).removeClass('phase-preroll').removeClass('phase-postroll').removeClass('phase-main');
                        
                        $(holder).append( '<div class="player" id="' + holder.videoID + '"></div>' );
                        
                        queueIndex = 0;
                        
                        return true;
                    };
                    
                    holder.addCustomEventListener('change-format', changeFormatFunc );
                    holder.addCustomEventListener('unload', unloadFunc);

                    
                    holder.setPlayPhase( queue[0].type );
                    
                    var queueIndex = 0;

                    try {
                    
                        $(holder).attr('data-version', 'v' + /^([a-z\d]+)/.exec(platformVersions[ holder.getActiveFormatIndex() ])[1]);
                    
                        if ( settings.debug )
                        console.warn("Setup: " + holder.videoID );
                    
                        jwplayer( holder.videoID ).setup({
                            "flashplayer": "/classes/OneDB/components/jwplayer/jwplayer.flash.swf",
                            "width"      : ( width || '100%' ),
                            "height"     : ( height || '100%' ),
                            "stretching" : stretching,
                            "file"       : ( function() {
                                                if ( !/^http\:\/\/www\.youtube\.com/.test( queue[ queueIndex ].file ) )
                                                    return getEdgePrefix() + queue[ queueIndex ].file.replace(/^onedb(\/|\:)/g, '/onedb$1') + "." + platformVersions[ holder.getActiveFormatIndex() ]
                                                else
                                                    return queue[ queueIndex ].file.replace(/^onedb(\/|\:)/g, '/onedb$1');
                                            } )(),
                            "autostart"  : true,
                            "primary"    : interfaceType,
                            "startparam" : "start",
                            "provider"   : "http",
                            "events"     : {
                                "onError": function( message ) {
                                    holder.onCustomEvent( "error", message );
                                },
                                "onReady": function( ) {
                                    holder.onCustomEvent( "ready" );
                                },
                                "onTime" : function (timeObj ) {
                                    
                                    timeObj = timeObj || {};
                                    
                                    timeObj.duration = timeObj.duration || 0;
                                    timeObj.position = timeObj.position || 0;
                                    timeObj.offset   = timeObj.offset   || 0;
                                    
                                    timeObj.phase = queue[ queueIndex ].type;
                                    
                                    holder.onCustomEvent( "time", timeObj );
                                },
                                "onBufferChange": function( bufferStatus ) {
                                    holder.setBufferPercent( bufferStatus.bufferPercent || 30 );
                                    // console.log( "buffering:", bufferStatus );
                                    // holder.setState("Buffering: " + percent );
                                },
                                "onBeforePlay": function() {
                                    holder.onCustomEvent( 'playing' );
                                },
                                "onComplete": function() {
                                    if ( !holder.onCustomEvent('next') ) {
                                        holder.getPlayer().stop();
                                    }
                                },
                                "onPause": function() {
                                    holder.onCustomEvent( 'pausing' );
                                }
                            }
                        });
                        
                    } catch (exception) {
                        alert(exception);
                    }
                    
                    setTimeout( function() {
                        if ( /\<p /i.test( $('#' + holder.videoID ).html() ) ) {
                            holder.onCustomEvent( "error", $('#' + holder.videoID ).text() );
                        }
                    }, 500 );
                    
                    holder.getPlayer = function() {
                        var o = {};
                        
                        o.player = function() {
                            return jwplayer( holder.videoID );
                        };
                        
                        o.play = function() {
                            return jwplayer(holder.videoID).play( true );
                        };
                        
                        o.pause= function() {
                            return jwplayer(holder.videoID).play( false );
                        };
                        
                        o.stop = function() {
                            holder.onCustomEvent('stopping');
                            holder.onCustomEvent('unload');
                            return jwplayer(holder.videoID).stop();
                        };
                        
                        o.changeFormat = function( version ) {
                            $(holder).attr('data-version', 'v' + /^([a-z\d]+)/.exec( version )[1] );
                            jwplayer(holder.videoID).load({
                                "file": getEdgePrefix() + queue[queueIndex].file + "." + version
                            });
                            
                            if ( holder.lastTimeObject ) {
                                jwplayer( holder.videoID ).seek( holder.lastTimeObject.position );
                            }
                            
                            if ( $(holder).hasClass('is-playing') ) {
                                jwplayer( holder.videoID ).play( true );
                            }
                        }
                        
                        o.seekTo = function( objTimePercents ) {
                            jwplayer(holder.videoID).seek( objTimePercents.time );
                        }
                        
                        o.fullscreen = function() {
                            jwplayer(holder.videoID).setFullscreen();
                        }
                        
                        return o;
                    };
                    
                    return true;
                
                };
            
                var staticEmbedMobile = function() {
                    
                    var player = document.createElement('video');
                    
                    $(player).attr( 'autobuffer', 'autobuffer' );
                    $(player).attr('autostart', 'autostart');
                    
                    if ( os == 'iphone' ) {
                        $(holder).find('#' + holder.videoID ).append( player );
                    }
                    
                    holder.addCustomEventListener('resize', function() {
                        if ( player.parentNode ) {
                            player.width = $(holder).width();
                            player.height= $(holder).height();
                        }
                        return true;
                    });
                    
                    holder.onCustomEvent('resize');
                    
                    holder.getPlayer = function() {
                        return {
                            "player": function() {
                                return { "fake": true };
                            },
                            "play": function() {
                                if ( os != 'iphone' ) {
                                    // alert( "play: " + getEdgePrefix() + source + '.' + platformVersions[ platformVersions.length - 1 ] );
                                    window.open( 
                                        getEdgePrefix() + 
                                        source + "." + 
                                        platformVersions[ platformVersions.length - 1 ]
                                    );
                                } else {
                                    
                                    holder.onCustomEvent('resize');
                                    
                                    player.src = getEdgePrefix() + 
                                        source + "." + 
                                        platformVersions[ platformVersions.length - 1 ];
                                    
                                    
                                    holder.setStatus('Starting video...');
                                    
                                    if ( os == 'iphone' )
                                        player.play();
                                    
                                    setTimeout( function() {
                                        player.play();
                                    }, 1000 );
                                }
                            },
                            "pause": function(){
                                return true;
                            },
                            "stop": function() {
                                if ( player.parentNode )
                                    player.parentNode.removeChild(player);
                                return true;
                            },
                            "changeFormat": function() {
                                return true;
                            },
                            "seekTo": function( objTimePercents ) {
                                return true;
                            },
                            "fullscreen": function() {
                                
                            }
                        };
                    };
                    
                    holder.getPlayer().play();
                    
                    return true;
                };
                
                function startCounter( strPrefix, keyTime, callback, cancelOnClick ) {
                
                    $(holder).find( '.startCounter' ).remove();
                
                    var h = document.createElement('div');
                    h.className = 'startCounter';
                    
                    var t = serverTime();
                    var now = ( ( new Date() ).getTime() / 1000 ) >> 0;
                    var timeDiff = t - now;

                    cancelOnClick = typeof cancelOnClick == 'undefined' ? true : !!cancelOnClick;
                    
                    h.run = function() {
                        var now = ( ( ( new Date() ).getTime() / 1000 ) >> 0 ) + timeDiff;
                        
                        if ( now - keyTime == 0 ) {
                            h.dispose();
                            if ( callback )
                                callback();
                        } else {
                            
                            var diff = Math.abs( now - keyTime );
                            h.innerHTML = strPrefix + '<br />' + timeToSec( diff ) + ( cancelOnClick ? '<br /><span>Click to cancel</span>' : '');
                        }
                        
                        if (!h.parentNode) {
                            h.dispose();
                        }
                    };
                    
                    var thread = window.setInterval( function() {
                        h.run();
                    }, 500 );
                    
                    h.dispose = function() {
                        if ( thread ) {
                            window.clearInterval( thread );
                        }
                        if ( h.parentNode )
                            h.parentNode.removeChild( h );
                    }
                    
                    $(h).on('mousedown', function(e) {
                        
                        if (cancelOnClick) {
                        
                            h.parentNode.removeChild(h);
                        
                        }
                        
                        e.stopPropagation();
                        e.preventDefault();
                    } );
                    
                    return h;
                }
                
                var liveEmbed = function() {
                    
                    if ( startTime || stopTime ) {
                        
                        var now = serverTime();
                        
                        if ( startTime ) {
                            
                            if ( now < startTime ) {
                                holder.onCustomEvent('error', settings.ERR_LIVESTREAM_NOT_STARTED || 'The Live stream will start in ' + timeToSec( startTime - now ) );
                                
                                $(holder).append( new startCounter( 'Starting in: ', startTime, function() {
                                    holder.play();
                                }) );
                                
                                return;
                            }
                            
                        }
                        
                        if ( stopTime ) {
                            
                            if ( now > stopTime ) {
                                holder.onCustomEvent('error', settings.ERR_LIVESTREAM_STOPPED || 'Live Event ended ' + timeToSec( now - stopTime ) + ' ago' );
                                return;
                            }
                            
                            $(holder).append( new startCounter( 'Stopping in: ', stopTime, function() {
                                holder.stop();
                            } , false ) ).find('.startCounter').addClass('small');
                            
                        }

                    }
                    
                    if ( os == 'android' && parseInt(version) <= 2.3 ) {
                    
                        //alert(JSON.stringify( liveEmbedSettings ) );
                        
                        window.onorientationchange = function() {
                            window.location.reload();
                        }
                        
                        var iframe = document.createElement('iframe');
                        iframe.style.position = 'absolute';
                        iframe.style.display  = 'block';
                        iframe.style.width    = window.innerWidth - 32 + "px";
                        iframe.style.height   = window.innerHeight - ( window.orientation == 90 ? 50 : 0 ) + "px";
                        iframe.style.border   = 'none';
                        iframe.style.padding  = '0px';
                        iframe.style.margin   = '0px';
                        iframe.style.top = '0px';
                        iframe.style.left= '0px';
                        iframe.style.backgroundColor = 'black';
                        iframe.style.zIndex = 3000000;
                        document.body.scrollTop = 0;
                        
                        document.body.appendChild( iframe );
                        iframe.src = balancerURL + '/stream/' + balancerScopeName + '/?token=' + balancerKey + '&width=' + iframe.offsetWidth + '&height=' + iframe.offsetHeight;
                        
                        var scrollTopThread = setInterval( function() {
                            document.body.scrollTop = 0;
                        }, 50 );
                        
                        $('body').append("<img src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYwIDYxLjEzNDc3NywgMjAxMC8wMi8xMi0xNzozMjowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNSBNYWNpbnRvc2giIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6RkVGQzhCNDg1OTQ4MTFFMUI4MEVCREMyOTdDQkExRDMiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6RkVGQzhCNDk1OTQ4MTFFMUI4MEVCREMyOTdDQkExRDMiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpGRUZDOEI0NjU5NDgxMUUxQjgwRUJEQzI5N0NCQTFEMyIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpGRUZDOEI0NzU5NDgxMUUxQjgwRUJEQzI5N0NCQTFEMyIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pot8HlQAAAWtSURBVHjarFdrLORXFL8zxput2dZaorQVz0YbsaxXPRrRerQq1AdEPEpCJEIikZCwHxAiWCERH4RE4pM0FaVBU0GIkCAh6k1rvRbxHmZnTM+5uVf+pjNj2nWSk/ufe+/cc+45v/O4kvLycqKJYF4k+CkWsAFjvq4CVjK+FTA/R0V0kPgB4bhuBGwG/DQlJeXzkZGRvM3NzY7Dw8PBs7OzyfPz88mTk5Ohg4OD9o2NjZy2tjZH9h+xhov8i0RCC6gJlgCbAEtbW1u/jY6O/snOzs6b6EEXFxdjOzs7Va6urr/DTwW3iCZr3CmgJtwY2AqEetbX179ydnZ+iQunp6dkamqKgBXI1tYWgZvTP1hZWRF7e3sSEhJCfHx8iFQqpfOXl5cDw8PDOTExMVvalKAKCISjb02BbQoLC7+B+QpLS8sPjo+PSVdXF2lpaSFXV1c6b29iYkJyc3NJQkICsbGxIUql8u3Kykqiu7v7GMPJPSXEanhA4XbZ2dnfVVZW1qLwoaEhEhUVRerq6h4UjnR9fU33gvXIwMAAMTAwsHZxcfl1cXExSBPmRIy52e38/PyC+vr66sCM0p6eHlJQUEDeh2pra0lcXBxa4nh0dDQwLCxsBd3BrcAVQNR+BPzlwsJCnYeHhyv6OT09nTwGNTU1kcjISLTguLm5+dcwJeeuEAtM/zH4PQqFA4JJUVEReSwqLi4m6+vrxMzMLGB1dfUHhrU7vxtijAN/lp+fH4+T3d3dBOL80RSAsKRnIkG0vGIyMQBEYhbrtklJSS8dHByez8/Pk4aGBo0HIbjW1tbo+F/WkDCCZmZmiLGxsevs7OxXPJOiAubAzwEoL3Bienpa601iY2PvRqGgkpKSe2vaiJ9ta2v7PXcDZjtLBCDEqQNOYNhpIxQKOLkTdHR0RL8zMjLu7dFGeHZWVhYBIAYyBRSogAUqAWmWpi8EizZqbm6mI1dCKJgL53s0EdQKOoIbHNnl5TwCTCwsLDAPECgwOgGFArRhQJdwJG4xiUQiZQrcRYFEJKKAxITxIKox9+szp4n4+U5OTobCVCySyWQ0OUD61XkAAk7d9NwduKaLwPeYmlEJGUSMkiuAH4rd3V1qewhFvYVD7aesrxKOjo68Sr7h1REVuEYwQMWimScgIEDrAerCKyoqKKsroY18fX3pCGV8SajAJSar3t7eVZwIDAzUegAXxIVzEiohVEadwsPD6bi0tPQHa1RUmI3QLgFGRkZe+/v7eQAm05ycHFpKH5OCgoJIR0cHubm5OfPy8noB5fkvHoYXwPtyufy0s7NzFjenpqaSx6bMzEw6zs3N/QzCj3lzggrIgHeB96BqTYIVzvz9/UlNTc2jCa+qqiLBwcGYYw6hv2hmuLvDwDvgt5ioAJ17aWlpv4E1lPHx8QS+31t4cnIySUxMxNBTYn85Pj6+yfoBFfYDvC7fMpOYQL02gJygiIiIcMImE5vNwcHB/33zvLw8+t3e3t4MKbwTEyK7NAkNDSVCBd4xZBpPTExAa3ctB7M5enp6ir29vWk/iOVWH0K0l5WV0S4Ibw7gawMMtMDSDjO/irdkEoECuPCGz1VXV98uLy+fwJsgEhBsjijGNm1sbIxWNWzL1ZMMCsZ96G8W76elpaWvoUb8Aj//Zni7VX8FCXtDzM9YKDyBf8RuCsLydWNj4wRY4EalRvAqoqxO4MJriKg+qPspcMYX7ExDLoc9BShrepjwF9Ez4E+BP8Eewtra+kN4mrlAH+Ds5ub2DHr+J8KLwNPsFPCz09/fPwv+Ht/e3v4Tgc0ALmPu1fww0fEmtGCK2DJ+yjooLN0GAFAclSDsjGVUjO895usDlmPkej3NNCghYl2LEesZsEw+YUqZCvCjYPg5x3aCjTImWMlez1pfyRINL2OVABsKdsgNO/iA/UciKOW3bJ9CsP/2IcGc/hFgACnOxnT9nvmvAAAAAElFTkSuQmCC' class='live-close' />")
                            .find("img.live-close")
                            .css({
                                "position": "absolute",
                                "right": "0px",
                                "top": "0px",
                                "display": "block",
                                "border": "none",
                                "zindex": "3000001"
                            }).click(function() {
                                $(iframe).remove();
                                window.onorientationchange = function() {};
                                clearInterval( scrollTopThread );
                                $('body').find('img.live-close').remove();
                            });
                        
                        return;
                    }
                    
                    
                    
                    if (!liveEmbedSettings) {
                    
                        holder.onCustomEvent('error', 'Error detecting live embedder settings!');
                        return false;
                    }
                    
                    if ( settings.debug ) {
                        var embedSettings = document.createElement('span');
                        
                        $(embedSettings).text( 'Embed settings: ' + JSON.stringify( liveEmbedSettings ).replace(/\,/g, ',\n') );
                        embedSettings.style.whiteSpace = 'pre';
                        $(holder).find('.debugger').append("<br /><br />");
                        $(holder).find('.debugger').append( embedSettings );
                    }
                    
                    switch ( liveEmbedSettings.embedMethod ) {
                        
                        case 'url':
                            ( function() {
                                versions = liveEmbedSettings.availableQualities;
                                $(holder).attr('data-version', 'v' + liveEmbedSettings.defaultQuality);
                                
                                $(holder).find('.controllbar > .button.settings > ul').html('');
                                
                                for ( var i=0, len = liveEmbedSettings.availableQualities.length; i<len; i++ ) {
                                    ( function( quality ) {
                                        
                                        var li = document.createElement('li');
                                        $(li).addClass( 'v' + quality );
                                        $(li).text( quality );
                                        
                                        $(holder).find('.controllbar > .button.settings > ul').append( li );
                                        
                                        $(li).click( function() {
                                            holder.onCustomEvent('change-stream-quality', quality );
                                        } );
                                        
                                    } )( liveEmbedSettings.availableQualities[i] );
                                }
                                
                                var changeQuality = function( quality ) {
                                
                            
                                    $_JSONP( balancerURL, {
                                        "scope": balancerScopeName,
                                        "type": ( os == 'android' && parseFloat( version ) < 3 ) ? 'rtsp' :  "hls",
                                        "quality": quality,
                                        "outputFormat": "jsonp",
                                        "token": balancerKey
                                    },
                                    function( statusCode, data ) {
                                    
                                        if (!statusCode) {
                                            holder.onCustomEvent('error', data || "Unknown error from balancer!");
                                            return false;
                                        }
                                
                                        holder.setPlayPhase('main');
                                
                                        var a = document.createElement('a');
                                        a.href = data.file;
                                        $(a).html('Play <b>' + ( function( q ) {
                                            switch (q) {
                                                case 'mq':
                                                    return 'Normal Quality';
                                                    break;
                                                case 'hq':
                                                    return 'High Definition';
                                                    break;
                                                case 'lq':
                                                    return 'Low Resolution';
                                                    break;
                                                default:
                                                    return q;
                                            }
                                        } )(quality) + '</b> stream');
                                        a.target = '_top';
                                        
                                        $(a).click = function( evt ) {
                                            $(holder).addClass('is-playing');
                                            $(holder).attr('data-version', 'v'+quality);
                                            window.open( this.href );
                                            evt.preventDefault();
                                            evt.stopPropagation();
                                        };
                                        
                                        $(a).addClass('v' + quality);
                                        
                                        $(holder).find( '#' + holder.videoID + 'a.v' + quality ).remove();
                                        $(holder).find( '#' + holder.videoID ).append(a);

                                        $(holder).find('.debugger').text( JSON.stringify( data ).replace(/\,/g, "\n") );
                                        
                                        return true;
                                        
                                    },
                                    function( ) {
                                        holder.onCustomEvent('error', 'Timeout while obtaining stream from balancer');
                                    }, 10000 );
                                
                                    return true;
                                }
                                
                                for ( var i=0, len = liveEmbedSettings.availableQualities.length; i<len; i++ ) {
                                    changeQuality( liveEmbedSettings.availableQualities[i] );
                                }
                                
                                holder.addCustomEventListener('change-quality', changeQuality );
                                
                                $(holder).addClass('is-playing');
                                
                                return true;
                                
                            })();
                        
                            break;
                        
                        case 'video':
                            ( function() {
                            
                                versions = liveEmbedSettings.availableQualities;
                                $(holder).attr('data-version', 'v' + liveEmbedSettings.defaultQuality);
                                
                                $(holder).find('.controllbar > .button.settings > ul').html('');
                                
                                for ( var i=0, len = liveEmbedSettings.availableQualities.length; i<len; i++ ) {
                                    ( function( quality ) {
                                        
                                        var li = document.createElement('li');
                                        $(li).addClass( 'v' + quality );
                                        $(li).text( quality );
                                        
                                        $(holder).find('.controllbar > .button.settings > ul').append( li );
                                        
                                        $(li).click( function() {
                                            holder.onCustomEvent('change-stream-quality', quality );
                                        } );
                                        
                                    } )( liveEmbedSettings.availableQualities[i] );
                                }
                                
                                var player = document.createElement('video');
                                
                                $(player).attr('autoplay', 'autoplay');
                                $(player).attr('autobuffer', 'autobuffer');
                                
                                $(holder).find('.player').find('video').remove();

                                $(holder).find('.player').append( player );
                                
                                var resizer = function() {
                                    player.width = $(holder).width() - 4;
                                    player.height= $(holder).height() - 70;
                                    return true;
                                };
                                
                                holder.addCustomEventListener('resize', resizer );
                                holder.onCustomEvent('resize');
                                
                                player.style.zIndex = 0;
                                
                                player.style.position = 'absolute';
                                player.style.top = '50px';
                                player.style.left = '1px';
                                
                                var changeQuality = function( quality ) {
                                    $_JSONP( balancerURL, {
                                        "scope": balancerScopeName,
                                        "type": "hls",
                                        "quality": quality,
                                        "outputFormat": "jsonp",
                                        "token": balancerKey
                                    },
                                    function( statusCode, data ) {
                                        if (!statusCode) {
                                            holder.onCustomEvent('error', data || "Unknown error from balancer!");
                                            return false;
                                        }

                                
                                        holder.onCustomEvent('playing');
                                        $(holder).attr('data-version', 'v'+quality);
                                        
                                        holder.setPlayPhase('main');
                                        
                                        if ( os == 'ipad' ) {
                                            // player.style.width = '100%';
                                            // player.style.height= '60px';
                                            // player.style.border= '1px solid white';
                                            
                                            player.controls = 'controls';
                                            
                                            var src = document.createElement('source');
                                            src.src = data.file;
                                            player.appendChild( src );
                                            
                                            $(player).on('click', function(e) {
                                                e.stopPropagation();
                                            } );
                                            
                                        } else {
                                            player.src = data.file;
                                            holder.setStatus('Stream initialized. Click on it to start playing!');
                                        }
                                        
                                        setTimeout( function() {
                                            player.play();
                                        }, 1000 );

                                        setTimeout( function() {
                                            player.play();
                                        }, 3000 );
                                        
                                        $(holder).find('.debugger').text( JSON.stringify( data ).replace(/\,/g, "\n") );
                                    },
                                    function( ) {
                                        holder.onCustomEvent('error', 'Timeout while obtaining stream from balancer');
                                    }, 10000 );
                                
                                    return true;
                                }
                                
                                holder.addCustomEventListener( 'change-stream-quality', changeQuality );
                                
                                holder.onCustomEvent('change-stream-quality', liveEmbedSettings.defaultQuality );
                                
                                holder.getPlayer = function() {
                                    var o = {};
                                    
                                    o.player = function() {
                                        try {
                                            return $(holder).find('.player > video').get(0);
                                        } catch (e) {
                                            return player;
                                        }
                                    };
                                    
                                    o.play = function() {
                                        return player.play();
                                    };
                        
                                    o.pause= function() {
                                        return player.pause();
                                    };
                            
                                    o.stop = function() {
                                        return player.stop();
                                    };
                        
                                    o.changeFormat = function( version ) {
                                    };
                            
                                    o.seekTo = function( objTimePercents ) {
                                    }
                        
                                    o.fullscreen = function() {
                                    }
                                    
                                    return o;
                                    
                                }
                                
                            })();
                            break;
                        
                        case 'flash':
                        
                            ( function() {
                                
                                holder.onCustomEvent('playing');
                                
                                /* Populate the settings dropdown */
                                versions = liveEmbedSettings.availableQualities;
                                $(holder).attr('data-version', 'v' + liveEmbedSettings.defaultQuality);
                                
                                $(holder).find('.controllbar > .button.settings > ul').html('');
                                
                                for ( var i=0, len = liveEmbedSettings.availableQualities.length; i<len; i++ ) {
                                    ( function( quality ) {
                                        
                                        var li = document.createElement('li');
                                        $(li).addClass( 'v' + quality );
                                        $(li).text( quality );
                                        
                                        $(holder).find('.controllbar > .button.settings > ul').append( li );
                                        
                                        $(li).click( function() {
                                            holder.onCustomEvent('change-stream-quality', quality );
                                        } );
                                        
                                    } )( liveEmbedSettings.availableQualities[i] );
                                }
                                
                                var queue = [], 
                                    queueIndex = -1;
                                
                                var nextEvent = function() {
                                    queueIndex++;

                                    if ( queueIndex >= queue.length )
                                        return false;
                                    
                                    holder.reinit( ( function( o ) { 
                                        var out = {};
                                        for ( var prop in o ) {
                                            if ( o.propertyIsEnumerable( prop ) && prop != 'type' )
                                                out[ prop ] = o[ prop ];
                                        }
                                        
                                        if ( typeof out.streamer != 'undefined' && typeof out.file != 'undefined' ) {
                                            switch ( true ) {
                                                case /^(rtmp(e)?)\:/i.test( out.streamer ):
                                                    
                                                    return out.streamer + '/' + out.file;
                                                    
                                                    break;
                                            }
                                        }
                                        
                                        if ( settings.debug ) {
                                            console.log("Loading file: ", out.file );
                                        }
                                        
                                        return out.file;
                                        
                                    } )( queue[ queueIndex ] ) );
                                    
                                    
                                    holder.setPlayPhase( queue[ queueIndex ].type );
                                    
                                    return true;
                                };
                                
                                holder.addCustomEventListener( 'next', nextEvent );
                                
                                var changeQuality = function( quality ) {
                                    
                                    holder.setStatus( '' );
                                    
                                    $(holder).attr('data-version', 'v'+quality);
                                    
                                    function ensureExtension( str ) {
                                        return str == '' 
                                            ? '' 
                                            : ( /(\.240p\.mp4|\.360p\.mp4|\.720p\.mp4|\.iphone\.mp4|\.android\.mp4|\.blackberry\.mp4|\.mp4|\.flv)$/
                                                .test( str )
                                                    ? str
                                                    : ( str + '.mp4' )
                                              )
                                    }
                                    
                                    $_JSONP( 
                                        balancerURL, 
                                        {
                                            "scope": balancerScopeName,
                                            "quality": quality,
                                            "type": liveEmbedSettings.streamType,
                                            "outputFormat": "jsonp"
                                        },
                                        function( responseCode, responseData ) {
                                            
                                            if ( !!!responseCode ) {
                                                holder.onCustomEvent('error', responseData || 'Unknown balancer response error');
                                                return;
                                            }
                                            
                                            queue = [];
                                            
                                            if ( platform == 'pc' ) {
                                                var prerollInject = ensureExtension( responseData.preroll  || preroll || '' );
                                                var postrollInject= ensureExtension( responseData.postroll || postroll|| '' );
                                            } else {
                                                var prerollInject = '';
                                                var postrollInject = '';
                                            }
                                            
                                            if ( prerollInject )
                                                prerollInject = getEdgePrefix() + prerollInject;
                                            
                                            if ( postrollInject )
                                                postrollInject = getEdgePrefix() + postrollInject;
                                            
                                            if ( settings.debug )
                                                console.log('Balancer response: ', responseData );
                                                
                                            var unsetKeys = [ 
                                                'preroll', 'postroll', 'usage', 'connections', 
                                                'maxConnections', 'mode', 'requested_quality', 
                                                'scope', 'serverID', 'stream_quality', 'type' 
                                            ];
                                            
                                            for ( var i=0, len=unsetKeys.length; i<len; i++ ) {
                                                if ( typeof responseData[ unsetKeys[i] ] !== 'undefined' ) {
                                                    if ( settings.debug )
                                                        console.log( "Ripping property: ", unsetKeys[i] );
                                                    delete responseData[ unsetKeys[i] ];
                                                }
                                            }
                                            
                                            if ( prerollInject )
                                                queue.push({
                                                    "type": "preroll",
                                                    "file": prerollInject
                                                });
                                            
                                            responseData.type = "main";
                                            queue.push( responseData );
                                            
                                            if ( postrollInject )
                                                queue.push({
                                                    "type": "postroll",
                                                    "file": postrollInject
                                                });
                                            
                                            if ( settings.debug ) {
                                                console.log('Playlist queue: ', queue );
                                            }
                                            
                                            queueIndex = -1;
                                            
                                            setTimeout( function() {
                                                holder.onCustomEvent('next');
                                            }, 100 );
                                            
                                            return true;
                                        },
                                        function( ) {
                                            
                                            holder.onCustomEvent( 'error', 'Balancer timeout error' );
                                            
                                        },
                                        10000
                                    );
                                    
                                    return true;
                                    
                                };
                                
                                holder.addCustomEventListener( 'change-stream-quality', changeQuality );
                                
                                var unloadFunc = function() {

                                    holder.removeCustomEventListener('next', nextEvent );
                                    holder.removeCustomEventListener('change-stream-quality', changeQuality);
                                    holder.removeCustomEventListener( 'unload', unloadFunc );
                                
                                    holder.parentNode.insertBefore( holder, holder );
                                
                                    try {
                                        try {
                                            jwplayer( holder.videoID ).stop();
                                        } catch( e) {
                                            console.trace( e );
                                        }
                                        jwplayer.prototype.constructor.api.destroyPlayer( holder.videoID );
                                    } catch (e) {}
                                
                                    
                                    $(holder).removeClass('phase-preroll').removeClass('phase-main').removeClass('phase-postroll');
                                    
                                    queueIndex = 0;
                                    queue = [];
                                    
                                    $('#' + holder.videoID ).remove();
                                    $(holder).append('<div class="player" id="' + holder.videoID + '"></div>');
                                    
                                    return true;
                                }
                                
                                holder.addCustomEventListener( 'unload', unloadFunc );
                                
                                holder.reinit = function( file ) {
                                
                                    try {
                                        try {
                                            jwplayer( holder.videoID ).stop();
                                        } catch (e) {
                                            console.trace( e );
                                        }
                                        jwplayer.prototype.constructor.api.destroyPlayer( holder.videoID );
                                    } catch( e ) {
                                        console.warn( "Could not destroy player: " + holder.videoID );
                                    }
                                    
                                    if (settings.debug )
                                    console.warn("Setup: " + holder.videoID );
                                    
                                    /* Embed the player */
                                    jwplayer( holder.videoID ).setup({
                                        "flashplayer": "/classes/OneDB/components/jwplayer/player.swf",
                                        "width"      : ( width || '100%' ),
                                        "height"     : ( height || '100%' ),
                                        "file"       : file || "/classes/OneDB/output-handlers/transcoding.mp4",
                                        "autostart"  : true,
                                        "primary"    : interfaceType,
                                        "startparam" : "start",
                                        "provider"   : "http",
                                        "stretching" : stretching,
                                        "events"     : {
                                            "onError": function( message ) {
                                                holder.onCustomEvent( "error", message.message || message );
                                            },
                                            "onReady": function( ) {
                                                holder.onCustomEvent( "ready" );
                                            },
                                            "onTime" : function (timeObj ) {
                                                if ( queueIndex >= 0 && [ 'preroll', 'postroll' ].indexOf( queue[ queueIndex ].type ) >= 0 ) {
                                                    /* we update the time information only on preroll and postroll items */
                                                    timeObj = timeObj || {};
                                        
                                                    timeObj.duration = timeObj.duration || 0;
                                                    timeObj.position = timeObj.position || 0;
                                                    timeObj.offset   = timeObj.offset   || 0;
                                        
                                                    timeObj.phase = queue[ queueIndex ].type;
                                        
                                                    holder.onCustomEvent( "time", timeObj );
                                                } else {
                                                    holder.onCustomEvent( "reset-time" );
                                                }
                                            },
                                            "onBufferChange": function( bufferStatus ) {
                                                holder.setBufferPercent( bufferStatus.bufferPercent || 30 );
                                                // console.log( "buffering:", bufferStatus );
                                                // holder.setState("Buffering: " + percent );
                                            },
                                            "onBeforePlay": function() {
                                                holder.onCustomEvent( 'playing' );
                                            },
                                            "onComplete": function() {
                                                if ( !holder.onCustomEvent('next') ) {
                                                    holder.getPlayer().stop();
                                                }
                                            },
                                            "onPause": function() {
                                                holder.onCustomEvent( 'pausing' );
                                            }
                                        }
                                    });
                                    
                                    holder.getPlayer = function() {
                                        
                                        var o = {};
                                        
                                        o.player = function() {
                                            try {
                                                return jwplayer( holder.videoID );
                                            } catch( e ) {
                                                holder.getPlayer = function() {
                                                    return null;
                                                }
                                            }
                                        };
                        
                                        o.play = function() {
                                            try {
                                                return jwplayer(holder.videoID).play( true );
                                            } catch( e ) {
                                                holder.getPlayer = function() {
                                                    return null;
                                                }
                                            }
                                        };
                        
                                        o.pause= function() {
                                            try {
                                                return jwplayer(holder.videoID).play( false );
                                            } catch( e ) {
                                                holder.getPlayer = function() {
                                                    return null;
                                                }
                                            }
                                        };
                        
                                        o.stop = function() {
                                            try {
                                                holder.onCustomEvent('stopping');
                                                holder.onCustomEvent('unload');
                                                return jwplayer(holder.videoID).stop();
                                            } catch (e) {
                                                holder.getPlayer = function() {
                                                    return null;
                                                }
                                            }
                                        };
                        
                                        o.changeFormat = function( version ) {
                                        }
                        
                                        o.seekTo = function( objTimePercents ) {
                                            try {
                                                jwplayer(holder.videoID).seek( objTimePercents.time );
                                            } catch (e) {
                                                holder.getPlayer = function() {
                                                    return null;
                                                }
                                            }
                                        }
                        
                                        o.fullscreen = function() {
                                            try {
                                                jwplayer(holder.videoID).setFullscreen();
                                            } catch( e ) {
                                                holder.getPlayer = function() {
                                                    return null;
                                                }
                                            }
                                        }
                                        
                                        return o;
                                    };
                                    
                                    setTimeout( function() {
                                        if ( /\<p /i.test( $('#' + holder.videoID ).html() ) ) {
                                            holder.onCustomEvent( "error", $('#' + holder.videoID ).text() );
                                        }
                                    }, 500 );
                                    
                                    setTimeout( function() {
                                        if ( /\<p /i.test( $('#' + holder.videoID ).html() ) ) {
                                            holder.onCustomEvent( "error", $('#' + holder.videoID ).text() );
                                        }
                                    }, 1000 );

                                    setTimeout( function() {
                                        if ( /\<p /i.test( $('#' + holder.videoID ).html() ) ) {
                                            holder.onCustomEvent( "error", $('#' + holder.videoID ).text() );
                                        }
                                    }, 2000 );

                                }
                                
                                
                                holder.onCustomEvent( 'change-stream-quality', liveEmbedSettings.defaultQuality );
                                
                            } )();
                            break;
                        default:
                            holder.onCustomEvent('error', 'Unimplemented embed method: ' + liveEmbedSettings.embedMethod );
                            break;
                    }
                
                    return true;
                };
            
                var isAuthenticating = false;
            
                holder.embed = function() {
                    
                    if ( embedded )
                        return true;
                    
                    switch ( assetType ) {
                        case 'static':
                            switch ( platform ) {
                                case 'pc':
                                    return embedded = staticEmbedPC();
                                    break;
                                case 'mobile':
                                    return embedded = staticEmbedMobile();
                                    break;
                            }
                            break;
                        case 'live':
                            
                            if ( isAuthenticating )
                                throw "ERR_AUTHENTICATION_IN_PROGRESS";
                            
                            holder.setStatus('Authenticating...');
                            
                            $_JSONP( balancerURL + '/jsonp-validate-key.php', 
                                    {
                                        "key": balancerKey
                                    },
                                    function( status, errorText ) {
                                    
                                        if ( settings.debug )
                                        console.log( arguments );
                                    
                                        if ( true === status ) {
                                            
                                            holder.setStatus('Autenticated...');
                                            
                                            isAuthenticating = false;
                                            
                                            holder.setStatus('Obtaining recommended settings in order to ensure the best performance...');
                                            
                                            // Authenticated, getting embed details
                                            
                                            $_JSONP( balancerURL + '/jsonp-get-embed-details.php', {
                                                    "scopeName": balancerScopeName
                                                },
                                                function( responseStatusBool, responseData ) {
                                                    if ( !responseStatusBool ) {
                                                        holder.onCustomEvent( 'error', responseData || 'Error obtaining embed settings from balancer!' );
                                                        return;
                                                    }
                                                    
                                                    if ( settings.debug )
                                                    console.log( responseData );
                                                    holder.setStatus('');
                                                    
                                                    /* Do the embed */
                                                    liveEmbedSettings = responseData;
                                                    
                                                    liveEmbed();
                                                    
                                                },
                                                function( ) {
                                                    holder.onCustomEvent( 'error', 'Timeout while obtaining embed settings from balancer!' );
                                                },
                                                5000
                                            );
                                            
                                            
                                            
                                        } else {
                                            holder.onCustomEvent( 'error', errorText || 'Unknown error while validating token' );
                                        }
                                    },
                                    function( ) {
                                        if ( os != 'android' )
                                            holder.onCustomEvent( 'error', 'Time-out error while validating token' );
                                        
                                        isAuthenticating = false;
                                    },
                                    5000
                            );
                            
                            return true;
                            
                    }
                };
            
            })();
            
            holder.play = function() {
            
                if ( holder.isReady() ) {
            
                    var player;
                    
                    if ( player = holder.getPlayer() )
                        player.play();
                
                    if (settings.debug)
                        console.log("Play!");
                } else {
                    if (!holder.embed()) {
                        holder.onCustomEvent( "error", "Error embedding player!" );
                    }
                }
            
            };
            
            holder.pause= function() {
            
                var player;
            
                if ( player = holder.getPlayer() )
                    player.pause();
                if ( settings.debug )
                    console.log("Pause!");
            };
            
            holder.stop = function() {
            
                if ( player = holder.getPlayer() )
                    player.stop();
            
                if ( settings.debug )
                    console.log("Stop!");
            };
            
            holder.seekTo = function( objectTimePercents ) {
                var player;
                
                if ( player = holder.getPlayer() )
                    player.seekTo( objectTimePercents );
            }
            
            holder.fullscreen = function() {
                var player;
                if ( player = holder.getPlayer() )
                    player.fullscreen();
            }
            
            $(holder).find("div.controllbar > div.button.play, div.play-overlay").click( function() {
                holder.play();
            } );
            
            $(holder).find("div.controllbar > div.button.pause").click( function() {
                holder.pause();
            } );
            
            $(holder).find("div.controllbar > div.button.stop").click( function() {
                holder.stop();
            } );
            
            $(holder).find("div.controllbar > div.button.fullscreen").click(function() {
                holder.fullscreen();
            });

            if ( !settings.debug )
            $(holder).contextmenu( function( evt ) {
                evt.preventDefault();
                evt.stopPropagation();
            });

            } catch (e){
                console.error(e);
                console.trace();
            }
            
        });


    };
})( jQuery );

var BrowserDetect = {
    init: function () {
        this.browser = this.searchString(this.dataBrowser) || "unknown";
        this.version = this.searchVersion(navigator.userAgent)
                        || this.searchVersion(navigator.appVersion)
                        || "unknown";
        this.OS = this.searchString(this.dataOS) || "unknown";
    },
    searchString: function (data) {
        for (var i=0;i<data.length;i++){
            var dataString = data[i].string;
            var dataProp = data[i].prop;
            this.versionSearchString = data[i].versionSearch || data[i].identity;
            if (dataString) {
                if (dataString.indexOf(data[i].subString) != -1)
                    return data[i].identity;
            }
            else if (dataProp)
                return data[i].identity;
        }
    },
    searchVersion: function (dataString) {
        var index = dataString.indexOf(this.versionSearchString);
        if (index == -1) return;
        return parseFloat(dataString.substring(index+this.versionSearchString.length+1));
    },
    dataBrowser: [
        {
            "string": navigator.userAgent,
            "subString": "Android",
            "identity": "Safari",
            "versionSearch": "Android"
        },
        {
            string: navigator.userAgent,
            subString: "Chrome",
            identity: "Chrome"
        },
        { 
            string: navigator.userAgent,
            subString: "OmniWeb",
            versionSearch: "OmniWeb/",
            identity: "OmniWeb"
        },
        {
            string: navigator.vendor,
            subString: "Apple",
            identity: "Safari",
            versionSearch: "Version"
        },
        {
            prop: window.opera,
            identity: "Opera",
            versionSearch: "Version"
        },
        {
            string: navigator.vendor,
            subString: "iCab",
            identity: "iCab"
        },
        {
            string: navigator.vendor,
            subString: "KDE",
            identity: "Konqueror"
        },
        {
            string: navigator.userAgent,
            subString: "Firefox",
            identity: "Firefox"
        },
        {
            string: navigator.vendor,
            subString: "Camino",
            identity: "Camino"
        },
        {// for newer Netscapes (6+)
            string: navigator.userAgent,
            subString: "Netscape",
            identity: "Netscape"
        },
        {
            string: navigator.userAgent,
            subString: "MSIE",
            identity: "Explorer",
            versionSearch: "MSIE"
        },
        {
            string: navigator.userAgent,
            subString: "Gecko",
            identity: "Mozilla",
            versionSearch: "rv"
        },
        { // for older Netscapes (4-)
            string: navigator.userAgent,
            subString: "Mozilla",
            identity: "Netscape",
            versionSearch: "Mozilla"
        }
    ],
    dataOS : [
        {
            string: navigator.platform,
            subString: "Win",
            identity: "windows"
        },
        {
            string: navigator.platform,
            subString: "Mac",
            identity: "mac"
        },
        {
            string: navigator.userAgent,
            subString: "iPhone",
            identity: "iphone"
        },
        {
            string: navigator.userAgent,
            subString: "iPad",
            identity: "ipad"
        },
        {
            string: navigator.userAgent,
            subString: "Android",
            identity: "android"
        },
        {
            string: navigator.platform,
            subString: "Linux",
            identity: "linux"
        }
    ]
};

BrowserDetect.init();
