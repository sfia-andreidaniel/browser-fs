unit libautocomplete;
interface

procedure autocomplete( var commandline: string; var cursorindex: integer );

implementation

uses classes, libosutils, libenv, crt, sysutils, process, strutils;

type tstrarray = array of string;

function parse_args_raw_mode  ( commandline: string ): tstrarray; forward;
function argument_is_quoted   ( argument   : string ): byte;      forward;
function argument_trim_quotes ( argument   : string ): string;    forward;
function autocomplete_get_starting_sequence( t: tstrarray; user_input: string ) : tstrarray; forward;

// basically we call the "auto.php" "$argumentIndex" "$firstArgText" "$subString"
function call_autocomplete    (
    argumentIndex: integer;
    firstArgumentText: string;
    subString: string ): tstrarray; forward;

procedure show_completion_list( list: tstrarray; leftArg: string ); forward;

function str_join( src: tstrarray ): string;
var out: string = '';
    i: integer;
begin
    for i := 0 to length( src ) - 1 do begin
        out := concat( out, src[i] );
    end;
    str_join := out;
end;

function str_needs_escaping( s: string ): boolean;
begin
    if pos( ' ', s ) > 0 then exit( true ) else exit( false );
end;

procedure autocomplete( var commandline: string; var cursorindex: integer );
var args               : tstrarray;   // array of string (dynamic array, 0-based index)
    i                  : integer =  0; // counter variable
    processed_chars    : integer =  0; // processed characters
    detected_arg_index : integer = -1; // on which argument of the command line the cursor is positioned?
    relative_cursor_pos: integer =  0; // relative cursor position to detected argument index
    ignore_first_chars : string  = ''; // ignore first space characters from current argument
    current_arg_text   : string  = ''; // current argument string contents
    moved_cursor_once  : boolean = false; // weather the autocomplete engine moved the cursor to the first char of detected argument
    arg_is_quoted      : byte    = 0;     // weather the argument is escaped byte, enum (0, 1)
    arg_unquoted_form  : string  = '';    // the argument with trimmed quotes
    arg_left_text      : string  = '';    // the left part before the cursor of the argument that will be evaluated for autocompletion
    first_script_arg   : string  = '';    // we need the first script argument of the command
    completionList     : tstrarray;
    cursor_increment   : integer = 0;
    new_argument       : string  = '';
begin
    
    if ( length( commandline ) = 0 ) then
        exit;
    
    args := parse_args_raw_mode( commandline );
    
    for i := 0 to length( args ) - 1 do
    begin
        if ( processed_chars + length( args[i] ) ) >= cursorindex then
        begin
            detected_arg_index := i;
            break; // exit loop
        end;
        processed_chars += length( args[i] );
    end;
    
    // detected argument index is 0-based
    if detected_arg_index = -1 then
        detected_arg_index := length( args ) - 1;
    
    // relative cursor position to current "raw" argument is 0-based
    relative_cursor_pos := cursorindex - processed_chars;
    
    // current argument text contents
    current_arg_text := args[ detected_arg_index ];
    
    // jump to first non white space in current argument.
    while ( ( current_arg_text <> '' ) and ( current_arg_text[1] = ' ' ) ) do
    begin
        ignore_first_chars := concat( ignore_first_chars, ' ' );
        relative_cursor_pos := relative_cursor_pos - 1;
        delete( current_arg_text, 1, 1 );
        
        //jump cursor to first character letter if needed
        if relative_cursor_pos < 0 then
        begin
            relative_cursor_pos += 1;
            cursorindex += 1;
            moved_cursor_once := true;
        end;
    end;
    
    // if we jump to the first argument character, we don't do auto complete
    // this time, but wait for the user to press the tab key another time
    if moved_cursor_once then exit;
    
    arg_is_quoted := argument_is_quoted( current_arg_text );
    
    arg_unquoted_form := argument_trim_quotes( current_arg_text );
    
    if ( arg_is_quoted = 1 ) and ( relative_cursor_pos > 0 ) then
        relative_cursor_pos -= 1;
    
    if ( arg_is_quoted = 1 ) and ( relative_cursor_pos = length( current_arg_text ) ) then
        relative_cursor_pos -= 1;
    
    arg_left_text := copy( arg_unquoted_form, 1, relative_cursor_pos );

    if ( arg_left_text <> '' ) and ( ( arg_left_text[1] = '"' ) or ( arg_left_text[1] = '''' ) ) then
        arg_left_text := copy( arg_left_text, 2, length( arg_left_text ) - 1 );
    
    //writeln( arg_left_text );
    
    first_script_arg := argument_trim_quotes( trim( args[0] ) );
    
    completionList := autocomplete_get_starting_sequence(
        call_autocomplete( detected_arg_index, first_script_arg, arg_left_text ),
        arg_left_text
    );
    
    if length( completionList ) > 1 then begin
        show_completion_list( completionList, arg_left_text );
        exit;
    end;
    
    if length( completionList ) = 0 then exit;
    
    // repack argument
    cursor_increment := length( completionList[0] ) - length( arg_left_text );
    //writeln( cursor_increment );
    
    new_argument := arg_left_text + copy( completionList[0], length( arg_left_text ) + 1, cursor_increment );
    
    if str_needs_escaping( new_argument ) = false then begin
        args[ detected_arg_index ] := ignore_first_chars + new_argument;
    end else
    begin
        args[ detected_arg_index ] := ignore_first_chars + escapeshellarg( new_argument );
    end;
    
    commandLine := str_join( args );
    
    if detected_arg_index = length( args ) - 1 then
        cursorindex := length( commandLine )
    else begin
        
        cursorindex := 0;
        
        for i := 0 to detected_arg_index do
            cursorindex += length( args[i] );
        
    end;
    
end;

function argument_is_quoted( argument: string ): byte;
begin
    if ( ( length( argument ) > 2 ) and 
         ( argument[1] = argument[ length(argument) ] ) and 
         ( ( argument[1] = '"' ) or ( argument[1] = '''' ) )
    ) then exit(1)
    else exit(0);
end;

function argument_trim_quotes( argument: string ): string;
begin
    if ( argument_is_quoted( argument ) = 1 ) then
        argument_trim_quotes := copy( argument, 2, length( argument ) - 2 )
    else
        argument_trim_quotes := argument;
end;

function str_read( var s: string ): char;
begin
    if length( s ) = 0 then
        exit( #0 )
    else begin
        str_read := s[1];
        delete( s, 1, 1 );
    end;
end;

function str_get( var s: string ): char;
begin
    if length( s ) = 0 then
        exit( #0 )
    else
        exit( s[1] );
end;

procedure array_push( var items: tstrarray; value: string );
begin
    setlength( items, length( items ) + 1 );
    items[ length( items ) - 1 ] := value;
end;

function parse_args_raw_mode( commandline: string ): tstrarray;
var items  : tstrarray;
    cmd    : string;
    seqend : integer;
    chr    : char;
    chr1   : char;
    arg    : string;

begin
    
    seqend := 0;
    
    cmd   := commandline;
    
    arg   := '';
    
    setlength( items, 0 );
    
    if cmd = '' then
        exit(items);
    
    
    while true do
    begin
        // initialize sequence end type
        seqend := 0;
        
        repeat
            
            chr := str_read( cmd );
            
            case chr of
                #0: begin
                    // string consumed. return
                    if arg <> '' then
                        array_push( items, arg );
                    exit( items ); //exit function
                end;
                ' ': begin
                    arg := concat( arg, chr );
                    // good, continue next
                end;
                else begin
                    arg := concat( arg, chr );
                    break; // break current repeat loop
                end;
            end;
        until ( false );
        
        case chr of
           '"': begin
               seqend := 1; // seqend 1 eq "
           end;
           '''': begin
               seqend := 2; // seqend 2 eq '
           end
           else begin
               seqend := 0; // seqend 0 eq non-space char
           end;
        end; //case
        
        repeat
            
            chr := str_read( cmd );
            
            case chr of
                #0: begin
                        if arg <> '' then
                            array_push( items, arg );
                        exit( items ); // exit function
                    end;
                ' ': begin
                        // encountered space.
                    
                        // if we're expecting a space, then restart loop
                        // otherwise treat the space as a normal char
                    
                        if seqend = 0 then
                        begin
                            array_push( items, arg );
                            arg := ' ';
                            break; // exit repeat loop
                            
                        end else
                        begin
                            // add the space to current arg
                            arg := concat( arg, ' ' );
                            // and continue repeat loop
                        end;
                    end;
                '"': begin
                    // encountered double quotes
                    
                    // if we're expecting a double quote, then restart the loop
                    // otherwise treat the double quote as a normal char
                    
                    chr1 := str_get( cmd );
                    
                    if ( seqend = 1 ) and ( ( chr1 = ' ' ) or ( chr1 = #0 ) ) then
                    begin
                        
                        // add current double quote to the current item
                        arg := concat( arg, '"' );
                        
                        // increment args, and exit this repeat loop
                        array_push( items, arg );
                        arg := '';

                        break; // exit repeat loop;
                    
                    end else
                    begin
                        
                        // add the double quote to the current arg
                        arg := concat( arg, '"' );
                        // and continue repeat loop
                        
                    end;
                end;
                '''': begin
                    // encountered mono quote
                    
                    // if we're expecting a mono quote, then restart the loop
                    // otherwise treat the mono quote as a normal char

                    chr1 := str_get( cmd );
                    
                    if ( seqend = 2 ) and ( ( chr1 = ' ' ) or ( chr1 = #0 ) ) then
                    begin
                        // add current mono quote to the current item
                        arg := concat( arg, '''' );
                        
                        // increment args, and exit this repeat loop
                        array_push( items, arg );
                        arg := '';
                        
                        break;
                    end else
                    begin
                        
                        // add the mono quote to the current arg
                        arg := concat( arg, '''' );
                        // and continue repeat loop
                    
                    end;
                end else
                begin
                    // encountered a non white-space character.
                    arg := concat( arg, chr );
                    // continue this repeat loop...
                end;
            end; //case
        until ( false );
    end;
end;

// basically we call the "auto.php" "$argumentIndex" "$firstArgText" "$subString"
function call_autocomplete    (
    argumentIndex: integer;
    firstArgumentText: string;
    subString: string ): tstrarray;

var testFile    : String;
    outputLines : TStringList;
    memStream   : TMemoryStream;
    ourProcess  : TProcess;
    numBytes    : Longint;
    bytesRead   : Longint;
    out         : tstrarray;
    i           : integer;

begin
    
    testFile    := base_dir() + '/../plugins/auto.php';
    setlength( out, 0 );
    
    
    if not fileExists( testFile ) then
    begin
        textcolor( red );
        writeln( '* autocomplete: file "plugins/auto.php" was not found. function disabled' );
        textcolor( lightgray );
        exit( out );
    end;

    outputLines := TStringList.create;
    memStream := TMemoryStream.create;

    
    bytesRead  := 0;
    ourProcess := TProcess.create( nil );
    ourProcess.executable := which( 'php' );
    
    ourProcess.parameters.add( testFile );
    ourProcess.parameters.add( '-ENV=site:' + term_get_env( 'site' ) );
    ourProcess.parameters.add( '-ENV=path:' + term_get_env( 'path' ) );
    ourProcess.parameters.add( '-ENV=user:' + term_get_env( 'user' ) );
    ourProcess.parameters.add( '-ENV=password:' + term_get_env( 'password' ) );
    
    ourProcess.parameters.add( IntToStr( argumentIndex ) );
    
    if argumentIndex > 0 then
    begin
        ourProcess.parameters.add( firstArgumentText );
    end else
    begin
        // add empty argument. it seems that empty arguments rise a problem into the
        // tprocess class, so we found a workaround for this.
        ourProcess.parameters.add( '---empty---argument---fpc---tprocess---bug---' );
    end;
    
    if subString = '' then
        ourProcess.parameters.add( '---empty---argument---fpc---tprocess---bug---' )
    else
        ourProcess.parameters.add( subString );
    
    ourProcess.Options := [ poUsePipes ];
    ourProcess.Execute;
    
    while true do begin
        memStream.setSize( bytesRead + 2048 );
        numBytes := ourProcess.output.read( ( memStream.memory + bytesRead )^, 2048 );
        
        if numBytes > 0 then
            inc( bytesRead, numBytes )
        else
            break;
    end;
    
    memStream.SetSize( bytesRead );
    outputLines.loadFromStream( memStream );
    
    ourProcess.free;
    memStream.free;
    
    // populate the out array with the entries
    for i:=0 to outputLines.count - 1 do begin
        if outputLines[i] <> '' then
            array_push( out, outputLines[i] )
        else
            break;
    end;
    
    exit( out );
end;

procedure show_completion_list( list: tstrarray; leftArg: string );
var longestItem : integer = 0;
    i: integer;
    maxCols: integer;
begin
    
    for i:=0 to length( list ) - 1 do begin
        if longestItem < length( list[i] ) then
            longestItem := length( list[i] );
    end;
    
    // determine on how many columns to display output
    maxCols := trunc( screenWidth / longestItem );
    
    if maxCols < 1 then maxCols := 1;
    
    writeln();
    
    for i := 0 to length( list ) - 1 do begin
        clreol();
        textcolor( lightgray );
        write( leftArg );
        textColor( cyan );
        write( copy( list[i], length( leftArg ) + 1, length( list[i] ) - length( leftArg ) ) );
        write( padLeft( ' ', longestItem - length( list[i] ) + 2 ) );
    end;
    
    writeln();
    
    textcolor( white );
    
end;

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

end.