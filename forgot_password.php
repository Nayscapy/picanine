<?php
session_start();
require_once 'classes/Database.php';

// โหลด PHPMailer จาก Composer
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ===== ตั้งค่า SMTP สำหรับส่งอีเมลจริง =====
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'your-email@gmail.com');     // ใส่ Gmail ของคุณ
define('SMTP_PASS', 'your-app-password');       // ใส่ App Password 16 หลักจาก Google
define('SMTP_PORT', 587);
define('SENDER_NAME', 'Picanine Photobooth Studio');

$message = '';
$isSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    if (!empty($username) && !empty($email)) {
        // 1. ตรวจสอบว่า Username และ Email มีอยู่ในระบบจริงหรือไม่
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // 2. สุ่ม OTP 6 หลัก และตั้งเวลาหมดอายุ 5 นาที
            $otp = sprintf("%06d", mt_rand(1, 999999));
            $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            // 3. บันทึก OTP ลงฐานข้อมูล
            $updateStmt = $db->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE username = ?");
            $updateStmt->bind_param("sss", $otp, $expiry, $username);

            if ($updateStmt->execute()) {
                // 4. ส่ง OTP ผ่าน PHPMailer
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = SMTP_HOST;
                        $mail->SMTPAuth   = true;
                        $mail->Username   = SMTP_USER;
                        $mail->Password   = SMTP_PASS;
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = SMTP_PORT;
                        $mail->CharSet    = 'UTF-8';

                        $mail->setFrom(SMTP_USER, SENDER_NAME);
                        $mail->addAddress($email, $username);

                        $mail->isHTML(true);
                        $mail->Subject = 'รหัส OTP สำหรับรีเซ็ตรหัสผ่าน - Picanine Photobooth';
                        $mail->Body    = "
                            <div style='font-family: sans-serif; padding: 20px; background-color: #f8fafc; border-radius: 10px;'>
                                <h2 style='color: #2563eb;'>Picanine Photobooth</h2>
                                <p>เรียนคุณ <b>" . htmlspecialchars($username) . "</b>,</p>
                                <p>รหัส OTP สำหรับยืนยันการตั้งรหัสผ่านใหม่ของคุณคือ:</p>
                                <div style='font-size: 32px; font-weight: bold; color: #d97706; letter-spacing: 5px; margin: 15px 0;'>$otp</div>
                                <p style='color: #64748b; font-size: 14px;'>รหัสนี้มีอายุการใช้งาน <b>5 นาที</b></p>
                            </div>
                        ";

                        $mail->send();
                    } catch (Exception $e) {
                        // บันทึก Log กรณีส่งไม่สำเร็จ
                        error_log("PHPMailer Error: " . $mail->ErrorInfo);
                    }
                }

                $_SESSION['reset_username'] = $username;
                header("Location: verify_otp.php");
                exit;
            }
        } else {
            $message = "ไม่พบข้อมูล Username และ Email นี้ในระบบ";
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
  <title>ขอรหัส OTP - Picanine Photobooth</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --bg-color: #F8FAFC; --text-main: #0F172A; --text-soft: #334155;
      --blue: #2563EB; --blue-hover: #1D4ED8; --yellow: #FACC15; --amber: #F59E0B;
      --border-color: #E2E8F0; --radius-lg: 16px; --shadow-yellow: 0 8px 20px -8px rgba(245, 158, 11, 0.75);
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
    .header-logo i { font-size: 2.2rem; color: var(--amber); background: rgba(245, 158, 11, 0.1); width: 60px; height: 60px; display: grid; place-items: center; border-radius: 16px; }
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
    .btn-submit {
      background: var(--yellow); color: var(--text-main); border: none; padding: 0.875rem;
      border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: var(--shadow-yellow); transition: all 0.2s;
    }
    .btn-submit:hover { background: var(--amber); transform: translateY(-1px); }
    .footer-link { text-align: center; font-size: 0.875rem; }
    .footer-link a { color: var(--blue); text-decoration: none; font-weight: 600; }
  </style>
</head>
<body>
  <div class="card">
    <div class="header-logo">
      <i class="fa-solid fa-shield-halved"></i>
      <h2>ขอรหัส OTP</h2>
      <p>ระบบจะส่งรหัส 6 หลักไปยังอีเมลของคุณ</p>
    </div>

    <?php if ($message): ?>
      <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="forgot_password.php" style="display: flex; flex-direction: column; gap: 1rem;">
      <div class="form-group">
        <label>ชื่อผู้ใช้ (Username)</label>
        <div class="input-wrapper">
          <i class="fa-solid fa-user"></i>
          <input type="text" name="username" placeholder="Username" required>
        </div>
      </div>

      <div class="form-group">
        <label>อีเมล (Email)</label>
        <div class="input-wrapper">
          <i class="fa-solid fa-envelope"></i>
          <input type="email" name="email" placeholder="Email" required>
        </div>
      </div>

      <button type="submit" class="btn-submit">
        ขอรหัส OTP <i class="fa-solid fa-paper-plane"></i>
      </button>
    </form>

    <div class="footer-link">
      <a href="login.php">กลับหน้าเข้าสู่ระบบ</a>
    </div>
  </div>
</body>
</html>