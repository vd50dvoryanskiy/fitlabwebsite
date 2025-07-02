<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$user = $auth->getCurrentUser();

$database = new Database();
$conn = $database->getConnection();

// Получаем все активные абонементы из базы данных
try {
    $query = "SELECT membership_type, price, duration_days, description, features 
              FROM Memberships 
              WHERE is_active = 1 
              ORDER BY price ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Ошибка получения абонементов: ' . $e->getMessage());
    $memberships = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Абонементы - FitLab</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="main-content">

    <!-- Общий hero, как на других страницах -->
    <section class="hero">
        <div class="container">
            <h1>Абонементы</h1>
            <p>Ознакомьтесь с нашими абонементами и выберите подходящий для себя</p>
        </div>
    </section>

    <!-- Секция с карточками абонементов -->
    <section class="section">
        <div class="container">
            <div class="grid grid-3">
                <?php foreach ($memberships as $m): ?>
                    <div class="membership-card">
                        <div class="membership-header">
                            <h3 class="membership-title"><?= htmlspecialchars($m['membership_type']) ?></h3>
                        </div>
                        
                        <div class="membership-pricing">
                            <div class="membership-price-main">
                                <?= number_format($m['price'], 0, '', ' ') ?> ₽
                            </div>
                            <?php if ($m['duration_days']): ?>
                                <div class="membership-duration">
                                    <?= $m['duration_days'] ?> дней
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($m['description']): ?>
                            <div class="membership-description">
                                <?= htmlspecialchars($m['description']) ?>
                            </div>
                        <?php endif; ?>

                        <div class="membership-features-section">
                            <h4 class="features-title">ЧТО ВКЛЮЧЕНО:</h4>
                            <ul class="membership-features">
                                <?php
                                // Разделяем список функций по "|"
                                $features = explode('|', $m['features']);
                                foreach ($features as $feature):
                                    $feature = trim($feature);
                                    if (!empty($feature)):
                                ?>
                                    <li class="feature-item">
                                        <?= htmlspecialchars($feature) ?>
                                    </li>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            </ul>
                        </div>

                        <!-- Кнопку "Выбрать план" убираем или оставляем по необходимости -->
                        <!--
                        <div class="membership-actions">
                            <button class="btn btn-membership">Выбрать план</button>
                        </div>
                        -->
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Сравнение абонементов -->
    <section class="section comparison-section">
        <div class="container">
            <h2 class="section-title">Сравнение абонементов</h2>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Тип</th>
                            <th>Цена</th>
                            <th>Продолжительность</th>
                            <th>Описание</th>
                            <th>Функции</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($memberships as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['membership_type']) ?></td>
                                <td><?= number_format($m['price'], 0, '', ' ') ?> ₽</td>
                                <td><?= $m['duration_days'] ?> дней</td>
                                <td><?= nl2br(htmlspecialchars($m['description'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($m['features'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Условия посещения -->
    <section class="section rules-section">
        <div class="container">
            <h2 class="section-title">Условия посещения зала</h2>
            <div class="grid grid-2">
                <div class="card">
                    <h3>Правила поведения</h3>
                    <ul class="rules-list">
                        <li>Не шуметь во время тренировок</li>
                        <li>Не занимать оборудование надолго</li>
                        <li>Следить за чистотой</li>
                        <li>Не мешать другим участникам</li>
                    </ul>
                </div>
                <div class="card">
                    <h3>Что взять с собой</h3>
                    <ul class="rules-list">
                        <li>Спортивная форма</li>
                        <li>Полотенце</li>
                        <li>Бутылка воды</li>
                        <li>Сменная обувь</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Акции -->
    <section class="section promotions-section">
        <div class="container">
            <h2 class="section-title promotions-title">Наши акции</h2>
            <div class="grid grid-3">
                <div class="card promotion-card">
                    <h3>Подарочный сертификат</h3>
                    <p>Идеальный подарок для близких. Подходит под любой тип абонемента.</p>
                    <small class="promotion-note">*Срок действия — 1 месяц</small>
                </div>
                <div class="card promotion-card">
                    <h3>Студенческая скидка</h3>
                    <p>Предъявите студенческий билет и получите скидку 15% на абонемент.</p>
                    <small class="promotion-note">*Только очная форма обучения</small>
                </div>
                <div class="card promotion-card">
                    <h3>Приведи друга</h3>
                    <p>Если ваш друг купит абонемент — вы получите бонусные дни к своему!</p>
                    <small class="promotion-note">*Друг должен указать ваше имя при регистрации</small>
                </div>
            </div>
        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
