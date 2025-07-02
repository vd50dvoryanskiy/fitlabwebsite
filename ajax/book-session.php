<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$session_id = $input['session_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Проверяем, есть ли свободные места
    $query = "SELECT s.*, (s.available_spots - s.booked_spots) as free_spots, 
                     p.name as program_name, t.full_name as trainer_name
              FROM Schedules s
              JOIN WorkoutTypes wt ON s.workout_type_id = wt.workout_type_id
              JOIN Programs p ON wt.program_id = p.program_id
              JOIN Trainers t ON s.trainer_id = t.trainer_id
              WHERE s.schedule_id = :session_id AND s.is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':session_id', $session_id);
    $stmt->execute();
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Тренировка не найдена']);
        exit;
    }
    
    if ($session['free_spots'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Нет свободных мест']);
        exit;
    }
    
    // Проверяем, не записан ли уже пользователь
    $query = "SELECT attendance_id FROM WorkoutAttendances WHERE user_id = :user_id AND schedule_id = :session_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':session_id', $session_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Вы уже записаны на эту тренировку']);
        exit;
    }
    
    // Начинаем транзакцию
    $conn->beginTransaction();
    
    // Создаем запись в WorkoutAttendances
    $query = "INSERT INTO WorkoutAttendances (user_id, schedule_id, attendance_date) VALUES (:user_id, :session_id, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':session_id', $session_id);
    $stmt->execute();
    
    // Увеличиваем количество забронированных мест
    $query = "UPDATE Schedules SET booked_spots = booked_spots + 1 WHERE schedule_id = :session_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':session_id', $session_id);
    $stmt->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Вы успешно записались на тренировку "' . $session['program_name'] . '" к тренеру ' . $session['trainer_name'] . '!'
    ]);
    
} catch(PDOException $exception) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Ошибка записи на тренировку']);
}
?>
