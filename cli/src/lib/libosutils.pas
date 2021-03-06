unit libosutils;

interface

uses classes, libutils;

function  which( binary_name: string ): string; // which( 'php' ) will return '/usr/bin/php'
function  base_dir(): string;                   // returns current program directory

// run a console command command_name (e.g. help), with given arguments args
function  run_command( args: TStrArray; var output: TStrArray ): boolean;

function escapeshellarg( str: string ) : string;

implementation

uses sysutils, dos, strings, strutils, process, libterm, libenv;

var basedir: string;

function which( binary_name: string ) : string;
var i: integer;
    path: string;
    current: string;
begin
    path := getenv( 'PATH' );

    current := '';

    for i := 1 to length(path) do
    begin

        if ( path[i] = ':' ) then
        begin

            if ( current <> '' ) then
            begin

                if fileexists( current + '/' + binary_name ) then
                begin
                    exit( current + '/' + binary_name );
                end;

            end;

            current := '';
        end else
        begin
            current := current + path[i];
        end;

    end;

    exit( '' );

end;

function base_dir(): string;
var rev: string;
    i: integer;
    n: integer;
    add: string = '';
begin
    
    n := length( basedir ) - 1;
    
    add := '';
    rev := '';
    
    for i:= 1 to n do begin
        if basedir[i] = '/' then
        begin
            add += '/';
            rev := concat( rev, add );
            add := '';
        end else
        begin
            add := concat( add, basedir[i] );
        end;
    end;
    
    if rev = '' then
        base_dir := '.'
    else
        base_dir := rev;
    
end;

// escapes a shell argument ( replace " and \ with \" and \\
function escapeshellarg( str: string ) : string;
var out : string = '';
    i   : integer;
    len : integer;
begin
    
    len := length( str );
    
    for i := 1 to len do begin
        
        if str[ i ] = '"' then
        begin
            out += '\';
            out += '"';
        end else
        begin
            
            if str[ i ] = '\' then begin
                out += '\\';
            end else
            begin
                out += str[i];
            end;
            
        end;
        
    end;
    
    escapeshellarg := '"' + out + '"';
    
end;

function run_command( args: TStrArray; var output: TStrArray ): boolean;

var testfile   : string;
    //cmdline    : string;
    i          : integer = 0;
    //len        : integer = 0;
    outputlines: TStringList;
    memstream  : TMemoryStream;
    ourprocess : TProcess;
    numbytes   : LongInt;
    bytesread  : LongInt;

begin
    
    //writeln( #10#13'running: ', args[0], ' ', args.count, ' args'#10#13 );
    
    testfile := base_dir() + '../plugins/' + args[0] + '.php';
    
    if not fileExists( testfile ) then begin
        // command does not exists
        exit(false);
    end;
    
    memstream := TMemoryStream.Create;
    bytesread := 0;
    
    ourprocess:= TProcess.create( nil );
    
    ourprocess.executable := which( 'php' );
    
    ourprocess.parameters.add( testfile );
    ourprocess.parameters.add( '-ENV=site:' + term_get_env('site') );
    ourprocess.parameters.add( '-ENV=path:' + term_get_env('path') );
    ourprocess.parameters.add( '-ENV=user:' + term_get_env('user') );
    ourprocess.parameters.add( '-ENV=password:' + term_get_env( 'password' ) );
    
    for i := 1 to length( args ) - 1 do begin

        if args[i] = '' then
            ourprocess.parameters.add( '---empty---argument---fpc---tprocess---bug---' )
        else
            ourprocess.parameters.add( args[i] );
    end;
    
    //ourprocess.CommandLine := cmdline;
    
    ourprocess.Options := [ poUsePipes ];
    ourprocess.Execute;
    
    while true do begin
        
        MemStream.SetSize(BytesRead + 2048);
        
        // try reading it
        NumBytes := OurProcess.Output.Read((MemStream.Memory + BytesRead)^, 2048);
        if NumBytes > 0 // All read() calls will block, except the final one.
            then begin
                Inc(BytesRead, NumBytes);
            end else
                break // Program has finished execution.
    end;

    if BytesRead > 0 then WriteLn;

    MemStream.SetSize(BytesRead);

    OutputLines := TStringList.Create;
    OutputLines.LoadFromStream(MemStream);

    term_set_process_output( OutputLines, output );

    OutputLines.Free;
    OurProcess.Free;
    MemStream.Free;

    run_command := true;
    
end;

initialization
    
    basedir := paramstr(0);
    
end.