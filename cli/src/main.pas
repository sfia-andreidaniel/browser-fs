program onedb;

uses crt, libhistory, libterm, libosutils;

var ch : char = #0;          // current readed char from console

begin

    // inifinite read character loop
    while true do begin
        
        ch := readkey();
        
        case ch of
            
            #0: begin
                
                ch := readkey();
                
                case ch of
                    
                    #72: begin
                        on_up();
                    end;
                    
                    #80: begin
                        on_down();
                    end;
                    
                    #77:begin
                        on_right();
                    end;
                    
                    #75:begin
                        on_left();
                    end;
                    
                    //else
                    //    writeln( 'key: ', ord( ch ), #13 );
                    
                end;
                
            end;
            
            // if backspace is pressed, delete char from the
            // left
            #8: begin
                on_bksp();
            end;
            
            // tab
            #9: begin
                on_autocomplete();
            end;
            
            // if character is esc, exit
            #27: begin
                die();
            end;
            
            // if character is enter, run command
            #13: begin
                on_command();
            end;
            
            // add character to current line
            else begin
                on_key( ch );
            end;
            
        end; //case
        
    end;

end.