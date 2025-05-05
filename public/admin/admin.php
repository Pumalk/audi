<?php
require_once '../config.php';

// Проверка прав администратора
if (empty($_SESSION['user']['is_admin'])) {
    header("Location: index.php");
    exit();
}

// Получение всех заказов с данными пользователей
$stmt = $conn->prepare("
    SELECT orders.*, 
        users.first_name, 
        users.last_name, 
        users.email, 
        users.birth_date 
    FROM orders 
    JOIN users ON orders.user_id = users.id
    ORDER BY orders.order_date DESC
");
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Обновление статусов заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    // CSRF-защита
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ошибка CSRF');
    }

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
    
    header("Refresh:0"); // Обновляем страницу
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <!-- Подключаем стили -->
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin.css">
    <!-- Фавиконки -->
    <link rel="apple-touch-icon" sizes="180x180" href="../media/фавиконки/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../media/фавиконки/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../media/фавиконки/favicon-16x16.png">
    <link rel="manifest" href="../media/фавиконки/site.webmanifest">
</head>
<body>
    <div class="admin-container"> <!-- Изменили класс контейнера -->
        <h1 style="color: #2beccb; border-bottom: 2px solid #be2828;">Управление заказами</h1>
        
        <a href="../index.php" class="nav-button">← На главную</a>
        
        <?php if(empty($orders)): ?>
            <div class="admin-notice">Нет активных заказов</div>
        <?php else: ?>

        <table class="admin-table">
        <thead>
            <tr>
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
                <td><?= htmlspecialchars($order['first_name']) ?></td>
                <td><?= htmlspecialchars($order['last_name']) ?></td>
                <td><?= htmlspecialchars($order['email']) ?></td>
                <td><?= htmlspecialchars($order['birth_date']) ?></td>
                <td><?= htmlspecialchars($order['phone']) ?></td>
                <td><?= htmlspecialchars($order['model']) ?></td>
                <td><?= nl2br(htmlspecialchars($order['message'])) ?></td>
                <td><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></td>
                <td>
                    <form class="status-form" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <select name="payment_status">
                            <option <?= $order['payment_status'] === 'не оплачен' ? 'selected' : '' ?>>не оплачен</option>
                            <option <?= $order['payment_status'] === 'оплачен' ? 'selected' : '' ?>>оплачен</option>
                        </select>
                </td>
                <td>
                        <select name="order_status">
                            <option <?= $order['order_status'] === 'оформляется' ? 'selected' : '' ?>>оформляется</option>
                            <option <?= $order['order_status'] === 'отправлен' ? 'selected' : '' ?>>отправлен</option>
                            <option <?= $order['order_status'] === 'доставлен' ? 'selected' : '' ?>>доставлен</option>
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
        <?php endif; ?>
    </div>
</body>
</html>