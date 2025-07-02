<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$attendance_id = $input['attendance_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$attendance_id) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Проверяем, принадлежит ли запись пользователю и можно ли её отменить
    $query = "SELECT wa.*, s.schedule_date, s.start_time, p.name as program_name
              FROM WorkoutAttendances wa 
              JOIN Schedules s ON wa.schedule_id = s.schedule_id 
              JOIN WorkoutTypes wt ON s.workout_type_id = wt.workout_type_id
              JOIN Programs p ON wt.program_id = p.program_id
              WHERE wa.attendance_id = :attendance_id AND wa.user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':attendance_id', $attendance_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Запись не найдена']);
        exit;
    }

    // Проверяем, можно ли отменить (за 2 часа до начала)
    $session_time = strtotime($booking['schedule_date'] . ' ' . $booking['start_time']);
    $current_time = time();
    $time_diff = $session_time - $current_time;

    if ($time_diff < 2 * 3600) { // 2 часа в секундах
        echo json_encode(['success' => false, 'message' => 'Отмена возможна только за 2 часа до начала тренировки']);
        exit;
    }

    // Начинаем транзакцию
    $conn->beginTransaction();

    // Удаляем запись
    $query = "DELETE FROM WorkoutAttendances WHERE attendance_id = :attendance_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':attendance_id', $attendance_id);
    $stmt->execute();

    // Уменьшаем количество забронированных мест
    $query = "UPDATE Schedules SET booked_spots = booked_spots - 1 WHERE schedule_id = :schedule_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':schedule_id', $booking['schedule_id']);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Запись на тренировку "' . $booking['program_name'] . '" успешно отменена'
    ]);

} catch(PDOException $exception) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("Ошибка отмены записи: " . $exception->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка отмены записи']);
}
?>
