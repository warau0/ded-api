## Draw Every Day API


#### Important PHP.ini configs:

;;;;;;;;;;;;;;;;;;;
; Resource Limits ;
;;;;;;;;;;;;;;;;;;;
max_execution_time = 30
max_input_time = 60
memory_limit = 512M

;;;;;;;;;;;;;;;;;
; Data Handling ;
;;;;;;;;;;;;;;;;;
post_max_size = 35M

;;;;;;;;;;;;;;;;
; File Uploads ;
;;;;;;;;;;;;;;;;
file_uploads = On
upload_max_filesize = 3M
max_file_uploads = 10

;;;;;;;;;;;;;;;;;;;;;;
; Dynamic Extensions ;
;;;;;;;;;;;;;;;;;;;;;;
extension=php_curl.dll
extension=php_fileinfo.dll
extension=php_gd2.dll
extension=php_mbstring.dll
extension=php_exif.dll
extension=php_openssl.dll
extension=php_pdo_mysql.dll
