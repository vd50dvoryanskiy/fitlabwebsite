<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$view_type = $_GET['view'] ?? '';
$data = [];
$view_title = '';

// Функции для получения данных представлений
function getView1($conn) {
    $query = "SELECT * FROM view_1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getView2($conn) {
    $query = "SELECT * FROM view_2";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getView3($conn) {
    $query = "SELECT * FROM view_3";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getView4($conn) {
    $query = "SELECT * FROM view_4";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getView5($conn) {
    $query = "SELECT * FROM view_5";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getView6($conn) {
    $query = "SELECT * FROM view_6";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getView7($conn) {
    $query = "SELECT * FROM view_7";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getView8($conn) {
    $query = "SELECT * FROM view_8";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getView9($conn) {
    $query = "SELECT * FROM view_9";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getView10($conn) {
    $query = "SELECT * FROM view_10";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Обработка выбора представления
if ($view_type) {
    try {
        switch ($view_type) {
            case 'view1':
                $data = getView1($conn);
                $view_title = 'Представление 1 - Записи пользователей на тренировки';
                break;
            case 'view2':
                $data = getView2($conn);
                $view_title = 'Представление 2 - Популярность тренировок';
                break;
            case 'view3':
                $data = getView3($conn);
                $view_title = 'Представление 3 - Расписание тренеров';
                break;
            case 'view4':
                $data = getView4($conn);
                $view_title = 'Представление 4 - Абонементы пользователей';
                break;
            case 'view5':
                $data = getView5($conn);
                $view_title = 'Представление 5 - Статистика записей пользователей';
                break;
            case 'view6':
                $data = getView6($conn);
                $view_title = 'Представление 6 - Статистика расписания по типам тренировок';
                break;
            case 'view7':
                $data = getView7($conn);
                $view_title = 'Представление 7 - Загруженность залов по дням';
                break;
            case 'view8':
                $data = getView8($conn);
                $view_title = 'Представление 8 - Активные абонементы';
                break;
            case 'view9':
                $data = getView9($conn);
                $view_title = 'Представление 9 - Детальное расписание';
                break;
            case 'view10':
                $data = getView10($conn);
                $view_title = 'Представление 10 - Нагрузка тренеров';
                break;
            default:
                $view_title = 'Неизвестное представление';
                break;
        }
    } catch(PDOException $e) {
        $view_title = 'Ошибка загрузки данных';
        $data = [];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Представления БД - FitLab</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <section class="section">
            <div class="container">
                <h1>Представления базы данных</h1>
                <p>Выберите представление для просмотра данных</p>

                <!-- Кнопки для выбора представлений -->
                <div class="card">
                    <h3>Доступные представления</h3>
                    <div class="grid grid-5" style="gap: 1rem; margin-bottom: 2rem;">
                        <a href="?view=view1" class="btn btn-primary">Представление 1</a>
                        <a href="?view=view2" class="btn btn-primary">Представление 2</a>
                        <a href="?view=view3" class="btn btn-primary">Представление 3</a>
                        <a href="?view=view4" class="btn btn-primary">Представление 4</a>
                        <a href="?view=view5" class="btn btn-primary">Представление 5</a>
                        <a href="?view=view6" class="btn btn-secondary">Представление 6</a>
                        <a href="?view=view7" class="btn btn-secondary">Представление 7</a>
                        <a href="?view=view8" class="btn btn-secondary">Представление 8</a>
                        <a href="?view=view9" class="btn btn-secondary">Представление 9</a>
                        <a href="?view=view10" class="btn btn-secondary">Представление 10</a>
                    </div>
                </div>

                <!-- Результаты представления -->
                <?php if ($view_type && $view_title): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($view_title); ?></h3>
                        
                        <?php if (empty($data)): ?>
                            <p style="text-align: center; color: var(--medium-gray); font-style: italic;">
                                Данные не найдены или произошла ошибка
                            </p>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <?php foreach (array_keys($data[0]) as $column): ?>
                                                <th><?php echo htmlspecialchars($column); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $value): ?>
                                                    <td><?php echo htmlspecialchars($value ?? '-'); ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div style="margin-top: 1rem; text-align: center;">
                                <small style="color: var(--medium-gray);">
                                    Всего записей: <?php echo count($data); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Описание представлений -->
                <div class="card">
                    <h3>Описание представлений</h3>
                    <div class="grid grid-2">
                        <div>
                            <h4>Представления 1-5:</h4>
                            <ul style="list-style-type: disc; margin-left: 2rem; color: var(--text-white);">
                                <li><strong>Представление 1:</strong> Записи пользователей на тренировки</li>
                                <li><strong>Представление 2:</strong> Популярность тренировок по количеству записей</li>
                                <li><strong>Представление 3:</strong> Расписание тренеров с деталями</li>
                                <li><strong>Представление 4:</strong> Абонементы пользователей</li>
                                <li><strong>Представление 5:</strong> Статистика записей по пользователям</li>
                            </ul>
                        </div>
                        <div>
                            <h4>Представления 6-10:</h4>
                            <ul style="list-style-type: disc; margin-left: 2rem; color: var(--text-white);">
                                <li><strong>Представление 6:</strong> Статистика расписания по типам тренировок</li>
                                <li><strong>Представление 7:</strong> Загруженность залов по дням</li>
                                <li><strong>Представление 8:</strong> Активные абонементы пользователей</li>
                                <li><strong>Представление 9:</strong> Детальное расписание с информацией</li>
                                <li><strong>Представление 10:</strong> Нагрузка тренеров по количеству занятий</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
