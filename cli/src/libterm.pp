unit libterm;

interface

uses classes;

procedure on_bksp;

procedure on_up;
procedure on_down;
procedure on_left;
procedure on_right;

procedure on_autocomplete;
procedure on_key( c: char );
procedure on_command();

function parse_command( command: string ): TStringList;

procedure die();

implementation

uses crt, libhistory;

var command  : string  = '';
    wr_index : integer = 0;
    prefix   : string  = '';
    cursor   : string  = '$ ';

procedure die();
begin
    textcolor( red );
    writeln( #10'goodbye'#10 );
    textcolor( lightgray );
    halt;

end;

function parse_command( command: string ): TStringList;
var y: TStringList;
begin

    y := TStringList.create;

    y.strictdelimiter := false;
    y.delimiter := ' ';
    y.quotechar := '"';

    y.delimitedtext := command;

    parse_command := y;

end;

procedure update();
begin
    gotoxy( 1, wherey() );
    clreol();
    textcolor( green );
    write( prefix );
    textcolor( blue );
    write( cursor );
    textcolor( white );
    write( command );
    gotoxy( wr_index + length( prefix ) + length( cursor ) + 1, wherey() );
end;

procedure on_autocomplete;
begin
end;

procedure on_bksp;
begin
    if ( wr_index >= 1 ) then
    begin
        delete( command, wr_index, 1 );
        wr_index -= 1;
    end;
    update();
end;

// when pressing the up key, we update
// the command with the previous history entry
// also if a command is written, we store the command
// before updating the history
procedure on_up;
begin
//    writeln( #13#10'up_pressed'#13#10 );
    command  := prev_history();
    wr_index := length( command );
    update();
end;

procedure on_down;
begin
//    writeln( #13#10'down_pressed'#13#10 );
    command  := next_history();
    wr_index := length( command );
    update();
end;

procedure on_left;
begin
    if ( wr_index > 0 ) then
    begin
        wr_index -= 1;
        update();
    end;
end;

procedure on_right;
begin
    if ( wr_index < length( command ) ) then
    begin
        wr_index += 1;
        update();
    end;
end;

procedure on_key( c: char );
begin
    wr_index += 1;
    insert( c, command, wr_index );
    update();
end;

procedure on_command();

var tcommand: string = '';
    args    : TStringList;

begin
    wr_index := 0;
    
    if ( command <> '' ) then
    begin
        // push command line in history
        push_history( -1, command );
        
        // set command that's going to be executed
        tcommand := command;
        
        // reset command
        command := '';
        reset_history_index();
    end;
    
    write( #13#10 );
    
    // execute tcommand
    
    if tcommand = '' then
        begin
            exit;
        end;
    
    args := parse_command( tcommand );
    
    case args.count of
        1: begin
            
            if args[0] = 'exit' then begin
                die();
            end;
            
        end;
        2: begin
            
            if args[0] = 'use' then begin
                // use command
                prefix := args[1];
            end;
            
        end;
    end;
    
    update();
end;

initialization
    
    update();
    
end.