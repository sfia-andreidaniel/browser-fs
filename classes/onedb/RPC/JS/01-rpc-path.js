// we prefer this method in order to avoid accidentally altering this
// constant.
Object.defineProperty( window, "OneDBRpc", {
    "get": function() {
        
        // FEEL FREE TO ALTER THIS STRING IF YOUR INSTALLED ONEDB RPC IS LOCATED
        // ON OTHER URL.
        
        return '/onedb-rpc.php';
    }
});
