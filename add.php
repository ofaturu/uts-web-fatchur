<?php
require_once 'config.php';

// Guard: hanya bisa diakses jika sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode  = trim($_POST['kode_unik_kendaraan'] ?? '');
    $nama  = trim($_POST['nama_kendaraan']      ?? '');
    $jenis = trim($_POST['jenis_kendaraan']     ?? '');
    $harga = $_POST['harga_per_hari']           ?? '';

    // --- Validasi ---
    if (empty($kode) || empty($nama) || empty($jenis) || $harga === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (!is_numeric($harga) || $harga < 0) {
        $error = 'Harga per hari harus berupa angka positif.';
    } else {
        // Cek kode unik sudah dipakai atau belum
        $cek = mysqli_prepare($mysqli, "SELECT kode_unik_kendaraan FROM kendaraan WHERE kode_unik_kendaraan = ?");
        mysqli_stmt_bind_param($cek, 's', $kode);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if (mysqli_stmt_num_rows($cek) > 0) {
            $error = 'Kode kendaraan sudah digunakan. Gunakan kode lain.';
        } else {
            $stmt = mysqli_prepare($mysqli,
                "INSERT INTO kendaraan (kode_unik_kendaraan, nama_kendaraan, jenis_kendaraan, harga_per_hari)
                 VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sssd', $kode, $nama, $jenis, $harga);

            if (mysqli_stmt_execute($stmt)) {
                header('Location: dashboard.php?msg=added');
                exit;
            } else {
                $error = 'Gagal menyimpan data. Coba lagi.';
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($cek);
    }
}

$nama_user = htmlspecialchars($_SESSION['user_nama']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Kendaraan — FTrans</title>
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
      --input-bg:  #16161f;
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
      transition: all 0.2s;
      margin-bottom: 0.2rem;
    }
    .nav-link:hover, .nav-link.active {
      background: var(--surface2);
      color: var(--text);
    }
    .nav-link.active { color: var(--accent); }
    .sidebar-footer {
      margin-top: auto;
      padding-top: 1.2rem;
      border-top: 1px solid var(--border);
    }
    .user-info { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; }
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
    .user-name { font-size: 0.88rem; font-weight: 500; }
    .user-role { font-size: 0.75rem; color: var(--muted); }
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

    /* ===== MAIN ===== */
    .main {
      margin-left: 240px;
      flex: 1;
      padding: 2rem 2.5rem;
      display: flex;
      flex-direction: column;
    }

    .page-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
    }
    .back-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px; height: 36px;
      border-radius: 10px;
      background: var(--surface);
      border: 1px solid var(--border);
      color: var(--muted);
      text-decoration: none;
      transition: color 0.2s, border-color 0.2s;
    }
    .back-btn:hover { color: var(--text); border-color: var(--muted); }

    .page-title {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 1.75rem;
      letter-spacing: -0.03em;
    }
    .page-subtitle { color: var(--muted); font-size: 0.88rem; margin-top: 0.2rem; }

    /* Form card */
    .form-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 18px;
      overflow: hidden;
      max-width: 640px;
      animation: fadeUp 0.4s cubic-bezier(.22,.68,0,1.2) both;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .form-card-header {
      padding: 1.25rem 1.75rem;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .form-icon {
      width: 38px; height: 38px;
      border-radius: 10px;
      background: rgba(232,197,71,0.12);
      border: 1px solid rgba(232,197,71,0.2);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem;
    }
    .form-card-title {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 1rem;
    }
    .form-card-desc { font-size: 0.8rem; color: var(--muted); }

    .form-body { padding: 1.75rem; }

    .alert-error {
      background: rgba(255,94,94,0.08);
      border: 1px solid rgba(255,94,94,0.25);
      color: var(--danger);
      border-radius: 10px;
      padding: 0.75rem 1rem;
      font-size: 0.85rem;
      margin-bottom: 1.5rem;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }
    .form-group { margin-bottom: 1.1rem; }
    .form-group.full { grid-column: 1 / -1; }

    label {
      display: block;
      font-size: 0.78rem;
      font-weight: 500;
      color: var(--muted);
      margin-bottom: 0.45rem;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }
    .required { color: var(--accent); margin-left: 2px; }

    input, select {
      width: 100%;
      background: var(--input-bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.93rem;
      padding: 0.75rem 1rem;
      transition: border-color 0.2s, box-shadow 0.2s;
      outline: none;
      appearance: none;
    }
    input:focus, select:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(232,197,71,0.1);
    }
    input::placeholder { color: var(--muted); }

    .input-prefix {
      position: relative;
    }
    .input-prefix span {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: 0.9rem;
      pointer-events: none;
    }
    .input-prefix input { padding-left: 2.5rem; }

    .hint { font-size: 0.76rem; color: var(--muted); margin-top: 0.35rem; }

    .form-footer {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 0.75rem;
      padding: 1.25rem 1.75rem;
      border-top: 1px solid var(--border);
      background: rgba(255,255,255,0.01);
    }

    .btn-cancel {
      padding: 0.7rem 1.25rem;
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 10px;
      color: var(--muted);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.88rem;
      cursor: pointer;
      text-decoration: none;
      transition: border-color 0.2s, color 0.2s;
    }
    .btn-cancel:hover { border-color: var(--muted); color: var(--text); }

    .btn-submit {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.7rem 1.5rem;
      background: var(--accent);
      color: #0a0a0f;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 0.9rem;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
    }
    .btn-submit:hover  { background: var(--accent2); transform: translateY(-1px); }
    .btn-submit:active { transform: translateY(0); }

    @media (max-width: 768px) {
      .sidebar { display: none; }
      .main    { margin-left: 0; padding: 1.25rem; }
      .form-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-brand">F<span>Trans</span></div>
  <div class="sidebar-section">Menu</div>
  <a href="dashboard.php" class="nav-link">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    Dashboard
  </a>
  <a href="dashboard.php" class="nav-link">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3m3 14H9a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2z"/></svg>
    Data Kendaraan
  </a>
  <a href="add.php" class="nav-link active">
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
  <div class="page-header">
    <a href="dashboard.php" class="back-btn" title="Kembali">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div>
      <h1 class="page-title">Tambah Kendaraan</h1>
      <p class="page-subtitle">Isi data kendaraan baru untuk didaftarkan ke sistem</p>
    </div>
  </div>

  <div class="form-card">
    <div class="form-card-header">
      <div class="form-icon">🚗</div>
      <div>
        <div class="form-card-title">Form Data Kendaraan</div>
        <div class="form-card-desc">Semua field bertanda * wajib diisi</div>
      </div>
    </div>

    <div class="form-body">
      <?php if ($error): ?>
        <div class="alert-error">⚠ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-row">

          <div class="form-group">
            <label>Kode Unik Kendaraan <span class="required">*</span></label>
            <input type="text" name="kode_unik_kendaraan"
                   placeholder="Contoh: 1111"
                   value="<?= htmlspecialchars($_POST['kode_unik_kendaraan'] ?? '') ?>"
                   maxlength="20" required>
            <p class="hint">Kode ini harus unik dan tidak boleh sama.</p>
          </div>

          <div class="form-group">
            <label>Jenis Kendaraan <span class="required">*</span></label>
            <select name="jenis_kendaraan" required>
              <option value="" disabled <?= empty($_POST['jenis_kendaraan']) ? 'selected' : '' ?>>-- Pilih Jenis --</option>
              <?php
              $jenis_list = ['Roda 2', 'Roda 4'];
              foreach ($jenis_list as $j):
                $sel = (($_POST['jenis_kendaraan'] ?? '') === $j) ? 'selected' : '';
              ?>
              <option value="<?= $j ?>" <?= $sel ?>><?= $j ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group full">
            <label>Nama Kendaraan <span class="required">*</span></label>
            <input type="text" name="nama_kendaraan"
                   placeholder="Contoh: Toyota Avanza 2023"
                   value="<?= htmlspecialchars($_POST['nama_kendaraan'] ?? '') ?>"
                   maxlength="100" required>
          </div>

          <div class="form-group full">
            <label>Harga per Hari (Rp) <span class="required">*</span></label>
            <div class="input-prefix">
              <span>Rp</span>
              <input type="number" name="harga_per_hari"
                     placeholder="150000"
                     value="<?= htmlspecialchars($_POST['harga_per_hari'] ?? '') ?>"
                     min="0" step="1000" required>
            </div>
            <p class="hint">Masukkan harga dalam Rupiah tanpa titik/koma.</p>
          </div>

        </div>
      </div><!-- /.form-body -->

      <div class="form-footer">
        <a href="dashboard.php" class="btn-cancel">Batal</a>
        <button type="submit" class="btn-submit">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Simpan Kendaraan
        </button>
      </div>
      </form>
  </div>

</main>
</body>
</html>