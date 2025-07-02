<?php
require_once 'includes/auth.php';
$auth = new Auth();
$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О нас - FitLab</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <!-- Герой секция -->
        <section class="hero about-hero">
            <div class="container">
                <h1>О фитнес-клубе FitLab</h1>
                <p>Мы создаем пространство, где каждый может достичь своих фитнес-целей в комфортной и мотивирующей атмосфере</p>
            </div>
        </section>

        <!-- История клуба -->
        <section class="section">
            <div class="container">
                <div class="grid grid-2 about-history">
                    <div>
                        <h2>Наша история</h2>
                        <p>FitLab был основан в 2019 году группой энтузиастов фитнеса, которые хотели создать нечто большее, чем просто тренажерный зал. Мы стремились построить сообщество, где люди могут не только тренироваться, но и находить поддержку, мотивацию и новых друзей.</p>
                        
                        <p>За 5 лет работы мы помогли более чем 500 клиентам достичь своих целей - от похудения до подготовки к соревнованиям. Наш клуб стал местом, где профессионализм сочетается с дружелюбной атмосферой.</p>
                        
                        <p>Сегодня FitLab - это современный фитнес-клуб с новейшим оборудованием, квалифицированными тренерами и разнообразными программами для людей любого уровня подготовки.</p>
                    </div>
                    <div>
                        <img src="/public/logofit.png" width=200px hight=400px>
                    </div>
                </div>
            </div>
        </section>

        <!-- Миссия и ценности -->
        <section class="section values-section">
            <div class="container">
                <h2 class="section-title">Наша миссия и ценности</h2>
                <div class="grid grid-3">
                    <div class="card">
                        <h3>Миссия</h3>
                        <p>Вдохновлять людей на здоровый образ жизни, предоставляя качественные фитнес-услуги и создавая поддерживающее сообщество для достижения личных целей каждого клиента.</p>
                    </div>
                    <div class="card">
                        <h3>Профессионализм</h3>
                        <p>Мы постоянно повышаем квалификацию наших тренеров и следим за новейшими тенденциями в фитнес-индустрии, чтобы предоставлять услуги высочайшего качества.</p>
                    </div>
                    <div class="card">
                        <h3>Индивидуальный подход</h3>
                        <p>Каждый клиент уникален, поэтому мы разрабатываем персональные программы тренировок, учитывающие цели, возможности и предпочтения каждого человека.</p>
                    </div>
                    <div class="card">
                        <h3>Безопасность</h3>
                        <p>Здоровье наших клиентов - наш приоритет. Мы обеспечиваем безопасную среду для тренировок и следим за правильной техникой выполнения упражнений.</p>
                    </div>
                    <div class="card">
                        <h3>Сообщество</h3>
                        <p>Мы создаем дружелюбную атмосферу, где люди поддерживают друг друга, делятся опытом и мотивируют на достижение новых высот.</p>
                    </div>
                    <div class="card">
                        <h3>Инновации</h3>
                        <p>Мы внедряем современные технологии и методики тренировок, чтобы сделать занятия более эффективными и интересными.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Наши достижения -->
        <section class="section">
            <div class="container">
                <h2 class="section-title">Наши достижения</h2>
                <div class="grid grid-2">
                    <div class="card">
                        <h3>Награды и сертификаты</h3>
                        <ul class="achievements-list">
                            <li>"Лучший фитнес-клуб года" - 2023</li>
                            <li>Сертификат качества ISO 9001</li>
                            <li>"Выбор клиентов" - рейтинг 4.9/5</li>
                            <li>Партнерство с федерацией фитнеса России</li>
                        </ul>
                    </div>
                    <div class="card">
                        <h3>Статистика успеха</h3>
                        <ul class="achievements-list">
                            <li>95% клиентов достигают своих целей</li>
                            <li>Средняя потеря веса: 8 кг за 3 месяца</li>
                            <li>15 подготовленных спортсменов-разрядников</li>
                            <li>98% клиентов рекомендуют нас друзьям</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Оборудование и удобства -->
        <section class="section equipment-section">
            <div class="container">
                <h2 class="section-title">Оборудование и удобства</h2>
                <div class="grid grid-2">
                    <div>
                        <h3>Тренажерный зал</h3>
                        <ul class="equipment-list">
                            <li>Кардио-зона: беговые дорожки, эллипсы, велотренажеры</li>
                            <li>Силовая зона: тренажеры для всех групп мышц</li>
                            <li>Зона свободных весов: гантели, штанги, гири</li>
                            <li>Функциональная зона: TRX, медболы, канаты</li>
                        </ul>
                        
                        <h3>Дополнительные услуги</h3>
                        <ul class="equipment-list">
                            <li>Просторные раздевалки с индивидуальными шкафчиками</li>
                            <li>Душевые кабины с горячей водой</li>
                            <li>Финская сауна</li>
                            <li>Зона отдыха с кулером</li>
                            <li>Бесплатный Wi-Fi</li>
                            <li>Парковка для клиентов</li>
                        </ul>
                    </div>
                    <div>
                        <img src="/public/FitLabтрен.jpg" height="400" width="500" alt="Тренажерный зал FitLab" class="equipment-image">
                    </div>
                </div>
            </div>
        </section>

        <!-- Призыв к действию -->
        <section class="section cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2 class="cta-title">Присоединяйтесь к нашей команде!</h2>
                    <p class="cta-text">
                        Станьте частью сообщества FitLab и начните свой путь к здоровой и активной жизни уже сегодня!
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

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
