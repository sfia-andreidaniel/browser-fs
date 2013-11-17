program procoutlarge;
{
    Copyright (c) 2004-2011 by Marc Weustink and contributors
 
    This example is created in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
}
// This is a 
// WORKING
// demo program that shows
// how to launch an external program
// and read from its output.
 
uses
  Classes, Process, SysUtils;
 
const
  READ_BYTES = 2048;
 
var
  OurCommand: String;
  OutputLines: TStringList;
  MemStream: TMemoryStream;
  OurProcess: TProcess;
  NumBytes: LongInt;
  BytesRead: LongInt;
 
begin
  // A temp Memorystream is used to buffer the output
  MemStream := TMemoryStream.Create;
  BytesRead := 0;
 
  OurProcess := TProcess.Create(nil);
  // Recursive dir is a good example.
  OurCommand:='invalid command, please fix the IFDEFS.';
  {$IFDEF Windows}
  //Can't use dir directly, it's built in
  //so we just use the shell:
  OurCommand:='cmd.exe /c "dir /s c:\windows\"';
  {$ENDIF Windows}
  {$IFDEF Unix}
  //Needs to be tested on Linux/Unix:
  OurCommand := 'ls';
  {$ENDIF Unix}
  writeln('-- Going to run: ' + OurCommand);
  OurProcess.CommandLine := OurCommand;
 
  // We cannot use poWaitOnExit here since we don't
  // know the size of the output. On Linux the size of the
  // output pipe is 2 kB; if the output data is more, we
  // need to read the data. This isn't possible since we are
  // waiting. So we get a deadlock here if we use poWaitOnExit.
  OurProcess.Options := [poUsePipes];
  WriteLn('-- External program run started');
  OurProcess.Execute;
  while True do
  begin          
    // make sure we have room
    MemStream.SetSize(BytesRead + READ_BYTES);

    // try reading it
    NumBytes := OurProcess.Output.Read((MemStream.Memory + BytesRead)^, READ_BYTES);
    if NumBytes > 0 // All read() calls will block, except the final one.
    then begin
      Inc(BytesRead, NumBytes);
      Write('.') //Output progress to screen.
    end else 
      BREAK // Program has finished execution.
  end;
  if BytesRead > 0 then WriteLn;
  MemStream.SetSize(BytesRead);
  WriteLn('-- External program run complete');
 
  OutputLines := TStringList.Create;
  OutputLines.LoadFromStream(MemStream);
  WriteLn('-- External program output line count = ', OutputLines.Count, ' --');
  for NumBytes := 0 to OutputLines.Count - 1 do
  begin
    WriteLn(OutputLines[NumBytes]);
  end;
  WriteLn('-- Program end');
  OutputLines.Free;
  OurProcess.Free;
  MemStream.Free;
end.