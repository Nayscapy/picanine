<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'classes/Database.php';
require_once 'classes/PhotoManager.php';

$database = new Database();
$db = $database->getConnection();
$photoManager = new PhotoManager($db);

$photos = $photoManager->getUserPhotos($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ประวัติรูปถ่าย - Picanine Photobooth</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @font-face {
      font-family: 'Stars';
      src: url('Stars-Regular.ttf') format('truetype');
      font-weight: normal;
      font-style: normal;
      font-display: swap;
    }

    :root {
      --bg-color: #F8FAFC;
      --card-bg: rgba(255, 255, 255, 0.85);
      --surface-muted: #F1F5F9;
      --border-color: #E2E8F0;
      --text-main: #0F172A;
      --text-soft: #334155;
      --blue: #2563EB;
      --blue-hover: #1D4ED8;
      --yellow: #FACC15;
      --radius-md: 12px;
      --radius-lg: 16px;
      --shadow-blue: 0 8px 20px -8px rgba(37, 99, 235, 0.55);
      --ease: cubic-bezier(0.2, 0.7, 0.2, 1);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'IBM Plex Sans Thai', system-ui, sans-serif;
      background-color: var(--bg-color);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      position: relative;
    }

    .bg-orbs {
      position: fixed; inset: 0; z-index: -1; overflow: hidden; pointer-events: none;
    }
    .bg-orbs span {
      position: absolute; width: 55vmax; height: 55vmax; border-radius: 50%; filter: blur(90px); animation: orbDrift 16s ease-in-out infinite;
    }
    .bg-orbs .orb-blue-1   { top: -18vmax; left: -14vmax; background: #2563EB; opacity: 0.35; }
    .bg-orbs .orb-yellow-1 { top: -12vmax; right: -16vmax; background: #FACC15; opacity: 0.45; animation-delay: -4s; }

    @keyframes orbDrift {
      0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
      50%      { transform: translate3d(4vmax, 3vmax, 0) scale(1.08); }
    }

    header {
      background-color: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      padding: 0.875rem max(1.5rem, calc((100% - 1280px) / 2 + 1.5rem));
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 50;
    }

    header h1 {
      font-family: 'Stars', 'IBM Plex Sans Thai', system-ui, sans-serif;
      font-size: 1.5rem;
      font-weight: 400;
      color: var(--blue-hover);
      -webkit-text-stroke: 0.5px var(--blue-hover);
      letter-spacing: 0.04em;
      line-height: 1;
      white-space: nowrap;
      min-width: 0;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    header h1 img {
      height: 40px;
      width: auto;
      max-width: 56px;
      object-fit: contain;
      flex-shrink: 0;
      display: block;
    }

    header h1 span {
      text-transform: none;
      padding-top: 0.1em;
    }

    .header-nav {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .btn-nav {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.45rem 0.85rem;
      border-radius: 999px;
      font-size: 0.8125rem;
      font-weight: 500;
      text-decoration: none;
      background: var(--surface-muted);
      border: 1px solid var(--border-color);
      color: var(--text-soft);
      transition: all 0.2s var(--ease);
    }

    .btn-nav:hover { background: var(--blue); color: #fff; border-color: var(--blue); }
    .btn-nav.logout { background: #FEE2E2; color: #991B1B; border-color: #FCA5A5; }
    .btn-nav.logout:hover { background: #DC2626; color: #fff; border-color: #DC2626; }

    .container {
      width: 100%;
      max-width: 1100px;
      margin: 2rem auto;
      padding: 0 1.5rem;
      flex: 1;
    }

    .page-title {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      border-bottom: 1px solid var(--border-color);
      padding-bottom: 1rem;
    }

    .page-title h2 {
      font-size: 1.35rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .user-badge {
      font-size: 0.8125rem;
      background: var(--yellow);
      color: var(--text-main);
      padding: 0.2rem 0.75rem;
      border-radius: 999px;
      font-weight: 600;
    }

    .photo-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
      gap: 1.5rem;
    }

    .photo-card {
      background-color: var(--card-bg);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: var(--radius-lg);
      padding: 1rem;
      border: 1px solid rgba(255, 255, 255, 0.9);
      box-shadow: 0 8px 24px -8px rgba(37, 99, 235, 0.12);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.75rem;
      transition: transform 0.2s var(--ease);
    }

    .photo-card:hover {
      transform: translateY(-4px);
    }

    .photo-card img {
      width: 100%;
      aspect-ratio: 600 / 1800;
      object-fit: cover;
      border-radius: var(--radius-md);
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
    }

    .photo-info {
      width: 100%;
      font-size: 0.8125rem;
      color: var(--text-soft);
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }

    .photo-info-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .badge-pill {
      font-family: 'Fira Code', monospace;
      font-size: 0.7rem;
      padding: 0.15rem 0.5rem;
      background: var(--surface-muted);
      border-radius: 6px;
      border: 1px solid var(--border-color);
      text-transform: uppercase;
    }

    .btn-action-group {
      display: flex;
      gap: 0.5rem;
      width: 100%;
      margin-top: 0.25rem;
    }

    .btn-dl {
      flex: 1;
      padding: 0.5rem;
      background: var(--blue);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 0.8125rem;
      font-weight: 500;
      cursor: pointer;
      text-decoration: none;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
    }

    .btn-dl:hover { background: var(--blue-hover); }

    .btn-del {
      padding: 0.5rem 0.75rem;
      background: #FEE2E2;
      color: #991B1B;
      border: 1px solid #FCA5A5;
      border-radius: 8px;
      font-size: 0.8125rem;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-del:hover { background: #DC2626; color: white; border-color: #DC2626; }

    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 4rem 1rem;
      background: var(--card-bg);
      border-radius: var(--radius-lg);
      border: 1px dashed var(--border-color);
      color: var(--text-soft);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
    }

    .empty-state i {
      font-size: 3rem;
      color: var(--blue);
      opacity: 0.5;
    }
  </style>
</head>
<body>

  <div class="bg-orbs" aria-hidden="true">
    <span class="orb-blue-1"></span>
    <span class="orb-yellow-1"></span>
  </div>

  <header>
    <h1><img src="your-logo.png" alt="" onerror="this.style.display='none'"><span>Picanine</span></h1>
    <div class="header-nav">
      <a href="index.html" class="btn-nav"><i class="fa-solid fa-camera"></i> <span>หน้าถ่ายรูป</span></a>
      <a href="logout.php" class="btn-nav logout"><i class="fa-solid fa-right-from-bracket"></i> <span>ออกจากระบบ</span></a>
    </div>
  </header>

  <div class="container">
    <div class="page-title">
      <h2><i class="fa-solid fa-clock-rotate-left"></i> ประวัติรูปถ่ายของคุณ</h2>
      <span class="user-badge"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>

    <div class="photo-grid">
      <?php if ($photos && $photos->num_rows > 0): ?>
        <?php while ($row = $photos->fetch_assoc()): ?>
          <div class="photo-card" id="photo-card-<?php echo $row['id']; ?>">
            <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Photobooth Strip">
            
            <div class="photo-info">
              <div class="photo-info-item">
                <span>กรอบ / ฟิลเตอร์</span>
                <span>
                  <span class="badge-pill"><?php echo htmlspecialchars($row['frame_type']); ?></span>
                </span>
              </div>
              <div class="photo-info-item" style="color: #64748B; font-size: 0.75rem; margin-top: 0.2rem;">
                <i class="fa-regular fa-calendar-alt"></i>
                <span><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></span>
              </div>
            </div>

            <div class="btn-action-group">
              <a href="<?php echo htmlspecialchars($row['image_path']); ?>" download class="btn-dl">
                <i class="fa-solid fa-download"></i> ดาวน์โหลด
              </a>
              <button class="btn-del" onclick="deletePhoto(<?php echo $row['id']; ?>)" title="ลบรูปนี้">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class="fa-solid fa-images"></i>
          <p>ยังไม่มีประวัติรูปถ่ายในระบบ</p>
          <a href="index.html" class="btn-nav" style="background: var(--yellow); border: none; font-weight: 600;">ไปถ่ายรูปแรกกันเลย!</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function deletePhoto(id) {
      if (!confirm("คุณต้องการลบรูปภาพนี้ใช่หรือไม่?")) return;

      fetch('api_delete_photo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ photo_id: id })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const card = document.getElementById('photo-card-' + id);
          if (card) {
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            setTimeout(() => card.remove(), 200);
          }
        } else {
          alert(data.message || 'ไม่สามารถลบรูปได้');
        }
      })
      .catch(err => alert('เกิดข้อผิดพลาดในการเชื่อมต่อ'));
    }
  </script>
</body>
</html>