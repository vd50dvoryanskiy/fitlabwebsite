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

// Обработка добавления пользователя
if ($_POST && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $phone_number = trim($_POST['phone_number']);
    $role = $_POST['role'];
    
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $message = 'Все обязательные поля должны быть заполнены';
        $message_type = 'error';
    } else {
        try {
            // Проверяем уникальность
            $checkQuery = "SELECT user_id FROM Users WHERE username = :username OR email = :email";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bindParam(':username', $username);
            $checkStmt->bindParam(':email', $email);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                $message = 'Пользователь с таким именем или email уже существует';
                $message_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO Users (username, email, password_hash, full_name, phone_number, role) 
                         VALUES (:username, :email, :password, :full_name, :phone_number, :role)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':phone_number', $phone_number);
                $stmt->bindParam(':role', $role);
                
                if ($stmt->execute()) {
                    $message = 'Пользователь успешно добавлен';
                    $message_type = 'success';
                    $_POST = [];
                }
            }
        } catch(PDOException $e) {
            $message = 'Ошибка добавления пользователя: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Обработка редактирования пользователя
if ($_POST && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $username = trim($_POST['edit_username']);
    $email = trim($_POST['edit_email']);
    $full_name = trim($_POST['edit_full_name']);
    $phone_number = trim($_POST['edit_phone_number']);
    $role = $_POST['edit_role'];
    
    if (empty($username) || empty($email) || empty($full_name)) {
        $message = 'Все обязательные поля должны быть заполнены';
        $message_type = 'error';
    } else {
        try {
            // Проверяем уникальность (исключая текущего пользователя)
            $checkQuery = "SELECT user_id FROM Users WHERE (username = :username OR email = :email) AND user_id != :user_id";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bindParam(':username', $username);
            $checkStmt->bindParam(':email', $email);
            $checkStmt->bindParam(':user_id', $user_id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                $message = 'Пользователь с таким именем или email уже существует';
                $message_type = 'error';
            } else {
                $query = "UPDATE Users SET username = :username, email = :email, full_name = :full_name, 
                         phone_number = :phone_number, role = :role WHERE user_id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':phone_number', $phone_number);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $message = 'Пользователь успешно обновлен';
                    $message_type = 'success';
                }
            }
        } catch(PDOException $e) {
            $message = 'Ошибка обновления пользователя: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Обработка удаления пользователя
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $query = "DELETE FROM Users WHERE user_id = :user_id AND role != 'admin'";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $_GET['delete']);
        
        if ($stmt->execute()) {
            $message = 'Пользователь успешно удален';
            $message_type = 'success';
        }
    } catch(PDOException $e) {
        $message = 'Ошибка удаления пользователя';
        $message_type = 'error';
    }
}

// Получаем список пользователей
try {
    $query = "SELECT * FROM Users ORDER BY registration_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - FitLab Admin</title>
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
                <h1>Управление пользователями</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Форма добавления пользователя -->
                <div class="card">
                    <h3>Добавить пользователя</h3>
                    <form method="POST" action="">
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label for="username">Имя пользователя *</label>
                                <input type="text" id="username" name="username" class="form-control" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Пароль *</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="full_name">Полное имя *</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" 
                                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone_number">Телефон</label>
                                <input type="tel" id="phone_number" name="phone_number" class="form-control" 
                                       value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Роль *</label>
                                <select id="role" name="role" class="form-control" required>
                                    <option value="user" <?php echo (isset($_POST['role']) && $_POST['role'] == 'user') ? 'selected' : ''; ?>>Пользователь</option>
                                    <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Администратор</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" name="add_user" class="btn btn-primary">Добавить пользователя</button>
                    </form>
                </div>

                <!-- Список пользователей -->
                <div class="card">
                    <h3>Список пользователей (<?php echo count($users); ?>)</h3>
                    
                    <?php if (empty($users)): ?>
                        <p style="text-align: center; color: var(--medium-gray); font-style: italic;">
                            Пользователи не найдены
                        </p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Имя пользователя</th>
                                        <th>Email</th>
                                        <th>Полное имя</th>
                                        <th>Телефон</th>
                                        <th>Роль</th>
                                        <th>Дата регистрации</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['user_id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone_number'] ?: '-'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $user['role']; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($user['registration_date'])); ?></td>
                                            <td>
                                                <button class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem; margin-right: 0.5rem;" 
                                                        onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                    Изменить
                                                </button>
                                                <?php if ($user['role'] !== 'admin'): ?>
                                                    <a href="?delete=<?php echo $user['user_id']; ?>" 
                                                       class="btn btn-secondary" 
                                                       style="font-size: 0.8rem; padding: 0.5rem 1rem;"
                                                       onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?')">
                                                        Удалить
                                                    </a>
                                                <?php endif; ?>
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
            <span class="close">&times;</span>
            <h3>Редактировать пользователя</h3>
            <form method="POST" action="">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="edit_username">Имя пользователя *</label>
                        <input type="text" id="edit_username" name="edit_username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_email">Email *</label>
                        <input type="email" id="edit_email" name="edit_email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_full_name">Полное имя *</label>
                        <input type="text" id="edit_full_name" name="edit_full_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_phone_number">Телефон</label>
                        <input type="tel" id="edit_phone_number" name="edit_phone_number" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_role">Роль *</label>
                        <select id="edit_role" name="edit_role" class="form-control" required>
                            <option value="user">Пользователь</option>
                            <option value="admin">Администратор</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" name="edit_user" class="btn btn-primary">Сохранить изменения</button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 FitLab Admin Panel. Все права защищены.</p>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_phone_number').value = user.phone_number || '';
            document.getElementById('edit_role').value = user.role;
            
            document.getElementById('editModal').style.display = 'block';
        }

        // Закрытие модального окна
        document.querySelector('.close').onclick = function() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                document.getElementById('editModal').style.display = 'none';
            }
        }
    </script>
</body>
</html>
