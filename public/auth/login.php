<?php
require_once '../config.php';

if (isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

$errors = [];
$current_form = 'login'; // По умолчанию показываем форму входа

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username)) $errors['login_username'] = "Введите имя пользователя";
    if (empty($password)) $errors['login_password'] = "Введите пароль";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, password, email, first_name, last_name, birth_date FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'avatar_path' => $user['avatar_path'] ?? null
                ];
                unset($_SESSION['video_shown']);
                header("Location: ../index.php");
                exit();
            } else {
                $errors['login'] = "Неверный пароль";        
            }
        } else {
            $errors['login'] = "Пользователь не найден";
        }
    }       
}

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $current_form = 'register';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $username = trim($_POST['reg_username'] ?? '');
    $password = $_POST['reg_password'] ?? '';

    if (empty($first_name)) $errors['reg_first_name'] = "Введите имя";
    if (empty($last_name)) $errors['reg_last_name'] = "Введите фамилию";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['reg_email'] = "Некорректный email";
    if (empty($birth_date)) $errors['reg_birth_date'] = "Введите дату рождения";
    if (empty($username)) $errors['reg_username'] = "Введите логин";
    if (empty($password)) $errors['reg_password'] = "Введите пароль";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $errors['reg_username'] = "Пользователь с таким именем или email уже существует";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users 
                (first_name, last_name, email, birth_date, username, password) 
                VALUES (?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("ssssss", 
                $first_name, 
                $last_name, 
                $email, 
                $birth_date, 
                $username, 
                $hashed_password
            );

            if ($stmt->execute()) {
                $_SESSION['user'] = [
                    'id' => $stmt->insert_id,
                    'username' => $username,
                    'email' => $email,
                    'first_name' => $first_name,
                    'last_name' => $last_name
                ];
                unset($_SESSION['video_shown']);
                header("Location: ../index.php");
                exit();
            } else {
                $errors[] = "Ошибка регистрации: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход/Регистрация</title>
    <link rel="apple-touch-icon" sizes="180x180" href="../media/фавиконки/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../media/фавиконки/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../media/фавиконки/favicon-16x16.png">
    <link rel="manifest" href="../media/фавиконки/site.webmanifest">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="forms-container">
        <input type="radio" name="slider" id="login" <?= $current_form === 'login' ? 'checked' : '' ?>>
        <input type="radio" name="slider" id="register" <?= $current_form === 'register' ? 'checked' : '' ?>>
        
        <div class="form-tabs">
            <label for="login" class="login-tab">Вход</label>
            <label for="register" class="register-tab">Регистрация</label>
        </div>

        <div class="forms-wrapper">
            <div class="login-form form-box">
                <form method="POST">
                    <h2>Авторизация</h2>
                    <div class="form-group">
                        <label for="username">Имя пользователя:</label>
                        <input type="text" id="username" name="username" required>
                        <?php if (!empty($errors['login_username'])): ?>
                            <div class="error"><?= $errors['login_username'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль:</label>
                        <input type="password" id="password" name="password" required>
                        <?php if (!empty($errors['login_password'])): ?>
                            <div class="error"><?= $errors['login_password'] ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($errors['login'])): ?>
                        <div class="error"><?= $errors['login'] ?></div>
                    <?php endif; ?>

                    <button type="submit" name="login_submit">Войти</button>
                </form>
            </div>

            <div class="register-form form-box">
                <form method="POST">
                    <h2>Регистрация</h2>
                    <div class="form-group">
                        <label for="first_name">Имя:</label>
                        <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name ?? '') ?>" required>
                        <?php if (!empty($errors['reg_first_name'])): ?>
                            <div class="error"><?= $errors['reg_first_name'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Фамилия:</label>
                        <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name ?? '') ?>" required>
                        <?php if (!empty($errors['reg_last_name'])): ?>
                            <div class="error"><?= $errors['reg_last_name'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
                        <?php if (!empty($errors['reg_email'])): ?>
                            <div class="error"><?= $errors['reg_email'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="birth_date">Дата рождения:</label>
                        <input type="date" id="birth_date" name="birth_date" value="<?= htmlspecialchars($birth_date ?? '') ?>" required>
                        <?php if (!empty($errors['reg_birth_date'])): ?>
                            <div class="error"><?= $errors['reg_birth_date'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="reg_username">Имя пользователя:</label>
                        <input type="text" id="reg_username" name="reg_username" value="<?= htmlspecialchars($username ?? '') ?>" required>
                        <?php if (!empty($errors['reg_username'])): ?>
                            <div class="error"><?= $errors['reg_username'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="reg_password">Пароль:</label>
                        <input type="password" id="reg_password" name="reg_password" required>
                        <?php if (!empty($errors['reg_password'])): ?>
                            <div class="error"><?= $errors['reg_password'] ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="register_submit">Зарегистрироваться</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>