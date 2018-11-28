Home project


Supervisor config:

[program:boiler]
autostart = true
autorestart = true
command =  bash -c "/usr/bin/php /data/www/app.php http://192.168.0.201"
#environment=SECRET_ID="secret_id",SECRET_KEY="secret_key_avoiding_%_chars"
stdout_logfile = /var/log/supervisor/boiler.log
stderr_logfile = /var/log/supervisor/boiler.err.log
startretries = 1000
user = root
