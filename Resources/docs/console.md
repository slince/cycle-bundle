# Supported Commands

You can use the following command to view the supported commands

```bash
$ php bin/console cycle
```

Available commands for the "cycle" namespace:

```bash
  cycle:db:list             Get list of available databases, their tables and records count
  cycle:db:table            Describe table schema of specific database
  cycle:migration:init      Init migrations component (create migrations table)
  cycle:migration:migrate   Perform one or all outstanding migrations
  cycle:migration:replay    Replay (down, up) one or multiple migrations
  cycle:migration:rollback  Rollback one (default) or multiple migrations
  cycle:migration:status    Get list of all available migrations and their statuses
  cycle:schema:migrate      Generate ORM schema migrations
  cycle:schema:render       Render available CycleORM schemas
  cycle:schema:sync         Sync Cycle ORM schema with database without intermediate migration (risk operation)
  cycle:schema:update       Update (init) cycle schema from database and annotated classes

```
