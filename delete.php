<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$kode = trim($_GET['kode'] ?? '');

if (empty($kode)) {
    header('Location: dashboard.php');
    exit;
}

// Ambil data kendaraan untuk ditampilkan di halaman konfirmasi
$stmt = mysqli_prepare($mysqli, "SELECT * FROM kendaraan WHERE kode_unik_kendaraan = ?");
mysqli_stmt_bind_param($stmt, 's', $kode);
mysqli_stmt_execute($stmt);
$result    = mysqli_stmt_get_result($stmt);
$kendaraan = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$kendaraan) {
    // Data tidak ditemukan, kembalikan ke dashboard
    header('Location: dashboard.php');
    exit;
}

// Proses hapus jika user menekan tombol konfirmasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $del = mysqli_prepare($mysqli, "DELETE FROM kendaraan WHERE kode_unik_kendaraan = ?");
    mysqli_stmt_bind_param($del, 's', $kode);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    header('Location: dashboard.php?msg=deleted');
    exit;
}

$nama_user = htmlspecialchars($_SESSION['user_nama']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hapus Kendaraan — FTrans</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:        #0a0a0f;
      --surface:   #12121a;
      --surface2:  #1a1a26;
      --border:    #1e1e2e;
      --accent:    #e8c547;
      --accent2:   #f0a500;
      --text:      #e8e8f0;
      --muted:     #6b6b80;
      --danger:    #ff5e5e;
      --success:   #5ecc8b;
      --info:      #5eaacc;
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      display: flex;
    }

    /* SIDEBAR */
    .sidebar {
      width: 240px; min-height: 100vh;
      background: var(--surface); border-right: 1px solid var(--border);
      display: flex; flex-direction: column;
      padding: 1.5rem 1.2rem;
      position: fixed; top: 0; left: 0; bottom: 0;
    }
    .sidebar-brand { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.6rem; letter-spacing: -0.04em; margin-bottom: 2rem; padding-bottom: 1.2rem; border-bottom: 1px solid var(--border); }
    .sidebar-brand span { color: var(--accent); }
    .sidebar-section { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 0.6rem; padding-left: 0.5rem; }
    .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.75rem; border-radius: 10px; color: var(--muted); text-decoration: none; font-size: 0.9rem; transition: all 0.2s; margin-bottom: 0.2rem; }
    .nav-link:hover, .nav-link.active { background: var(--surface2); color: var(--text); }
    .nav-link.active { color: var(--accent); }
    .sidebar-footer { margin-top: auto; padding-top: 1.2rem; border-top: 1px solid var(--border); }
    .user-info { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; }
    .avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), var(--accent2)); display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.85rem; color: #0a0a0f; flex-shrink: 0; }
    .user-name { font-size: 0.88rem; font-weight: 500; }
    .user-role { font-size: 0.75rem; color: var(--muted); }
    .btn-logout { display: flex; align-items: center; gap: 0.5rem; width: 100%; padding: 0.6rem 0.75rem; background: rgba(255,94,94,0.08); border: 1px solid rgba(255,94,94,0.2); border-radius: 10px; color: var(--danger); font-family: 'DM Sans', sans-serif; font-size: 0.85rem; cursor: pointer; text-decoration: none; transition: background 0.2s; }
    .btn-logout:hover { background: rgba(255,94,94,0.15); }

    /* MAIN */
    .main { margin-left: 240px; flex: 1; padding: 2rem 2.5rem; display: flex; flex-direction: column; align-items: flex-start; }

    .page-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; }
    .back-btn { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: var(--surface); border: 1px solid var(--border); color: var(--muted); text-decoration: none; transition: color 0.2s, border-color 0.2s; }
    .back-btn:hover { color: var(--text); border-color: var(--muted); }
    .page-title { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1.75rem; letter-spacing: -0.03em; }
    .page-subtitle { color: var(--muted); font-size: 0.88rem; margin-top: 0.2rem; }

    /* Confirm Card */
    .confirm-card {
      background: var(--surface);
      border: 1px solid rgba(255,94,94,0.2);
      border-radius: 18px;
      overflow: hidden;
      width: 100%;
      max-width: 520px;
      animation: fadeUp 0.4s cubic-bezier(.22,.68,0,1.2) both;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* Top danger stripe */
    .danger-header {
      padding: 1.5rem 1.75rem;
      border-bottom: 1px solid rgba(255,94,94,0.15);
      background: rgba(255,94,94,0.04);
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .danger-icon {
      width: 48px; height: 48px;
      border-radius: 12px;
      background: rgba(255,94,94,0.12);
      border: 1px solid rgba(255,94,94,0.25);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.4rem;
      flex-shrink: 0;
    }
    .danger-title {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 1.05rem;
      color: var(--danger);
      margin-bottom: 0.25rem;
    }
    .danger-desc { font-size: 0.85rem; color: var(--muted); line-height: 1.5; }

    /* Data preview */
    .data-preview {
      padding: 1.5rem 1.75rem;
      border-bottom: 1px solid var(--border);
    }
    .preview-label {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted);
      margin-bottom: 1rem;
    }
    .data-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.75rem;
    }
    .data-item {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 0.75rem 1rem;
    }
    .data-item-label { font-size: 0.74rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 0.3rem; }
    .data-item-val   { font-size: 0.92rem; font-weight: 500; }
    .data-item-val.accent { color: var(--accent); }
    .data-item-val.danger { color: var(--danger); }
    .data-item.full { grid-column: 1 / -1; }

    /* Warning note */
    .warning-note {
      margin: 0 1.75rem 1.5rem;
      padding: 0.75rem 1rem;
      background: rgba(255,94,94,0.06);
      border: 1px solid rgba(255,94,94,0.15);
      border-radius: 10px;
      font-size: 0.83rem;
      color: var(--danger);
      line-height: 1.5;
      display: flex;
      gap: 0.5rem;
      align-items: flex-start;
    }

    /* Footer buttons */
    .confirm-footer {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 0.75rem;
      padding: 1.25rem 1.75rem;
      border-top: 1px solid var(--border);
    }
    .btn-cancel { padding: 0.7rem 1.25rem; background: transparent; border: 1px solid var(--border); border-radius: 10px; color: var(--muted); font-family: 'DM Sans', sans-serif; font-size: 0.88rem; cursor: pointer; text-decoration: none; transition: border-color 0.2s, color 0.2s; }
    .btn-cancel:hover { border-color: var(--muted); color: var(--text); }

    .btn-delete-confirm {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.7rem 1.5rem;
      background: var(--danger);
      color: #fff;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 0.9rem;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: opacity 0.2s, transform 0.15s;
    }
    .btn-delete-confirm:hover  { opacity: 0.85; transform: translateY(-1px); }
    .btn-delete-confirm:active { transform: translateY(0); }

    /* Countdown on button */
    #countdown { font-size: 0.8rem; opacity: 0.75; }

    @media (max-width: 768px) {
      .sidebar { display: none; }
      .main    { margin-left: 0; padding: 1.25rem; }
      .data-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">F<span>Trans</span></div>
  <div class="sidebar-section">Menu</div>
  <a href="dashboard.php" class="nav-link active">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    Dashboard
  </a>
  <a href="main.php" class="nav-link">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3m3 14H9a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2z"/></svg>
    Data Kendaraan
  </a>
  <a href="add.php" class="nav-link">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
    Tambah Kendaraan
  </a>
  <div class="sidebar-footer">
    <div class="user-info">
      <div class="avatar"><?= strtoupper(substr($_SESSION['user_nama'], 0, 1)) ?></div>
      <div>
        <div class="user-name"><?= $nama_user ?></div>
        <div class="user-role">Operator</div>
      </div>
    </div>
    <a href="logout.php" class="btn-logout">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</aside>

<main class="main">
  <div class="page-header">
    <a href="dashboard.php" class="back-btn" title="Kembali">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div>
      <h1 class="page-title">Hapus Kendaraan</h1>
      <p class="page-subtitle">Tinjau data sebelum melakukan penghapusan</p>
    </div>
  </div>

  <div class="confirm-card">

    <div class="danger-header">
      <div class="danger-icon">🗑️</div>
      <div>
        <div class="danger-title">Konfirmasi Penghapusan</div>
        <div class="danger-desc">
          Anda akan menghapus kendaraan berikut secara permanen.<br>
          Pastikan data yang dihapus sudah benar.
        </div>
      </div>
    </div>

    <div class="data-preview">
      <div class="preview-label">Data yang akan dihapus</div>
      <div class="data-grid">
        <div class="data-item">
          <div class="data-item-label">Kode Kendaraan</div>
          <div class="data-item-val accent"><?= htmlspecialchars($kendaraan['kode_unik_kendaraan']) ?></div>
        </div>
        <div class="data-item">
          <div class="data-item-label">Jenis</div>
          <div class="data-item-val"><?= htmlspecialchars($kendaraan['jenis_kendaraan']) ?></div>
        </div>
        <div class="data-item full">
          <div class="data-item-label">Nama Kendaraan</div>
          <div class="data-item-val"><?= htmlspecialchars($kendaraan['nama_kendaraan']) ?></div>
        </div>
        <div class="data-item full">
          <div class="data-item-label">Harga per Hari</div>
          <div class="data-item-val danger">Rp <?= number_format($kendaraan['harga_per_hari'], 0, ',', '.') ?></div>
        </div>
      </div>
    </div>

    <div class="warning-note">
      <span>⚠</span>
      <span>Tindakan ini <strong>tidak dapat dibatalkan</strong>. Data yang sudah dihapus tidak bisa dipulihkan kembali.</span>
    </div>

    <form method="POST" action="">
      <div class="confirm-footer">
        <a href="dashboard.php" class="btn-cancel">Batal, Kembali</a>
        <button type="submit" name="confirm_delete" class="btn-delete-confirm" id="deleteBtn" disabled>
          🗑 Hapus <span id="countdown">(3)</span>
        </button>
      </div>
    </form>

  </div>
</main>

<script>
  // Tombol hapus aktif setelah 3 detik — mencegah klik tidak sengaja
  let sisa = 3;
  const btn       = document.getElementById('deleteBtn');
  const countdown = document.getElementById('countdown');

  const timer = setInterval(() => {
    sisa--;
    if (sisa <= 0) {
      clearInterval(timer);
      btn.disabled = false;
      countdown.textContent = '';
    } else {
      countdown.textContent = `(${sisa})`;
    }
  }, 1000);
</script>

</body>
</html>