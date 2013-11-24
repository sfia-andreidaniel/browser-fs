// the libpipe unit implements piping.
// piping from the point of view of the onedb cli, is a method
// of passing the output of a command as a input of another next command
//
// eg: ls /foo | grep file | split " " 3
//             ^ pipe      ^ pipe
//
// the above command consists of two pipe processors, "grep" and "split", and
// a cli plugin command, "ls"
//
// i don't know how to explain better, at this point, better look at the code
//
//

unit libpipe;

{$mode objfpc}

interface

    uses libutils, sysutils;

    const term_no_color        =  0;

    const term_fg_black        =  1;
          term_fg_dark_gray    =  2;
          term_fg_blue         =  3;
          term_fg_light_blue   =  4;
          term_fg_green        =  5;
          term_fg_light_green  =  6;
          term_fg_cyan         =  7;
          term_fg_light_cyan   =  8;
          term_fg_red          =  9;
          term_fg_light_red    = 10;
          term_fg_purple       = 11;
          term_fg_light_purple = 12;
          term_fg_brown        = 13;
          term_fg_yellow       = 14;
          term_fg_light_gray   = 15;
          term_fg_white        = 16;

    const term_bg_black        = 1;
          term_bg_red          = 2;
          term_bg_green        = 3;
          term_bg_yellow       = 4;
          term_bg_blue         = 5;
          term_bg_magenta      = 6;
          term_bg_cyan         = 7;
          term_bg_light_gray   = 8;

    type TPipeCommand = class

        protected
            _input      : TStrArray;
            _output     : TStrArray;
            _arguments  : TStrArray;
            
        public
            // create with input and arguments
            constructor Create ( input: TStrArray; arguments: TStrArray );
            
            // create only with arguments
            constructor Create ( arguments: TStrArray );
            
            // create without arguments
            constructor Create ();
            
            // adds a line to internal _input
            procedure   write_line( line: string ); virtual;
            
            // adds an argument to internal _arguments
            procedure   add_arg( argument: string ); virtual;
            
            // removes terminal string codes from a string
            function    decolorize( s: string ): string;
            
            // adds terminal string codes to a string. use constants term_bg*, term_fg* for background / foreground colors
            function    colorize  ( s: string; const fg_color: byte = 0; const bg_color: byte = 0): string;
            
            // the pipe command compiler. should be overrided
            function    Compile( arguments: TStrArray ): string; virtual;
            
            // the pipe command code impl. should be overrided
            procedure   Run(); virtual;
        
            // access to pipe command generated output
            property    Output   : TStrArray read _output;
            
            // access to pipe command arguments list
            property    Arguments: TStrArray read _arguments;
        
            // the command destructor. can be overidded.
            destructor  Free();
    end;
    
    Type TPipeCommand_Screen = class( TPipeCommand )
        
        function    Compile( args: TStrArray ): string; override;
        procedure   Run;                                override;
        procedure   write_line( line: string );         override;
        
    end;
    
    Type TPipeCommand_Grep = class( TPipeCommand )
        
        public
            
            function  Compile( args: TStrArray ): string; override;
            procedure Run;                                override;
        
    end;
    
    Type TPipeCommand_EGrep = class( TPipeCommand )
        
        public
        
            function  Compile( args: TStrArray ): string; override;
            procedure Run;                                override;
        
    end;
    
    Type TPipeCommand_Split = class( TPipeCommand )
        
        public
            
            function  Compile( args: TStrArray ): string; override;
            procedure Run;                                override;
    
    end;
    
implementation

constructor TPipeCommand.Create( input: TStrArray; arguments: TStrArray );
var i: integer;
begin
    setLength( _input, length( input ) );
    
    for i:= 0 to length( input ) - 1 do begin
        _input[i] := decolorize( input[i] );
    end;

    _arguments := arguments;
end;

constructor TPipeCommand.Create( arguments: TStrArray );
begin
    _arguments := arguments;
end;

constructor TPipeCommand.create();
begin
end;

destructor TPipeCommand.Free();
begin
end;

procedure TPipeCommand.Run();
begin
end;

function TPipeCommand.Compile( arguments: TStrArray ): string;
begin
    exit( 'abstract class TPipeCommand');
end;

function TPipeCommand.decolorize( s: string ): string;
var esc: boolean;
    i  : integer;
    out: string;
begin
    out := '';
    esc := false;
    i   := 0;
    
    for i := 1 to length( s ) do
    begin
        
        case s[i] of

            #27: begin
                esc := true;
            end;
            
            '0'..'9': begin
                if esc = false then
                    out := concat( out, s[i] );
            end;
            
            ';': begin
                if esc = false then
                    out := concat( out, s[i] );
            end;
            
            '[': begin
                if esc = false
                    then out := concat( out, s[i] );
            end;
            
            'm': begin
                if esc = true
                    then esc := false
                    else out := concat( out, s[i] );
            end else
            begin
                esc := false;
                out := concat( out, s[i] );
            end;
        end;
    end;
    
    exit( out );
end;

procedure TPipeCommand.write_line( line: string );
begin
    setLength( _input, length( _input ) + 1 );
    _input[ length( _input ) - 1 ] := decolorize( line );
end;

procedure TPipeCommand.add_arg( argument: string );
begin
    setLength( _arguments, length( _arguments ) + 1 );
    _arguments[ length( _arguments ) - 1 ] := argument;
end;


function TPipeCommand.colorize( s: string; const fg_color: byte = 0; const bg_color: byte = 0 ): string;
var pref: string;
begin
    
    pref := '';
    
    case fg_color of

        1:
            pref := #27'[0;30m';

        2:
            pref := #27'[1;30m';

        3:
            pref := #27'[0;34m';

        4:
            pref := #27'[1;34m';

        5:
            pref := #27'[0;32m';

        6:
            pref := #27'[1;32m';

        7:
            pref := #27'[0;36m';

        8:
            pref := #27'[1;36m';

        9:
            pref := #27'[0;31m';

        10:
            pref := #27'[1;31m';

        11:
            pref := #27'[0;35m';

        12:
            pref := #27'[1;35m';

        13:
            pref := #27'[0;33m';

        14:
            pref := #27'[1;33m';

        15:
            pref := #27'[0;37m';

        16:
            pref := #27'[1;37m';

    end;
    
    case bg_color of
    
        1:
            pref := concat( pref, #27'[40m' );

        2:
            pref := concat( pref, #27'[41m' );

        3:
            pref := concat( pref, #27'[42m' );
            
        4:
            pref := concat( pref, #27'[43m' );
            
        5:
            pref := concat( pref, #27'[44m' );

        6:
            pref := concat( pref, #27'[45m' );

        7:
            pref := concat( pref, #27'[46m' );

        8:
            pref := concat( pref, #27'[47m' );
    
    end;
    
    if pref = ''
        then exit( s )
        else exit( concat( pref, s, #27'[0m' ) );
    
end;

{$i inc/screen.pas}
{$i inc/grep.pas}
{$i inc/split.pas}
{$i inc/egrep.pas}

end.