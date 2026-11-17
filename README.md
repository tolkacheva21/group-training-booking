ЗАПУСК
.env создать
npm install
composer install
php artisan config:clear
php artisan key:generate
php artisan migrate
npm run build
php artisan serve

МИГРАЦИИ
php artisan migrate:reset
php artisan migrate
php artisan db:seed --class=RoleSeeder