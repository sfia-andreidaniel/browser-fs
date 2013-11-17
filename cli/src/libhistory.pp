unit libhistory;

interface

    type      console_history = array[ 1..65534 ] of string;
    
    procedure init_history;
    
    function  get_history ( index: integer ): string;
    function  push_history( index: integer; command: string ): boolean; 
    function  prev_history(): string;
    function  next_history(): string;
    procedure reset_history_index();

implementation

var history        : console_history;    // history entries
    history_length : integer;            // current history length
    history_index  : integer = 1;        // current writing index in history

// initialize history for the issued commands
procedure init_history();
begin
    history_index := 0;
    history_length := 0;
end;

// fetches the previous entry from commands history.
function prev_history(): string;
begin
    //writeln( #10#13'prev_history: ', history_index , #10#13 );
    if history_index >= 1 then
    begin
        prev_history := history[ history_index ];
        history_index -= 1;
    end else
    begin
        //writeln( 'cannot prev');
        prev_history := '';
    end;
end;

// fetches the next entry from commands history.
function next_history(): string;
begin
//    writeln( #10#13'next_history: ', history_index , #10#13 );
    
    if history_index < history_length then
    begin
        history_index += 1;
        next_history := history[ history_index ];
    end else
    begin
//        writeln( 'cannot next');
        next_history := '';
    end;
end;

// retrieves a command at specified history index
function get_history( index: integer ): string;
begin
    if ( (index < 1) or (index > history_length) )
        then get_history := ''
        else get_history := history[ index ];
end;


procedure reset_history_index();
begin
    history_index := history_length;
end;


// sets an entry  at specified index in history.
// @param index if -1, appends at the end of the history
function push_history( index: integer; command: string ): boolean;
begin

//    writeln( #13#10'push history: ', command, #13#10 );

    if command = '' then begin
        push_history := false;
//        write( 'no push to history command empty');
        exit;
    end;

    if ( index = -1 ) then
    begin
        
        if ( history[ history_length ] = command ) then
        begin
            push_history := true;
            exit;
        end;
        
        history_length += 1;
        history[ history_length ] := command;
        history_index := history_length;
        push_history := true;
    end
    else begin
        if ( ( index < 1 ) or ( index > history_length ) ) then 
            begin
                push_history := false;
            end
            else begin
                history[ history_length ] := command;
                push_history := true;
            end;
    end;

end;

initialization
    
    history_index := 1;
    
end.