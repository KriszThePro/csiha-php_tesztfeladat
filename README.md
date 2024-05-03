# SETUP

A _'wsl'_ parancs elhagyható, ha nem Linux Subsystemen futtatjuk az utasításokat. Én Windowson dolgoztam, nekem kellett.

### A projekt _'laravel'_ könyvtárában futtassuk a következő parancsokat:

- _composer install_
- _wsl ./vendor/bin/sail up_

### Miután a docker felinstallálta a dependencyket és felállt, futtassuk sorrendben a következő parancsokat dockeren belül (a sail app-ban!):

- _php artisan migrate_ : az adatbázis inicializálása
- _php artisan db:seed_ : dummy adatok betöltése az adatbázisba; minden futtatásnál 5 Task és 5 User adódik hozzá az adatbázishoz
- _npm install; npm run dev_ : a vite szerver elindítása

# Esetleges felmerülő hibák:

Valamelyik szolgáltatás (pl. mysql => 3306) portja foglalt, így nem tud elindulni a dockeren sem => nem lesz elérhető a weboldal.
Lehetséges megoldás: zárjuk be azokat a szolgáltatásokat, amik lefoglalták ezeket a portokat.

#

Ha minden jól ment, akkor a böngészőből el tudjuk érni az applikációt a következő linken:
http://localhost:80
