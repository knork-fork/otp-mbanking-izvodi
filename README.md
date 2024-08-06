# otp-mbanking-izvodi

Parser for OTP mBanking PDF statements in Croatia.

## Installation

Start container:

```
docker-compose up --build -d
```

By default app should be available @ `localhost:60011`

Run composer install:

```
docker/composer install --no-interaction
```

Run setup script to setup local env variables and add host ssh keys to container (some endpoints may need access to container host):

```
scripts/setup.sh
```

Run migrations:

```
docker/console doctrine:migrations:migrate
```

Create an admin user(s):

```
docker/console dashboard:create-user
```

## Extras

Or if already built:

```
docker-compose up -d
```

Stop container:

```
docker-compose down
```

Enter the shell:

```
docker/shell
```
