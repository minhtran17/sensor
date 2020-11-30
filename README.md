# CO2 Sensor

# Installation
```bash
make up
make install-composer
```

- Add `symfony.local` to `/etc/hosts`
- Now you can acess to: http://symfony.local/app_dev.php or http://symfony.local/app.php


# Available commands
Build image
```bash
make build
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
