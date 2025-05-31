<?php
// Включение отображения ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение файла конфигурации
require_once 'config.php';

// Старт сессии (если еще не запущена)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Логика для показа попапа после редиректа
$showPopup = false;
if (isset($_SESSION['show_popup'])) {
    $showPopup = true; // Устанавливаем флаг для отображения попапа
    unset($_SESSION['show_popup']); // Удаляем флаг из сессии
}

// Обработка закрытия попапа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_popup'])) {
    $showPopup = false; // Закрываем попап
    header("Location: " . $_SERVER['PHP_SELF']); // Перенаправляем на ту же страницу
    exit();
}

// Логика для показа видео только при первом посещении
if (!isset($_SESSION['video_shown'])) {
    $_SESSION['video_shown'] = true;
    $showVideo = true;
} else {
    $showVideo = false;
}

// Генерация CSRF-токена для защиты от CSRF-атак
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Генерация уникального токена
}

// Проверка авторизации пользователя
if (!isset($_SESSION['user'])) {
    header("Location: auth/login.php"); // Редирект на страницу авторизации
    exit();
}

// Получение данных пользователя из сессии
$user = $_SESSION['user'];
$errors = []; // Массив для хранения ошибок

// Обработка формы заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ошибка CSRF');
    }

    // Валидация и санитизация данных из формы
    $phone = trim($_POST['phone'] ?? '');
    $car_id = trim($_POST['car_id'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Проверка формата телефона
    if (!preg_match('/^\+7\d{10}$/', $phone)) {
        $errors['phone'] = "Телефон должен быть в формате +79161234567";
    }

    // Проверка выбора автомобиля
    if (empty($car_id) || !isset($cars[$car_id])) {
        $errors['car_id'] = "Выберите модель автомобиля";
    }

    // Если ошибок нет, сохраняем данные в базу данных
    if (empty($errors)) {
        try {
            // Подготовка SQL-запроса для вставки данных
            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, car_id, phone, message, order_date) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iiss", $user['id'], $car_id, $phone, $message);
        
            $stmt->execute();

            // Устанавливаем флаг для показа попапа
            $_SESSION['show_popup'] = true;

            // Редирект для предотвращения повторной отправки формы
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (Exception $e) {
            $errors[] = "Ошибка при выполнении запроса: " . $e->getMessage();
        }
    }
}

// Данные об автомобилях
$cars_result = $conn->query("SELECT * FROM cars");
if (!$cars_result) {
    die("Ошибка при выполнении запроса: " . $conn->error);
}

$cars = [];
while ($row = $cars_result->fetch_assoc()) {
    $cars[$row['id']] = [
        'model_name' => $row['model_name'],
        'category' => $row['category'],
        'img' => $row['main_image_path'],
        'link' => $row['detail_page_path']
    ];
}

// Обработка поискового запроса
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$filteredCars = [];

if (!empty($searchQuery)) {
    foreach ($cars as $id => $data) {
        if (stripos($data['model_name'], $searchQuery) !== false) {
            $filteredCars[$id] = $data;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <!-- Метаданные страницы -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audi - Главная страница</title>
    <!-- Подключение фавиконок -->
    <link rel="apple-touch-icon" sizes="180x180" href="../media/фавиконки/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../media/фавиконки/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../media/фавиконки/favicon-16x16.png">
    <link rel="manifest" href="../media/фавиконки/site.webmanifest">
    <!-- Подключение CSS -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- Попап для подтверждения заказа -->
<?php if ($showPopup): ?>
    <div class="popup show">
        <p>Спасибо за заказ!<br>Ваша заявка отправлена на рассмотрение.<br>В ближайшее время с вами свяжется наш специалист.</p>
        <form method="POST">
            <button type="submit" name="close_popup" class="close-popup">Закрыть</button>
        </form>
    </div>
<?php endif; ?>

<!-- Видео при первом посещении -->
<?php if ($showVideo): ?>
<div class="video-container">
    <video autoplay muted>
        <source src="./media/медиа для страницы/video.mp4" type="video/mp4">
    </video>
</div>
<?php endif; ?>

<!-- Кнопка для прокрутки вверх -->
<a href="#top" id="scrollToTop">⬆</a>
<div id="top"></div>

<!-- Шапка сайта -->
<header>
    <div class="logo">
        <img src="./media/медиа для страницы/лого.png" alt="Audi Logo">
    </div>
    
    <a href="account/account.php" class="auth-button">Личный кабинет</a>
    
    <div class="order-button2">
        <a href="#forma" class="order-button">Заказать автомобиль</a>
    </div>
    
    <div class="search-form">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Поиск по названию..." 
                value="<?= htmlspecialchars($searchQuery) ?>">
            <button type="submit">Найти</button>
        </form>
    </div>
</header>

<!-- Навигация по категориям -->
<nav>
    <div>Категории</div>
    <div>
        <ul>
            <li><a href="#kuzov-l">Легковые</a></li>
            <li><a href="#kuzov-v">Внедорожники</a></li>
            <li><a href="#kuzov-s">S-Class</a></li>
        </ul>
    </div>
</nav>
    <div class="opisanie">Добро пожаловать в мир совершенства! 
    Здесь, среди элегантных линий и безупречного дизайна, рождаются автомобили, которые воплощают мечты о свободе и комфорте.
    Audi – это не просто машина, это произведение искусства, созданное для тех, кто ценит качество, инновации и уникальный стиль.
        Каждая модель Audi – это сочетание передовых технологий и традиций немецкого качества.
        Инженеры компании неустанно работают над тем, чтобы каждый автомобиль стал идеальным продолжением вашего характера.
        Мощный двигатель, интеллектуальные системы безопасности, комфортная подвеска – всё это создано для того, чтобы вы наслаждались каждым километром пути.
        Audi – это выбор тех, кто стремится к лучшему. 
        Присоединяйтесь к миру престижа и эксклюзивности, выберите свой идеальный Audi сегодня!
</div>

<!-- Основной контент с категориями автомобилей -->
<main class="car">
    <?php if (!empty($searchQuery)): ?>
    <div class="kuzov">Результаты поиска: "<?= htmlspecialchars($searchQuery) ?>"</div>
    
    <?php if (!empty($filteredCars)): ?>
        <?php foreach ($filteredCars as $id => $data): ?>
            <div class="site-link">
                <img src="<?= $data['img'] ?>" alt="<?= htmlspecialchars($data['model_name']) ?>">
                <h2><?= htmlspecialchars($data['model_name']) ?></h2>
                <a href="<?= $data['link'] ?>">Описание</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-results">
            <p>По вашему запросу "<?= htmlspecialchars($searchQuery) ?>" не найдено автомобилей</p>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="kuzov" id="kuzov-l">Легковые</div>
    <?php foreach ($cars as $id => $data): ?>
        <?php if ($data['category'] === 'kuzov-l'): ?>
            <div class="site-link">
                <img src="<?= $data['img'] ?>" alt="<?= htmlspecialchars($data['model_name']) ?>">
                <h2><?= htmlspecialchars($data['model_name']) ?></h2>
                <a href="<?= $data['link'] ?>">Описание</a>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
    
    <div class="kuzov" id="kuzov-v">Внедорожники</div>
    <?php foreach ($cars as $id => $data): ?>
        <?php if ($data['category'] === 'kuzov-v'): ?>
            <div class="site-link">
                <img src="<?= $data['img'] ?>" alt="<?= htmlspecialchars($data['model_name']) ?>">
                <h2><?= htmlspecialchars($data['model_name']) ?></h2>
                <a href="<?= $data['link'] ?>">Описание</a>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
    
    <div class="kuzov" id="kuzov-s">S-Class</div>
    <?php foreach ($cars as $id => $data): ?>
        <?php if ($data['category'] === 'kuzov-s'): ?>
            <div class="site-link">
                <img src="<?= $data['img'] ?>" alt="<?= htmlspecialchars($data['model_name']) ?>">
                <h2><?= htmlspecialchars($data['model_name']) ?></h2>
                <a href="<?= $data['link'] ?>">Описание</a>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
</main>

<!-- Форма заказа автомобиля -->
<section class="forma" id="forma">
    <h2>Заказать автомобиль</h2>
    <form method="POST">
        <!-- CSRF-токен -->
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <!-- Поле для ввода телефона -->
        <label for="phone">Телефон:</label>
        <input type="tel" id="phone" name="phone" 
            value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>" 
            required>
        <?php if (!empty($errors['phone'])): ?>
            <div class="error"><?= $errors['phone'] ?></div>
        <?php endif; ?>

        <!-- Выбор модели автомобиля -->
        <label for="car_id">Выберите модель:</label>
        <select id="car_id" name="car_id" required>
            <option value="" disabled selected>Выберите...</option>
            <?php foreach ($cars as $car_id => $data): ?>
                <option value="<?= $car_id ?>"><?= htmlspecialchars($data['model_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['car_id'])): ?>
            <div class="error"><?= $errors['car_id'] ?></div>
        <?php endif; ?>

        <!-- Поле для дополнительных пожеланий -->
        <label for="message">Дополнительные пожелания:</label>
        <textarea id="message" name="message"></textarea>

        <!-- Кнопка отправки формы -->
        <button type="submit" name="submit">Отправить заказ</button>

        <!-- Вывод ошибок -->
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach($errors as $error): ?>
                    <p><?= $error ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </form>
</section>

<!-- Подвал сайта -->
<footer>
    <p>Более подробную информацию вы можете найти на сайте <a href="https://ru.wikipedia.org/wiki/Audi">Википедии</a> 
        или же на официальном сайте <a href="https://www.audi.com/">Audi</a>. 
    </p>
    <p>© 2024-2025 Audi</p>
</footer>
</body>
<!--https://github.com/Pumalk -->
</html>