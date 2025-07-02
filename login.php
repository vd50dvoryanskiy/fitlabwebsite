<?php
require_once 'includes/auth.php';

$auth = new Auth();
$message = '';
$message_type = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $message = 'Пожалуйста, заполните все поля';
        $message_type = 'error';
    } else {
        $result = $auth->login($username, $password);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
        
        if ($result['success']) {
            if ($auth->isAdmin()) {
                header('Location: admin/index.php');
            } else {
                header('Location: profile.php');
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - FitLab</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <section class="section">
            <div class="container">
                <div style="max-width: 400px; margin: 0 auto;">
                    <div class="card">
                        <h2 style="text-align: center; margin-bottom: 2rem;">Вход в систему</h2>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="username">Имя пользователя или Email</label>
                                <input type="text" id="username" name="username" class="form-control" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Пароль</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Войти</button>
                        </form>
                        
                        <p style="text-align: center; margin-top: 1rem;">
                            Нет аккаунта? <a href="register.php" style="color: var(--accent-blue);">Зарегистрироваться</a>
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
