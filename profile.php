<?php
session_start();
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();
$user_id = $user['user_id'];

$database = new Database();
$conn = $database->getConnection();

// Получаем информацию о пользователе
$query = "SELECT * FROM Users WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userInfo) {
    error_log("Fatal Error: User info not found in DB for user_id: " . $user_id);
    header('Location: login.php');
    exit;
}

// Получаем записи пользователя на тренировки
$query = "SELECT wa.*, s.schedule_date, s.start_time, s.end_time,
                 p.name as program_name, p.duration_minutes,
                 t.full_name as trainer_name, r.name as room_name
          FROM WorkoutAttendances wa
          JOIN Schedules s ON wa.schedule_id = s.schedule_id
          JOIN WorkoutTypes wt ON s.workout_type_id = wt.workout_type_id
          JOIN Programs p ON wt.program_id = p.program_id
          JOIN Trainers t ON s.trainer_id = t.trainer_id
          JOIN Rooms r ON s.room_id = r.room_id
          WHERE wa.user_id = :user_id
            AND CONCAT(s.schedule_date, ' ', s.start_time) >= NOW()
          ORDER BY s.schedule_date, s.start_time";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$upcomingBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем историю тренировок
$query = "SELECT wa.*, s.schedule_date, s.start_time, s.end_time,
                 p.name as program_name, p.duration_minutes,
                 t.full_name as trainer_name, r.name as room_name
          FROM WorkoutAttendances wa
          JOIN Schedules s ON wa.schedule_id = s.schedule_id
          JOIN WorkoutTypes wt ON s.workout_type_id = wt.workout_type_id
          JOIN Programs p ON wt.program_id = p.program_id
          JOIN Trainers t ON s.trainer_id = t.trainer_id
          JOIN Rooms r ON s.room_id = r.room_id
          WHERE wa.user_id = :user_id
            AND CONCAT(s.schedule_date, ' ', s.start_time) < NOW()
          ORDER BY s.schedule_date DESC, s.start_time DESC
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$pastBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем статистику пользователя
$query = "SELECT COUNT(*) as total_bookings FROM WorkoutAttendances WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$totalBookings = $stmt->fetch(PDO::FETCH_ASSOC)['total_bookings'];

$message = '';
$message_type = '';

// Обработка обновления профиля
if ($_POST && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $phone_number = trim($_POST['phone_number']);

    if (empty($full_name)) {
        $message = 'Полное имя обязательно для заполнения';
        $message_type = 'error';
    } else {
        try {
            $query = "UPDATE Users SET full_name = :full_name, phone_number = :phone_number WHERE user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':phone_number', $phone_number);
            $stmt->bindParam(':user_id', $user_id);

            if ($stmt->execute()) {
                $message = 'Профиль успешно обновлен';
                $message_type = 'success';
                // Обновляем данные в сессии и массиве userInfo
                $_SESSION['full_name'] = $full_name;
                $userInfo['full_name'] = $full_name;
                $userInfo['phone_number'] = $phone_number;
            }
        } catch(PDOException $exception) {
            $message = 'Ошибка обновления профиля: ' . $exception->getMessage();
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - FitLab</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <section class="section profile-hero">
            <div class="container">
                <div class="profile-welcome">
                    <h1 class="profile-title">Добро пожаловать, <?php echo htmlspecialchars($userInfo['full_name']); ?>!</h1>
                    <p>Управляйте своими тренировками и следите за прогрессом</p>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-4 profile-stats">
                    <div class="card stats-card stats-card-blue">
                        <div class="stats-number"><?php echo count($upcomingBookings); ?></div>
                        <p class="stats-label">Предстоящих тренировок</p>
                    </div>
                    <div class="card stats-card stats-card-green">
                        <div class="stats-number"><?php echo $totalBookings; ?></div>
                        <p class="stats-label">Всего записей</p>
                    </div>
                    <div class="card stats-card stats-card-orange">
                        <div class="stats-number"><?php echo count($pastBookings); ?></div>
                        <p class="stats-label">Завершенных тренировок</p>
                    </div>
                    <div class="card stats-card stats-card-purple">
                        <div class="stats-number"><?php echo date_diff(date_create($userInfo['registration_date']), date_create('now'))->days; ?></div>
                        <p class="stats-label">Дней с нами</p>
                    </div>
                </div>

                <div class="grid grid-3">
                    <div class="card">
                        <h3>Мой профиль</h3>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="username">Имя пользователя</label>
                                <input type="text" id="username" class="form-control"
                                        value="<?php echo htmlspecialchars($userInfo['username']); ?>" disabled>
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" class="form-control"
                                        value="<?php echo htmlspecialchars($userInfo['email']); ?>" disabled>
                            </div>

                            <div class="form-group">
                                <label for="full_name">Полное имя *</label>
                                <input type="text" id="full_name" name="full_name" class="form-control"
                                        value="<?php echo htmlspecialchars($userInfo['full_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="phone_number">Телефон</label>
                                <input type="tel" id="phone_number" name="phone_number" class="form-control"
                                        value="<?php echo htmlspecialchars($userInfo['phone_number']); ?>">
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-primary btn-full-width">
                                Обновить профиль
                            </button>
                        </form>

                        <div class="profile-registration-date">
                            <small class="profile-date-text">
                                Регистрация: <?php echo date('d.m.Y', strtotime($userInfo['registration_date'])); ?>
                            </small>
                        </div>
                    </div>

                    <div class="card">
                        <h3>Быстрые действия</h3>
                        <div class="profile-actions">
                            <a href="schedule.php" class="btn btn-primary">Записаться на тренировку</a>
                            <a href="programs.php" class="btn btn-secondary">Посмотреть программы</a>
                            <a href="trainers.php" class="btn btn-secondary">Наши тренеры</a>
                            <a href="memberships.php" class="btn btn-secondary">Купить абонемент</a>
                        </div>

                        <div class="next-workout-card">
                            <h4 class="next-workout-title">Ближайшая тренировка</h4>
                            <?php if (!empty($upcomingBookings)): ?>
                                <?php $nextBooking = $upcomingBookings[0]; ?>
                                <p class="next-workout-name"><strong><?php echo htmlspecialchars($nextBooking['program_name']); ?></strong></p>
                                <p class="next-workout-date">
                                    <?php echo date('d.m.Y в H:i', strtotime($nextBooking['schedule_date'] . ' ' . $nextBooking['start_time'])); ?>
                                </p>
                                <p class="next-workout-trainer">
                                    Тренер: <?php echo htmlspecialchars($nextBooking['trainer_name']); ?>
                                </p>
                            <?php else: ?>
                                <p class="no-workouts">Нет записей</p>
                                <a href="schedule.php" class="btn btn-primary btn-small">Записаться</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <h3>Уведомления</h3>
                        <div class="notifications">
                            <?php if (count($upcomingBookings) > 0): ?>
                                <div class="notification notification-success">
                                    <strong class="notification-title">У вас <?php echo count($upcomingBookings); ?> предстоящих тренировок</strong>
                                </div>
                            <?php else: ?>
                                <div class="notification notification-warning">
                                    <strong class="notification-title">Нет записей на тренировки</strong><br>
                                    <small>Запишитесь на тренировку прямо сейчас!</small>
                                </div>
                            <?php endif; ?>

                            <div class="notification notification-info">
                                <strong class="notification-title">Совет дня:</strong><br>
                                <small>Не забывайте пить воду во время тренировок и приходить за 10 минут до начала!</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="profile-section">
                    <h2>Предстоящие тренировки</h2>
                    <?php if (empty($upcomingBookings)): ?>
                        <div class="card">
                            <p class="no-data-message">
                                У вас нет записей на предстоящие тренировки
                            </p>
                            <div class="no-data-actions">
                                <a href="schedule.php" class="btn btn-primary">Записаться на тренировку</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-2">
                            <?php foreach ($upcomingBookings as $booking): ?>
                                <div class="card">
                                    <div class="booking-header">
                                        <h4><?php echo htmlspecialchars($booking['program_name']); ?></h4>
                                        <span class="badge badge-active">Активна</span>
                                    </div>

                                    <div class="booking-details">
                                        <p><strong>Дата:</strong> <?php echo date('d.m.Y', strtotime($booking['schedule_date'])); ?></p>
                                        <p><strong>Время:</strong> <?php echo date('H:i', strtotime($booking['start_time'])); ?> - <?php echo date('H:i', strtotime($booking['end_time'])); ?></p>
                                        <p><strong>Длительность:</strong> <?php echo $booking['duration_minutes']; ?> минут</p>
                                        <p><strong>Тренер:</strong> <?php echo htmlspecialchars($booking['trainer_name']); ?></p>
                                        <p><strong>Зал:</strong> <?php echo htmlspecialchars($booking['room_name']); ?></p>
                                    </div>

                                    <div class="booking-actions">
                                        <button class="btn btn-secondary btn-small"
                                                onclick="cancelBooking(<?php echo $booking['attendance_id']; ?>)">
                                            Отменить запись
                                        </button>
                                        <small class="cancel-note">
                                            Отмена за 2 часа до начала
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profile-section">
                    <h2>История тренировок</h2>
                    <?php if (empty($pastBookings)): ?>
                        <div class="card">
                            <p class="no-data-message">
                                История тренировок пуста
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Дата</th>
                                            <th>Программа</th>
                                            <th>Тренер</th>
                                            <th>Зал</th>
                                            <th>Длительность</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pastBookings as $booking): ?>
                                            <tr>
                                                <td><?php echo date('d.m.Y H:i', strtotime($booking['schedule_date'] . ' ' . $booking['start_time'])); ?></td>
                                                <td><?php echo htmlspecialchars($booking['program_name']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['trainer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                                <td><?php echo $booking['duration_minutes']; ?> мин</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
    <script>
        function cancelBooking(attendanceId) {
            if (!confirm('Вы уверены, что хотите отменить запись на тренировку?')) {
                return;
            }

            fetch('ajax/cancel-booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    attendance_id: attendanceId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Успех: ' + data.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                alert('Произошла ошибка при отмене записи');
            });
        }
    </script>
</body>
</html>
