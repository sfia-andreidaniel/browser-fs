function  TPipeCommand_Split.Compile( args: TStrArray ): string;
begin
    
    if length( args ) <> 2 then
        exit( 'bad split command usage. please type "man split" in order to see help!' );
    
    if not is_int( args[1] ) then
        exit( 'the second argument of "split" command should be integer. type "man split" for help!' );
    
    _arguments := args;
    
    exit(''); // no error
    
end;

procedure TPipeCommand_Split.Run;
var delimiter: string;
    line     : TStrArray;
    index    : integer;
    i        : integer = 0;
    n        : integer = 0;
begin
    n         := length( _input );
    index     := strtoint( _arguments[1] );
    delimiter := _arguments[0];
    
    for i:=0 to n - 1 do begin
        line := str_split( _input[i], delimiter );
        if length( line ) >= index then
            push( _output, line[ index - 1 ] );
    end;
    
end;
