<?php
require_once 'auth_check.php';
require_once '../includes/config.php';

// Pagination
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
if ($search) {
    $where = "WHERE judul ILIKE '%$search%' OR deskripsi ILIKE '%$search%' OR abstrak ILIKE '%$search%' OR kategori ILIKE '%$search%'";
}

// Get total
$count_query = "SELECT COUNT(*) as total FROM hasil_penelitian $where";
$count_result = pg_query($conn, $count_query);
$total_items = pg_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get data with personil name AND kategori detail
$query = "SELECT hp.*, p.nama as personil_nama, kp.nama_kategori as kat_nama, kp.warna as kat_warna
          FROM hasil_penelitian hp
          LEFT JOIN personil p ON hp.personil_id = p.id
          LEFT JOIN kategori_penelitian kp ON hp.kategori_id = kp.id
          $where 
          ORDER BY hp.tahun DESC, hp.created_at DESC 
          LIMIT $items_per_page OFFSET $offset";
$result = pg_query($conn, $query);

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>

<!-- Main Content -->
<div class="admin-content">
    
    <!-- Top Bar -->
    <div class="admin-topbar">
        <div>
            <h4 class="mb-0">Kelola Hasil Penelitian</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Kelola Penelitian</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Content -->
    <div class="container-fluid">
        
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" data-aos="fade-down">
            <i class="bi bi-check-circle me-2"></i>
            <?php 
            if ($success == 'add') echo 'Penelitian berhasil ditambahkan!';
            elseif ($success == 'edit') echo 'Penelitian berhasil diupdate!';
            elseif ($success == 'delete') echo 'Penelitian berhasil dihapus!';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" data-aos="fade-down">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Action Bar -->
        <div class="card mb-4" data-aos="fade-up">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <a href="penelitian_form.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Tambah Penelitian
                        </a>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" placeholder="Cari judul, kategori, atau deskripsi..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if ($search): ?>
                            <a href="manage_penelitian.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i>
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Data Table -->
        <div class="card" data-aos="fade-up" data-aos-delay="100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Daftar Hasil Penelitian</h5>
            </div>
            <div class="card-body">
                
                <?php if (pg_num_rows($result) > 0): ?>
                
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="10%">Cover</th>
                                <th width="25%">Judul</th>
                                <th width="8%">Tahun</th>
                                <th width="12%">Kategori</th>
                                <th width="15%">Personil</th>
                                <th width="10%">File</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = $offset + 1;
                            while ($row = pg_fetch_assoc($result)): 
                            ?>
                            <tr class="fade-in">
                                <td><?php echo $no++; ?></td>
                               <td>
                                        <?php 
                                        // Logika Cek Gambar
                                        if (!empty($row['gambar'])) {
                                            $img_src = BASE_URL . '/public/uploads/produk/' . htmlspecialchars($row['gambar']);
                                        } else {
                                            $img_src = BASE_URL . '/public/img/no-image.png';
                                        }
                                        ?>
                                        
                                        <img src="<?php echo $img_src; ?>" 
                                            class="img-thumbnail" 
                                            style="width: 60px; height: 60px; object-fit: cover;"
                                            alt="Produk"
                                            /* Jika file gambar rusak/hilang, otomatis ganti ke no-image.png */
                                            onerror="this.onerror=null; this.src='<?php echo BASE_URL; ?>/public/img/no-image.png';">
                                    </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['judul']); ?></strong>
                                    <?php if ($row['file_pdf']): ?>
                                    <br><small class="text-danger"><i class="bi bi-file-pdf"></i> PDF</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $row['tahun']; ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($row['kat_nama'])): ?>
                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($row['kat_warna'] ?? '#0d6efd'); ?>">
                                        <?php echo htmlspecialchars($row['kat_nama']); ?>
                                    </span>
                                    <?php elseif ($row['kategori']): ?>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($row['kategori']); ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['personil_nama']): ?>
                                    <small><?php echo htmlspecialchars($row['personil_nama']); ?></small>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['file_pdf']): ?>
                                    <a href="<?php echo BASE_URL . '/public/uploads/penelitian/' . htmlspecialchars($row['file_pdf']); ?>"  
                                       class="btn btn-sm btn-outline-danger" target="_blank" title="Download PDF">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="penelitian_form.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['judul'])); ?>')" 
                                                title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <?php else: ?>
                
                <div class="text-center py-5">
                    <i class="bi bi-journal-text text-muted" style="font-size: 4rem;"></i>
                    <h5 class="text-muted mt-3">Tidak ada data penelitian</h5>
                    <p class="text-muted">Silakan tambahkan penelitian baru</p>
                    <a href="penelitian_form.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Penelitian
                    </a>
                </div>
                
                <?php endif; ?>
                
            </div>
        </div>
        
    </div>
    
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus penelitian:</p>
                <h6 id="deleteJudul" class="text-danger"></h6>
                <p class="text-muted mb-0"><small>Data, gambar, dan PDF yang dihapus tidak dapat dikembalikan!</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="bi bi-trash me-2"></i>Ya, Hapus
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
function confirmDelete(id, judul) {
    document.getElementById('deleteJudul').textContent = judul;
    document.getElementById('confirmDeleteBtn').href = 'delete_penelitian.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Auto hide alerts after 5 seconds
setTimeout(function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        var bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php
pg_close($conn);
include 'includes/admin_footer.php';
?>
