## SETUP

A wsl parancs elhagyható, ha nem Linux Subsystemen futtatjuk az utasításokat.
Én Windowson dolgoztam, nekem kellett.

-   a 'laravel' könyvtárban a következő parancs futtatása:

    -   wsl ./vendor/bin/sail up

-   miután a docker felinstallálta a dependencyket és felállt, futtassuk sorrendben a következő parancsokat dockeren belül (a sail app-ban!):
    -   php artisan migrate : az adatbázis inicializálása
    -   php artisan db:seed : dummy adatok betöltése az adatbázisba; minden futtatásnál 5 Task és 5 User adódik hozzá az adatbázishoz
    -   npm install; npm run dev : a vite szerver elindítása

Esetleges felmerülő hibák:

-   Valamelyik szolgáltatás (pl. mysql => 3306) portja foglalt, így nem tud elindulni a dockeren sem => nem lesz elérhető a weboldal
    Lehetséges megoldás: zárjuk be azokat a szolgáltatásokat, amik lefoglalták ezeket a portokat.

Ha minden jól ment, akkor a böngészőből el tudjuk érni az applikációt a következő linken:
http://localhost:80
