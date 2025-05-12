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
        die('Ошибка CSRF'); // Завершаем выполнение при ошибке токена
    }

    // Валидация и санитизация данных из формы
    $phone = trim($_POST['phone'] ?? ''); // Телефон
    $model = trim($_POST['model'] ?? ''); // Модель автомобиля
    $message = trim($_POST['message'] ?? ''); // Дополнительное сообщение

    // Проверка формата телефона
    if (!preg_match('/^\+7\d{10}$/', $phone)) {
        $errors['phone'] = "Телефон должен быть в формате +79161234567";
    }

    // Проверка выбора модели автомобиля
    if (empty($model) || $model === 'Выберите...') {
        $errors['model'] = "Выберите модель автомобиля";
    }

    // Если ошибок нет, сохраняем данные в базу данных
    if (empty($errors)) {
        try {
            // Подготовка SQL-запроса для вставки данных
            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, phone, model, message, order_date) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("isss", $user['id'], $phone, $model, $message);
        
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
    <div class="header-center">
        <div class="logo">
            <img src="./media/медиа для страницы/лого.png" alt="Audi Logo">
        </div>
        <div class="order-button2">
            <a href="#forma" class="order-button">Заказать автомобиль</a>
        </div>
    </div>
    <!-- Кнопка авторизации -->
    <a href="account/account.php" class="auth-button">Личный кабинет</a>
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
    <!-- Легковые автомобили -->
    <div class="kuzov" id="kuzov-l">Легковые</div>
    <div class="site-link">
        <img src="авто/легковые/rs 6/audi 1.jpg" alt="Audi RS6 C8">
        <h2>RS6 C8</h2>
        <a href="авто/легковые/rs 6/rs6.html">Описание</a>
    </div>
        <div class="site-link">
            <img src="авто/легковые/e-tron/1.webp" alt="Audi e-tron GT">
            <h2>e-tron GT</h2>
            <a href="авто/легковые/e-tron/e-tron.html">Описание</a>
        </div>
        <div class="site-link">
            <img src="авто/легковые/a3/1.jpg" alt="Audi A3 8Y">
            <h2>A3 8Y</h2>
            <a href="авто/легковые/a3/a3.html">Описание</a>
        </div>
        <div class="site-link">
            <img src="авто/легковые/A6/1.jpg" alt="Audi A6 allroad quattro C8">
            <h2>A6 allroad quattro C8</h2>
            <a href="авто/легковые/A6/A6.html">Описание</a>
        </div>
        
        <div class="kuzov" id="kuzov-v">Внедорожники</div>
        <div class="site-link">
            <img src="авто/внедорожники/q3/1.webp" alt="Audi Q3 F3">
            <h2>Q3 F3</h2>
            <a href="авто/внедорожники/q3/q3.html">Описание</a>
        </div>
        <div class="site-link">
            <img src="авто/внедорожники/q5/1.webp" alt="Audi Q5 FY">
            <h2>Q5 FY</h2>
            <a href="авто/внедорожники/q5/q5.html">Описание</a>
        </div>
        <div class="site-link">
            <img src="авто/внедорожники/q7/1.webp" alt="Audi Q7 4M">
            <h2>Q7 4M</h2>
            <a href="авто/внедорожники/q7/q7.html">Описание</a>
        </div>
        <div class="site-link">
            <img src="авто/внедорожники/q8/1.webp" alt="Audi Q8 4M">
            <h2>Q8 4M</h2>
            <a href="авто/внедорожники/q8/q8.html">Описание</a>
        </div>
        
        <div class="kuzov" id="kuzov-s">S-Class</div>
        <div class="site-link">
            <img src="авто/S-класс/S3/1.webp" alt="Audi S3 8Y">
            <h2>S3 8Y</h2>
            <a href="авто/S-класс/s3/s3.html">Описание</a>
        </div>
        <div class="site-link">
            <img src="авто/S-класс/s4/1.webp" alt="Audi S4 B9">
            <h2>S4 B9</h2>
            <a href="авто/S-класс/s4/s4.html">Описание</a>
        </div>
        <div class="site-link">
            <img src="авто/S-класс/s6/1.webp" alt="Audi S6 C8">
            <h2>S6 C8</h2>
            <a href="авто/S-класс/s6/s6.html">Описание</a>
        </div>
        <div class="site-link">
            <img src="авто/S-класс/s8/1.webp" alt="Audi S8 D5">
            <h2>S8 D5</h2>
            <a href="авто/S-класс/s8/s8.html">Описание</a>
        </div>
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
        <label for="model">Выберите модель:</label>
        <select id="model" name="model" required>
            <option value="" disabled selected>Выберите...</option>
            <option value="RS6 C8">Audi RS6 C8</option>
                <option value="e-tron GT">Audi e-tron GT</option>
                <option value="A3 8Y">Audi A3 8Y</option>
                <option value="Audi A6 allroad quattro C8">Audi A6 allroad quattro C8</option>
                <option value="Q7 4M">Audi Q7 4M</option>
                <option value="Q8 4M">Audi Q8 4M</option>
                <option value="Q5 FY">Audi Q5 FY</option>
                <option value="Q3 F3">Audi Q3 F3</option>
                <option value="S3 8Y">Audi S3 8Y</option>
                <option value="S4 B9">Audi S4 B9</option>
                <option value="S6 C8">Audi S6 C8</option>
                <option value="S8 D5">Audi S8 D5</option>
        </select>

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