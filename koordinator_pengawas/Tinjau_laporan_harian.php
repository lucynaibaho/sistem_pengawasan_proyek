<?php
session_start();
$koneksi = null;
require_once "../koneksi.php";

if (!isset($koneksi) || !$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Proteksi Role
if (!isset($_SESSION['role']) || $_SESSION['role'] != "koordinator") {
    header("Location: ../login.php");
    exit;
}

$active_page = 'tinjau';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $laporan_id = intval($_POST['laporan_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');

    if ($laporan_id <= 0 || !in_array($action, ['approve', 'revision'], true)) {
        $error = 'Aksi tidak valid. Mohon ulangi kembali.';
    } else {
        $status = $action === 'approve' ? 'Tervalidasi' : 'Diminta Revisi';
        $stmt = mysqli_prepare($koneksi, "UPDATE laporan_harian SET status = ? WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $status, $laporan_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = $action === 'approve'
                    ? 'Laporan berhasil disahkan dan status disimpan ke database.'
                    : 'Laporan berhasil dikembalikan untuk revisi dan status disimpan ke database.';
            } else {
                $error = 'Gagal memperbarui status laporan. Silakan coba lagi.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Gagal mempersiapkan query. Silakan coba lagi.';
        }
    }
}

$laporan_result = mysqli_query($koneksi, "SELECT * FROM laporan_harian ORDER BY tanggal DESC");
$laporan_list = [];
if ($laporan_result) {
    while ($laporan = mysqli_fetch_assoc($laporan_result)) {
        $laporan_list[] = $laporan;
    }
} else {
    $error = 'Gagal memuat data laporan. Silakan coba lagi nanti.';
}

function safe($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatDate($value) {
    if (empty($value) || $value === '0000-00-00') {
        return '-';
    }
    $ts = strtotime($value);
    return $ts ? date('d M Y', $ts) : $value;
}

function reportCode($id) {
    return 'LH-' . str_pad($id, 3, '0', STR_PAD_LEFT);
}

function badgeClass($status) {
    $status = strtolower(trim($status));
    if ($status === 'menunggu') return 'badge-wait';
    if ($status === 'tervalidasi') return 'badge-done';
    if ($status === 'diminta revisi') return 'badge-review';
    return 'badge-draft';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tinjau Laporan Harian | CV Cipta Manunggal</title>
    <link rel="stylesheet" href="asset/koordinator.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main">
    <!-- TOPBAR -->
    <div class="topbar">
        <h2>Tinjau Laporan Harian</h2>
        <div class="topbar-right">
            <div class="date-chip" id="date-chip"></div>
            <a href="riwayat_laporan_mingguan.php" class="notif-btn">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="#aaa" stroke-width="1.5">
                    <path d="M8 1a5 5 0 015 5v3l1 2H2l1-2V6a5 5 0 015-5z"/>
                    <path d="M6.5 13.5a1.5 1.5 0 003 0"/>
                </svg>
                <div class="notif-dot"></div>
            </a>
        </div>
    </div>

    <!-- SECTION HEADER -->
    <div class="section-header fade-up">
        <div>
            <div class="section-title">Tinjau Laporan Harian</div>
            <div class="section-sub">Tinjauan & pengesahan laporan dari Pengawas Lapangan</div>
        </div>
    </div>

    <!-- SEARCH & FILTER -->
    <div class="panel fade-up" style="animation-delay:.04s">
        <div style="display: flex; gap: 16px; margin-bottom: 0;">
            <input type="text" id="search-input" class="form-group" style="background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; padding: 10px 14px; color: var(--text); width: 200px;" placeholder="Cari laporan..." oninput="filterTable()">
            <select id="status-filter" class="form-group" style="background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; padding: 10px 14px; color: var(--text);" onchange="filterTable()">
                <option value="">Semua Status</option>
                <option value="Menunggu">Menunggu</option>
                <option value="Tervalidasi">Tervalidasi</option>
            </select>
        </div>
    </div>

    <!-- TABLE -->
    <div class="table-wrap fade-up" style="animation-delay:.08s">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Pengawas</th>
                    <th>Kontraktor</th>
                    <th>Progres</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="tinjau-tbody">
                <?php if (count($laporan_list) > 0): ?>
                    <?php foreach ($laporan_list as $laporan): ?>
                        <?php
                            $code = reportCode($laporan['id']);
                            $tanggal = formatDate($laporan['tanggal'] ?? '');
                            $pengawas = $laporan['pengawas'] ?? '-';
                            $kontraktor = $laporan['kontraktor'] ?? ($laporan['kontraktor_id'] ?? '-');
                            $progres = $laporan['progres'] ?? '-';
                            $status = $laporan['status'] ?? 'Menunggu';
                            $badge_class = badgeClass($status);
                            $search_text = strtolower($code . ' ' . $pengawas . ' ' . $kontraktor . ' ' . $progres . ' ' . $status);
                            $catatan = $laporan['catatan'] ?? '-';
                        ?>
                        <tr
                            data-id="<?= safe($laporan['id']) ?>"
                            data-code="<?= safe($code) ?>"
                            data-date="<?= safe($tanggal) ?>"
                            data-pengawas="<?= safe($pengawas) ?>"
                            data-kontraktor="<?= safe($kontraktor) ?>"
                            data-progres="<?= safe($progres) ?>"
                            data-status="<?= safe($status) ?>"
                            data-desc="<?= safe($catatan) ?>"
                            data-search="<?= safe($search_text) ?>"
                        >
                            <td><?= safe($code) ?></td>
                            <td><?= safe($tanggal) ?></td>
                            <td><?= safe($pengawas) ?></td>
                            <td><?= safe($kontraktor) ?></td>
                            <td><?= safe($progres) ?></td>
                            <td><span class="badge <?= $badge_class ?>"><?= safe($status) ?></span></td>
                            <td>
                                <div class="action-group" style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-outline btn-sm" onclick="openModal(this.closest('tr'))">Detail</button>
                                    <?php if ($status !== 'Tervalidasi'): ?>
                                        <button type="button" class="btn btn-gold btn-sm" onclick="submitAction(this.closest('tr'), 'approve')">Setujui</button>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="submitAction(this.closest('tr'), 'revision')">Revisi</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:24px; color: var(--muted);">Belum ada laporan harian yang tersedia.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- MODAL DETAIL -->
<div class="modal-overlay" id="modal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modal-title">Detail Laporan</h3>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body" id="modal-body"></div>
        <div class="modal-actions">
            <button class="btn btn-outline" onclick="closeModal()">Tutup</button>
            <button class="btn btn-gold" id="modal-action-approve" onclick="submitModalAction('approve')" style="display:none;">Setujui</button>
            <button class="btn btn-outline" id="modal-action-revision" onclick="submitModalAction('revision')" style="display:none;">Minta Revisi</button>
        </div>
    </div>
</div>

<form id="action-form" method="post" style="display:none;">
    <input type="hidden" name="laporan_id" id="laporan_id">
    <input type="hidden" name="action" id="action">
</form>

<script>
function updateDateChip() {
    const now = new Date();
    const options = {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    document.getElementById('date-chip').textContent = now.toLocaleDateString('id-ID', options);
}
updateDateChip();
setInterval(updateDateChip, 60000);

function filterTable() {
    const query = document.getElementById('search-input').value.toLowerCase();
    const status = document.getElementById('status-filter').value;
    document.querySelectorAll('#tinjau-tbody tr').forEach(row => {
        const text = row.dataset.search || '';
        const rowStatus = row.dataset.status || '';
        const matchesQuery = !query || text.includes(query);
        const matchesStatus = !status || rowStatus === status;
        row.style.display = matchesQuery && matchesStatus ? '' : 'none';
    });
}

function openModal(row) {
    const code = row.dataset.code || '-';
    const tanggal = row.dataset.date || '-';
    const pengawas = row.dataset.pengawas || '-';
    const kontraktor = row.dataset.kontraktor || '-';
    const progres = row.dataset.progres || '-';
    const status = row.dataset.status || 'Menunggu';
    const desc = row.dataset.desc || '-';

    document.getElementById('modal-title').textContent = 'Detail Laporan ' + code;
    document.getElementById('modal-body').innerHTML = `
        <div class="detail-row"><span class="key">ID</span><span>${code}</span></div>
        <div class="detail-row"><span class="key">Tanggal</span><span>${tanggal}</span></div>
        <div class="detail-row"><span class="key">Pengawas</span><span>${pengawas}</span></div>
        <div class="detail-row"><span class="key">Kontraktor</span><span>${kontraktor}</span></div>
        <div class="detail-row"><span class="key">Progres</span><span>${progres}</span></div>
        <div class="detail-row"><span class="key">Status</span><span class="badge ${status === 'Tervalidasi' ? 'badge-done' : status === 'Diminta Revisi' ? 'badge-review' : 'badge-wait'}">${status}</span></div>
        <div class="detail-row"><span class="key">Catatan</span><span>${desc}</span></div>
    `;

    const approveBtn = document.getElementById('modal-action-approve');
    const revisionBtn = document.getElementById('modal-action-revision');
    const isOpen = status !== 'Tervalidasi';
    approveBtn.style.display = isOpen ? 'inline-flex' : 'none';
    revisionBtn.style.display = isOpen ? 'inline-flex' : 'none';

    const modal = document.getElementById('modal');
    modal.dataset.reportId = row.dataset.id;
    modal.classList.add('open');
}

function closeModal() {
    const modal = document.getElementById('modal');
    modal.classList.remove('open');
    modal.dataset.reportId = '';
}

function submitAction(row, action) {
    const id = row.dataset.id;
    if (!id) return;
    if (action === 'revision') {
        const comment = prompt('Masukkan komentar revisi untuk laporan ini:');
        if (!comment) return;
    }
    document.getElementById('laporan_id').value = id;
    document.getElementById('action').value = action;
    document.getElementById('action-form').submit();
}

function submitModalAction(action) {
    const modal = document.getElementById('modal');
    const id = modal.dataset.reportId;
    if (!id) return;
    if (action === 'revision') {
        const comment = prompt('Masukkan komentar revisi untuk laporan ini:');
        if (!comment) return;
    }
    document.getElementById('laporan_id').value = id;
    document.getElementById('action').value = action;
    document.getElementById('action-form').submit();
}
</script>

</body>
</html>