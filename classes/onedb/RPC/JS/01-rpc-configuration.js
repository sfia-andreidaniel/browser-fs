// we prefer this method of defining the rpc path in the window namespace, 
// in order to avoid accidentally altering this constant.
Object.defineProperty( window, "OneDBRpc", {
    "get": function() {
        
        // FEEL FREE TO ALTER THIS STRING IF YOUR INSTALLED ONEDB RPC IS LOCATED
        // ON OTHER URL.
        
        return window.BFS_RPC || '/onedb-rpc.php';
    }
});
