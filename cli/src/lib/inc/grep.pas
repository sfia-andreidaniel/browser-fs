function  TPipeCommand_Grep.Compile( args: TStrArray ): string;
begin
    
    if length( args ) <> 1 then
        exit( 'bad grep command usage. please type "man grep" in order to see help!' );
    
    _arguments := args;
    
    exit(''); // no error
    
end;

procedure TPipeCommand_Grep.Run;
var i: integer = 0;
    n: integer = 0;
begin
    n := length( _input );
    
    for i:=0 to n - 1 do begin
        //writeln( 'grep: ', _arguments[0], ' in ', _input[i] );
        if pos( _arguments[0], _input[i] ) > 0 then
            push( _output, StringReplace( _input[i], _arguments[0], colorize( _arguments[0], term_fg_red ), [ rfReplaceAll ] ) );
    end;
    
end;
