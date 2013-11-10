<?php

    $do = isset( $_POST['do'] ) ? $_POST['do'] : die("What to do?");
    
    switch ($do) {
    
        case 'validate-parent':
            $_parent = isset( $_POST['_parent'] ) ? $_POST['_parent'] : die("Which parent?");
            
            if ( OneDB::get()->categories( array(
                    "_id"  => MongoIdentifier( $_parent ),
                    "tags" => "matches"
                ) )->length
            ) echo json_encode("ok");
            else echo "Sorry, you cannot store matches events here!";
            break;
    
        case 'save-match':
        
            $data = isset( $_POST['data'] ) ? @json_decode( $_POST['data'], TRUE ) : die("Which data?");
            
            if (!is_array( $data )) die("Unserializeable data");
            
            if (!isset( $data['database'] ) )
                die("Which data.database?");
            
            $database = $data['database'];
            unset( $data['database' ] );
            
            if ($data['online'] && !strlen( $data['streamerScope'] ) && !strlen( $data['externalURL'] ))
                die("If you want to put the match Active, you should complete either the StreamerScope, either the External Match URL");
            
            if (!isset( $database['_parent'] ) || empty( $database['_parent'] ) )
                die("Which data.database._parent?");
            
            $_parent = MongoIdentifier( $database['_parent'] );
        
            $match = empty( $database['_id'] ) ? 
                OneDB::get()->categories(
                    array(
                        '_id' => MongoIdentifier( $database['_parent'] ),
                        'tags'=> 'matches'
                    )
                )->get(0)->createArticle('Match') :
                OneDB::get()->articles(
                    array(
                        '_id' => MongoIdentifier( $database['_id'] ),
                        'type'=> 'Match'
                    )
                )->get(0);
            
            $match->_autoCommit = FALSE;
            
            $team_A_Name = OneDB_toAscii( $data['team_A_Name'] );
            $team_B_Name = OneDB_toAscii( $data['team_B_Name'] );
            
            $match_Name = "$team_A_Name - $team_B_Name - $data[match_id]";
        
            if (!strlen( trim( $match_Name, ' -' ) ) )
                die("Please Fill In the teams names!");
        
            $match->name = $match_Name;
            
            foreach (array_keys( $data ) as $key) {
                if (!in_array( $key, array( 'startTime', 'stopTime' ) ) )
                $match->{"$key"} = $data["$key"];
            }
            
            $match->modifier = $_SESSION['UNAME'];
            
            $dateParser = function( $str ) {
                if ( preg_match( '/^([\d]+)-([\d]+)-([\d]+) ([\d]+)\:([\d]+)\:([\d]+)$/', $str, $matches ) ) {
                    list( $dummy, $YY, $MM, $DD, $hh, $mm, $ss ) = $matches;
                    return mktime( $hh, $mm, $ss, $MM, $DD, $YY );
                } else return '';
            };
            
            $match->startTime = $dateParser( $data['startTime'] );
            $match->stopTime  = $dateParser( $data['stopTime'] );
            
            $match->save();
            
            $matchData = $match->toArray();
            
            $matchData['_id'] = "$matchData[_id]";
            $matchData['_parent'] = "$matchData[_parent]";
            
            echo json_encode(
                $matchData
            );
        
            break;
        
        case 'load-match':
            $_id = isset( $_POST['_id'] ) ? $_POST['_id'] : die("Which match_id?");
            
            $match = OneDB::get()->articles( array(
                '_id' => MongoIdentifier( $_id ),
                'type'=> 'Match'
            ) )->get(0)->toArray();

            $match["_id"] = "$match[_id]";
            $match["_parent"] = "$match[_parent]";

            echo json_encode(
                $match
            );
            
            break;
        
        case 'get-match-details':
        
            $path = isset( $_POST['soccerwayPath'] ) ? $_POST['soccerwayPath'] : die("Which soccerwayPath?");
        
            // $path = str_replace("'", '', $path);
        
            $data = @json_decode(
                file_get_contents(
                    $link = 'http://www2.digisport.ro/soccerway/football/webservices/getMatchDetails.php?path=' . urlencode( $path )
                ),
                TRUE
            );
        
            if (!is_array( $data )) {
                print_r( $link );
                throw new Exception("Invalid match path!");
            }
                
            echo json_encode( $data );
                
            break;
    
        default:
            throw new Exception("Unknown handler command '$do' in onedb plugin file " . __FILE__ );
    }

?>