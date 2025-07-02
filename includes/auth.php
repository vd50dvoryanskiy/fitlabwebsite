<?php
session_start();
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function register($username, $email, $password, $first_name, $last_name, $phone = '') {
        try {
            // Проверяем, существует ли пользователь
            $query = "SELECT user_id FROM Users WHERE username = :username OR email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Пользователь с таким именем или email уже существует'];
            }
            
            // Хешируем пароль
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Создаем полное имя
            $full_name = trim($first_name . ' ' . $last_name);
            
            // Вставляем нового пользователя
            $query = "INSERT INTO Users (username, email, password_hash, full_name, phone_number) 
                     VALUES (:username, :email, :password, :full_name, :phone)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':phone', $phone);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Регистрация успешна'];
            }
        } catch(PDOException $exception) {
            return ['success' => false, 'message' => 'Ошибка регистрации: ' . $exception->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Неизвестная ошибка'];
    }
    
    public function login($username, $password) {
        try {
            $query = "SELECT user_id, username, email, password_hash, full_name, role 
                     FROM Users WHERE (username = :username OR email = :username)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    // Обновляем время последнего входа
                    $updateQuery = "UPDATE Users SET last_login = NOW() WHERE user_id = :user_id";
                    $updateStmt = $this->conn->prepare($updateQuery);
                    $updateStmt->bindParam(':user_id', $user['user_id']);
                    $updateStmt->execute();
                    
                    return ['success' => true, 'message' => 'Вход выполнен успешно'];
                }
            }
        } catch(PDOException $exception) {
            return ['success' => false, 'message' => 'Ошибка входа: ' . $exception->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Неверные данные для входа'];
    }
    
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Выход выполнен успешно'];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'],
                'full_name' => $_SESSION['full_name']
            ];
        }
        return null;
    }
}
?>
