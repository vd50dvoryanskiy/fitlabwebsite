<?php
session_start();
require_once 'includes/auth.php';

$auth = new Auth();
$errors = [];
$data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF токена
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('Ошибка безопасности');
    }
    unset($_SESSION['csrf_token']);

    $data = $_POST;

    if (empty($data['username'])) {
        $errors['username'] = 'Имя пользователя обязательно';
    } elseif (!preg_match("/^[a-zA-Z0-9_\-]{3,30}$/", $data['username'])) {
        $errors['username'] = 'Только буквы, цифры, тире и подчёркивания (3–30 символов)';
    } elseif ($auth->isUsernameExists($data['username'])) {
        $errors['username'] = 'Это имя пользователя уже занято';
    }

    if (empty($data['email'])) {
        $errors['email'] = 'Email обязателен';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Неверный формат email';
    } elseif ($auth->isEmailExists($data['email'])) {
        $errors['email'] = 'Этот email уже зарегистрирован';
    }

    if (empty($data['full_name'])) {
        $errors['full_name'] = 'Полное имя обязательно';
    }

    if (!empty($data['phone'])) {
        $phone = preg_replace('/[^\d+]/', '', $data['phone']);
        if (!preg_match("/^\+7\d{10}$/", $phone)) {
            $errors['phone'] = 'Введите телефон в формате +7 (XXX) XXX-XX-XX';
        }
        $data['phone'] = $phone; // Сохраняем очищенный номер
    }

    if (empty($data['password'])) {
        $errors['password'] = 'Пароль обязателен';
    } elseif (strlen($data['password']) < 6) {
        $errors['password'] = 'Пароль должен быть не менее 6 символов';
    }

    if (empty($data['confirm_password'])) {
        $errors['confirm_password'] = 'Подтвердите пароль';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $errors['confirm_password'] = 'Пароли не совпадают';
    }

    if (empty($errors)) {
        $result = $auth->register(
            $data['username'],
            $data['email'],
            $data['password'],
            $data['full_name'],
            $data['phone'] ?? null
        );

        if ($result['success']) {
            header('Location: login.php?registered=1');
            exit;
        } else {
            $errors['general'] = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - FitLab</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Маска для телефона -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js "></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.7/inputmask.min.js "></script>
    <script>
        $(document).ready(function () {
            $('#phone').inputmask("+7 (999) 999-99-99");
        });
    </script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <section class="section">
            <div class="container">
                <div class="auth-container">
                    <div class="card">
                        <h2 class="auth-title">Регистрация</h2>

                        <?php if (!empty($errors['general'])): ?>
                            <div class="alert alert-error">
                                <?= htmlspecialchars($errors['general']) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] = bin2hex(random_bytes(50)) ?>">

                            <div class="form-group">
                                <label for="username">Имя пользователя *</label>
                                <input type="text"
                                       id="username"
                                       name="username"
                                       value="<?= htmlspecialchars($data['username'] ?? '') ?>"
                                       class="form-control"
                                       required>
                                <?php if (isset($errors['username'])): ?>
                                    <div class="alert alert-error"><?= htmlspecialchars($errors['username']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       value="<?= htmlspecialchars($data['email'] ?? '') ?>"
                                       class="form-control"
                                       required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="alert alert-error"><?= htmlspecialchars($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="full_name">Полное имя *</label>
                                <input type="text"
                                       id="full_name"
                                       name="full_name"
                                       value="<?= htmlspecialchars($data['full_name'] ?? '') ?>"
                                       class="form-control"
                                       required>
                                <?php if (isset($errors['full_name'])): ?>
                                    <div class="alert alert-error"><?= htmlspecialchars($errors['full_name']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="phone">Телефон</label>
                                <input type="text"
                                       id="phone"
                                       name="phone"
                                       placeholder="+7 (___) ___-__-__"
                                       value="<?= htmlspecialchars($data['phone'] ?? '') ?>"
                                       class="form-control">
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="alert alert-error"><?= htmlspecialchars($errors['phone']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="password">Пароль *</label>
                                <input type="password"
                                       id="password"
                                       name="password"
                                       class="form-control"
                                       required>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="alert alert-error"><?= htmlspecialchars($errors['password']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Подтвердите пароль *</label>
                                <input type="password"
                                       id="confirm_password"
                                       name="confirm_password"
                                       class="form-control"
                                       required>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="alert alert-error"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="btn btn-primary btn-full-width">Зарегистрироваться</button>
                        </form>

                        <p class="auth-link">
                            Уже есть аккаунт? <a href="login.php" class="link">Войти</a>
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
