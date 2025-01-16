# Readability Addon for FreshRSS

This extension uses Readability, Mercury or the free FiveFilters implementation for fetching article content for selected feeds. 

 * The Readbility API is offered by [phpdockerio's Docker image](https://hub.docker.com/r/phpdockerio/readability-js-server) 
 * The Mercury API is offered by [wangqiru's Docker image](https://hub.docker.com/r/wangqiru/mercury-parser-api).
 * The FiveFilters API is offered by [heussd's Docker image](https://github.com/heussd/fivefilters-full-text-rss-docker).

There is also an alternative ![freshrss-af-readability](https://github.com/Niehztog/freshrss-af-readability), which uses the five-filters implementation natively and does not require any further setup.

## Usage

Configure the hosts in the configuration section of the extension according to your config. 

After ticking the feeds **YOU HAVE TO HIT SUBMIT AT THE BOTTOM**.

In the following example the hosts are configured according to the docker-compose.yml from this repo:

![image](https://store.eris.cc/uploads/2f9775d35ab6b7f89f66bbabc9f1fe4d.JPG?)

If all options are ticked, the FiveFilters parser will be used.

## Setup 

### Install the Extension

You have to clone the extension into your designated extension folder

### Deploy the Containers

If you run FreshRSS with docker-compose, this is what you need to add to your config. Maybe have a look at the example `compose.yml` file for complete reference.

```yml
  read:
    image: phpdockerio/readability-js-server
    restart: always

  merc:
    image: wangqiru/mercury-parser-api
    restart: always

  fivefilters:
    image: "heussd/fivefilters-full-text-rss:latest"
    environment:
      # Leave empty to disable admin section
      - FTR_ADMIN_PASSWORD=
    volumes:
      - "./rss-cache:/var/www/html/cache/rss"
    ports:
      - "127.0.0.1:8000:80"
    restart: always
```

Since both Readibility and Mercury offer their API on port 3000 and are only used locally, the reduced config suffices our purpose. The FiveFilters implementation on the other hand is a full blown feed customizer with an easy to use web interface, that will also generate whole feed urls for you to add to FreshRSS itself. I'd **highly** recommend to make this frontend available for your usage.

### Local Instance

If you run freshrss locally without docker, you can still use the docker images, but you'd have to forward the ports to you local host, so freshrss can access them:

```yml
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

  fivefilters:
    image: "heussd/fivefilters-full-text-rss:latest"
    environment:
      # Leave empty to disable admin section
      - FTR_ADMIN_PASSWORD=
    volumes:
      - "./rss-cache:/var/www/html/cache/rss"
    ports:
      - "127.0.0.1:3002:80"
    restart: always
```

In that case your value for the *Readability Host* is `http://127.0.0.1:3000`; for the *Mercury Host* it'd be `http://127.0.0.1:3001` and for the fivefilters container it's: `http://127.0.0.1:3002`

### No Containers ...

If you don't like containers at all, I can't help you.

## Notes

 * I won't provide help on problems inside the used containers.
 * The compose.yml file in this repo is just an example and doesn't auto-deploy this addon on FreshRSS
 



