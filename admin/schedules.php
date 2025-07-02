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

$message = '';
$message_type = '';

// Обработка добавления расписания
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $workout_type_id = $_POST['workout_type_id'];
    $trainer_id = $_POST['trainer_id'];
    $room_id = $_POST['room_id'];
    $schedule_date = $_POST['schedule_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $max_participants = $_POST['max_participants'] ?: null;

    if (empty($workout_type_id) || empty($trainer_id) || empty($room_id) || empty($schedule_date) || empty($start_time) || empty($end_time)) {
        $message = 'Все обязательные поля должны быть заполнены';
        $message_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO Schedules (workout_type_id, trainer_id, room_id, schedule_date, start_time, end_time, max_participants)
                                      VALUES (:workout_type_id, :trainer_id, :room_id, :schedule_date, :start_time, :end_time, :max_participants)");
            $stmt->execute([
                ':workout_type_id' => $workout_type_id,
                ':trainer_id' => $trainer_id,
                ':room_id' => $room_id,
                ':schedule_date' => $schedule_date,
                ':start_time' => $start_time,
                ':end_time' => $end_time,
                ':max_participants' => $max_participants
            ]);
            $message = 'Расписание успешно добавлено';
            $message_type = 'success';
            $_POST = [];
        } catch (PDOException $e) {
            $message = 'Ошибка добавления расписания: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Обработка редактирования расписания
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_schedule'])) {
    $schedule_id = intval($_POST['schedule_id']);
    $workout_type_id = $_POST['edit_workout_type_id'];
    $trainer_id = $_POST['edit_trainer_id'];
    $room_id = $_POST['edit_room_id'];
    $schedule_date = $_POST['edit_schedule_date'];
    $start_time = $_POST['edit_start_time'];
    $end_time = $_POST['edit_end_time'];
    $max_participants = $_POST['edit_max_participants'] ?: null;

    if (empty($workout_type_id) || empty($trainer_id) || empty($room_id) || empty($schedule_date) || empty($start_time) || empty($end_time)) {
        $message = 'Все обязательные поля должны быть заполнены';
        $message_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE Schedules SET 
                                        workout_type_id = :workout_type_id,
                                        trainer_id = :trainer_id,
                                        room_id = :room_id,
                                        schedule_date = :schedule_date,
                                        start_time = :start_time,
                                        end_time = :end_time,
                                        max_participants = :max_participants
                                      WHERE schedule_id = :schedule_id");
            $stmt->execute([
                ':workout_type_id' => $workout_type_id,
                ':trainer_id' => $trainer_id,
                ':room_id' => $room_id,
                ':schedule_date' => $schedule_date,
                ':start_time' => $start_time,
                ':end_time' => $end_time,
                ':max_participants' => $max_participants,
                ':schedule_id' => $schedule_id
            ]);

            $message = 'Расписание успешно обновлено';
            $message_type = 'success';

        } catch (PDOException $e) {
            $message = 'Ошибка обновления расписания: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Обработка удаления расписания
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM Schedules WHERE schedule_id = ?");
        $stmt->execute([$_GET['delete']]);
        $message = 'Расписание успешно удалено';
        $message_type = 'success';

    } catch (PDOException $e) {
        $message = 'Ошибка удаления расписания: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Получаем данные для форм
try {
    $workoutTypes = $conn->query("SELECT * FROM WorkoutTypes ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $trainers = $conn->query("SELECT * FROM Trainers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
    $rooms = $conn->query("SELECT * FROM Rooms ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

    $schedulesQuery = "SELECT s.*, wt.name as workout_name, t.full_name AS trainer_name, r.name AS room_name
                      FROM Schedules s
                      JOIN WorkoutTypes wt ON s.workout_type_id = wt.workout_type_id
                      JOIN Trainers t ON s.trainer_id = t.trainer_id
                      JOIN Rooms r ON s.room_id = r.room_id
                      ORDER BY s.schedule_date DESC, s.start_time";
    $stmt = $conn->prepare($schedulesQuery);
    $stmt->execute();
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $workoutTypes = [];
    $trainers = [];
    $rooms = [];
    $schedules = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление расписанием - FitLab Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <h1>Управление расписанием</h1>

            <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($message_type); ?>">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Форма добавления -->
            <div class="card">
                <h3>Добавить в расписание</h3>
                <form method="POST" action="">
                    <div class="grid grid-3">
                        <div class="form-group">
                            <label for="workout_type_id">Тип тренировки *</label>
                            <select id="workout_type_id" name="workout_type_id" class="form-control" required>
                                <option value="">Выберите тип</option>
                                <?php foreach ($workoutTypes as $type): ?>
                                    <option value="<?= $type['workout_type_id']; ?>" 
                                        <?= (isset($_POST['workout_type_id']) && $_POST['workout_type_id'] == $type['workout_type_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="trainer_id">Тренер *</label>
                            <select id="trainer_id" name="trainer_id" class="form-control" required>
                                <option value="">Выберите тренера</option>
                                <?php foreach ($trainers as $t): ?>
                                    <option value="<?= $t['trainer_id']; ?>"
                                        <?= (isset($_POST['trainer_id']) && $_POST['trainer_id'] == $t['trainer_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="room_id">Зал *</label>
                            <select id="room_id" name="room_id" class="form-control" required>
                                <option value="">Выберите зал</option>
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?= $r['room_id']; ?>"
                                        <?= (isset($_POST['room_id']) && $_POST['room_id'] == $r['room_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['name']); ?> (<?= $r['capacity']; ?> мест)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="schedule_date">Дата *</label>
                            <input type="date" id="schedule_date" name="schedule_date" class="form-control"
                                   value="<?= htmlspecialchars($_POST['schedule_date'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="start_time">Время начала *</label>
                            <input type="time" id="start_time" name="start_time" class="form-control"
                                   value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="end_time">Время окончания *</label>
                            <input type="time" id="end_time" name="end_time" class="form-control"
                                   value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="max_participants">Макс. участников</label>
                            <input type="number" id="max_participants" name="max_participants" class="form-control"
                                   value="<?= htmlspecialchars($_POST['max_participants'] ?? '') ?>" min="1">
                        </div>
                    </div>
                    <button type="submit" name="add_schedule" class="btn btn-primary">Добавить расписание</button>
                </form>
            </div>

            <!-- Список расписаний -->
            <div class="card">
                <h3>Список расписаний (<?php echo count($schedules); ?>)</h3>
                <?php if (empty($schedules)): ?>
                    <p>Нет доступных расписаний.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Тренировка</th>
                                    <th>Тренер</th>
                                    <th>Зал</th>
                                    <th>Дата</th>
                                    <th>Время</th>
                                    <th>Макс. участников</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $s): ?>
                                    <tr>
                                        <td><?= $s['schedule_id'] ?></td>
                                        <td><?= htmlspecialchars($s['workout_name']) ?></td>
                                        <td><?= htmlspecialchars($s['trainer_name']) ?></td>
                                        <td><?= htmlspecialchars($s['room_name']) ?></td>
                                        <td><?= date('d.m.Y', strtotime($s['schedule_date'])) ?></td>
                                        <td><?= substr($s['start_time'], 0, 5) ?> – <?= substr($s['end_time'], 0, 5) ?></td>
                                        <td><?= $s['max_participants'] ?: '-' ?></td>
                                        <td>
                                            <button class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem; margin-right: 0.5rem;"
                                                    onclick="editSchedule(<?= json_encode($s) ?>)">Изменить</button>
                                            <a href="?delete=<?= $s['schedule_id'] ?>" class="btn btn-secondary"
                                               style="font-size: 0.8rem; padding: 0.5rem 1rem;"
                                               onclick="return confirm('Вы уверены?')">Удалить</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<!-- Модальное окно редактирования -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span onclick="closeModal()" style="float:right; cursor:pointer; font-size: 24px;">&times;</span>
        <h3>Редактировать расписание</h3>
        <form method="POST" action="">
            <input type="hidden" id="edit_schedule_id" name="schedule_id">
            <div class="grid grid-3">
                <div class="form-group">
                    <label for="edit_workout_type_id">Тип тренировки *</label>
                    <select id="edit_workout_type_id" name="edit_workout_type_id" class="form-control" required>
                        <?php foreach ($workoutTypes as $type): ?>
                            <option value="<?= $type['workout_type_id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_trainer_id">Тренер *</label>
                    <select id="edit_trainer_id" name="edit_trainer_id" class="form-control" required>
                        <?php foreach ($trainers as $t): ?>
                            <option value="<?= $t['trainer_id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_room_id">Зал *</label>
                    <select id="edit_room_id" name="edit_room_id" class="form-control" required>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= $r['room_id'] ?>"><?= htmlspecialchars($r['name']) ?> (<?= $r['capacity'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_schedule_date">Дата *</label>
                    <input type="date" id="edit_schedule_date" name="edit_schedule_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_start_time">Время начала *</label>
                    <input type="time" id="edit_start_time" name="edit_start_time" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_end_time">Время окончания *</label>
                    <input type="time" id="edit_end_time" name="edit_end_time" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_max_participants">Максимум участников</label>
                    <input type="number" id="edit_max_participants" name="edit_max_participants" class="form-control" min="1">
                </div>
            </div>
            <button type="submit" name="edit_schedule" class="btn btn-primary">Сохранить изменения</button>
        </form>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p>&copy; 2024 FitLab Admin Panel. Все права защищены.</p>
    </div>
</footer>

<script>
function editSchedule(schedule) {
    document.getElementById('edit_schedule_id').value = schedule.schedule_id;

    // Проверяем время и убираем секунды, если они есть
    const startTime = schedule.start_time.includes(':') ? schedule.start_time.slice(0, 5) : schedule.start_time;
    const endTime = schedule.end_time.includes(':') ? schedule.end_time.slice(0, 5) : schedule.end_time;

    document.getElementById('edit_workout_type_id').value = schedule.workout_type_id;
    document.getElementById('edit_trainer_id').value = schedule.trainer_id;
    document.getElementById('edit_room_id').value = schedule.room_id;
    document.getElementById('edit_schedule_date').value = schedule.schedule_date;
    document.getElementById('edit_start_time').value = startTime;
    document.getElementById('edit_end_time').value = endTime;
    document.getElementById('edit_max_participants').value = schedule.max_participants || '';

    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}
.modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 2rem;
    border: 1px solid #888;
    width: 70%;
    border-radius: 8px;
}
</style>

</body>
</html>
