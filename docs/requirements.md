# Requirements

## SQLite

Calibre and therefore BicBucStriim require that SQLite3 is installed. Since Calibre version 5 the SQLite library
must include the [FTS5 extension](https://sqlite.org/fts5.html) for full-text search. Without this extension you will get
strange error messages like `SQLSTATE[HY000]: General error: 11 malformed database schema`. 

To test if your SQLite version supports that open the SQLite CLI and search for it with the SQL command
`select sqlite_compileoption_used('SQLITE_ENABLE_FTS5');`. Example:

```shell
> sqlite3
SQLite version 3.32.3 2020-06-18 14:16:19
Enter ".help" for usage hints.
Connected to a transient in-memory database.
Use ".open FILENAME" to reopen on a persistent database.

sqlite> select sqlite_compileoption_used('SQLITE_ENABLE_FTS5');
1 
```

If the SELECT returns `1` the FTS5 extension is included. 