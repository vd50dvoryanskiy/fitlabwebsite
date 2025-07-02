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
$request_id = $input['request_id'] ?? null;
$status = $input['status'] ?? null;

if (!$request_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "UPDATE contact_requests SET status = :status WHERE id = :request_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':request_id', $request_id);
    
    if ($stmt->execute()) {
        $statusNames = [
            'in_progress' => 'взята в работу',
            'resolved' => 'отмечена как решенная'
        ];
        $action = $statusNames[$status] ?? 'обновлена';
        echo json_encode(['success' => true, 'message' => "Заявка успешно $action"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка обновления статуса']);
    }
} catch(PDOException $exception) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
}
?>
