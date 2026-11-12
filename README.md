<h1 align="center">Вольт — интернет-магазин на Laravel</h1>

<p align="center">Учебный магазин электроники для серии о разработке REST API на канале Short Fuse in IT.</p>

<p align="center">
  <img src="public/shop-hero.webp" width="760" alt="Электроника на обложке магазина Вольт">
</p>

<p align="center">
  <strong>Laravel 13</strong> · <strong>PHP 8.5</strong> · <strong>Blade</strong> · <strong>SQLite / MySQL</strong> · <strong>PHPUnit</strong>
</p>

## Что можно попробовать

- Найти товар по названию, выбрать категорию и открыть карточку товара. В демо-каталоге **100 категорий и 500 товаров**.
- Добавить товары в корзину, изменить количество или удалить позиции. Корзина хранится в сессии и сохраняется при входе и регистрации.
- Оформить демо-заказ без онлайн-оплаты. Магазин сохраняет заказ и списывает остатки в одной транзакции.
- Создать аккаунт, войти по электронной почте и паролю или выйти из аккаунта. Оформить заказ можно и без регистрации.

## Запуск с SQLite

Понадобятся Git, Composer и PHP 8.5 с расширением `pdo_sqlite`. Витрина использует Blade и CSS из `public/`, поэтому для запуска Node.js не нужен.

```bash
git clone https://github.com/livej0urnal/laravel_api_learn_youtube.git
cd laravel_api_learn_youtube

composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed --class=CatalogSeeder
php artisan serve
```

Откройте [localhost:8000](http://localhost:8000). Чтобы проверить вход, создайте аккаунт на странице регистрации.

### Запуск через MAMP PRO

Создайте хост с корнем в папке `public/` и пустую базу MySQL. После установки зависимостей и генерации ключа укажите в `.env` значение `DB_CONNECTION=mysql`, затем заполните `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` и `DB_PASSWORD` по настройкам MAMP PRO. В `APP_URL` укажите адрес хоста.

```bash
php artisan migrate
php artisan db:seed --class=CatalogSeeder
```

Откройте адрес хоста MAMP PRO в браузере.

## Где искать код

| Файл | За что отвечает |
| --- | --- |
| [`routes/web.php`](routes/web.php) | Маршруты витрины, корзины, заказа и аккаунта |
| [`ShopCartController.php`](app/Http/Controllers/ShopCartController.php) | Карточка товара, корзина и оформление заказа |
| [`ShopAuthController.php`](app/Http/Controllers/ShopAuthController.php) | Регистрация, вход и выход |
| [`resources/views/`](resources/views) | Шаблоны страниц магазина |
| [`CatalogSeeder.php`](database/seeders/CatalogSeeder.php) | Категории и товары для демо |
| [`tests/Feature/`](tests/Feature) | Проверки основных сценариев магазина |

## Тесты

PHPUnit использует SQLite в памяти. Тесты проверяют регистрацию, вход, ограничение попыток входа, оформление заказа и изменение остатков.

```bash
vendor/bin/phpunit
```

## Серия на YouTube

Витрина служит основой для серии о REST API на Laravel. В ней можно показать, как добавить API каталога, фильтры и пагинацию к существующему приложению, а затем перейти к защищённым запросам для работы с корзиной и заказами.

[Short Fuse in IT на YouTube](https://www.youtube.com/channel/UC__PY1YqvcfmQTg_17BnBsQ)
