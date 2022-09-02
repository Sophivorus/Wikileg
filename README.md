# Wikileg

Argentine laws in wiki format.

## Cronjobs

crontab -e
* * * * * /usr/local/php74/bin/php /home/sophivorus/sophivorus.com/wikileg/descargar-textos.php Decreto >> /home/sophivorus/sophivorus.com/wikileg/cronlog
* * * * * /usr/local/php74/bin/php /home/sophivorus/sophivorus.com/wikileg/main.php Decreto >> /home/sophivorus/sophivorus.com/wikileg/cronlog
crontab -r