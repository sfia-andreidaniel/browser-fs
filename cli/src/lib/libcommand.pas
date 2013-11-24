unit libcommand;

interface uses libutils;

function  parse_command         ( command: string )      : TCommandChain;
function  compile_command_chain ( chain: TCommandChain ) : string;
procedure exec_command          ( command: string );

implementation uses classes, libpipe, crt, libosutils, libterm, libenv, libpassword;

function parse_command( command: string ): TCommandChain;
var y: TStringList;
    out: TCommandChain;
    local : TStrArray;
    i: integer;
begin
    
    setLength( local, 0 );
    
    y := TStringList.create;
    y.strictDelimiter := false;
    y.delimiter := ' ';
    y.quoteChar := '"';
    y.delimitedText := command;
    
    for i := 0 to y.count - 1 do begin
        
        if y[i] = '|' then
        begin
            if length( local ) > 0 then
                push( out, local );
            setLength( local, 0 );
        end else
        begin
            push( local, y[i] );
        end;
        
    end;
    
    if length( local ) > 0 then
        push( out, local );
    
    exit( out );
    
end;

function compile_command( cmd: TStrArray ): string;
var iCmd   : TPipeCommand;
    i      : integer;
    args   : TStrArray;
    result : String;
begin
    
    setLength( args, 0 );
    
    if length( cmd ) = 0 then
        exit('');
    
    iCmd := nil;
    
    if cmd[0] = 'grep' then
        iCmd := TPipeCommand_Grep.create;
    
    if cmd[0] = 'screen' then
        iCmd := TPipeCommand_Screen.create;
    
    if cmd[0] = 'split' then
        iCmd := TPipeCommand_Split.create;
    
    if cmd[0] = 'egrep' then
        iCmd := TPipeCommand_EGrep.create;
    
    if iCmd <> nil then
    begin
        for i := 1 to length( cmd ) - 1 do
            push( args, cmd[i] );
        
        result := iCmd.Compile( args );
        
        iCmd.Free();
        
        exit( result );
    end else
    begin
        
        exit( 'unrecognized internal command or bad command usage: ' + cmd[0] );
        
    end;
end;

function create_pipe_command( cmd: TStrArray ) : TPipeCommand;
var iCmd : TPipeCommand;
       i : integer;
begin
    
    if length( cmd ) = 0 then
        exit( nil );
    
    iCmd := nil;
    
    if cmd[0] = 'grep' then
        iCmd := TPipeCommand_Grep.create;
    
    if cmd[0] = 'screen' then
        iCmd := TPipeCommand_Screen.create;
    
    if cmd[0] = 'split' then
        iCmd := TPipeCommand_Split.create;
    
    if cmd[0] = 'egrep' then
        iCmd := TPipeCommand_Egrep.create;
    
    if iCmd = nil then exit( nil );
    
    // push command arguments
    
    for i := 1 to length( cmd ) - 1 do
        iCmd.add_arg( cmd[i] );
    
    exit( iCmd );
    
end;

function compile_command_chain( chain: TCommandChain ): string;
var i   : integer;
    len : integer;
    result: string;
begin
    len := length( chain );
    
    for i:=1 to len - 1 do
    begin
        result := compile_command( chain[i] );
        
        if result <> '' then
            exit( result );
    end;
    
    exit( '' );
end;

function shift_command_chain( var chain: TCommandChain ): TStrArray;
var out: TStrArray;
      i: integer;
    len: integer;
begin
    if length( chain ) = 0 then
        exit( nil );
    
    out := chain[0];
    
    len := length( chain );
    
    for i := 0 to len - 2 do
        chain[i] := chain[i + 1];
    
    setlength( chain, length( chain ) - 1 );
    
    exit( out );
    
end;

procedure exec_command( command: string );
var chain   : TCommandChain;
    compile : string;
    current : TStrArray;
    index   : integer;
    handled : boolean;
    su_pwd  : string;
    output  : TStrArray;
    i       : integer;
    screen  : TStrArray;
    iCmd    : TPipeCommand;
begin

    chain := parse_command( command );

    setlength( screen, 1 );
    screen[0] := 'screen';

    push( chain, screen );

    compile := compile_command_chain( chain );
    
    if compile <> '' then
    begin
        writeln();
        textcolor( red );
        writeln( '> ', compile );
        textcolor( lightgray );
        term_update();
        exit;
    end;
    
    current := shift_command_chain( chain );
    index   := 0;
    
    while current <> nil do
    begin
        
        if length( current ) > 0 then
        begin
        
            handled := false;
        
            case index of
                
                0: begin
                    
                    case length( current ) of
                        1: begin
                            
                            if current[0] = 'exit' then
                            begin
                                handled := true;
                                die();
                            end;
                            
                            if current[0] = 'clear' then
                            begin
                                handled := true;
                                clrscr();
                                term_update();
                            end;
                            
                        end;
                        2: begin
                            
                            if ( current[0] = 'su' ) and ( term_get_env( 'site' ) <> '' ) then
                            begin
                            
                                writeln();
                            
                                su_pwd := read_password();
                            
                                if su_pwd = '' then
                                begin
                                    textcolor( red );
                                    writeln( 'conversation error' );
                                    textcolor( lightgray );
                                    writeln();
                                    handled := true;
                                end else
                                begin
                                    push( current, su_pwd );
                                end;
                            
                            end;
                        end;
                    end;
                    
                    if handled = false then
                    begin
                        
                            handled := run_command( current, output );
                            
                    end;
                    
                end
                else begin
                    // running a command that is piped
                            
                    iCmd := create_pipe_command( current );
                            
                    if iCmd = nil then
                    begin
                        handled := false;
                    end else
                    begin
                        
                        for i := 0 to length( output ) - 1 do
                            iCmd.write_line( output[i] );
                        
                        iCmd.Run();
                        
                        output := iCmd.output;
                        
                        iCmd.Free();
                        
                        handled := true;
                        
                    end;
                end;
                
            end;

            if handled = false then
            begin
                textcolor( red );
                write( #10#13'> ' );
                textcolor( lightgray );
                write( 'unrecognizable command: ' );
                textcolor( yellow );
                writeln( current[0] );
                textcolor( lightgray );
                term_update();
                exit();
            end;
        end;
        
        current := shift_command_chain( chain );
        index := index + 1;
    end;
    
    term_update();
    
end;

end.