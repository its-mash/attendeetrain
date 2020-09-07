# attendeetrain
Server setup instructions: https://scotch.io/tutorials/deploying-laravel-to-digitalocean

SSL setup : https://www.digitalocean.com/community/tutorials/how-to-create-a-self-signed-ssl-certificate-for-nginx-in-ubuntu-16-04
## Start up
1. copy .env.examples to .env and update database info
2. Install packages using `sudo apt install php-gd php-zip` && `composer install`
3. `php artisan key:generate`
4. `php artisan migrate`
5. `valet link`
6. `valet secure attendeetrain`
7. `valet share`

## Links
QR code: https://attendeetrain.test/attendance/CS118/1

Gallery: https://attendeetrain.test/gallery/cs118/1

