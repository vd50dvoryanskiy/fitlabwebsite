<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = $auth->getCurrentUser();
$database = new Database();
$conn = $database->getConnection();

// Получаем статистику
$stats = [];

try {
    // Общее количество клиентов
    $query = "SELECT COUNT(*) as count FROM Users WHERE role = 'user'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Количество тренеров
    $query = "SELECT COUNT(*) as count FROM Trainers";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['trainers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Активные абонементы
    $query = "SELECT COUNT(*) as count FROM Memberships WHERE is_active = 1 AND end_date >= CURDATE()";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['active_memberships'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Тренировки сегодня
    $query = "SELECT COUNT(*) as count FROM Schedules WHERE schedule_date = CURDATE()";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['today_workouts'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

} catch(PDOException $e) {
    $stats = ['clients' => 0, 'trainers' => 0, 'active_memberships' => 0, 'today_workouts' => 0];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Административная панель - FitLab</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="../index.php" class="logo">FitLab Admin</a>
            <nav class="nav">
                <ul>
                    <li><a href="index.php">Главная</a></li>
                    <li><a href="users.php">Пользователи</a></li>
                    <li><a href="trainers.php">Тренеры</a></li>
                    <li><a href="programs.php">Программы</a></li>
                    <li><a href="workouts.php">Тренировки</a></li>
                    <li><a href="schedules.php">Расписание</a></li>
                    <li><a href="memberships.php">Абонементы</a></li>
                    <li><a href="clients.php">Отчеты</a></li>
                    <li><a href="../views.php">Представления</a></li>
                    <li><a href="../index.php">На сайт</a></li>
                    <li><a href="../logout.php">Выход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <section class="section">
            <div class="container">
                <h1>Административная панель</h1>
                <p>Добро пожаловать, <?php echo htmlspecialchars($user['full_name']); ?>!</p>

                <!-- Статистика -->
                <div class="grid grid-4" style="margin: 2rem 0;">
                    <div class="card stats-card" style="background: linear-gradient(135deg, var(--accent-blue), var(--hover-blue));">
                        <div class="stats-number"><?php echo $stats['clients']; ?></div>
                        <p class="stats-label">Клиентов</p>
                    </div>
                    <div class="card stats-card" style="background: linear-gradient(135deg, var(--energy-orange), var(--hover-orange));">
                        <div class="stats-number"><?php echo $stats['trainers']; ?></div>
                        <p class="stats-label">Тренеров</p>
                    </div>
                    <div class="card stats-card" style="background: linear-gradient(135deg, var(--success-green), #45A049);">
                        <div class="stats-number"><?php echo $stats['active_memberships']; ?></div>
                        <p class="stats-label">Активных абонементов</p>
                    </div>
                    <div class="card stats-card" style="background: linear-gradient(135deg, #9C27B0, #7B1FA2);">
                        <div class="stats-number"><?php echo $stats['today_workouts']; ?></div>
                        <p class="stats-label">Тренировок сегодня</p>
                    </div>
                </div>

                <!-- Быстрые действия -->
                <div class="grid grid-2">
                    <div class="card">
                        <h3>Управление</h3>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <a href="users.php" class="btn btn-primary">Управление пользователями</a>
                            <a href="trainers.php" class="btn btn-primary">Управление тренерами</a>
                            <a href="schedules.php" class="btn btn-secondary">Управление расписанием</a>
                            <a href="memberships.php" class="btn btn-secondary">Управление абонементами</a>
                        </div>
                    </div>

                    <div class="card">
                        <h3>Отчеты и аналитика</h3>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <a href="clients.php" class="btn btn-primary">Отчеты</a>
                            <a href="clients.php?type=all" class="btn btn-secondary">Отчет по клиентам</a>
                            <a href="clients.php?type=trainers_info" class="btn btn-secondary">Отчет по тренерам</a>
                            <a href="clients.php?type=members_expiring_soon" class="btn btn-secondary">Отчет по абонементам</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 FitLab Admin Panel. Все права защищены.</p>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>
