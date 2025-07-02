<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$user = $auth->getCurrentUser();

$message = '';
$message_type = '';

if ($_POST) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $subject = trim($_POST['subject']);
    $messageText = trim($_POST['message']);

    if (empty($name) || empty($email) || empty($messageText)) {
        $message = 'Пожалуйста, заполните все обязательные поля';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Некорректный email адрес';
        $message_type = 'error';
    } else {
        // Всё заполнено верно — просто показываем успех без записи в БД
        $message = 'Ваше сообщение отправлено! Мы свяжемся с вами в ближайшее время.';
        $message_type = 'success';

        // Очищаем форму после успешной отправки
        $_POST = [];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты - FitLab</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <!-- Герой секция -->
        <section class="hero contacts-hero">
            <div class="container">
                <h1>Контакты</h1>
                <p>Свяжитесь с нами любым удобным способом</p>
            </div>
        </section>

        <!-- Контактная информация -->
        <section class="section">
            <div class="container">
                <div class="grid grid-2">
                    <!-- Форма обратной связи -->
                    <div class="card">
                        <h2>Напишите нам</h2>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="name">Имя *</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Телефон</label>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Тема</label>
                                <select id="subject" name="subject" class="form-control">
                                    <option value="">Выберите тему</option>
                                    <option value="Общие вопросы" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Общие вопросы') ? 'selected' : ''; ?>>Общие вопросы</option>
                                    <option value="Абонементы" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Абонементы') ? 'selected' : ''; ?>>Абонементы</option>
                                    <option value="Персональные тренировки" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Персональные тренировки') ? 'selected' : ''; ?>>Персональные тренировки</option>
                                    <option value="Расписание" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Расписание') ? 'selected' : ''; ?>>Расписание</option>
                                    <option value="Жалобы и предложения" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Жалобы и предложения') ? 'selected' : ''; ?>>Жалобы и предложения</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Сообщение *</label>
                                <textarea id="message" name="message" class="form-control" rows="5" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-full-width">Отправить сообщение</button>
                        </form>
                    </div>
                    
                    <!-- Контактная информация -->
                    <div>
                        <div class="contact-info">
                            <h3>Наши контакты</h3>
                            <div class="contact-item">
                                <h4>📍 Адрес</h4>
                                <p>г. Москва, ул. Спортивная, 15<br>
                                метро "Спортивная" (5 минут пешком)</p>
                            </div>
                            
                            <div class="contact-item">
                                <h4>📞 Телефоны</h4>
                                <p>
                                    <a href="tel:+74951234567" class="contact-link">+7 (495) 123-45-67</a> - администрация<br>
                                    <a href="tel:+74951234568" class="contact-link">+7 (495) 123-45-68</a> - тренеры
                                </p>
                            </div>
                            
                            <div class="contact-item">
                                <h4>📧 Email</h4>
                                <p>
                                    <a href="mailto:info@fitlab.ru" class="contact-link">info@fitlab.ru</a> - общие вопросы<br>
                                    <a href="mailto:trainers@fitlab.ru" class="contact-link">trainers@fitlab.ru</a> - тренеры
                                </p>
                            </div>
                            
                            <div class="contact-item">
                                <h4>🌐 Социальные сети</h4>
                                <p>
                                    <a href="#" class="contact-link">Instagram</a>
                                    <a href="#" class="contact-link">VKontakte</a>
                                    <a href="#" class="contact-link">Telegram</a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="card">
                            <h3>Часы работы</h3>
                            <table class="schedule-table">
                                <tr>
                                    <td><strong>Понедельник - Пятница</strong></td>
                                    <td>06:00 - 24:00</td>
                                </tr>
                                <tr>
                                    <td><strong>Суббота - Воскресенье</strong></td>
                                    <td>08:00 - 22:00</td>
                                </tr>
                                <tr>
                                    <td><strong>Праздничные дни</strong></td>
                                    <td>10:00 - 20:00</td>
                                </tr>
                            </table>
                            
                            <div class="schedule-note">
                                <small><strong>Обратите внимание:</strong> Групповые занятия проводятся согласно расписанию. Последний вход в зал за 30 минут до закрытия.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Карта -->
        <section class="section map-section">
            <div class="container">
                <h2 class="section-title">Как нас найти</h2>
                <div class="card">
                    <div class="location-info">
                        <p>г. Москва, ул. Спортивная, 15</p>
                        <p>метро "Спортивная"</p>
                    </div>
                    
                    <div class="directions-section">
                        <h3>Как добраться</h3>
                        <div class="grid grid-2">
                            <div>
                                <h4>🚇 На метро</h4>
                                <p>Станция метро "Спортивная" (Сокольническая линия). Выход №2, далее 5 минут пешком по ул. Спортивная.</p>
                            </div>
                            <div>
                                <h4>🚗 На автомобиле</h4>
                                <p>Бесплатная парковка для клиентов. Въезд с ул. Спортивная. 50 парковочных мест.</p>
                            </div>
                            <div>
                                <h4>🚌 На автобусе</h4>
                                <p>Автобусы №15, 47, 132. Остановка "Спортивный комплекс", далее 2 минуты пешком.</p>
                            </div>
                            <div>
                                <h4>🚶 Пешком</h4>
                                <p>От центра города 15 минут пешком. Удобные пешеходные дорожки и освещение.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="section">
            <div class="container">
                <h2 class="section-title">Часто задаваемые вопросы</h2>
                <div class="grid grid-2">
                    <div class="card">
                        <h4>Можно ли прийти на пробную тренировку?</h4>
                        <p>Да, первая тренировка для новых клиентов бесплатная. Просто приходите в удобное время или запишитесь заранее.</p>
                    </div>
                    <div class="card">
                        <h4>Есть ли возрастные ограничения?</h4>
                        <p>Мы принимаем клиентов от 16 лет. Для несовершеннолетних необходимо согласие родителей.</p>
                    </div>
                    <div class="card">
                        <h4>Нужна ли справка от врача?</h4>
                        <p>Справка не обязательна, но рекомендуется для людей с хроническими заболеваниями или после травм.</p>
                    </div>
                    <div class="card">
                        <h4>Можно ли заморозить абонемент?</h4>
                        <p>Да, абонемент можно заморозить на срок от 7 дней до 1 месяца при наличии уважительной причины.</p>
                    </div>
                    <div class="card">
                        <h4>Есть ли детские группы?</h4>
                        <p>В настоящее время детские группы не работают, но мы планируем их запуск в ближайшее время.</p>
                    </div>
                    <div class="card">
                        <h4>Работает ли клуб в праздники?</h4>
                        <p>Да, мы работаем в праздничные дни по сокращенному графику: с 10:00 до 20:00.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
