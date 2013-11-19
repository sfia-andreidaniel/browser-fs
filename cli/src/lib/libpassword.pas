unit libpassword;

interface

function read_password: string;

implementation

uses crt;

function read_password: string;
var c   : char;
    pass: string = '';
begin
    
    write( 'enter password: ' );
    
    repeat
        
        c := readkey();
        
        case c of
            #13: begin
                     // enter pressed. will return the password
                 end;
            
            #8:  begin
                     // backspace presset
                     if pass <> '' then
                        begin
                            write( c );
                            pass := copy( pass, 1, length( pass ) - 1 );
                            clreol();
                        end;
                 end;
            
            #0:  begin
                    c := readkey();
                 end;
            
            #32 .. #126:
                 begin
                    write( '*' );
                    pass := concat( pass, c );
                 end;
        end; //end case
        
    until ( c = #13 );
    
    read_password := pass;
    
end;

end.