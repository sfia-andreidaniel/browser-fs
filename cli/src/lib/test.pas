function autocomplete_get_starting_sequence( t: tstrarray; user_input: string ) : tstrarray;

var out: tstrarray;
      i: integer;
    ret: string;
 retlen: integer;
  loops: integer;
   good: boolean;
      c: char;
  index: integer;

begin
    
    ret   := user_input;
    loops := 0;
    
    if length( t ) < 2 then exit( t );
    
    while true do
    begin

        retlen := length( ret );
        index  := retlen + 1;
        good   := true;
        
    
        for i:=0 to length( t ) - 1 do begin
            
            if length( t[i] ) < retlen then
            begin
                good := false;
                break;
            end;
            
            if i = 0 then
                c := t[i][ index ]
            else begin
                
                if c <> t[i][index] then
                begin
                    good := false;
                    break;
                end;
                
            end;
            
        end;
        
        if good = true then
        begin
            loops := loops + 1;
            ret := ret + c;
        end else
        begin
            break;
        end;
    
    end;
    
    if loops > 0 then
    begin
        setlength( out, 1 );
        out[0] := ret;
        exit( out );
    end else
    begin
        exit( t );
    end;
    
end;