Front-end for Hachschara
========================
License
-------
    Code for the Front-end of hachschara.juedische-geschichte-online.net

    (C) 2023-2024 Moses Mendelssohn Center for European-Jewish Studies (MMZ)
        Daniel Burckhardt


    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    A public copy of the site must not give the impression of being
    operated by the MMZ.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

Installation
------------
### Requirements

- PHP >= 8.2 (check with `php -v`)
- composer (check with `composer -v`; if it is missing, see https://getcomposer.org/)

### Adjust Local Settings

- vi .env.local (not commited)

Directory Permissions for

cache and logs

- sudo setfacl -R -m u:www-data:rwX ./var
- sudo setfacl -dR -m u:www-data:rwX ./var

bundles

- sudo setfacl -R -m u:www-data:rwX ./public/bundles
- sudo setfacl -dR -m u:www-data:rwX ./public/bundles

### SCSS compilation
In a `prod` environment, generate `public/css/base.css`

- ./bin/console scss:compile

Development Notes
-----------------
### Local Web Server

- php -S localhost:8000 -t public
- http://localhost:8000

### Internationalization
Translate messages and routes according to settings in
`jms_translation.configs.app`

    ./bin/console jms:translation:extract de --config=app


### Project Setup

- add to config/packages/scssphp.yaml
```
    scssphp:
        enabled: '%kernel.debug%'
        autoUpdate: '%kernel.debug%'
        assets:
            "css/base.css":
                src: "assets/scss/base.scss"
                sourceMap: true
```
