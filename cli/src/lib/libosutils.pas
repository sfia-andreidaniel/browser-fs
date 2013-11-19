unit libosutils;

interface

uses classes;

function  which( binary_name: string ): string; // which( 'php' ) will return '/usr/bin/php'
function  base_dir(): string;                   // returns current program directory

// run a console command command_name (e.g. help), with given arguments args
function  run_command( args: TStringList ): boolean;

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

function run_command( args: TStringList ): boolean;

var testfile   : string;
    cmdline    : string;
    i          : integer = 0;
    len        : integer = 0;
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
    
    cmdline := which( 'php' ) + ' ' 
        + escapeshellarg( testfile ) + ' ' 
        + escapeshellarg( '-ENV=site:' + term_get_env( 'site' ) ) + ' ' 
        + escapeshellarg( '-ENV=path:' + term_get_env('path') ) + ' ' 
        + escapeshellarg( '-ENV=user:' + term_get_env( 'user' ) ) + ' '
        + escapeshellarg( '-ENV=password:' + term_get_env( 'password' ) );
    
    len := args.count;
    
    for i := 1 to len - 1 do begin
        cmdline := concat( cmdline, ' ', escapeshellarg( args[i] ) );
    end;
    
    //writeln( 'running: ', cmdline );
    
    memstream := TMemoryStream.Create;
    bytesread := 0;
    ourprocess:= TProcess.create( nil );
    ourprocess.CommandLine := cmdline;
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

    term_dump_process_output( OutputLines );

//    for NumBytes := 0 to OutputLines.Count - 1 do
//    begin
//        writeLn(OutputLines[NumBytes]);
//    end;

    OutputLines.Free;
    OurProcess.Free;
    MemStream.Free;

    run_command := true;
    
end;

initialization
    
    basedir := paramstr(0);
    
end.