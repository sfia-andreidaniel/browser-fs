unit libutils;

interface

type
    TStrArray = array of string;

function is_int( s: string ): boolean;

implementation

function is_int( s: string ): boolean;
var i: integer;
begin
    if s = '' then exit( false );
    for i := 1 to length( s ) do
    begin
        case s[i] of
            '0'..'9': begin
            end else exit( false );
        end;
    end;
    exit( true );
end;

end.