# eYaf 


* 安装Yaf
   
```php
    wget http://pecl.php.net/get/yaf-3.2.5.tgz
    tar zxvf yaf-3.2.5.tgz
    cd yaf-3.2.5
    phpize   [注意：phpize的路径，环境不同可能路径]
    ./configure --with-php-config=php-config  [注意：php-config的路径，环境不同可能路径]
    make && sudo make install
```  

 * 编辑php.ini
    * 在php.ini中增加如下代码
 ```php
     extension=yaf.so
     yaf.use_namespace=1
     yaf.name_suffix=0
     yaf.name_separator=\;
```

* 初始化项目
```php
    php vendor/bin/eyaf install
```
 
* nginx 配置

```php

server {
  listen 8080;
  server_name xx.com ;
  access_log off;
  index index.html index.htm index.php;
  # 项目路径
  root /var/www/xxxx/public;

  #error_page 404 /404.html;
  #error_page 502 /502.html;

  location / {
      try_files $uri $uri/ /index.php?$args;
  }

  #根据实际环境修改
  location ~ [^/]\.php(/|$) {
    #fastcgi_pass remote_php_ip:9000;
    fastcgi_pass unix:/dev/shm/php-cgi.sock;
    fastcgi_index index.php;
    include fastcgi.conf;
  }
  
  location ~ /\.ht {
    deny all;
  }
  location ~ /\.git {
      deny all;
  }
  location ~ /\.md {
        deny all;
    }
}



```
