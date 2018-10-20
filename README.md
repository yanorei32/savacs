# SAVACS 0.0.3

**S**enior **A**ssisting **V**ideo &amp; **A**udio **C**ommunication **S**ystem.


## How to up the server

```bash
cd savacs-server
docker-compose up -d --build
```

## How to down the server

```bash
cd savacs-server
docker-compose down
```

## How to clean the server

```bash
docker volume rm savacsserver_contents savacsserver_db
```

## NOTE
`savacs-server/web/app/api/debug_*`, `savacs-server/web/app/api/admin_*` and `savacs-server/web/app/api/registration_photostand.php` are dangerous.
It is recommended to prohibit access.

