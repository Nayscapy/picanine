<?php
session_start();
require_once 'classes/Database.php';
require_once 'classes/User.php';

$message = '';
$isSuccess = false;

if (isset($_GET['registered'])) {
    $message = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
    $isSuccess = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $userData = $user->login($username, $password);
    if ($userData) {
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $userData['username'];
        header("Location: index.html");
        exit;
    } else {
        $message = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        $isSuccess = false;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เข้าสู่ระบบ - Picanine Photobooth</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --bg-color: #F8FAFC;
      --text-main: #0F172A;
      --text-soft: #334155;
      --blue: #2563EB;
      --blue-hover: #1D4ED8;
      --yellow: #FACC15;
      --amber: #F59E0B;
      --border-color: #E2E8F0;
      --radius-lg: 16px;
      --shadow-yellow: 0 8px 20px -8px rgba(245, 158, 11, 0.75);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'IBM Plex Sans Thai', system-ui, sans-serif;
      background-color: var(--bg-color);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
      padding: 1.5rem;
    }

    .bg-orbs {
      position: fixed; inset: 0; z-index: -1; overflow: hidden; pointer-events: none;
    }
    .bg-orbs span {
      position: absolute; width: 55vmax; height: 55vmax; border-radius: 50%; filter: blur(90px); animation: orbDrift 16s ease-in-out infinite;
    }
    .bg-orbs .orb-blue-1   { top: -18vmax; left: -14vmax; background: #2563EB; opacity: 0.42; }
    .bg-orbs .orb-yellow-1 { top: -12vmax; right: -16vmax; background: #FACC15; opacity: 0.55; animation-delay: -4s; }

    @keyframes orbDrift {
      0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
      50%      { transform: translate3d(4vmax, 3vmax, 0) scale(1.08); }
    }

    .card {
      background-color: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(25px);
      -webkit-backdrop-filter: blur(25px);
      border-radius: var(--radius-lg);
      padding: 2.5rem 2rem;
      border: 1px solid rgba(255, 255, 255, 0.9);
      box-shadow: 0 12px 40px -12px rgba(37, 99, 235, 0.15);
      width: 100%;
      max-width: 400px;
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    .header-logo {
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.5rem;
    }

    .header-logo i {
      font-size: 2.2rem;
      color: var(--blue);
      background: rgba(37, 99, 235, 0.1);
      width: 60px; height: 60px;
      display: grid; place-items: center;
      border-radius: 16px;
      margin-bottom: 0.25rem;
    }

    .header-logo h2 {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--text-main);
    }

    .header-logo p {
      font-size: 0.875rem;
      color: var(--text-soft);
    }

    .msg {
      padding: 0.75rem 1rem;
      border-radius: 10px;
      font-size: 0.875rem;
      text-align: center;
      line-height: 1.4;
    }
    .msg.error { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
    .msg.success { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .form-group label {
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--text-soft);
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-wrapper i {
      position: absolute;
      left: 1rem;
      color: #94A3B8;
      font-size: 0.95rem;
    }

    .input-wrapper input {
      width: 100%;
      padding: 0.75rem 1rem 0.75rem 2.75rem;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.9375rem;
      background: rgba(255, 255, 255, 0.9);
      outline: none;
      transition: all 0.2s;
    }

    .input-wrapper input:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .btn-submit {
      margin-top: 0.5rem;
      background: var(--yellow);
      color: var(--text-main);
      border: none;
      padding: 0.875rem;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      box-shadow: var(--shadow-yellow);
      transition: all 0.2s;
    }

    .btn-submit:hover {
      background: var(--amber);
      transform: translateY(-1px);
    }

    .footer-link {
      font-size: 0.875rem;
      color: var(--text-soft);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .footer-link a {
      color: var(--blue);
      text-decoration: none;
      font-weight: 600;
    }

    .footer-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="bg-orbs" aria-hidden="true">
    <span class="orb-blue-1"></span>
    <span class="orb-yellow-1"></span>
  </div>

  <div class="card">
    <div class="header-logo">
      <i class="fa-solid fa-right-to-bracket"></i>
      <h2>เข้าสู่ระบบ</h2>
      <p>Picanine Photobooth Studio</p>
    </div>

    <?php if ($message): ?>
      <div class="msg <?php echo $isSuccess ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" style="display: flex; flex-direction: column; gap: 1rem;">
      <div class="form-group">
        <label>ชื่อผู้ใช้ หรือ อีเมล</label>
        <div class="input-wrapper">
          <i class="fa-solid fa-user"></i>
          <input type="text" name="username" placeholder="Username or Email" required>
        </div>
      </div>

      <div class="form-group">
        <label>รหัสผ่าน</label>
        <div class="input-wrapper">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" placeholder="Password" required>
        </div>
      </div>

      <button type="submit" class="btn-submit">
        เข้าสู่ระบบ <i class="fa-solid fa-arrow-right"></i>
      </button>
    </form>

    <div class="footer-link">
      <a href="forgot_password.php" style="color: var(--text-soft); font-weight: normal;">ลืมรหัสผ่าน?</a>
      <a href="register.php">สมัครสมาชิกที่นี่</a>
    </div>
  </div>

</body>
</html>