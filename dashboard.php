<?php
require_once 'config.php';

// Guard: hanya bisa diakses jika sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$nama_user = htmlspecialchars($_SESSION['user_nama']);

// Ambil semua data kendaraan
$result     = mysqli_query($mysqli, "SELECT * FROM kendaraan ORDER BY kode_unik_kendaraan ASC");
$kendaraan  = mysqli_fetch_all($result, MYSQLI_ASSOC);
$total      = count($kendaraan);

// Hitung rata-rata harga
$rata_harga = $total > 0 ? array_sum(array_column($kendaraan, 'harga_per_hari')) / $total : 0;

// Pesan sukses dari operasi CRUD
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — FTrans</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
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

    /* ===== SIDEBAR ===== */
    .sidebar {
      width: 240px;
      min-height: 100vh;
      background: var(--surface);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      padding: 1.5rem 1.2rem;
      position: fixed;
      top: 0; left: 0; bottom: 0;
      z-index: 10;
    }

    .sidebar-brand {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 1.6rem;
      letter-spacing: -0.04em;
      margin-bottom: 2rem;
      padding-bottom: 1.2rem;
      border-bottom: 1px solid var(--border);
    }
    .sidebar-brand span { color: var(--accent); }

    .sidebar-section {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--muted);
      margin-bottom: 0.6rem;
      padding-left: 0.5rem;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.65rem 0.75rem;
      border-radius: 10px;
      color: var(--muted);
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 400;
      transition: all 0.2s;
      margin-bottom: 0.2rem;
    }
    .nav-link:hover, .nav-link.active {
      background: var(--surface2);
      color: var(--text);
    }
    .nav-link.active { color: var(--accent); }
    .nav-link svg { flex-shrink: 0; }

    .sidebar-footer {
      margin-top: auto;
      padding-top: 1.2rem;
      border-top: 1px solid var(--border);
    }
    .user-info {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 0.75rem;
    }
    .avatar {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 0.85rem;
      color: #0a0a0f;
      flex-shrink: 0;
    }
    .user-name  { font-size: 0.88rem; font-weight: 500; }
    .user-role  { font-size: 0.75rem; color: var(--muted); }

    .btn-logout {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      width: 100%;
      padding: 0.6rem 0.75rem;
      background: rgba(255,94,94,0.08);
      border: 1px solid rgba(255,94,94,0.2);
      border-radius: 10px;
      color: var(--danger);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.85rem;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s;
    }
    .btn-logout:hover { background: rgba(255,94,94,0.15); }

    /* ===== MAIN CONTENT ===== */
    .main {
      margin-left: 240px;
      flex: 1;
      padding: 2rem 2.5rem;
      min-height: 100vh;
    }

    /* Header */
    .page-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 2rem;
      gap: 1rem;
    }
    .page-title {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 1.75rem;
      letter-spacing: -0.03em;
    }
    .page-subtitle { color: var(--muted); font-size: 0.88rem; margin-top: 0.25rem; }

    .btn-add {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.7rem 1.3rem;
      background: var(--accent);
      color: #0a0a0f;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 0.88rem;
      border-radius: 10px;
      text-decoration: none;
      transition: background 0.2s, transform 0.15s;
      white-space: nowrap;
    }
    .btn-add:hover { background: var(--accent2); transform: translateY(-1px); }

    /* Alert */
    .alert {
      padding: 0.75rem 1rem;
      border-radius: 10px;
      font-size: 0.88rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .alert-success { background: rgba(94,204,139,0.1); border: 1px solid rgba(94,204,139,0.25); color: var(--success); }
    .alert-danger   { background: rgba(255,94,94,0.1);  border: 1px solid rgba(255,94,94,0.25);  color: var(--danger); }

    /* Stats cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem;
      margin-bottom: 2rem;
    }
    .stat-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 1.25rem 1.5rem;
      position: relative;
      overflow: hidden;
    }
    .stat-card::after {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 2px;
      background: var(--accent-line, var(--accent));
    }
    .stat-card:nth-child(2) { --accent-line: var(--info); }
    .stat-card:nth-child(3) { --accent-line: var(--success); }

    .stat-label {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted);
      margin-bottom: 0.6rem;
    }
    .stat-value {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 2rem;
      letter-spacing: -0.04em;
    }
    .stat-unit {
      font-size: 0.8rem;
      color: var(--muted);
      margin-top: 0.2rem;
    }

    /* Table */
    .table-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
    }
    .table-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--border);
    }
    .table-title {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 1rem;
    }
    .badge {
      background: var(--surface2);
      border: 1px solid var(--border);
      color: var(--muted);
      font-size: 0.78rem;
      padding: 0.2rem 0.6rem;
      border-radius: 999px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }
    thead th {
      padding: 0.75rem 1.5rem;
      text-align: left;
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      color: var(--muted);
      font-weight: 500;
      border-bottom: 1px solid var(--border);
      white-space: nowrap;
    }
    tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background 0.15s;
    }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--surface2); }
    tbody td {
      padding: 0.9rem 1.5rem;
      font-size: 0.9rem;
    }

    .kode-badge {
      font-family: 'DM Sans', sans-serif;
      font-size: 0.8rem;
      background: rgba(232,197,71,0.1);
      color: var(--accent);
      border: 1px solid rgba(232,197,71,0.25);
      padding: 0.2rem 0.6rem;
      border-radius: 6px;
      font-weight: 500;
    }

    .jenis-badge {
      font-size: 0.8rem;
      background: var(--surface2);
      color: var(--text);
      border: 1px solid var(--border);
      padding: 0.2rem 0.6rem;
      border-radius: 6px;
    }

    .harga-cell {
      font-weight: 500;
      color: var(--success);
      font-size: 0.88rem;
    }

    .action-btns {
      display: flex;
      gap: 0.4rem;
    }
    .btn-edit, .btn-delete {
      padding: 0.35rem 0.75rem;
      border-radius: 7px;
      font-size: 0.8rem;
      font-weight: 500;
      text-decoration: none;
      border: none;
      cursor: pointer;
      transition: opacity 0.2s, transform 0.15s;
    }
    .btn-edit {
      background: rgba(94,170,204,0.12);
      border: 1px solid rgba(94,170,204,0.25);
      color: var(--info);
    }
    .btn-delete {
      background: rgba(255,94,94,0.1);
      border: 1px solid rgba(255,94,94,0.2);
      color: var(--danger);
    }
    .btn-edit:hover, .btn-delete:hover {
      opacity: 0.8;
      transform: translateY(-1px);
    }

    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: var(--muted);
    }
    .empty-state .icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
    .empty-state p    { font-size: 0.9rem; }

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar { display: none; }
      .main    { margin-left: 0; padding: 1.25rem; }
      .stats-grid { grid-template-columns: 1fr 1fr; }
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
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

<!-- MAIN -->
<main class="main">

  <!-- Header -->
  <div class="page-header">
    <div>
      <h1 class="page-title">Dashboard</h1>
      <p class="page-subtitle">Selamat datang, <?= $nama_user ?>! Kelola data kendaraan di sini.</p>
    </div>
    <a href="add.php" class="btn-add">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Tambah Kendaraan
    </a>
  </div>

  <!-- Alert pesan -->
  <?php if ($msg === 'added'): ?>
    <div class="alert alert-success">✓ Kendaraan berhasil ditambahkan.</div>
  <?php elseif ($msg === 'updated'): ?>
    <div class="alert alert-success">✓ Data kendaraan berhasil diperbarui.</div>
  <?php elseif ($msg === 'deleted'): ?>
    <div class="alert alert-danger">🗑 Kendaraan berhasil dihapus.</div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">Total Kendaraan</div>
      <div class="stat-value"><?= $total ?></div>
      <div class="stat-unit">unit terdaftar</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Rata-rata Harga</div>
      <div class="stat-value">Rp <?= number_format($rata_harga, 0, ',', '.') ?></div>
      <div class="stat-unit">per hari</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pengguna Aktif</div>
      <div class="stat-value">1</div>
      <div class="stat-unit">sesi sekarang</div>
    </div>
  </div>

  <!-- Table -->
  <div class="table-card">
    <div class="table-header">
      <span class="table-title">Data Kendaraan</span>
      <span class="badge"><?= $total ?> data</span>
    </div>

    <?php if ($total > 0): ?>
    <table>
      <thead>
        <tr>
          <th>No</th>
          <th>Kode</th>
          <th>Nama Kendaraan</th>
          <th>Jenis</th>
          <th>Harga / Hari</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($kendaraan as $i => $k): ?>
        <tr>
          <td style="color:var(--muted)"><?= $i + 1 ?></td>
          <td><span class="kode-badge"><?= htmlspecialchars($k['kode_unik_kendaraan']) ?></span></td>
          <td><?= htmlspecialchars($k['nama_kendaraan']) ?></td>
          <td><span class="jenis-badge"><?= htmlspecialchars($k['jenis_kendaraan']) ?></span></td>
          <td class="harga-cell">Rp <?= number_format($k['harga_per_hari'], 0, ',', '.') ?></td>
          <td>
            <div class="action-btns">
              <a href="edit.php?kode=<?= urlencode($k['kode_unik_kendaraan']) ?>" class="btn-edit">✏ Edit</a>
              <a href="delete.php?kode=<?= urlencode($k['kode_unik_kendaraan']) ?>"
                 class="btn-delete"
                 onclick="return confirm('Yakin hapus kendaraan ini?')">🗑 Hapus</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
      <div class="icon">🚗</div>
      <p>Belum ada data kendaraan.<br>Klik <strong>Tambah Kendaraan</strong> untuk memulai.</p>
    </div>
    <?php endif; ?>
  </div>

</main>
</body>
</html>