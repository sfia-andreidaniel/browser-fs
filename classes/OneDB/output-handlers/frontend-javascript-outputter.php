<?php
    
    header("Content-Type: text/javascript");
    
    $files = scandir("components/frontend/js");
    
    $buffer = '';
    
    foreach ($files as $file) {
        if ( $file == 'init.js' )
            continue;
        $fpath = "components/frontend/js/$file";
        if ( preg_match('/\.js$/', $file) && is_file( $fpath ) ) {
            $buffer .= "\n//FILE: $file\n\n" . file_get_contents( $fpath );
        }
    }
    
    if ( file_exists( "components/frontend/js/init.js" ) )
        $buffer .= "\n//INITIALIZATION FILE: init.js\n\n" . file_get_contents( 'components/frontend/js/init.js' );
    
    // $buffer = preg_replace('/\n[\s]+/', "\n", $buffer);

    $out = '';

    $buffer .= ' ';

    /* Strip comments */
    $i = 0;
    while ($i < strlen($buffer) - 1) {
        switch ( TRUE ) {
        
            case $buffer[$i] == "'":
                $out .= "'";
                
                for ($j=$i+1, $len=strlen($buffer); $j<$len; $j++) {
                    $out .= $buffer[$j];
                    if ($buffer[$j] == "'")
                        break;
                }
                
                $i = $j;
                
                break;

            case $buffer[$i] == '"':
                $out .= '"';
                for ($j=$i+1, $len=strlen($buffer); $j<$len; $j++) {
                    $out .= $buffer[$j];
                    if ($buffer[$j] == '"')
                        break;
                }
                $i = $j;
                break;
        
            case $buffer[$i] == ' ':
                if ($buffer[$i + 1] == ' ')
                    $i++;
                else
                    $out .= ' ';
                break;
        
            case $buffer[$i] == '/':
                
                switch ( $buffer[$i + 1] ) {
                    case '/':
                        for ($j=$i+1,$n=strlen($buffer);$j<$n;$j++) {
                            if ($buffer[$j] == "\n") {
                                break;
                            }
                        }
                        $i = $j;
                        break;
                    case '*':
                        $nextPos = strpos( $buffer, '*/', $i );
                        if ($nextPos) {
                            $i = $nextPos + 2;
                        } else $i = strlen( $buffer );
                        
                    default:
                        $out .= $buffer[$i];
                }
                
                break;
            
            default:
                $out .= $buffer[$i];
        }
        $i++;
    }
    
    $out = preg_replace('/\\n([\\s]+)?\\n/', "\n", $out);
    
    echo trim( $out );
?>