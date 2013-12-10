var BFS_View_Sorters = BFS_View_Sorters || {};

BFS_View_Sorters.type = function( item1, item2 ) {
    var type1 = item1.inode.type.split( '.' )[0].toLowerCase(),
        type2 = item2.inode.type.split( '.' )[0].toLowerCase();
    
    return type1 == 'category' &&
           type2 == 'category'
        ? item1.inode.name.toLowerCase().strcmp( item2.inode.name.toLowerCase() )
        : ( type1 == 'category'
            ? -1
            : 1
        );
};

BFS_View_Sorters.name = function( inode1, inode2 ) {
    return inode1.inode.name.toLowerCase().strcmp( inode2.inode.name.toLowerCase() );
};
