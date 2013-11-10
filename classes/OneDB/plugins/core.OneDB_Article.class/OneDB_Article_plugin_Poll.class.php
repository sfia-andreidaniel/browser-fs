<?php

    class OneDB_Article_plugin_Poll {

        protected $_ = NULL;

        public function __construct( &$that ) {
            $this->_ = $that;
        }
        
        public function html( $includeOuterHTML = FALSE ) {
            
            if (!isset( $_SESSION ) )
                session_start();
            
            $_SESSION['OneDB_Ratings'] = isset( $_SESSION['OneDB_Ratings'] ) && is_array( $_SESSION['OneDB_Ratings'] ) ? $_SESSION['OneDB_Ratings'] : array();
            
            $id = $this->_->_id . '';
            
            $str = '';
            
            /* Return the inner HTML of the poll */
            $str .= '<h3>' . htmlentities( $this->_->title, ENT_COMPAT, 'utf-8' ) . '</h3><ul class="poll-type-' . $this->_->pollType . '">';
            
            $options = $this->_->options;
            
            foreach ($options as $option) {
                $str .= ( '<li data-option-id="' . $option['_id'] . '" data-votes="' . $option['votes'] . '" data-percent="' . $option['percent'] . '">'
                          . preg_replace('/url\\:\\[(http\\:\\/\\/|https\\:\\/\\/)?(.*)(\\|\\|(.*))?\\]/', '<a href="$1$2">$2</a>', htmlentities( $option['htmlCaption'], ENT_COMPAT, 'utf-8' ) ) . '</li>'
                        );
            }
            
            $str .= '</ul>';
            
            if ( isset( $_SESSION['OneDB_Ratings'][ $id ] ) && $_SESSION['OneDB_Ratings'][ $id ] ) {
                $str .= '<p class="voted" style="display: none">Allready-voted</p>';
            }
            
            return !$includeOuterHTML ? $str : '<div class="poll" data-poll-id="' . $this->_->_id . '">' . $str . '</div>';
        }
        
        public function vote( array $optionsList, $USERID_SESSION_KEY_IDENTIFIER = false ) {
        
            if (!isset( $_SESSION ))
                session_start();
            
            if ( $USERID_SESSION_KEY_IDENTIFIER &&
                 ( !isset( $_SESSION[ $USERID_SESSION_KEY_IDENTIFIER ] ) || empty( $_SESSION[ $USERID_SESSION_KEY_IDENTIFIER ] ) )
            ) return FALSE;
            
            $_SESSION['OneDB_Ratings'] = isset( $_SESSION['OneDB_Ratings'] ) && is_array( $_SESSION['OneDB_Ratings'] ) 
                ? $_SESSION['OneDB_Ratings'] 
                : array();
            
            $myID = $this->_->_id . '';
            
            if ( isset( $_SESSION['OneDB_Ratings'][ $myID ] ) && $_SESSION['OneDB_Ratings'][ $myID ] )
                return TRUE; //Allready voted
            
            $options = $this->_->options;

            if (!is_array( $options ))
                $options = array();
            
            $totalVotes = 0;
            
            //update votes
            for ( $i=0, $n = count($options); $i<$n; $i++ ) {
                
                if ( is_array( $options[$i] ) && isset( $options[$i]['_id'] ) && isset( $options[$i]['votes'] ) ) {

                    if ( in_array( $options[$i]['_id'], $optionsList ) )
                        $options[$i]['votes']++;

                    $totalVotes += (int)$options[$i]['votes'];
                }
                
            }
            
            $out = array();
            
            //update percents ...
            for ( $i=0, $n = count($options); $i<$n; $i++ ) {
                if ( is_array( $options[$i] ) && isset( $options[$i]['_id'] ) ) {
                    $options[$i]['percent'] = $totalVotes == 0 ? 0 : $options[$i]['votes'] / ( $totalVotes / 100 );
                    
                    $out[] = array(
                        '_id' => $options[$i]['_id'],
                        'votes' => $options[$i]['votes'],
                        'percent' => $options[$i]['percent']
                    );
                }
            }
            
            $this->_->options = $options;
            $this->_->save();
            
            $_SESSION['OneDB_Ratings'][ $myID ] = TRUE;
            
            return $out;
        }
    }

?>