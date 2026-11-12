<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedCatalog();
        });
    }

    private function seedCatalog(): void
    {
        $now = now();

        DB::table('categories')->upsert([
            ['name' => 'Смартфоны', 'slug' => 'smartphones', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Аксессуары', 'slug' => 'accessories', 'created_at' => $now, 'updated_at' => $now],
        ], ['slug'], ['name', 'updated_at']);

        $categoryIds = DB::table('categories')->pluck('id', 'slug');

        DB::table('products')->upsert([
            [
                'category_id' => $categoryIds['smartphones'],
                'name' => 'iPhone 17 Pro 256 ГБ',
                'slug' => 'iphone-17-pro-256gb',
                'description' => 'Смартфон Apple с памятью 256 ГБ.',
                'price' => '1199.00',
                'stock' => 12,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $categoryIds['accessories'],
                'name' => 'Зарядное устройство USB-C 30 Вт',
                'slug' => 'usb-c-charger-30w',
                'description' => 'Сетевое зарядное устройство с портом USB-C.',
                'price' => '29.90',
                'stock' => 35,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['slug'], ['category_id', 'name', 'description', 'price', 'stock', 'updated_at']);

        $categoryGroups = [
            [80, 'Кнопочные телефоны|Защищённые телефоны|Планшеты|Электронные книги|Стилусы|Чехлы для телефонов|Чехлы для планшетов|Защитные стёкла|Держатели для телефонов'],
            [350, 'Ноутбуки|Игровые ноутбуки|Ультрабуки|Моноблоки|Мини-ПК|Рабочие станции|Мониторы|Игровые мониторы|Портативные мониторы'],
            [40, 'Клавиатуры|Мыши|Коврики для мыши|Веб-камеры|USB-хабы|Док-станции|Подставки для ноутбуков|Сумки для ноутбуков|Графические планшеты|Цифровые микроскопы'],
            [65, 'Видеокарты|Процессоры|Материнские платы|Оперативная память|SSD|Жёсткие диски|Блоки питания|Корпуса для ПК|Кулеры для процессоров|Корпусные вентиляторы'],
            [35, 'Беспроводные наушники|Проводные наушники|Игровые гарнитуры|Портативные колонки|Саундбары|Сабвуферы|Студийные мониторы|Микрофоны|Звуковые карты|Портативные плееры'],
            [120, 'Телевизоры|Проекторы|Экраны для проекторов|ТВ-приставки|Медиаплееры|Кронштейны для телевизоров|Экшн-камеры|Фотокамеры|Объективы|Штативы'],
            [30, 'Роутеры|Mesh-системы|Репитеры Wi-Fi|Сетевые коммутаторы|Сетевые адаптеры|IP-камеры|Видеодомофоны|Умные розетки|Умные лампы|Датчики движения'],
            [45, 'Смарт-часы|Фитнес-браслеты|Умные кольца|GPS-трекеры|Умные весы|Ремешки для часов|Пульсометры|Велокомпьютеры|Нагрудные камеры|Спортивные наушники'],
            [25, 'Пауэрбанки|Кабели USB-C|Кабели HDMI|Беспроводные зарядки|Автомобильные зарядки|Сетевые фильтры|Источники бесперебойного питания|Карты памяти|USB-накопители|Картридеры'],
            [55, 'Игровые приставки|Геймпады|Игровые рули|VR-гарнитуры|Принтеры|Сканеры|Ламинаторы|Этикет-принтеры|Шредеры|3D-принтеры'],
        ];

        $demoCategories = [];
        $basePrices = ['smartphones' => 299, 'accessories' => 15];

        foreach ($categoryGroups as [$basePrice, $names]) {
            foreach (explode('|', $names) as $name) {
                $slug = 'demo-'.Str::slug($name);
                $demoCategories[] = [
                    'name' => $name,
                    'slug' => $slug,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $basePrices[$slug] = $basePrice;
            }
        }

        DB::table('categories')->upsert($demoCategories, ['slug'], ['name', 'updated_at']);

        $catalogCategories = DB::table('categories')
            ->whereIn('slug', array_keys($basePrices))
            ->get(['id', 'name', 'slug']);

        $models = ['Lite', 'Air', 'Plus', 'Pro', 'Ultra'];
        $products = [];

        foreach ($catalogCategories as $category) {
            $categoryModels = str_starts_with($category->slug, 'demo-')
                ? $models
                : array_slice($models, 0, 4);

            foreach ($categoryModels as $index => $model) {
                $products[] = [
                    'category_id' => $category->id,
                    'name' => "{$category->name} — Nova {$model}",
                    'slug' => "nova-{$category->slug}-".Str::lower($model),
                    'description' => "Демонстрационная модель Nova {$model}. Категория: {$category->name}.",
                    'price' => ($basePrices[$category->slug] + $index * 15).'.90',
                    'stock' => ($category->id + $index * 7) % 40,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($products, 100) as $batch) {
            DB::table('products')->upsert(
                $batch,
                ['slug'],
                ['category_id', 'name', 'description', 'price', 'stock', 'updated_at'],
            );
        }
    }
}
