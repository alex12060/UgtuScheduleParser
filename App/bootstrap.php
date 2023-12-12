<?php

use App\App;

require 'vendor/autoload.php';

# Подключаем роутинг
$router = new AltoRouter();

# Объявляем имя файла
$inputFileName = APP . '/files/schedule.xlsx';
# Прокидываем url
$url = 'https://ugtu.net/schedule/download_file/5-kurs-feuiit-osen-2023-2024-uch-god/zaochnaya-forma-obucheniya-5-kurs-feuiit-osen-2023-2024-uch-god.xlsx';

require_once APP . '/Router/web.php';

$App = new App();
$App->init($url, $inputFileName);
$App->afterRoute($router);