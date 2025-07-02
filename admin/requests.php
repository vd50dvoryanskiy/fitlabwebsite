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

try {
    $query = "SELECT * FROM contact_requests ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $requests = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявки клиентов - FitLab Admin</title>
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
                    <li><a href="bookings.php">Записи</a></li>
                    <li><a href="requests.php">Заявки</a></li>
                    <li><a href="trainers.php">Тренеры</a></li>
                    <li><a href="programs.php">Программы</a></li>
                    <li><a href="../index.php">На сайт</a></li>
                    <li><a href="../logout.php">Выход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <section class="section">
            <div class="container">
                <h1>Заявки клиентов</h1>
                
                <?php if (empty($requests)): ?>
                    <div class="card">
                        <p style="text-align: center; color: var(--medium-gray); font-style: italic;">
                            Заявки не найдены
                        </p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-2">
                        <?php foreach ($requests as $request): ?>
                            <div class="card">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                    <h4><?php echo htmlspecialchars($request['name']); ?></h4>
                                    <span class="badge badge-<?php echo $request['status']; ?>">
                                        <?php 
                                        $statusNames = [
                                            'new' => 'Новая',
                                            'in_progress' => 'В работе',
                                            'resolved' => 'Решена'
                                        ];
                                        echo $statusNames[$request['status']] ?? ucfirst($request['status']); 
                                        ?>
                                    </span>
                                </div>
                                
                                <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($request['email']); ?>" style="color: var(--accent-blue);"><?php echo htmlspecialchars($request['email']); ?></a></p>
                                
                                <?php if ($request['phone']): ?>
                                    <p><strong>Телефон:</strong> <a href="tel:<?php echo htmlspecialchars($request['phone']); ?>" style="color: var(--accent-blue);"><?php echo htmlspecialchars($request['phone']); ?></a></p>
                                <?php endif; ?>
                                
                                <?php if ($request['subject']): ?>
                                    <p><strong>Тема:</strong> <?php echo htmlspecialchars($request['subject']); ?></p>
                                <?php endif; ?>
                                
                                <p><strong>Сообщение:</strong></p>
                                <div style="background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                                    <?php echo nl2br(htmlspecialchars($request['message'])); ?>
                                </div>
                                
                                <p><strong>Дата:</strong> <?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></p>
                                
                                <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <?php if ($request['status'] === 'new'): ?>
                                        <button class="btn btn-primary" style="font-size: 0.9rem; padding: 0.5rem 1rem;" 
                                                onclick="updateRequestStatus(<?php echo $request['id']; ?>, 'in_progress')">
                                            Взять в работу
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['status'] !== 'resolved'): ?>
                                        <button class="btn btn-secondary" style="font-size: 0.9rem; padding: 0.5rem 1rem;" 
                                                onclick="updateRequestStatus(<?php echo $request['id']; ?>, 'resolved')">
                                            Отметить решенной
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 FitLab Admin Panel. Все права защищены.</p>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
    <script>
        function updateRequestStatus(requestId, newStatus) {
            const statusNames = {
                'in_progress': 'взять в работу',
                'resolved': 'отметить решенной'
            };
            
            if (!confirm(`Вы уверены, что хотите ${statusNames[newStatus]} эту заявку?`)) {
                return;
            }
            
            fetch('ajax/update-request-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    request_id: requestId,
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Произошла ошибка', 'error');
            });
        }
    </script>
</body>
</html>
