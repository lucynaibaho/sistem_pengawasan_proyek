<?php
session_start();
include '../koneksi.php';

// Proteksi Role
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'koordinator') {
    header('Location: ../login.php');
    exit;
}

// Load riwayat laporan mingguan dari database
$activePage = 'riwayat';
$riwayat_items = [];
$tersimpan_count = 0;
$evaluasi_count = 0;
$total_count = 0;

if ($koneksi) {
    $koordinator_id = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
    $where = $koordinator_id > 0 ? "WHERE koordinator_id = $koordinator_id" : '';
    $query = "SELECT id, judul, periode, progress, total_tk, status, ringkasan, temuan, pencapaian, kendala FROM laporan_mingguan $where ORDER BY id DESC";
    $result = mysqli_query($koneksi, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['display_id'] = 'LM-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
            $riwayat_items[] = $row;
            if (strtolower($row['status']) === 'tersimpan') {
                $tersimpan_count++;
            }
            if (strtolower($row['status']) === 'evaluasi tl') {
                $evaluasi_count++;
            }
        }
        $total_count = count($riwayat_items);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Laporan Mingguan – Koordinator Pengawas</title>
  <link rel="stylesheet" href="asset/koordinator.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main">
  <!-- TOPBAR -->
  <div class="topbar">
    <h2>Riwayat Laporan Mingguan</h2>
    <div class="topbar-right">
      <div class="date-chip" id="date-chip"></div>
      <a href="Tinjau_laporan_harian.php" class="notif-btn">
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
      <div class="section-title">Riwayat Laporan Mingguan</div>
      <div class="section-sub">Arsip laporan yang telah tersimpan</div>
    </div>
    <a href="susun_laporan_mingguan.php" class="btn btn-gold btn-sm">+ Susun Baru</a>
  </div>

  <!-- SUMMARY CHIPS -->
  <div style="display:flex;gap:10px;margin-bottom:20px;" class="fade-up" style="animation-delay:.08s">
    <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:20px;
                padding:6px 16px;font-size:12px;color:#22c55e;">
      ✓ Tersimpan: <strong><?= $tersimpan_count ?></strong>
    </div>
    <div style="background:rgba(56,189,248,.1);border:1px solid rgba(56,189,248,.2);border-radius:20px;
                padding:6px 16px;font-size:12px;color:#38bdf8;">
      🔍 Evaluasi TL: <strong><?= $evaluasi_count ?></strong>
    </div>
    <div style="background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:20px;
                padding:6px 16px;font-size:12px;color:var(--muted);">
      Total: <strong><?= $total_count ?></strong>
    </div>
  </div>

  <!-- TABLE -->
  <div class="table-wrap fade-up" style="animation-delay:.12s">
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Judul</th><th>Periode</th>
          <th>Progress</th><th>TK Total</th><th>Status</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
    <?php if ($total_count > 0): ?>
      <?php foreach ($riwayat_items as $item): ?>
        <tr>
          <td style="color:var(--muted);font-weight:600;"><?= htmlspecialchars($item['display_id']) ?></td>
          <td style="font-weight:500;"><?= htmlspecialchars($item['judul']) ?></td>
          <td style="color:var(--muted);"><?= htmlspecialchars($item['periode']) ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div class="progress-wrap" style="width:100px;">
                <div class="progress-bar" style="width:<?= intval($item['progress']) ?>%;"></div>
              </div>
              <span style="font-size:11px;color:var(--muted);"><?= intval($item['progress']) ?>%</span>
            </div>
          </td>
          <td><?= intval($item['total_tk']) ?></td>
          <td><span class="badge <?= strtolower($item['status']) === 'evaluasi tl' ? 'badge-review' : 'badge-done' ?>"><?= htmlspecialchars($item['status']) ?></span></td>
          <td style="display:flex;gap:6px;">
            <button class="btn btn-outline btn-sm" onclick="openDetail(<?= json_encode($item['id']) ?>)">Lihat</button>
            <button class="btn btn-outline btn-sm" onclick="cetakLaporan(<?= json_encode($item['id']) ?>)"
              style="color:var(--info);border-color:rgba(56,189,248,.3);">Cetak</button>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="7" style="text-align:center;color:var(--muted);padding:24px;">Tidak ada riwayat laporan mingguan.</td>
      </tr>
    <?php endif; ?>
  </tbody>
    </table>
  </div>

</main>

<!-- MODAL DETAIL RIWAYAT -->
<div class="modal-overlay" id="modal" onclick="if(event.target===this)closeModal()">
  <div class="modal" style="width:580px;">
    <div class="modal-header">
      <h3 id="modal-title">Detail Laporan Mingguan</h3>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body" id="modal-body"></div>
    <div class="modal-actions">
      <button class="btn btn-outline" onclick="closeModal()">Tutup</button>
      <button class="btn btn-outline" style="color:var(--info);border-color:rgba(56,189,248,.3);"
        onclick="showToast('Mencetak laporan...','')">
        🖨 Cetak
      </button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
// Date chip
const d = new Date();
document.getElementById('date-chip').textContent =
  d.toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long',year:'numeric'});

// Riwayat data
const riwayatData = <?= json_encode(array_reduce($riwayat_items, function($carry, $item){ $carry[$item['id']] = [
        'display_id' => $item['display_id'],
        'judul' => $item['judul'],
        'periode' => $item['periode'],
        'progress' => intval($item['progress']),
        'tk' => intval($item['total_tk']),
        'status' => $item['status'],
        'ringkasan' => $item['ringkasan'],
        'temuan' => $item['temuan'],
        'rekomendasi' => $item['pencapaian'] ?: $item['kendala'] ?: ''
    ]; return $carry; }, []), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;

function openDetail(id){
  const r = riwayatData[id];
  if(!r) return;
  document.getElementById('modal-title').textContent = 'Detail — ' + r.judul;
  document.getElementById('modal-body').innerHTML = `
    <div style="background:var(--surface2);border:1px solid var(--border-gold);border-radius:10px;
                padding:14px;margin-bottom:16px;">
      <div style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;">${r.judul}</div>
      <div style="font-size:12px;color:var(--muted);margin-top:4px;">
        Periode: ${r.periode} &nbsp;|&nbsp; Progress: ${r.progress}% &nbsp;|&nbsp; TK: ${r.tk} orang
      </div>
      <div class="progress-wrap" style="margin-top:10px;">
        <div class="progress-bar" style="width:${r.progress}%;"></div>
      </div>
    </div>
    <div class="detail-row">
      <span class="key">Status</span>
      <span class="badge ${r.status==='Disetujui TL'||r.status==='Tersimpan'?'badge-done':'badge-review'}">${r.status}</span>
    </div>
    <div class="detail-row">
      <span class="key">Ringkasan Progres</span>
      <span style="text-align:right;max-width:300px;font-size:12px;">${r.ringkasan}</span>
    </div>
    <div class="detail-row">
      <span class="key">Temuan Lapangan</span>
      <span style="text-align:right;max-width:300px;font-size:12px;">${r.temuan}</span>
    </div>
    <div class="detail-row">
      <span class="key">Rekomendasi</span>
      <span style="text-align:right;max-width:300px;font-size:12px;">${r.rekomendasi}</span>
    </div>`;
  document.getElementById('modal').classList.add('open');
}
function closeModal(){ document.getElementById('modal').classList.remove('open'); }

function cetakLaporan(id){
  showToast('Mencetak laporan ' + id + '...', '');
}

function showToast(msg, type){
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast show' + (type?' '+type:'');
  setTimeout(()=>t.className='toast', 2800);
}
</script>
</body>
</html>