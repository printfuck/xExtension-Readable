# Readability Addon for FreshRSS

Using Readability or Mercury this addon fetches article content from any feed. 

The Readbility API is offered by [phpdockerio's Docker image](https://hub.docker.com/r/phpdockerio/readability-js-server) and the Mercury API is offered by [wangqiru's Docker image](https://hub.docker.com/r/wangqiru/mercury-parser-api).

## Usage

Configure the hosts in the configuration section of the extension according to your config. 

After ticking the feeds **YOU HAVE TO SCROLL DOWN AND HIT SUBMIT**.

In the following example the Readability host is configured according to the docker-compose.yml from this repo:

If both options are ticked, the Readability parser will be used.

## Setup 

If you run FreshRSS from docker-compose, this is what you need to add to your config. Maybe have a look at the example `docker-compose.yml` file for complete reference.

```
  read:
    image: phpdockerio/readability-js-server
    restart: always

  merc:
    image: wangqiru/mercury-parser-api
    restart: always
```

Since both images offer their API on port 3000 and are only used locally, this reduced config suffices our purpose.

### local instance

If you run freshrss locally without docker, you can still use the docker images, but you'd have to forward the ports to you local host, so freshrss can access them:

```
services:
  read:
    image: phpdockerio/readability-js-server
    restart: always
    ports:
      - 127.0.0.1:3000:3000

  merc:
    image: wangqiru/mercury-parser-api
    restart: always
    ports:
      - 127.0.0.1:3001:3000
```

In that case your value for the *Readability Host* is `http://127.0.0.1:3000` and for the *Mercury Host* it'd be `http://127.0.0.1:3001`.

### Not docker ...

If you don't like containers at all, I can't help you. 

## Notes

 * On a few occasions the parsers crashed - resulting in freshrss responding slowly. But that happened two years ago, so it's probably fixed by now.
 



