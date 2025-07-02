<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$user = $auth->getCurrentUser();

$database = new Database();
$conn = $database->getConnection();

// Получаем всех тренеров из базы данных
try {
    $query = "SELECT * FROM Trainers ORDER BY full_name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Ошибка получения тренеров: " . $e->getMessage());
    $trainers = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Тренеры - FitLab</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="main-content">
    <section class="hero">
        <div class="container">
            <h1>Наши тренеры</h1>
            <p>Познакомьтесь с нашей командой профессиональных тренеров</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <?php if (empty($trainers)): ?>
                <div class="no-data-message">
                    <p>В данный момент информация о тренерах недоступна.</p>
                </div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($trainers as $trainer): ?>
                        <div class="card trainer-card">
                            <div class="trainer-header">
                                <div class="trainer-title">
                                    <h3><?= htmlspecialchars($trainer['full_name']) ?></h3>
                                    <p class="trainer-specialization"><?= htmlspecialchars($trainer['specialization']) ?></p>
                                </div>
                            </div>

                            <div class="trainer-info">
                                <div class="trainer-info-item">
                                    <strong>Дата найма:</strong> <?= date('d.m.Y', strtotime($trainer['hire_date'])) ?>
                                </div>
                                
                                <?php if (!empty($trainer['phone_number'])): ?>
                                    <div class="trainer-info-item">
                                        <strong>Телефон:</strong> 
                                        <a href="tel:<?= htmlspecialchars($trainer['phone_number']) ?>" class="trainer-link">
                                            <?= htmlspecialchars($trainer['phone_number']) ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($trainer['email'])): ?>
                                    <div class="trainer-info-item">
                                        <strong>Email:</strong> 
                                        <a href="mailto:<?= htmlspecialchars($trainer['email']) ?>" class="trainer-link">
                                            <?= htmlspecialchars($trainer['email']) ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="trainer-actions">
                                <!-- При необходимости: кнопки для записи или ссылки. Сейчас пусто. -->
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Преимущества работы с тренерами -->
    <section class="section trainers-benefits">
        <div class="container">
            <h2 class="section-title">Почему стоит заниматься с тренером?</h2>
            <div class="grid grid-3">
                <div class="card">
                    <h3>Персональный подход</h3>
                    <p>Каждый тренер разрабатывает индивидуальную программу тренировок, учитывая ваши цели, физическую подготовку и особенности здоровья.</p>
                </div>
                <div class="card">
                    <h3>Правильная техника</h3>
                    <p>Тренер следит за правильностью выполнения упражнений, что помогает избежать травм и максимизировать эффективность тренировок.</p>
                </div>
                <div class="card">
                    <h3>Мотивация и поддержка</h3>
                    <p>Профессиональный тренер поможет вам преодолеть трудности, поддержит мотивацию и поможет достичь поставленных целей.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Модальное окно для редактирования тренера (пример, если нужно) -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span onclick="closeModal()" class="close">&times;</span>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="trainer_id" id="edit_trainer_id">
            <div class="form-group">
                <label for="edit_full_name">Полное имя *</label>
                <input type="text" id="edit_full_name" name="edit_full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_phone_number">Телефон</label>
                <input type="tel" id="edit_phone_number" name="edit_phone_number" class="form-control">
            </div>
            <div class="form-group">
                <label for="edit_email">Email</label>
                <input type="email" id="edit_email" name="edit_email" class="form-control">
            </div>
            <div class="form-group">
                <label for="edit_specialization">Специализация</label>
                <input type="text" id="edit_specialization" name="edit_specialization" class="form-control">
            </div>
            <div class="form-group">
                <label for="edit_hire_date">Дата найма</label>
                <input type="date" id="edit_hire_date" name="edit_hire_date" class="form-control">
            </div>
            <button type="submit" name="update_trainer" class="btn btn-primary">Сохранить изменения</button>
        </form>
    </div>
</div>

<script>
function editTrainer(trainer) {
    document.getElementById('edit_trainer_id').value = trainer.trainer_id;
    document.getElementById('edit_full_name').value = trainer.full_name;
    document.getElementById('edit_phone_number').value = trainer.phone_number || '';
    document.getElementById('edit_email').value = trainer.email || '';
    document.getElementById('edit_specialization').value = trainer.specialization || '';
    document.getElementById('edit_hire_date').value = trainer.hire_date;
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

function deleteTrainer(trainerId) {
    if (confirm('Вы уверены, что хотите удалить этого тренера?')) {
        // Логика удаления (AJAX или перенос на другой скрипт)
        alert('Тренер успешно удален');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
