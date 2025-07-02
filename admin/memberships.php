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

// Обработка добавления абонемента
if ($_POST && isset($_POST['add_membership'])) {
    $membership_type = trim($_POST['membership_type']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    if (empty($membership_type) || empty($start_date) || empty($end_date)) {
        $message = 'Все поля обязательны для заполнения';
        $message_type = 'error';
    } else {
        try {
            $query = "INSERT INTO Memberships (membership_type, start_date, end_date, is_active) 
                     VALUES (:membership_type, :start_date, :end_date, 1)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':membership_type', $membership_type);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            
            if ($stmt->execute()) {
                $message = 'Абонемент успешно добавлен';
                $message_type = 'success';
                $_POST = [];
            }
        } catch(PDOException $e) {
            $message = 'Ошибка добавления абонемента: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Обработка редактирования абонемента
if ($_POST && isset($_POST['edit_membership'])) {
    $membership_id = $_POST['membership_id'];
    $membership_type = trim($_POST['edit_membership_type']);
    $start_date = $_POST['edit_start_date'];
    $end_date = $_POST['edit_end_date'];
    $is_active = isset($_POST['edit_is_active']) ? 1 : 0;
    
    if (empty($membership_type) || empty($start_date) || empty($end_date)) {
        $message = 'Все поля обязательны для заполнения';
        $message_type = 'error';
    } else {
        try {
            $query = "UPDATE Memberships SET membership_type = :membership_type, start_date = :start_date, 
                     end_date = :end_date, is_active = :is_active WHERE membership_id = :membership_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':membership_type', $membership_type);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->bindParam(':is_active', $is_active);
            $stmt->bindParam(':membership_id', $membership_id);
            
            if ($stmt->execute()) {
                $message = 'Абонемент успешно обновлен';
                $message_type = 'success';
            }
        } catch(PDOException $e) {
            $message = 'Ошибка обновления абонемента: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Обработка деактивации абонемента
if (isset($_GET['deactivate']) && is_numeric($_GET['deactivate'])) {
    try {
        $query = "UPDATE Memberships SET is_active = 0 WHERE membership_id = :membership_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':membership_id', $_GET['deactivate']);
        
        if ($stmt->execute()) {
            $message = 'Абонемент деактивирован';
            $message_type = 'success';
        }
    } catch(PDOException $e) {
        $message = 'Ошибка деактивации абонемента: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Обработка удаления абонемента
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $query = "DELETE FROM Memberships WHERE membership_id = :membership_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':membership_id', $_GET['delete']);
        
        if ($stmt->execute()) {
            $message = 'Абонемент успешно удален';
            $message_type = 'success';
        }
    } catch(PDOException $e) {
        $message = 'Ошибка удаления абонемента: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Получаем данные
try {
    $membershipsQuery = "SELECT * FROM Memberships ORDER BY purchase_date DESC";
    $stmt = $conn->prepare($membershipsQuery);
    $stmt->execute();
    $memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $memberships = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление абонементами - FitLab Admin</title>
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
                <h1>Управление абонементами</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Форма добавления абонемента -->
                <div class="card">
                    <h3>Добавить абонемент</h3>
                    <form method="POST" action="">
                        <div class="grid grid-3">
                            <div class="form-group">
                                <label for="membership_type">Тип абонемента *</label>
                                <select id="membership_type" name="membership_type" class="form-control" required>
                                    <option value="">Выберите тип</option>
                                    <option value="Дневной" <?php echo (isset($_POST['membership_type']) && $_POST['membership_type'] == 'Дневной') ? 'selected' : ''; ?>>Дневной</option>
                                    <option value="Месячный" <?php echo (isset($_POST['membership_type']) && $_POST['membership_type'] == 'Месячный') ? 'selected' : ''; ?>>Месячный</option>
                                    <option value="Годовой" <?php echo (isset($_POST['membership_type']) && $_POST['membership_type'] == 'Годовой') ? 'selected' : ''; ?>>Годовой</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="start_date">Дата начала *</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" 
                                       value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date">Дата окончания *</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" 
                                       value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <button type="submit" name="add_membership" class="btn btn-primary">Добавить абонемент</button>
                    </form>
                </div>

                <!-- Список абонементов -->
                <div class="card">
                    <h3>Список абонементов (<?php echo count($memberships); ?>)</h3>
                    
                    <?php if (empty($memberships)): ?>
                        <p style="text-align: center; color: var(--medium-gray); font-style: italic;">
                            Абонементы не найдены
                        </p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Тип абонемента</th>
                                        <th>Дата покупки</th>
                                        <th>Дата начала</th>
                                        <th>Дата окончания</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($memberships as $membership): ?>
                                        <tr>
                                            <td><?php echo $membership['membership_id']; ?></td>
                                            <td><?php echo htmlspecialchars($membership['membership_type']); ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($membership['purchase_date'])); ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($membership['start_date'])); ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($membership['end_date'])); ?></td>
                                            <td>
                                                <?php 
                                                $isActive = $membership['is_active'] && strtotime($membership['end_date']) >= time();
                                                $statusClass = $isActive ? 'badge-active' : 'badge-cancelled';
                                                $statusText = $isActive ? 'Активен' : 'Неактивен';
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem; margin-right: 0.5rem;" 
                                                        onclick="editMembership(<?php echo htmlspecialchars(json_encode($membership)); ?>)">
                                                    Изменить
                                                </button>
                                                <?php if ($membership['is_active']): ?>
                                                    <a href="?deactivate=<?php echo $membership['membership_id']; ?>" 
                                                       class="btn btn-secondary" 
                                                       style="font-size: 0.8rem; padding: 0.5rem 1rem; margin-right: 0.5rem;"
                                                       onclick="return confirm('Вы уверены, что хотите деактивировать этот абонемент?')">
                                                        Деактивировать
                                                    </a>
                                                <?php endif; ?>
                                                <a href="?delete=<?php echo $membership['membership_id']; ?>" 
                                                   class="btn btn-secondary" 
                                                   style="font-size: 0.8rem; padding: 0.5rem 1rem;"
                                                   onclick="return confirm('Вы уверены, что хотите удалить этот абонемент?')">
                                                    Удалить
                                                </a>
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
            <h3>Редактировать абонемент</h3>
            <form method="POST" action="">
                <input type="hidden" id="edit_membership_id" name="membership_id">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="edit_membership_type">Тип абонемента *</label>
                        <select id="edit_membership_type" name="edit_membership_type" class="form-control" required>
                            <option value="Дневной">Дневной</option>
                            <option value="Месячный">Месячный</option>
                            <option value="Годовой">Годовой</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_start_date">Дата начала *</label>
                        <input type="date" id="edit_start_date" name="edit_start_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_end_date">Дата окончания *</label>
                        <input type="date" id="edit_end_date" name="edit_end_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="edit_is_active" name="edit_is_active" style="margin-right: 0.5rem;">
                            Активен
                        </label>
                    </div>
                </div>
                
                <button type="submit" name="edit_membership" class="btn btn-primary">Сохранить изменения</button>
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
        function editMembership(membership) {
            document.getElementById('edit_membership_id').value = membership.membership_id;
            document.getElementById('edit_membership_type').value = membership.membership_type;
            document.getElementById('edit_start_date').value = membership.start_date;
            document.getElementById('edit_end_date').value = membership.end_date;
            document.getElementById('edit_is_active').checked = membership.is_active == 1;
            
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
