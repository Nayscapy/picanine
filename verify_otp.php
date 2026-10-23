<?php
session_start();
require_once 'classes/Database.php';

if (!isset($_SESSION['reset_username'])) {
    header("Location: forgot_password.php");
    exit;
}

$message = '';
$username = $_SESSION['reset_username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $otp_input = trim($_POST['otp']);
    $new_password = $_POST['new_password'];

    if (!empty($otp_input) && !empty($new_password)) {
        // ตรวจสอบ OTP กับฐานข้อมูล และเช็กเวลาหมดอายุ
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND otp_code = ? AND otp_expiry >= NOW()");
        $stmt->bind_param("ss", $username, $otp_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // OTP ถูกต้อง -> แฮชรหัสผ่านใหม่ และล้างค่า OTP
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $updateStmt = $db->prepare("UPDATE users SET password = ?, otp_code = NULL, otp_expiry = NULL WHERE username = ?");
            $updateStmt->bind_param("ss", $hashed_password, $username);

            if ($updateStmt->execute()) {
                unset($_SESSION['reset_username']);
                header("Location: login.php?registered=1");
                exit;
            }
        } else {
            $message = "รหัส OTP ไม่ถูกต้อง หรือหมดอายุแล้ว";
        }
    } else {
        $message = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ยืนยัน OTP - Picanine Photobooth</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600&family=Fira+Code:wght@500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --bg-color: #F8FAFC; --text-main: #0F172A; --text-soft: #334155;
      --blue: #2563EB; --blue-hover: #1D4ED8; --yellow: #FACC15;
      --border-color: #E2E8F0; --radius-lg: 16px; --shadow-blue: 0 8px 20px -8px rgba(37, 99, 235, 0.55);
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'IBM Plex Sans Thai', system-ui, sans-serif;
      background-color: var(--bg-color); color: var(--text-main);
      min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem;
    }
    .card {
      background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(25px);
      border-radius: var(--radius-lg); padding: 2.5rem 2rem; border: 1px solid rgba(255, 255, 255, 0.9);
      box-shadow: 0 12px 40px -12px rgba(37, 99, 235, 0.15); width: 100%; max-width: 400px;
      display: flex; flex-direction: column; gap: 1.5rem;
    }
    .header-logo { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
    .header-logo i { font-size: 2.2rem; color: var(--blue); background: rgba(37, 99, 235, 0.1); width: 60px; height: 60px; display: grid; place-items: center; border-radius: 16px; }
    .msg { padding: 0.75rem; border-radius: 10px; font-size: 0.875rem; text-align: center; background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
    .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
    .form-group label { font-size: 0.875rem; font-weight: 500; color: var(--text-soft); }
    .input-wrapper { position: relative; display: flex; align-items: center; }
    .input-wrapper i { position: absolute; left: 1rem; color: #94A3B8; }
    .input-wrapper input {
      width: 100%; padding: 0.75rem 1rem 0.75rem 2.75rem; border: 1px solid var(--border-color);
      border-radius: 10px; font-family: inherit; font-size: 0.9375rem; outline: none; transition: all 0.2s;
    }
    .input-wrapper input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
    .input-wrapper input.otp-input {
      font-family: 'Fira Code', monospace; letter-spacing: 6px; font-size: 1.25rem; text-align: center; padding-left: 1rem;
    }
    .btn-submit {
      background: var(--blue); color: white; border: none; padding: 0.875rem;
      border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: var(--shadow-blue); transition: all 0.2s;
    }
    .btn-submit:hover { background: var(--blue-hover); transform: translateY(-1px); }
    .footer-link { text-align: center; font-size: 0.875rem; }
    .footer-link a { color: var(--blue); text-decoration: none; font-weight: 600; }
  </style>
</head>
<body>
  <div class="card">
    <div class="header-logo">
      <i class="fa-solid fa-key"></i>
      <h2>ยืนยันรหัส OTP</h2>
      <p>กรุณากรอก OTP ที่ได้รับในอีเมล</p>
    </div>

    <?php if ($message): ?>
      <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="verify_otp.php" style="display: flex; flex-direction: column; gap: 1rem;">
      <div class="form-group">
        <label>รหัส OTP 6 หลักจากอีเมล</label>
        <div class="input-wrapper">
          <input type="text" name="otp" class="otp-input" maxlength="6" placeholder="000000" required autofocus autocomplete="off">
        </div>
      </div>

      <div class="form-group">
        <label>รหัสผ่านใหม่ (New Password)</label>
        <div class="input-wrapper">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="new_password" placeholder="New Password" required>
        </div>
      </div>

      <button type="submit" class="btn-submit">
        ยืนยันและเปลี่ยนรหัสผ่าน <i class="fa-solid fa-check"></i>
      </button>
    </form>

    <div class="footer-link">
      <a href="forgot_password.php">ขอรหัส OTP ใหม่</a>
    </div>
  </div>
</body>
</html>