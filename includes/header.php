<?php
if (!isset($auth)) {
    require_once 'includes/auth.php';
    $auth = new Auth();
}
$user = $auth->getCurrentUser();
?>
<header class="header">
    <div class="container">
        <div class="header-left">
            <div class="logo-container">
                <!-- Место для логотипа -->
                <img src="/public/logofit.png" width="200px" hight="200px"  alt="FitLab Logo" class="logo-image">
                <a href="index.php" class="logo">FitLab</a>
            </div>
        </div>
        <nav class="nav">
            <ul>
                <li><a href="index.php">Главная</a></li>
                <li><a href="about.php">О нас</a></li>
                <li><a href="programs.php">Программы</a></li>
                <li><a href="trainers.php">Тренеры</a></li>
                <li><a href="schedule.php">Расписание</a></li>
                <li><a href="memberships.php">Абонементы</a></li>
                <li><a href="contacts.php">Контакты</a></li>
                <?php if ($user): ?>
                    <?php if ($auth->isAdmin()): ?>
                        <li><a href="admin/index.php">Админ панель</a></li>
                        <li><a href="views.php">Представления</a></li>
                    <?php endif; ?>
                    <li><a href="profile.php">Профиль</a></li>
                    <li><a href="logout.php">Выход</a></li>
                <?php else: ?>
                    <li><a href="login.php">Вход</a></li>
                    <li><a href="register.php">Регистрация</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
