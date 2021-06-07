# Point Cloud Portal

Repository for PointCloudPortal, prepared for FOSSIGIS2021



### Requirements

* a webserver set up with apache or nginx
* PHP 
* PDO SQLite (optional)
* PotreeConverter (https://github.com/potree/PotreeConverter, https://github.com/potree/potree/)



### Setup

Due to the huge files that are being handled when working with point clouds, some files (nginx/apache and php) need alteration. The relevant parts of the `php.ini` and `nginx configuration` in use:

**php.ini**

```plaintext
upload_max_filesize = 10G
max_execution_time = 1800
max_input_time = -1
post_max_size = 10G
extension=pdo_sqlite
```

post_max_size needs to be >= upload_max_filesize (when uploading three files >= 10MB, the "upload_max_filesize" can be 10MB, the "post_max_size" needs to be 30MB)

**nginx configuration**

```plaintext
client_max_body_size 10G;
send_timeout         1800;  # TODO needed?
fastcgi_read_timeout 1800;

# for the conversion logs
location /logs/ {
    add_header Content-Type text/plain;
}
```

If a SQLite database is being used, the directory in which it is stored has to be writable (and maybe even executable for the webserver/php user).

### ToDo:
* Central path for strings for different versions of the portal (e.g. mobile)
* Full installation guide for the portal
