<?php
require_once '../config.php';

// Включение сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Включение отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Блокировка неавторизованного доступа
if (empty($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Получаем данные пользователя из сессии
$user = $_SESSION['user'];

// Проверка структуры сессии
if (!is_array($user) || empty($user['id'])) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

// Обновляем данные пользователя из базы данных
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$_SESSION['user'] = $result->fetch_assoc();
$user = $_SESSION['user'];

// Проверка прав администратора
$is_admin = !empty($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] == 1;

// Получение истории заказов пользователя
$orders = [];
$stmt = $conn->prepare("
    SELECT orders.*, cars.model_name 
    FROM orders 
    JOIN cars ON orders.car_id = cars.id 
    WHERE orders.user_id = ? 
    ORDER BY orders.order_date DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$_SESSION['user'] = $result->fetch_assoc();
$user = $_SESSION['user'];

// Установка дефолтных значений для пользователя
$user = array_merge([
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'birth_date' => '',
    'avatar_path' => '',
], $user);

$_SESSION['user'] = $user; // Обновляем сессию

// Генерация CSRF-токена для защиты от CSRF-атак
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Обработка загрузки аватара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    $target_dir = "uploads/avatars/";
    $errors = [];

    // Создание директории для аватаров, если она не существует
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Проверка CSRF-токена
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ошибка CSRF');
    }

    // Проверка загруженного файла
    if (isset($_FILES['avatar'])) {
        // Обработка ошибок загрузки
        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            switch ($_FILES['avatar']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $errors[] = "Размер файла превышает лимит в 2 МБ (настройки сервера).";
                    break;
                default:
                    $errors[] = "Ошибка загрузки файла (код: {$_FILES['avatar']['error']}).";
            }
            goto skip_upload;
        }

        // Исходное имя файла и путь
        $srcFileName = $_FILES['avatar']['name'];
        $target_file = $target_dir . $srcFileName;

        // Проверка существования файла с таким именем
        if (file_exists($target_file)) {
            $errors[] = "Файл с именем '$srcFileName' уже существует.";
            goto skip_upload;
        }

        // Проверка расширения файла
        $imageFileType = strtolower(pathinfo($srcFileName, PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
            $errors[] = "Допустимы только JPG, JPEG и PNG файлы.";
            goto skip_upload;
        }

        // Проверка, является ли файл изображением
        $check = getimagesize($_FILES['avatar']['tmp_name']);
        if ($check === false) {
            $errors[] = "Файл не является изображением.";
            goto skip_upload;
        }

        // Проверка размеров изображения (ширина и высота)
        $max_width = 1280;
        $max_height = 720;
        list($width, $height) = $check;
        if ($width > $max_width || $height > $max_height) {
            $errors[] = "Размер изображения не должен превышать {$max_width}x{$max_height} пикселей.";
            goto skip_upload;
        }

        // Проверка размера файла (8 МБ)
        if ($_FILES['avatar']['size'] > 8000000) {
            $errors[] = "Размер файла превышает 8 МБ.";
            goto skip_upload;
        }

        // Перемещение файла
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
            // Удаление старого аватара
            if (!empty($user['avatar_path']) && file_exists($user['avatar_path'])) {
                unlink($user['avatar_path']);
            }

            // Обновление пути к аватару в БД
            $stmt = $conn->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
            $stmt->bind_param("si", $target_file, $user['id']);
            $stmt->execute();

            // Обновление данных в сессии
            $_SESSION['user']['avatar_path'] = $target_file;
            $user['avatar_path'] = $target_file;
            $success = "Аватар успешно обновлен!";
        } else {
            $errors[] = "Ошибка при загрузке файла.";
        }
    } else {
        $errors[] = "Файл не был загружен.";
    }

    skip_upload:
}

// Обновление профиля пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Проверка CSRF-токена
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ошибка CSRF');
    }

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $birth_date = trim($_POST['birth_date']);

    // Валидация данных профиля
    if (empty($birth_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
        $errors[] = "Некорректный формат даты рождения.";
    }

    // Если ошибок нет, обновляем данные в базе данных
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, birth_date = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $birth_date, $user['id']);

        if ($stmt->execute()) {
            // Обновляем данные пользователя в сессии
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $_SESSION['user'] = $result->fetch_assoc();
            $user = $_SESSION['user'];
            $success = "Данные успешно обновлены!";
        } else {
            $errors[] = "Ошибка обновления данных.";
        }
    }
}

// Выход из аккаунта
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет</title>
    <!-- Подключение фавиконок -->
    <link rel="apple-touch-icon" sizes="180x180" href="../media/фавиконки/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../media/фавиконки/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../media/фавиконки/favicon-16x16.png">
    <link rel="manifest" href="../media/фавиконки/site.webmanifest">
    <!-- Подключение стилей -->
    <link rel="stylesheet" href="account.css">
</head>
<body>
    <!-- Навигация -->
    <div class="account-nav1">
        <a href="../index.php" class="nav-button">На главную</a>
    </div>
    <div class="account-nav2">
        <a href="?logout=1" class="nav-button">Выйти из аккаунта</a>
    </div>

    <?php if ($is_admin): ?>
    <div class="account-nav3">
        <a href="../admin/admin.php" class="nav-button admin">📦 Управление заказами</a>
    </div>
    <?php endif; ?>

    <div class="container">
        <h1>Личный кабинет</h1>

        <!-- Секция аватара -->
        <div class="avatar-section">
            <div class="avatar-preview">
                <?php if (!empty($user['avatar_path'])): ?>
                    <img src="<?= htmlspecialchars($user['avatar_path']) ?>" alt="Аватар">
                <?php else: ?>
                    <div class="no-avatar">Нет аватара</div>
                <?php endif; ?>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="file" name="avatar" accept="image/*">
                <button type="submit" name="upload_avatar">Загрузить аватар</button>
            </form>
        </div>

        <!-- Вывод сообщений об ошибках и успехах -->
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Форма редактирования профиля -->
        <details class="profile-details">
        <summary>Показать/Скрыть данные</summary>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <label>Имя: <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>"></label>
            <label>Фамилия: <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>"></label>
            <label>Email: <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"></label>
            <label>Дата рождения: <input type="date" name="birth_date" value="<?= htmlspecialchars($user['birth_date']) ?>" required></label>
            <button type="submit" name="update_profile">Обновить профиль</button>
        </form>
        </details>
    </div>
    <!-- История заказов -->
    <div class="orders-history"> 
        <h2>История заказов</h2>
        <?php if (!empty($orders)): ?>
            <table>
                <tr>
                    <th>Модель</th>
                    <th>Телефон</th>
                    <th>Оплата</th>
                    <th>Статус</th>
                    <th>Дата заказа</th>
                    <th>Комментарий</th>
                </tr>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['model_name']) ?></td>
                    <td><?= htmlspecialchars($order['phone']) ?></td>
                    <td><?= htmlspecialchars($order['payment_status']) ?></td>
                    <td><?= htmlspecialchars($order['order_status']) ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></td>
                    <td><?= nl2br(htmlspecialchars($order['message'] ?? '')) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>У вас пока нет заказов.</p>
        <?php endif; ?>
    </div>
</body>
</html>
