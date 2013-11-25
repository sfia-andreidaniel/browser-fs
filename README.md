## BrowserFS - The Web FileSystem

### History

BrowserFS is the next implementation of OneDB project - One Database to rule them all. As of
date of 17 november 2013, OneDB changed it's code name in "BrowserFS".

For history purposes, the version of the project was not restarted, in order to
make things more clear.

### New in version 2

* new category type: Aggregator, which combines the results of multiple paths into a single path
* new in v2: all commands running in php can be now executed in browser, via onedb rpc
* new in v2: integrated with transcoder cloud project, written in pure nodejs
* new in v2: unified configuration is now found in etc/onedb/onedb.ini
* new in v2: can operate now on multiple onedb sites in the same time
* new in v2: onedb command line shell from where admins can do all the operations they need

### Changed in version 2

* merged the categories and articles into a single collection
* merged methods .articles and .categories into a single method: .find
* eliminated sphinx text search engine and implemented native mongodb text searches
* renamed "Layout" type to "List" type
* improved File handling fallback type extensions methods

### Features removed in version 2

* Categories sort order

### Still to do in version 2 until a first release

Api:

* OneDB_Object.rename()
* OneDB_Object.copy()
* OneDB_Type_File: finish file storage stuff
* OneDB_Router class

Cli:

* commands: chown, chmod, mv, cp, route
* autocompleter: message "Do you want to display all 372 results?"

Administration:

* reimplement onedb administration application under BrowserOS project

Installer:

* script to install OneDB on computers