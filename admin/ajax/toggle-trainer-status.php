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
$trainer_id = $input['trainer_id'] ?? null;
$is_active = $input['is_active'] ?? null;

if (!$trainer_id || $is_active === null) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "UPDATE trainers SET is_active = :is_active WHERE id = :trainer_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':is_active', $is_active, PDO::PARAM_BOOL);
    $stmt->bindParam(':trainer_id', $trainer_id);
    
    if ($stmt->execute()) {
        $action = $is_active ? 'активирован' : 'деактивирован';
        echo json_encode(['success' => true, 'message' => "Тренер успешно $action"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка обновления статуса']);
    }
} catch(PDOException $exception) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
}
?>
