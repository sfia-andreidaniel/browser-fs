function  TPipeCommand_EGrep.Compile( args: TStrArray ): string;
begin
    
    if length( args ) <> 1 then
        exit( 'bad egrep command usage. please type "man egrep" in order to see help!' );
    
    _arguments := args;
    
    exit(''); // no error
    
end;

procedure TPipeCommand_EGrep.Run;
var i: integer = 0;
    n: integer = 0;
begin
    n := length( _input );
    
    for i:=0 to n - 1 do begin
        //writeln( 'grep: ', _arguments[0], ' in ', _input[i] );
        if preg_match( _input[i], _arguments[0] ) then
            push( _output, _input[i] );
    end;
    
end;
