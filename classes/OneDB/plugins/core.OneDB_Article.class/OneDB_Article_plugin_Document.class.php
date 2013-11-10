<?php

    class OneDB_Article_plugin_Document implements ArrayAccess {
    
        protected $_    = NULL;
        protected $_dom = NULL;
        
        public function __construct( &$that ) {
        
            global $__OneDB_Default_Document__;
        
            $this->_ = $that;
            
            $this->_->addTrigger('document', 'before', function( &$newValue, $oldValue, $selfObject ) {
                $newValue = OneDB_EnsureUTF8( $newValue );
            } );
            
            $this->_->addEventListener('save', function( &$thatInst ) {
            
                $dom = new DOMDocument();
                
                $docHTML = '';
                
                $thatInst->document = preg_replace('/\=\"(\/)?onedb\:/', '="/onedb/', $thatInst->document );
                
                @$dom->loadHTML( $docHTML = mb_convert_encoding("$thatInst->document", 'HTML-ENTITIES', 'UTF-8') );
                
                $titleFound = FALSE;
                $title = '';
                
                /* Determine the title */
                foreach (array('h1','h2','h3','h4','h5','h6') as $tagName) {
                    $tag = $dom->getElementsByTagName( $tagName );
                    if ($tag->length) {
                        $title = trim($tag->item(0)->textContent);
                        $title = preg_replace('/[\s]+/', ' ', $title);
                        if (strlen( $title )) {
                            $titleFound = TRUE;
                            $tag->item(0)->parentNode->removeChild( $tag->item(0) );
                            $docHTML = $dom->saveHTML( );
                            
                            $docHTML = trim( preg_replace('/([^*]+)?<body>([^*]+)<\/body>([^*]+)?/i', '$2', $docHTML ) );
                            break;
                        }
                    }
                }
                
                $thatInst->title = $titleFound ? $title : $thatInst->name;
                
                /* Determine the textContent. We load this 3rd party function with @ prefix, in order to avoid warnings. */
                require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "html2text" . DIRECTORY_SEPARATOR . "html2text.php";
                
                /* Remove from the DOM the div.rating, div.gallery, and div.poll */
                
                $pQuery = new pqLoader();
            
                $pq = phpQuery::newDocument( $docHTML );
                
                $pq->find('div.rating, div.poll, div.article-gallery')->remove();
                
                $docHTML = $pq->htmlOuter();
                
                @$dom->loadHTML( mb_convert_encoding($docHTML, 'HTML-ENTITIES', 'UTF-8') );
                
                $textContent = @html2text( $dom );
                
                $textContent = preg_replace('/(^[\s ]+|[\s ]+$)/', '', $textContent);
                $textContent = preg_replace('/[\s ]+/',' ', $textContent);
                
                $thatInst->textContent = $textContent;
                
                return TRUE;
            });
            
            $this->_->addEventListener('update', function( &$that ) {
                $that->_collection->db->onedb->siteCache()->deleteURLCache( $that->getPath() );
            });
        }
        
        public function relatedDocs( $maxDocs ) {
            $docs = $this->_->relatedDocs;
            
            $maxDocs = (int)$maxDocs;
            $maxDocs = $maxDocs < 0 ? 0 : $maxDocs;
            
            if (!$maxDocs || !is_array( $docs ) || !count( $docs ) ) {
                return new OneDB_ResultsNavigator(
                    array(),
                    $this->_->_collection->db->onedb,
                    'Generic'
                );
            }
            
            $docs = array_slice( $docs, 0, $maxDocs );
            
            $db = $this->_->_collection->db;
            $docsIDs = array();
            $orderIDs = array();
            
            foreach ($docs as $documentID) {
                $docsIDs[] = MongoIdentifier( $documentID );
                $orderIDs[] = $documentID;
            }
            
            $cursor = $db->articles->find(
                array(
                    '_id' => array(
                        '$in' => $docsIDs
                    )
                )
            );
            
            $out = array();
            
            while ($cursor->hasNext()) {
                $article = $cursor->getNext();
                $out[ array_search( "$article[_id]", $orderIDs ) ] = new OneDB_Article(
                    $db->articles,
                    $article['_id'],
                    $article
                );
            }
            
            $out = array_values( $out );
            
            return new OneDB_ResultsNavigator(
                $out,
                $db->onedb,
                'Article'
            );
        }
        
        public function ratings() {
            $_ratings = $this->_->ratings;
            
            $_ratings = is_array( $_ratings ) ? $_ratings : array();
            
            $out = array();
            
            $out['value'] = isset( $_ratings['value'] ) && is_numeric( $_ratings['voters'] ) ? $_ratings['value'] : 0;
            $out['voters']= isset( $_ratings['voters'] ) && is_numeric( $_ratings['voters'] ) ? $_ratings['voters'] : 0;
            
            return $out;
        }
        
        public function rate( $mark, $USERID_SESSION_KEY_IDENTIFIER = false ) {
            
            if ( !isset( $_SESSION ) )
                session_start();
            
            $mark = (int)$mark;
            
            if ($mark < 1 || $mark > 5)
                throw new Exception("Bad rating value ($mark). Should be in interval [1..5]");
            
            $myID = $this->_->_id . '';
            
            if ( ( 
                    
                    is_string( $USERID_SESSION_KEY_IDENTIFIER ) && (
                            !strlen( $_USERID_SESSION_KEY_IDENTIFIER ) ||
                            !isset( $_SESSION[ $USERID_SESSION_KEY_IDENTIFIER ] ) ||
                            !$_SESSION[$USERID_SESSION_KEY_IDENTIFIER]
                    )
                 )
                 || 
                 (
                    isset( $_SESSION['OneDB_Ratings'] ) &&
                    is_array( $_SESSION['OneDB_Ratings'] ) &&
                    isset( $_SESSION['OneDB_Ratings'][ $myID ] ) &&
                    $_SESSION['OneDB_Ratings'][ $myID ]
                 )
            ) {
            
                return $this->ratings();
            }
            
            $_SESSION['OneDB_Ratings'] = 
                isset( $_SESSION['OneDB_Ratings'] ) && 
                is_array( $_SESSION['OneDB_Ratings'] ) 
                    ? $_SESSION['OneDB_Ratings'] 
                    : array();
            
            $_SESSION['OneDB_Ratings'][ $myID ] = TRUE;
            
            $_ratings = $this->_->ratings;
            
            $_ratings = is_array( $_ratings ) ? $_ratings : array();
            
            if ( !isset( $_ratings['votes'] ) ) {
                
                $_ratings['value'] = $mark;
                $_ratings['voters'] = 1;
                $_ratings['votes'] = array(
                    'v' . $mark => 1
                );
                
                $this->_->ratings = $_ratings;
                
                return array(
                    'value' => $mark,
                    'voters' => 1
                );
                
            } else {
                
                $_ratings['votes'] = is_array( $_ratings['votes'] ) ? $_ratings['votes'] : array();
                
                $_ratings['votes']['v' . $mark ] = 
                    is_int( $_ratings['votes']['v' . $mark] )
                        ? $_ratings['votes']['v' . $mark] + 1
                        : 1;
                
                /* Calculate total number of votes */
                $votesByRating = array(
                    0, 0, 0, 0, 0
                );
                
                for( $i=0, $n = count( $votesByRating ); $i < $n; $i++ ) {
                    if ( isset( $_ratings['votes'][ $key = ( 'v' . ($i+1) ) ] ) &&
                         is_int( $_ratings['votes'][ $key ] ) &&
                         $_ratings['votes'][$key] > 0 
                    ) {
                        $votesByRating[ $i ] = $_ratings['votes'][ $key ];
                    }
                }
                
                $numVotes = 0;
                $sum = 0;
                
                for ($i=0, $n = count( $votesByRating ); $i<$n; $i++) {
                    $numVotes += $votesByRating[ $i ];
                    $sum += ( $votesByRating[ $i ] * ( $i + 1 ) );
                }
                
                $_ratings['voters'] = $numVotes;
                $_ratings['value'] = $sum / $numVotes;
                
                $this->_->ratings = $_ratings;
                
                return array(
                    'value' => $_ratings['value'],
                    'voters'=> $_ratings['voters']
                );
            }
        }
        
        public function html() {
            $date     = htmlentities( strftime( '%a, %e %b %Y, %H:%M', $this->_->date ), ENT_COMPAT, 'utf-8' );
            $modified = htmlentities( strftime( '%a, %e %b %Y, %H:%M', $this->_->modified ), ENT_COMPAT, 'utf-8' );
            
            $owner    = htmlentities( ucwords( preg_replace('/[^a-z]/i', ' ', end( explode('/', $this->_->owner ) ) ) ), ENT_COMPAT, 'utf-8' );
            $modifier = htmlentities( ucwords( preg_replace('/[^a-z]/i', ' ', end( explode('/', $this->_->modifier ) ) ) ), ENT_COMPAT, 'utf-8' );
            
            $ratings = $this->ratings();
            $ratings['value'] = number_format( $ratings['value'], 2 );
            
            $pQuery = new pqLoader();
            
            $html = "<div class=\"article\" data-article-id=\"$this->_id\" data-owner=\"$owner\" data-date=\"$date\" data-modifier=\"$modifier\" data-modified=\"$modified\" data-rating-score=\"$ratings[value];$ratings[voters]\" >\n" . $this->_->document . "\n</div>";
            
            $pq = phpQuery::newDocument( $html );
            
            $self = $this;
            
            $onedb = $this->_->_collection->db->onedb;
            
            $pq['.poll'] = "\n";

            $pq['.poll']->each( function( $o ) use (&$onedb, &$pq) {
                
                $pollID = $o->getAttribute('data-poll-id');
                
                if ($pollID) {
                    try {
                        
                        $embed = $onedb->articles(
                            array(
                                '_id' => MongoIdentifier( $pollID )
                            )
                        )->get(0)->html();
                        
                    } catch (Exception $e) {
                        $embed = '<!-- Error rendering poll -->';
                    }
                } else $embed = '<!-- Error rendering poll: Missing data-poll-id attribute -->';
                
                pq($o)->append( $embed );
            } );
            
            $balancerURL = NULL;
            
            $pq['div.video.live-stream']->each( function($live) use (&$balancerURL, &$onedb) {

                if ($balancerURL === NULL) {
                    $balancerURL = $onedb->registry()->{'OneDB.LiveVideoBalancerURL'};
                    if (!$balancerURL) {
                        $balancerURL = FALSE;
                    }
                }
                
                if ($balancerURL && is_string($balancerURL) && strlen($balancerURL)) {
                    $key = @file_get_contents( $balancerURL . '/streamer/make_key.php' );
                    
                    if ( strlen( $key ) ) {
                        $live->setAttribute('data-balancer-key', $key );
                    }
                }
            });
            
            return preg_replace('/\=\"(\/)?onedb\:/', '="/onedb/', $pq->htmlOuter() );
        }
        
        public function galleries() {
            $out = array();
            $pQuery = new pqLoader();
            $pq = phpQuery::newDocument( $this->_->document );
            $pq['div.article-gallery']->each(function( $node ) use (&$out) {
                pq($node)->removeClass('mceNonEditable');
                $out[] = array(
                    'html' => pq($node)->htmlOuter(),
                    'title' => $node->getAttribute('data-gallery-title')
                );
            });
            return $out;
        }
        
        public function images() {
            $out = array();
            $pQuery = new pqLoader();
            $pq = phpQuery::newDocument( $this->_->document );
            $pq['img']->each( function($node) use (&$out) {
                $src = $node->getAttribute('src');
                if ($src) {
                    $out[] = array( 
                        'src' => $src,
                        'node'=> $node
                    );
                }
            } );
            return $out;
        }
        
        public function videos( ) {
            $out = array();
            $qQuery = new pqLoader();
            $pq = phpQuery::newDocument( $this->_->document );
            $pq['video, div.video']->each( function($node) use (&$out) {

                $src = $node->getAttribute('src');
                $src1= $node->getAttribute('data-src');
                
                $thumb = $node->getAttribute('poster');
                $thumb1= $node->getAttribute('data-poster');
                
                $vid = array();
            
                if ( strlen( "$src$src1" ) )
                    $vid['src'] = $src ? $src : $src1;
                
                if ( strlen( "$thumb$thumb1" ) )
                    $vid['poster'] = $thumb ? $thumb : $thumb1;
                
                $vid['node'] = $node;
                
                $vid['isLive'] = $node->getAttribute('data-asset-type') == 'live' ? TRUE : FALSE;

                if ($vid['src'])
                    $out[] = $vid;
        
            });
            return $out;
        }
        
        public function __get( $propertyName ) {
            return $this->_->{$propertyName};
        }
        
        public function __set( $propertyName, $propertyValue ) {
            $this->_->{$propertyName} = $propertyValue;
        }
        
        public function __call( $methodName, $args ) {
            if (!method_exists( $this, $methodName ))
                return call_user_func_array( array( $this->_, $methodName ), $args );
            else 
                return call_user_func_array( array( $this, $methodName ), $args );
        }
        
        /* ArrayAccess interface implementation */
        public function offsetSet( $offset, $value ) {
            throw new Exception("Array access implemented as readOnly for this class");
        }
        
        public function offsetUnset( $offset ) {
            // Unset doesn't work on this class
            return FALSE;
        }
        
        public function offsetExists( $offset ) {
            return $this->dom()->find($offset)->length > 0;
        }
        
        public function offsetGet( $offset ) {
            return $this->dom()->find( $offset );
        }
        
        private function dom() {
            if ( $this->_dom === NULL ) {
                new pqLoader();
                $this->_dom = phpQuery::newDocument( $this->_->document );
            }
            return $this->_dom;
        }
        
        public function saveDom() {
            if ( $this->_dom !== NULL ) {
                $this->_->document = $this->_dom->htmlOuter();
                unset( $this->_dom );
                $this->_dom = NULL;
            }
        }
    }

?>