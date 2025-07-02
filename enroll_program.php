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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['program_id'])) {
    $program_id = intval($_POST['program_id']);
    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Найти любое расписание для этой программы
        $stmt = $conn->prepare("SELECT schedule_id FROM Schedules 
                                 WHERE workout_type_id IN 
                                     (SELECT workout_type_id FROM WorkoutTypes WHERE program_id = ?)
                                 AND schedule_date >= CURDATE()
                                 ORDER BY schedule_date ASC, start_time ASC
                                 LIMIT 1");
        $stmt->execute([$program_id]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            $_SESSION['message'] = 'Нет доступного расписания для этой программы.';
            $_SESSION['message_type'] = 'error';
            header('Location: programs.php');
            exit;
        }

        $schedule_id = $schedule['schedule_id'];

        // Проверить, записан ли уже пользователь на эту тренировку
        $stmt = $conn->prepare("SELECT * FROM WorkoutAttendances 
                                WHERE user_id = ? AND schedule_id = ?");
        $stmt->execute([$user_id, $schedule_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['message'] = 'Вы уже записаны на эту тренировку';
            $_SESSION['message_type'] = 'warning';
        } else {
            // Записываем пользователя на тренировку
            $stmt = $conn->prepare("INSERT INTO WorkoutAttendances (user_id, schedule_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $schedule_id]);

            $_SESSION['message'] = 'Вы успешно записались на тренировку!';
            $_SESSION['message_type'] = 'success';
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Ошибка при записи: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }

    header('Location: programs.php');
    exit;
} else {
    header('Location: programs.php');
    exit;
}