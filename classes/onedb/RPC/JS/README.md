## RPC files for OneDB v2

Here are the files that assembled, will create a single javascript file
that is forming the OneDB backend administration basecode.

The aim of the RPC is to provide native functionality on the browser in
the same manner that the functionality is provided on the client side.

### Development guidelines

When the RPC.Assembler assembles the files, it takes the files, and first
it sorts them by the numbered prefix.

You see that in this folder all javascript files are prefixed with an index.

Index meaning

01 .. 09 - These are core files, required to implement transportation,
           encapsulation, serialization, and deserialization of the rpc


10 .. 19 - These are base classes, that are implementing the other types
           of OneDB objects

20 .. 29 - Reserved for OneDB.Client.* classes

30 .. 39 - Reserved for OneDB.Iterator.* classes

40 .. 49 - Reserved for OneDB.Object.* classes

50 .. 89 - Reserved for OneDB.Type.* classes

90 .. 99 - Other files, mostly for test purposes.

### What are the basics that this RPC is working?

The idea of this RPC camed from reading on wikipedia site an article about
muxers (used in video, telephony, etc ).

What does a muxer does (from what I understood, of course):

A muxer takes an amount of 'streams', and encodes them into a single stream.

What does a demuxer does:

A demuxer is the reverse of a muxer, which means that is taking a muxed single
stream of data, and re-transpose it in the original streams that were received
at the input of the muxer.

Ok, so how does a muxer and a demuxer help me in this RPC?

This is an ascii representation of the data flow. I don't know if the representation
can be seen into a markup viewer, so the big picture will be observed when viewing
this file in a text editor.


  PHP CLASSES RUNNING ON SERVER  | RPC SERVER SCRIPT HANDLER      | JAVASCRIPT BROWSER CLASSES
.................................+................................+..............................
  class foo php implementation ( |                                | class foo js implementation (
                                 |                                |
     foo::__mux()                | <---- transfer foo's snapshot  | foo.prototype.__demux()
        argument 1               | implemented in browser by      |
        argument 2               | demuxing stream of arguments   |
        ...                      | and running foo::__mux( args ) |
        argument n               | on php stack                   |
     end of foo::__mux()         |                                | end of foo.prototype.__demux()
                                 |                                |
                                 |                                |
                                 |                                |
                                 |                                |
                                 |                                |
                                 |                                |
     -- foo->__demux()           | ----> transfer foo's snapshot  | foo.prototype.__mux()
                                 | implemented on server side     |    argument 1
                                 | to a foo's implementation      |    ...
     -- end of foo->__demux()    | implemented on browser side.   |    argument n
                                 |                                | end of foo.prototype.__mux()
                                 |                                |
                                 |                                |
                                 |                                |
                                 |                                |
     -- other methods native     | <--> call mthds of foo's imple-| -- other foo's methods with the
        written in php           | mented on browser, mux result  |   same name of the methods written
     --                          | and send it back to browser    |   in php stack
                                 |                                |
  ) // END OF CLASS FOO (PHP)    |                                | ) // END OF CLASS FOO (JS)
                                 |                                |
.................................+................................+..................................
                                 |                                |
  OTHER CLASSES                  | same process from upper cell   | OTHER CLASSES WITH THE SAME NAME
                                 |                                | OF CLASSES WRITTEN ON SERVER SIDE
.................................+................................+..................................


So, we can transfer a class instance to browser and from browser via the mux and demux methods,
and we can invoke class methods on browser side, run the methods on the client side, and pass back
the result to the browser side.

When I say a class instance, I want to say that via muxing, a "snapshot" of the class is spawned
on the other side, with the exact state.

So again:

Create class on Browser, initialize it from Server, alter it, spawn it back to server, run methods,
spawn it back to Browser.

This is what this RPC is about, and I would be glad to answer and clarify things for the persons
which are wanting to enter in details or did not understand well what is all about.

Thank you,

\Andrei Sfia,
sfia.andreidaniel@gmail.com