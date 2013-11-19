unit liberror;

interface

procedure error( reason: string );

implementation

uses crt;

procedure error( reason: string );
begin
    
    textcolor( red );
    write( 'ERROR: ' );
    textcolor( lightgray );
    writeln( reason );
    
    halt(1);
    
end;

initialization

end.