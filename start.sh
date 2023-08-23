#!/bin/bash
BOT_FOLDER="/var/www/bot/easymoney/"

if [ -d ${BOT_FOLDER} ]; then
  echo "The project already exists, you cannot run this script again."

  exit
fi

echo "Update system."
sudo apt update

echo "Installing all required libraries."
sudo apt install nginx mysql-server phpmyadmin php8.1-fpm php-mysql php-dom php-curl supervisor composer

sudo ufw app list
sudo ufw allow 'Nginx HTTP'
sudo ufw status

sudo mkdir -p ${PROJECT}
sudo chown -R $USER:$USER ${PROJECT}

git clone https://github.com/money-bots/easymoney.git ${BOT_FOLDER}
sudo chmod -R 777 ${BOT_FOLDER}storage
cp ${BOT_FOLDER}.env.example ${BOT_FOLDER}.env
sudo composer install --working-dir=${BOT_FOLDER}
php ${BOT_FOLDER}artisan key:generate

MAINDB=binance
PASSWDDB="$(openssl rand -base64 12)"
sudo mysql -e "CREATE DATABASE ${MAINDB};"
sudo mysql -e "CREATE USER '${MAINDB}'@'%' IDENTIFIED WITH mysql_native_password BY '${PASSWDDB}';"
sudo mysql -e "GRANT ALL ON ${MAINDB}.* TO '${MAINDB}'@'%';"
sudo mysql -e "FLUSH PRIVILEGES;"

sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${PASSWDDB}/" ${BOT_FOLDER}.env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=${MAINDB}/" ${BOT_FOLDER}.env
sed -i "s/DB_HOST=.*/DB_HOST=localhost/" ${BOT_FOLDER}.env
sed -i "s/APP_ENV=.*/APP_ENV=local/" ${BOT_FOLDER}.env
sed -i "s/APP_NAME=.*/APP_NAME=EasyMoney/" ${BOT_FOLDER}.env
sed -i "s/SESSION_LIFETIME=.*/SESSION_LIFETIME=1200/" ${BOT_FOLDER}.env

echo -e 'server {
          listen 80;
          root '$BOT_FOLDER'public;
          index index.php index.html index.htm;
          location / {
              try_files $uri $uri/ /index.php?$query_string;
          }
          location ~ \.php$ {
              try_files $uri /index.php =404;
              fastcgi_split_path_info ^(.+\.php)(/.+)$;
              fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
              fastcgi_index index.php;
              fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
              include fastcgi_params;
          }
          location /phpmyadmin {
        root /usr/share/;
        index index.php index.html index.htm;
        location ~ ^/phpmyadmin/(.+\.php)$ {
                 try_files $uri =404;
                 root /usr/share/;
                 fastcgi_pass unix:/run/php/php8.1-fpm.sock;
                 fastcgi_index index.php;
                 fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
                 include /etc/nginx/fastcgi_params;
        }
        location ~* ^/phpmyadmin/(.+\.(jpg|jpeg|gif|css|png|js|ico|html|xml|txt))$ {
                 root /usr/share/;
        }
         }
      }
' > /etc/nginx/sites-available/bot

sudo ln -s /etc/nginx/sites-available/bot /etc/nginx/sites-enabled/
sudo unlink /etc/nginx/sites-enabled/default
sudo nginx -t

echo -e '[program:invest-worker]
        process_name=%(program_name)s_%(process_num)02d
        command=php '$BOT_FOLDER'artisan queue:work --tries=3
        autostart=true
        autorestart=true
        stopasgroup=true
        killasgroup=true
        user=root
        numprocs=1
        redirect_stderr=true
        stdout_logfile='$BOT_FOLDER'storage/logs/worker.log
        stopwaitsecs=3600
' > /etc/supervisor/conf.d/worker.conf

php ${BOT_FOLDER}artisan migrate

sed -i "s/APP_ENV=.*/APP_ENV=prodaction/" ${BOT_FOLDER}.env
sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" ${BOT_FOLDER}.env

sudo systemctl reload nginx
service supervisor restart
