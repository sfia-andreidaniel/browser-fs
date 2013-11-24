unit libutils;

interface

type
    TStrArray     = array of string;
    TCommandChain = array of TStrArray;

function is_int( s: string ): boolean;

procedure push( var a: TStrArray;     s: String );
procedure push( var a: TCommandChain; s: TStrArray );

function  str_split( str: string; delimiter: string ): TStrArray;
function  preg_match( str: string; pattern: string ): boolean;

implementation

uses oldregexpr, strings;

function is_int( s: string ): boolean;
var i: integer;
begin
    if s = '' then exit( false );
    for i := 1 to length( s ) do
    begin
        case s[i] of
            '0'..'9': begin
            end else exit( false );
        end;
    end;
    exit( true );
end;

procedure push( var a: tstrarray; s: string );
var len: integer;
begin
    len := length( a );
    setlength( a, len + 1 );
    a[ len ] := s;
end;

procedure push( var a: TCommandChain; s: TStrArray );
var len: integer;
begin
    len := length( a );
    setLength( a, len + 1 );
    a[ len ] := s;
end;

function  str_split( str: string; delimiter: string ): TStrArray;
var out: TStrArray;
    i: integer;
    n: integer;
    item: string;
    dlen: integer;
begin
    
    n := length( str );
    i := 1;
    item := '';
    
    dlen := length( delimiter );
    
    while i <= n do begin
        if copy( str, i, dlen ) = delimiter then
        begin
            push( out, item );
            item := '';
            i := i + dlen - 1;
        end else
        begin
            item := concat( item, str[i] );
        end;
        
        inc( i );
    end;
    
    push( out, item );
    
    exit( out );
    
end;

function  preg_match( str: string; pattern: string ): boolean;
var initok: boolean;
         r: tregexprengine;
     index,
       len: longint;
    result: boolean;
         p: pchar;
        p1: pchar;

begin

    p := stralloc( length( pattern ) + 1 );
    strpcopy( p, pattern );

    initok := GenerateRegExprEngine( p, [ ref_caseinsensitive ], r );
    
    if not initok then
    begin
        DestroyregExprEngine( r );
        strdispose( p );
        exit( false );
    end;
    
    p1 := stralloc( length( str ) + 1 );
    strpcopy( p1, str );
    
    if not(RegExprPos( r, p1, index, len ) ) then
    begin
        DestroyregExprEngine( r );
        strdispose( p );
        strdispose( p1 );
        exit( false );
    end else
    begin
        DestroyregExprEngine( r );
        strdispose( p );
        strdispose( p1 );
        exit( true );
    end;
end;

end.