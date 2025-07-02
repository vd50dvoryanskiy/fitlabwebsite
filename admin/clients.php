<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$report_type = $_GET['type'] ?? 'all';
$date_filter = $_GET['date'] ?? '';
$workout_filter = $_GET['workout'] ?? '';
$trainer_filter = $_GET['trainer'] ?? '';

// === Функции для отчетов ===

function getAllClients($conn) {
    $query = "SELECT user_id AS client_id, full_name, email, phone_number, registration_date 
              FROM Users 
              WHERE role = 'user' 
              ORDER BY registration_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getWorkoutsByDate($conn, $date) {
    $query = "SELECT s.*, wt.name as workout_name, t.full_name as trainer_name, r.name as room_name
              FROM Schedules s
              JOIN WorkoutTypes wt ON s.workout_type_id = wt.workout_type_id
              JOIN Trainers t ON s.trainer_id = t.trainer_id
              JOIN Rooms r ON s.room_id = r.room_id
              WHERE s.schedule_date = :date
              ORDER BY s.start_time";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTrainersInfo($conn) {
    $query = "SELECT * FROM Trainers ORDER BY full_name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getClientsByWorkout($conn, $workout_type_id) {
    $query = "SELECT DISTINCT u.user_id AS client_id, u.full_name, u.phone_number, u.email
              FROM Users u
              JOIN WorkoutAttendances wa ON u.user_id = wa.user_id
              JOIN Schedules s ON wa.schedule_id = s.schedule_id
              WHERE s.workout_type_id = :workout_type_id
              AND u.role = 'user'
              ORDER BY u.full_name";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':workout_type_id', $workout_type_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMembersExpiringInMonth($conn, $month, $year) {
    $query = "SELECT u.user_id AS client_id, u.full_name, m.membership_type, m.start_date, m.end_date
              FROM Users u
              JOIN UserMemberships um ON u.user_id = um.user_id
              JOIN Memberships m ON um.membership_id = m.membership_id
              WHERE MONTH(m.end_date) = :month AND YEAR(m.end_date) = :year
              AND u.role = 'user'
              ORDER BY m.end_date";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':month', $month);
    $stmt->bindParam(':year', $year);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMembersExpiringSoon($conn) {
    $query = "SELECT u.user_id AS client_id, u.full_name, m.membership_type, m.start_date, m.end_date
              FROM Users u
              JOIN UserMemberships um ON u.user_id = um.user_id
              JOIN Memberships m ON um.membership_id = m.membership_id
              WHERE m.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
              AND u.role = 'user'
              ORDER BY m.end_date";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getActiveMembersWithoutWorkouts($conn) {
    $query = "SELECT u.user_id AS client_id, u.full_name, m.membership_type, m.start_date, m.end_date
              FROM Users u
              JOIN UserMemberships um ON u.user_id = um.user_id
              JOIN Memberships m ON um.membership_id = m.membership_id
              LEFT JOIN WorkoutAttendances wa ON u.user_id = wa.user_id
              WHERE m.is_active = 1 AND m.end_date >= CURDATE() AND wa.attendance_id IS NULL
              AND u.role = 'user'
              ORDER BY u.full_name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// === Получаем данные для фильтров ===

try {
    $workoutTypes = $conn->query("SELECT * FROM WorkoutTypes ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $trainers = $conn->query("SELECT * FROM Trainers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
    $rooms = $conn->query("SELECT * FROM Rooms ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка загрузки данных: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчеты - FitLab Admin</title>
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
            <h1>Отчеты</h1>

            <!-- Форма выбора типа отчета -->
            <form method="GET" action="" class="card" style="margin-bottom: 2rem;">
                <div class="grid grid-4">
                    <div class="form-group">
                        <label for="type">Тип отчета</label>
                        <select id="type" name="type" class="form-control" onchange="this.form.submit()">
                            <option value="all" <?= $report_type == 'all' ? 'selected' : '' ?>>Все клиенты</option>
                            <option value="workouts_by_date" <?= $report_type == 'workouts_by_date' ? 'selected' : '' ?>>Тренировки по дате</option>
                            <option value="trainers_info" <?= $report_type == 'trainers_info' ? 'selected' : '' ?>>Информация о тренерах</option>
                            <option value="clients_by_workout" <?= $report_type == 'clients_by_workout' ? 'selected' : '' ?>>Клиенты по типу тренировки</option>
                            <option value="members_expiring_in_month" <?= $report_type == 'members_expiring_in_month' ? 'selected' : '' ?>>Абонементы истекают в этом месяце</option>
                            <option value="members_expiring_soon" <?= $report_type == 'members_expiring_soon' ? 'selected' : '' ?>>Скоро истекают абонементы</option>
                            <option value="active_members_without_workouts" <?= $report_type == 'active_members_without_workouts' ? 'selected' : '' ?>>Активные без тренировок</option>
                        </select>
                    </div>

                    <?php if ($report_type === 'workouts_by_date'): ?>
                        <div class="form-group">
                            <label for="date">Выберите дату</label>
                            <input type="date" id="date" name="date" class="form-control" value="<?= htmlspecialchars($date_filter); ?>">
                        </div>
                    <?php endif; ?>

                    <?php if ($report_type === 'clients_by_workout'): ?>
                        <div class="form-group">
                            <label for="workout">Выберите тип тренировки</label>
                            <select id="workout" name="workout" class="form-control">
                                <option value="">-- Выберите тип --</option>
                                <?php foreach ($workoutTypes as $type): ?>
                                    <option value="<?= $type['workout_type_id']; ?>" <?= $workout_filter == $type['workout_type_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary">Показать</button>
            </form>

            <!-- Блок вывода данных -->
            <div class="card">
                <?php
                switch ($report_type):
                    case 'all':
                        $data = getAllClients($conn);
                        echo '<h3>Все клиенты (' . count($data) . ')</h3>';
                        if (!empty($data)) {
                            echo '<div style="overflow-x:auto;"><table class="table"><thead><tr>
                                    <th>ID</th>
                                    <th>Полное имя</th>
                                    <th>Телефон</th>
                                    <th>Email</th>
                                    <th>Дата регистрации</th>
                                  </tr></thead><tbody>';
                            foreach ($data as $row) {
                                echo '<tr>
                                        <td>' . $row['client_id'] . '</td>
                                        <td>' . htmlspecialchars($row['full_name']) . '</td>
                                        <td>' . htmlspecialchars($row['phone_number'] ?: '-') . '</td>
                                        <td>' . htmlspecialchars($row['email'] ?: '-') . '</td>
                                        <td>' . date('d.m.Y H:i', strtotime($row['registration_date'])) . '</td>
                                      </tr>';
                            }
                            echo '</tbody></table></div>';
                        } else {
                            echo '<p>Клиенты не найдены</p>';
                        }
                        break;

                    case 'workouts_by_date':
                        if ($date_filter) {
                            $data = getWorkoutsByDate($conn, $date_filter);
                            echo '<h3>Тренировки на ' . date('d.m.Y', strtotime($date_filter)) . ' (' . count($data) . ')</h3>';
                            if (!empty($data)) {
                                echo '<div style="overflow-x:auto;"><table class="table"><thead><tr>
                                        <th>Тренировка</th>
                                        <th>Тренер</th>
                                        <th>Зал</th>
                                        <th>Начало</th>
                                        <th>Окончание</th>
                                        <th>Макс. участников</th>
                                      </tr></thead><tbody>';
                                foreach ($data as $row) {
                                    echo '<tr>
                                            <td>' . htmlspecialchars($row['workout_name']) . '</td>
                                            <td>' . htmlspecialchars($row['trainer_name']) . '</td>
                                            <td>' . htmlspecialchars($row['room_name']) . '</td>
                                            <td>' . substr($row['start_time'], 0, 5) . '</td>
                                            <td>' . substr($row['end_time'], 0, 5) . '</td>
                                            <td>' . $row['max_participants'] . '</td>
                                          </tr>';
                                }
                                echo '</tbody></table></div>';
                            } else {
                                echo '<p>На эту дату нет расписаний</p>';
                            }
                        } else {
                            echo '<h3>Выберите дату</h3>';
                        }
                        break;

                    case 'trainers_info':
                        $data = getTrainersInfo($conn);
                        echo '<h3>Информация о тренерах (' . count($data) . ')</h3>';
                        if (!empty($data)) {
                            echo '<div style="overflow-x:auto;"><table class="table"><thead><tr>
                                    <th>ID</th>
                                    <th>Имя</th>
                                    <th>Телефон</th>
                                    <th>Email</th>
                                    <th>Специализация</th>
                                    <th>Дата найма</th>
                                  </tr></thead><tbody>';
                            foreach ($data as $row) {
                                echo '<tr>
                                        <td>' . $row['trainer_id'] . '</td>
                                        <td>' . htmlspecialchars($row['full_name']) . '</td>
                                        <td>' . htmlspecialchars($row['phone_number'] ?: '-') . '</td>
                                        <td>' . htmlspecialchars($row['email'] ?: '-') . '</td>
                                        <td>' . htmlspecialchars($row['specialization'] ?: '-') . '</td>
                                        <td>' . date('d.m.Y', strtotime($row['hire_date'])) . '</td>
                                      </tr>';
                            }
                            echo '</tbody></table></div>';
                        } else {
                            echo '<p>Тренеры не найдены</p>';
                        }
                        break;

                    case 'clients_by_workout':
                        if ($workout_filter) {
                            $data = getClientsByWorkout($conn, $workout_filter);
                            $workoutName = '';
                            foreach ($workoutTypes as $type) {
                                if ($type['workout_type_id'] == $workout_filter) {
                                    $workoutName = $type['name'];
                                    break;
                                }
                            }
                            echo "<h3>Клиенты, занимающиеся \"$workoutName\" (" . count($data) . ")</h3>";
                            if (!empty($data)) {
                                echo '<div style="overflow-x:auto;"><table class="table"><thead><tr>
                                        <th>ID</th>
                                        <th>Имя</th>
                                        <th>Телефон</th>
                                        <th>Email</th>
                                      </tr></thead><tbody>';
                                foreach ($data as $row) {
                                    echo '<tr>
                                            <td>' . $row['client_id'] . '</td>
                                            <td>' . htmlspecialchars($row['full_name']) . '</td>
                                            <td>' . htmlspecialchars($row['phone_number'] ?: '-') . '</td>
                                            <td>' . htmlspecialchars($row['email'] ?: '-') . '</td>
                                          </tr>';
                                }
                                echo '</tbody></table></div>';
                            } else {
                                echo '<p>Клиенты не найдены</p>';
                            }
                        } else {
                            echo '<h3>Выберите тип тренировки</h3>';
                        }
                        break;

                    case 'members_expiring_in_month':
                        list($month, $year) = explode('-', date('m-Y'));
                        $data = getMembersExpiringInMonth($conn, $month, $year);
                        echo "<h3>Абонементы истекают в {$month}.{$year} (" . count($data) . ")</h3>";
                        if (!empty($data)) {
                            echo '<div style="overflow-x:auto;"><table class="table"><thead><tr>
                                    <th>Имя</th>
                                    <th>Тип абонемента</th>
                                    <th>Дата начала</th>
                                    <th>Дата окончания</th>
                                  </tr></thead><tbody>';
                            foreach ($data as $row) {
                                echo '<tr>
                                        <td>' . htmlspecialchars($row['full_name']) . '</td>
                                        <td>' . htmlspecialchars($row['membership_type']) . '</td>
                                        <td>' . date('d.m.Y', strtotime($row['start_date'])) . '</td>
                                        <td>' . date('d.m.Y', strtotime($row['end_date'])) . '</td>
                                      </tr>';
                            }
                            echo '</tbody></table></div>';
                        } else {
                            echo '<p>Клиентов с истекающими абонементами не найдено</p>';
                        }
                        break;

                    case 'members_expiring_soon':
                        $data = getMembersExpiringSoon($conn);
                        echo '<h3>Абонементы истекут в ближайшие 7 дней (' . count($data) . ')</h3>';
                        if (!empty($data)) {
                            echo '<div style="overflow-x:auto;"><table class="table"><thead><tr>
                                    <th>Имя</th>
                                    <th>Тип абонемента</th>
                                    <th>Дата начала</th>
                                    <th>Дата окончания</th>
                                  </tr></thead><tbody>';
                            foreach ($data as $row) {
                                echo '<tr>
                                        <td>' . htmlspecialchars($row['full_name']) . '</td>
                                        <td>' . htmlspecialchars($row['membership_type']) . '</td>
                                        <td>' . date('d.m.Y', strtotime($row['start_date'])) . '</td>
                                        <td>' . date('d.m.Y', strtotime($row['end_date'])) . '</td>
                                      </tr>';
                            }
                            echo '</tbody></table></div>';
                        } else {
                            echo '<p>Не найдено</p>';
                        }
                        break;

                    case 'active_members_without_workouts':
                        $data = getActiveMembersWithoutWorkouts($conn);
                        echo '<h3>Клиенты с активными абонементами, но без тренировок (' . count($data) . ')</h3>';
                        if (!empty($data)) {
                            echo '<div style="overflow-x:auto;"><table class="table"><thead><tr>
                                    <th>Имя</th>
                                    <th>Тип абонемента</th>
                                    <th>Дата начала</th>
                                    <th>Дата окончания</th>
                                  </tr></thead><tbody>';
                            foreach ($data as $row) {
                                echo '<tr>
                                        <td>' . htmlspecialchars($row['full_name']) . '</td>
                                        <td>' . htmlspecialchars($row['membership_type']) . '</td>
                                        <td>' . date('d.m.Y', strtotime($row['start_date'])) . '</td>
                                        <td>' . date('d.m.Y', strtotime($row['end_date'])) . '</td>
                                      </tr>';
                            }
                            echo '</tbody></table></div>';
                        } else {
                            echo '<p>Не найдено</p>';
                        }
                        break;

                    default:
                        echo '<h3>Выберите тип отчета</h3>';
                        break;
                endswitch;
                ?>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="container">
        <p>&copy; 2024 FitLab Admin Panel. Все права защищены.</p>
    </div>
</footer>
</body>
</html>
