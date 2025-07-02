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
    $query = "SELECT b.*, u.first_name, u.last_name, u.email, s.date_time, 
                     p.name as program_name, t.name as trainer_name
              FROM bookings b
              JOIN users u ON b.user_id = u.id
              JOIN schedule s ON b.schedule_id = s.id
              JOIN programs p ON s.program_id = p.id
              JOIN trainers t ON s.trainer_id = t.id
              ORDER BY s.date_time DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $bookings = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление записями - FitLab Admin</title>
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
                <h1>Управление записями</h1>
                
                <div class="card">
                    <h3>Все записи (<?php echo count($bookings); ?>)</h3>
                    
                    <?php if (empty($bookings)): ?>
                        <p style="text-align: center; color: var(--medium-gray); font-style: italic;">
                            Записи не найдены
                        </p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Клиент</th>
                                        <th>Email</th>
                                        <th>Программа</th>
                                        <th>Тренер</th>
                                        <th>Дата тренировки</th>
                                        <th>Дата записи</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td><?php echo $booking['id']; ?></td>
                                            <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['email']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['program_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['trainer_name']); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($booking['date_time'])); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($booking['booking_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $booking['status']; ?>">
                                                    <?php 
                                                    $statusNames = [
                                                        'active' => 'Активна',
                                                        'completed' => 'Завершена',
                                                        'cancelled' => 'Отменена'
                                                    ];
                                                    echo $statusNames[$booking['status']] ?? ucfirst($booking['status']); 
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($booking['status'] === 'active'): ?>
                                                    <button class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem;" 
                                                            onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'completed')">
                                                        Завершить
                                                    </button>
                                                    <button class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem; margin-left: 0.5rem;" 
                                                            onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'cancelled')">
                                                        Отменить
                                                    </button>
                                                <?php else: ?>
                                                    <span style="color: var(--medium-gray);">-</span>
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

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 FitLab Admin Panel. Все права защищены.</p>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
    <script>
        function updateBookingStatus(bookingId, newStatus) {
            const action = newStatus === 'completed' ? 'завершить' : 'отменить';
            if (!confirm(`Вы уверены, что хотите ${action} эту запись?`)) {
                return;
            }
            
            fetch('ajax/update-booking-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    booking_id: bookingId,
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
