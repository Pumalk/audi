<?php
// Начало сессии для работы с сессионными данными
session_start();

// Динамическое изменение доступных настроек
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Настройки подключения к базе данных
$host = '127.127.126.26'; // Хост базы данных
$user = 'root'; // Имя пользователя базы данных
$password = ''; // Пароль пользователя базы данных
$database = 'audi'; // Имя базы данных
$port = 3306; // Порт для подключения к базе данных

// Создание подключения к базе данных
$conn = new mysqli($host, $user, $password, $database, $port);

// Проверка успешности подключения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error); // Завершение работы скрипта при ошибке подключения
}

// Генерация CSRF-токена для защиты от CSRF-атак
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Создание уникального токена
}

// Настройки для загрузки файлов
define('UPLOAD_DIR', 'account/uploads/avatars/'); // Директория для загрузки файлов
@mkdir(UPLOAD_DIR, 0755, true); // Создание директории, если она не существует, с правами доступа 0755
define('MAX_FILE_SIZE', 8 * 1024 * 1024); // Максимальный размер загружаемого файла (8MB)

// Функция для конвертации размеров
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch ($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

// Проверка доступности изменения настроек
if (!ini_get('file_uploads')) {
    die("Ошибка: загрузка файлов отключена на сервере.");
}
?>