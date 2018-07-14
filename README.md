# SAVACS

**S**enior **A**ssisting **V**ideo &amp; **A**udio **C**ommunication **S**ystem.

In development.


## How to up the server

```bash
cd server
docker-compose -p savacs up -d --build
```

## How to down the server

```bash
cd server
docker-compose -p savacs down
```

## How to clean the server

```bash
docker volume rm savacs_db
```

