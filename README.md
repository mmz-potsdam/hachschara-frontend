Front-end for Hachschara
========================

Installation
------------
Adjust Local Settings

- vi .env.local (not commited)

Directory Permissions for cache and logs

- sudo setfacl -R -m u:www-data:rwX ./var
- sudo setfacl -dR -m u:www-data:rwX ./var

Generate `public/css/base.css`

- ./bin/console scss:compile

Development Notes
-----------------
Project Setup

- add to config/packages/scssphp.yaml
```
    scssphp:
        enabled: '%kernel.debug%'
        autoUpdate: '%kernel.debug%'
        assets:
            "css/base.css":
                src: "public/assets/scss/base.scss"
                sourceMap: true
```

Local Web Server
- php -S localhost:8000 -t public
- http://localhost:8000

Translate messages and routes according to settings in
`jms_translation.configs.app`

    ./bin/console translation:extract de --config=app
