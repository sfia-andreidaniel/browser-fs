function  TPipeCommand_Screen.Compile( args: TStrArray ): string;
begin
    
    exit(''); // no error
    
end;

procedure TPipeCommand_Screen.Run;
var i: integer = 0;
    n: integer = 0;
begin
    n := length( _input );
    
    for i:=0 to n - 1 do begin
        writeln( _input[i] );
    end;
    
end;

procedure TPipeCommand_Screen.write_line( line: string );
begin
    setLength( _input, length( _input ) + 1 );
    _input[ length( _input ) - 1 ] := line;
end;
