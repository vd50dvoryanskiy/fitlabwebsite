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
$user_id = $input['user_id'] ?? null;
$is_active = $input['is_active'] ?? null;

if (!$user_id || $is_active === null) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "UPDATE users SET is_active = :is_active WHERE id = :user_id AND role != 'admin'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':is_active', $is_active, PDO::PARAM_BOOL);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        $action = $is_active ? 'разблокирован' : 'заблокирован';
        echo json_encode(['success' => true, 'message' => "Пользователь успешно $action"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка обновления статуса']);
    }
} catch(PDOException $exception) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
}
?>
