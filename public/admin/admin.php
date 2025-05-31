<?php
require_once '../config.php';

// Проверка прав администратора
if (empty($_SESSION['user']['is_admin'])) {
    header("Location: index.php");
    exit();
}

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Настройки пагинации
$orders_per_page = 10;
$users_per_page = 10;

// Получение текущей страницы для заказов
$order_page = isset($_GET['order_page']) ? (int)$_GET['order_page'] : 1;
$order_offset = ($order_page - 1) * $orders_per_page;

// Получение текущей страницы для пользователей
$user_page = isset($_GET['user_page']) ? (int)$_GET['user_page'] : 1;
$user_offset = ($user_page - 1) * $users_per_page;

// Получение общего количества заказов
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders");
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total'];
$total_order_pages = ceil($total_orders / $orders_per_page);

// Получение общего количества пользователей
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['total'];
$total_user_pages = ceil($total_users / $users_per_page);

// Получение заказов для текущей страницы
$stmt = $conn->prepare("
    SELECT orders.*, 
        cars.model_name,
        users.first_name, 
        users.last_name, 
        users.email, 
        users.birth_date,
        users.username
    FROM orders 
    JOIN users ON orders.user_id = users.id
    JOIN cars ON orders.car_id = cars.id
    ORDER BY orders.order_date DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $orders_per_page, $order_offset);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Получение пользователей для текущей страницы
$stmt = $conn->prepare("
    SELECT * FROM users 
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $users_per_page, $user_offset);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-защита
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ошибка CSRF');
    }

    // Обновление заказа
    if (isset($_POST['update_order'])) {
        $order_id = (int)$_POST['order_id'];
        $payment_status = $_POST['payment_status'];
        $order_status = $_POST['order_status'];

        $stmt = $conn->prepare("
            UPDATE orders 
            SET payment_status = ?, order_status = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $payment_status, $order_status, $order_id);
        $stmt->execute();
    }
    
    // Обновление пользователя
    if (isset($_POST['update_user'])) {
        $user_id = (int)$_POST['user_id'];
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;

        $stmt = $conn->prepare("
            UPDATE users 
            SET is_admin = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $is_admin, $user_id);
        $stmt->execute();
    }
    
    // Удаление пользователя
    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Сначала удаляем заказы пользователя
        $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Затем удаляем самого пользователя
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    header("Location: admin.php?order_page=$order_page&user_page=$user_page");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="apple-touch-icon" sizes="180x180" href="../media/фавиконки/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../media/фавиконки/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../media/фавиконки/favicon-16x16.png">
    <link rel="manifest" href="../media/фавиконки/site.webmanifest">
</head>
<body>
    <div class="admin-container">
        <h1 class="admin-section-title">Управление заказами</h1>
        
        <div class="admin-nav-buttons">
            <a href="../index.php" class="nav-button">← На главную</a>
            <a href="#users-section" class="nav-button">Пользователи</a>
        </div>
        
        <?php if(empty($orders)): ?>
    <div class="admin-notice">Нет активных заказов</div>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID заказа</th>
                <th>Имя</th>
                <th>Фамилия</th>
                <th>Email</th>
                <th>Дата рождения</th>
                <th>Телефон</th>
                <th>Модель</th>
                <th>Комментарий</th>
                <th>Дата заказа</th>
                <th>Оплата</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= $order['id'] ?></td>
                <td><?= htmlspecialchars($order['first_name']) ?></td>
                <td><?= htmlspecialchars($order['last_name']) ?></td>
                <td><?= htmlspecialchars($order['email']) ?></td>
                <td><?= htmlspecialchars($order['birth_date']) ?></td>
                <td><?= htmlspecialchars($order['phone']) ?></td>
                <td><?= htmlspecialchars($order['model_name']) ?></td>
                <td><?= nl2br(htmlspecialchars($order['message'] ?? '')) ?></td>
                <td><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></td>
                <td>
                    <form class="status-form" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <select name="payment_status">
                            <option value="не оплачен" <?= $order['payment_status'] === 'не оплачен' ? 'selected' : '' ?>>не оплачен</option>
                            <option value="оплачен" <?= $order['payment_status'] === 'оплачен' ? 'selected' : '' ?>>оплачен</option>
                        </select>
                </td>
                <td>
                        <select name="order_status">
                            <option value="оформляется" <?= $order['order_status'] === 'оформляется' ? 'selected' : '' ?>>оформляется</option>
                            <option value="отправлен" <?= $order['order_status'] === 'отправлен' ? 'selected' : '' ?>>отправлен</option>
                            <option value="доставлен" <?= $order['order_status'] === 'доставлен' ? 'selected' : '' ?>>доставлен</option>
                        </select>
                </td>
                <td>
                    <button type="submit" name="update_order">Обновить</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
            
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_order_pages; $i++): ?>
                    <a href="?order_page=<?= $i ?>&user_page=<?= $user_page ?>" class="<?= $i == $order_page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

        <h1 id="users-section" class="admin-section-title">Управление пользователями</h1>
        
        <?php if(empty($users)): ?>
            <div class="admin-notice">Нет зарегистрированных пользователей</div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логин</th>
                        <th>Имя</th>
                        <th>Фамилия</th>
                        <th>Email</th>
                        <th>Дата рождения</th>
                        <th>Аватар</th>
                        <th>Дата регистрации</th>
                        <th>Админ</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['first_name']) ?></td>
                        <td><?= htmlspecialchars($user['last_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['birth_date']) ?></td>
                        <td>
                            <?php if ($user['avatar_path']): ?>
                                <img src="../account/<?= htmlspecialchars($user['avatar_path']) ?>" alt="Аватар" class="user-avatar">
                            <?php else: ?>
                                <div class="no-avatar">Нет аватара</div>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($user['user_created'] ?? $user['created_at'])) ?></td>
                        <td>
                            <form method="POST" class="admin-toggle-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <label class="admin-toggle">
                                    <input type="checkbox" name="is_admin" <?= $user['is_admin'] ? 'checked' : '' ?>>
                                    <span class="admin-toggle-slider"></span>
                                </label>
                                <button type="submit" name="update_user" class="hidden-submit"></button>
                            </form>
                        </td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="delete_user" class="delete-button">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_user_pages; $i++): ?>
                    <a href="?order_page=<?= $order_page ?>&user_page=<?= $i ?>" class="<?= $i == $user_page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>