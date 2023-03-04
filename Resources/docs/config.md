# Configure cycle bundle

Create the configuration file `cycle.yaml` in directory `config/packages/`

```yaml
cycle:
    dbal:
        connection:
            dsn: '%env(resolve:DATABASE_DSN)%'
            user: '%env(resolve:DATABASE_USER)%'
            password: '%env(resolve:DATABASE_PASSWORD)%'
    orm:
        schema:
            type: annotation
            dir: "%kernel.project_dir%/src/App/Entity"
        cache_dir: "%kernel.cache_dir%/cycle"
        relation:
            fk_create: false
            index_create: false
    migration: ~
```