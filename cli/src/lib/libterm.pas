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
procedure term_update();

procedure die();

implementation

uses crt, libhistory, libosutils, liberror, libenv, libpassword;

var command  : string  = '';
    wr_index : integer = 0;
    cursor   : string  = '$ ';
    phpbin   : string  = '';

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

procedure term_update();
var site: string = '';
    path: string = '';
    user: string = '';
    incr: integer = 0;
begin

    site := term_get_env( 'site' );
    path := term_get_env( 'path' );
    user := term_get_env( 'user' );

    gotoxy( 1, wherey() );
    clreol();
    
    if ( user <> '' ) and ( user <> 'onedb' ) then begin
        textcolor( yellow );
        write( user );
        textcolor( green );
        write( '@' );
        incr += ( length( user ) + 1 );
    end;
    
    if site <> '' then begin
        textcolor( magenta );
        write( site, ' ' );
        incr += ( length( site ) + 1 );
    end;
    
    if ( path <> '' ) then begin
        textcolor( cyan );
        write( path, ' ' );
        incr += ( length( path ) + 1 );
    end;
    
    if ( cursor <> '' ) then begin
        textcolor( blue );
        write( cursor );
        incr += length( cursor );
    end;
    
    textcolor( lightgray );
    write( command );
    
    gotoxy( wr_index + incr + 1, wherey() );
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
    term_update();
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
    term_update();
end;

procedure on_down;
begin
//    writeln( #13#10'down_pressed'#13#10 );
    command  := next_history();
    wr_index := length( command );
    term_update();
end;

procedure on_left;
begin
    if ( wr_index > 0 ) then
    begin
        wr_index -= 1;
        term_update();
    end;
end;

procedure on_right;
begin
    if ( wr_index < length( command ) ) then
    begin
        wr_index += 1;
        term_update();
    end;
end;

procedure on_key( c: char );
begin
    wr_index += 1;
    insert( c, command, wr_index );
    term_update();
end;

procedure on_command();

var tcommand   : string = '';      // the terminal command ( unparsed )
    args       : TStringList;      // the arguments of the parsed command line
    handled    : boolean = false;  // weather the command has been handled internally
    supassword : string = '';      // for the "su" command

begin
    wr_index := 0;
    
    handled := false;
    
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
    
    //write( #13#10 );
    
    // execute tcommand
    
    if tcommand = '' then
        begin
            exit;
        end;
    
    args := parse_command( tcommand );
    
    case args.count of
        1: begin
            
            if args[0] = 'exit' then begin
                handled := true;
                die();
            end;
            
            if args[0] = 'clear' then begin
                clrscr();
                handled := true;
                term_update();
            end;
            
        end;
        
        2: begin
            
            // su is a special command where we need to read
            // the password before running the script
            if ( args[0] = 'su' ) and ( term_get_env('site') <> '' ) then begin
                
                writeln();
                
                supassword := read_password();
                
                if supassword = '' then
                begin
                    textcolor( red );
                    writeln( 'conversation error' );
                    textcolor( lightgray );
                    writeln();
                    handled := true;
                end else
                begin
                    // add the password as last argument to 'su'
                    args.add( supassword );
                end;
                
            end;
            
        end;
    end;
    
    if handled = false then begin
        handled := run_command( args );
        if handled then begin
            term_update();
        end;
    end;
    
    if handled = false then
    begin
        textcolor( red );
        write( #10#13'> ' );
        textcolor( lightgray );
        write( 'unrecognized command: ' );
        textcolor( yellow );
        writeln( tcommand );
        textcolor( lightgray );
        term_update();
    end;
    
end;

initialization
    
    textcolor( green );
    
    write( 'onedb command line interface' );
    
    textcolor( yellow );
    
    writeln( ' v 1.0' );
    
    textcolor( lightgray );
    
    write( '* press ' );
    
    textcolor( red );
    
    write( 'esc ' );
    
    textcolor( lightgray );
    
    write( 'or type ' );
    
    textcolor( red );
    
    write( 'exit ' );
    
    textcolor( lightgray );
    
    writeln( 'to exit console' );
    
    write( '* type ' );
    
    textcolor( yellow );
    
    write( 'help ' );
    
    textcolor( lightgray );
    
    writeln( 'to get help' );
    
    phpbin := which( 'php' );
    
    if phpbin <> '' then
        writeln( '* using php binary: ', phpbin )
    else error( 'php binary file not found!');
    
    term_update();
    
end.