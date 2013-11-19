unit libenv;

interface

uses strutils, strings, sysutils, classes;

function term_set_env( name: string; value: string ): boolean;
function term_get_env( name: string ): string;

procedure term_dump_process_output( var lines: TStringList );

implementation

var site     : string = '';
    path     : string = '';
    user     : string = '';
    password : string = '';

function term_set_env( name: string; value: string ): boolean;
begin
    
    term_set_env := false;
    
    if name = 'site' then
        begin
            site := value;
            term_set_env := true;
        end;
    
    if name = 'user' then
        begin
            user := value;
            term_set_env := true;
        end;
    
    if name = 'path' then
        begin
            path := value;
            term_set_env := true;
        end;
    
    if name = 'password' then
        begin
            password := value;
            term_set_env := true;
        end;
end;

function term_get_env( name: string ): string;
begin
    
    term_get_env := '';
    
    if name = 'site' then
    begin
        term_get_env := site;
    end
    else begin
        if name = 'path' then
        begin
            term_get_env := path;
        end
        else begin
    
            if name = 'user' then
            begin
                term_get_env := user;
            end else
            begin
                if name = 'password' then
                begin
                    term_get_env := password;
                end;
            end;
        end;
    end;
    
end;

procedure term_parse_var( s: string );
var space: integer = 0;
    name : string;
    value: string;
begin
    
    space := pos( ' ', s );
    
    name := copy( s, 1, space - 1 );
    value:= copy( s, space + 1, length( s ) - space );
    
    //writeln( 'space: ', space, ' name: "', name, '", value: "', value, '"' );
    
    if name <> '' then
        term_set_env( name, value );
    
end;

procedure term_dump_process_output( var lines: TStringList );
var total_lines  : integer = 0;
    output_lines : integer = 0;
    i            : integer = 0;
    j            : integer = 0;
    headers      : boolean = true;
    //prev_empty   : boolean = false;
    env_var      : string  = '';
begin

    //writeln( 'dumping process output...' );
    
    total_lines := lines.count;
    output_lines:= total_lines;
    
    //writeln( 'total lines: ', total_lines );
    
    j := 0;
    
    for i:=total_lines - 1 downto 0 do begin
    
        //writeln( 'line: ', j, ' len: ', length( lines[i] ), ' => ', lines[i] );
        //writeln( j );
        
        if (lines[i] = '' ) and ( j > 0 ) and ( headers = true ) then begin
            
            headers := false;
            output_lines := i;
            
        end;
        
        j := j + 1;
    end;
    
    if ( headers = false ) and ( output_lines > 0 ) then
    begin
        
        // decrement output lines while they're empty at the end
        repeat
        
            if lines[ output_lines - 1 ] = '' then
                output_lines -= 1;
        
        until ( output_lines = 0 ) or ( lines[ output_lines - 1 ] <> '' );
        
        
    end;
    
    for i := 0 to output_lines - 1 do
        writeln( lines[i] );
    
    for i := output_lines + 1 to total_lines - 1 do begin
        
        //writeln( 'parse_env_line: ', i, ' => ', lines[i] );
        
        if length( lines[i] ) > 0 then begin
            
            if copy( lines[i], 1, 9 ) = '$SETENV: ' then
            begin
                
                env_var := copy( lines[i], 10, length( lines[i] ) - 9 );
                
                //writeln( 'env: "', env_var, '"' );
                
                term_parse_var( env_var );
                
            end;
            
        end;
        
    end;
    
    //writeln( 'output lines: ', output_lines );

end;

initialization

end.