<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$booking_id = $input['booking_id'] ?? null;
$status = $input['status'] ?? null;

if (!$booking_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "UPDATE bookings SET status = :status WHERE id = :booking_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':booking_id', $booking_id);
    
    if ($stmt->execute()) {
        $statusNames = [
            'completed' => 'завершена',
            'cancelled' => 'отменена'
        ];
        $action = $statusNames[$status] ?? 'обновлена';
        echo json_encode(['success' => true, 'message' => "Запись успешно $action"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка обновления статуса']);
    }
} catch(PDOException $exception) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
}
?>
