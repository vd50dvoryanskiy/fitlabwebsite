<?php
// Подключаем файл аутентификации и создаем объект Auth для проверки авторизации пользователя
require_once 'includes/auth.php';
$auth = new Auth();
$user = $auth->getCurrentUser(); // Получаем текущего пользователя или null, если не залогинен
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitLab - Фитнес-клуб премиум класса</title>
    <!-- Подключаем основные стили -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Иконка сайта -->
    <link rel="icon" type="logofit.png" href="public/logofit.png">
</head>
<body>
    <!-- Подключаем шапку сайта -->
    <?php include 'includes/header.php'; ?>

    <!-- Основной контент -->
    <main class="main-content">
        <!-- Секция "Герой" (главная визуальная секция) -->
        <section class="hero">
            <div class="container">
                <h1>Добро пожаловать в FitLab</h1>
                <p>
                    Премиальный фитнес-клуб, где каждая тренировка — это шаг к лучшей версии себя.
                    Современное оборудование, профессиональные тренеры и индивидуальный подход к каждому клиенту.
                </p>
                <!-- Кнопки меняются в зависимости от того, вошёл ли пользователь -->
                <div class="hero-buttons">
                    <?php if (!$user): ?>
                        <a href="register.php" class="btn btn-primary">Начать тренировки</a>
                        <a href="about.php" class="btn btn-secondary">Узнать больше</a>
                    <?php else: ?>
                        <a href="schedule.php" class="btn btn-primary">Записаться на тренировку</a>
                        <a href="profile.php" class="btn btn-secondary">Мой профиль</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Преимущества FitLab -->
        <section class="section">
            <div class="container">
                <h2 class="section-title">Почему выбирают FitLab</h2>
                <!-- Сетка из 3 колонок -->
                <div class="grid grid-3">
                    <div class="card">
                        <h3>Профессиональные тренеры</h3>
                        <p>Наши сертифицированные тренеры имеют многолетний опыт и помогут достичь ваших целей безопасно и эффективно.</p>
                    </div>
                    <div class="card">
                        <h3>Современное оборудование</h3>
                        <p>Новейшие тренажеры от ведущих мировых производителей обеспечивают комфортные и результативные тренировки.</p>
                    </div>
                    <div class="card">
                        <h3>Разнообразие программ</h3>
                        <p>Йога, пилатес, CrossFit, силовые тренировки, кардио — найдите программу, которая подходит именно вам.</p>
                    </div>
                    <div class="card">
                        <h3>Гибкое расписание</h3>
                        <p>Тренировки с раннего утра до позднего вечера. Выберите удобное время для занятий.</p>
                    </div>
                    <div class="card">
                        <h3>Индивидуальный подход</h3>
                        <p>Персональные программы тренировок, учитывающие ваш уровень подготовки и цели.</p>
                    </div>
                    <div class="card">
                        <h3>Комфортная атмосфера</h3>
                        <p>Просторные залы, раздевалки с душевыми, зона отдыха — все для вашего комфорта.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Статистика FitLab -->
        <section class="section">
            <div class="container">
                <h2 class="section-title">FitLab в цифрах</h2>
                <!-- Сетка из 4 колонок -->
                <div class="grid grid-4">
                    <div class="card stats-card stats-card-green">
                        <div class="stats-number">500+</div>
                        <p class="stats-label">Довольных клиентов</p>
                    </div>
                    <div class="card stats-card stats-card-blue">
                        <div class="stats-number">15</div>
                        <p class="stats-label">Профессиональных тренеров</p>
                    </div>
                    <div class="card stats-card stats-card-orange">
                        <div class="stats-number">20+</div>
                        <p class="stats-label">Видов тренировок</p>
                    </div>
                    <div class="card stats-card stats-card-purple">
                        <div class="stats-number">5</div>
                        <p class="stats-label">Лет опыта</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Призыв к действию -->
        <section class="section cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2 class="cta-title">Готовы начать свой путь к здоровью?</h2>
                    <p class="cta-text">
                        Присоединяйтесь к нашему сообществу и получите первую тренировку бесплатно!
                    </p>
                    <div class="cta-buttons">
                        <?php if (!$user): ?>
                            <a href="register.php" class="btn btn-primary">Зарегистрироваться</a>
                            <a href="contacts.php" class="btn btn-secondary">Связаться с нами</a>
                        <?php else: ?>
                            <a href="schedule.php" class="btn btn-primary">Записаться на тренировку</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Подвал сайта -->
    <?php include 'includes/footer.php'; ?>

    <!-- Подключение JavaScript -->
    <script src="assets/js/main.js"></script>
</body>
</html>
