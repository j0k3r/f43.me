echo 'server {
    listen 8100;
    server_name f43me.local;
    root /usr/share/nginx/html/public;

    index index.php index.html;

    access_log /var/log/nginx/f43me-access_log;
    error_log /var/log/nginx/f43me-error_log;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_index index.php;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include /etc/nginx/fastcgi_params;
    }

    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
}' > /etc/nginx/sites-available/default
