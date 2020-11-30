# CO2 Sensor

# Installation
```bash
make up
make install-composer
```

- Add `symfony.local` to `/etc/hosts`
- Now you can acess to: http://symfony.local/app_dev.php or http://symfony.local/app.php
- Adminer: http://localhost:8080
  - username: root
  - password: root
  - database: symfony


# Available commands
Build image
```bash
make up
```

Migrate DB execution
```bash
make migrate
```

Stop and remove containers
```bash
make down
```

# API documentation
- http://symfony.local/app_dev.php/api/doc

![API doc](https://github.com/minhtran17/sensor/blob/master/apidoc.png)
