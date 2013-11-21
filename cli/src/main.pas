program onedb;

uses crt, libhistory, libterm, libosutils;

var ch     : char = #0;          // current readed char from console
    special: boolean = false;    // special key handling

begin

    // inifinite read character loop
    while true do begin
        
        ch := readkey();
        special := false;
        
        case ch of
            
            #0: begin
                
                special := true;
                
                ch := readkey();
                
                case ch of
                
                    #72: begin
                        // up arrow
                        on_up();
                    end;
                    
                    #80: begin
                        // down arrow
                        on_down();
                    end;
                    
                    #77:begin
                        // right arrow
                        on_right();
                    end;
                    
                    #75:begin
                        // left arrow
                        on_left();
                    end;
                    
                    #67:begin
                        //ctrl right -> jump 1 word right
                        on_next_word();
                    end;
                    
                    #68:begin
                        //ctrl left -> jump 1 word left
                        on_prev_word();
                    end;
                    
                    #83:begin
                        //delete key pressed. delete 1 char right
                        on_delete();
                    end;
                    
                    #73:begin
                        //page up pressed. not working under linux
                        //writeln('page_up');
                    end;
                    
                    #81: begin
                        // page down pressed. not working under linux
                        //writeln('page_down');
                    end;
                    
                    //else
                    //    writeln( '#', ord( ch ), '(2nd)' );
                    
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
            
            #32..#127: begin
                if special = false then
                begin
                    on_key( ch );
                end else
                begin
                    writeln('special: #0#', ord(ch));
                end;
            end;
            
            #3: begin
                // ctrl + c pressed
                die();
            end;
            
            // add character to current line
            //else begin
            //    writeln('special key: #', ord(ch) );
            //    
            //end;
            
        end; //case
        
    end;

end.