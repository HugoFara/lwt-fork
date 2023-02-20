# Prerequisites:

docker  
docker compose plugin

# How to use:

0.  create directory somewhere you want
1.  copy [docker-compose.yml](docker-compose.yml)
2.  copy [.env.sample](.env.sample) to `.env` ( optionally edit it )
3.  run
```bash
docker compose up -d
```

By default the server can be accessed on port 8010 (<http://localhost:8010>).

To remove the created containers run:

```bash
docker compose  down
```

All user data will be stored in local folder `./lwt_db_data`.