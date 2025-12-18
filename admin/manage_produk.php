<?php
require_once 'auth_check.php';
require_once '../includes/config.php';

// Pagination & Search
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$where = '';
if ($search) {
    $where = "WHERE nama_produk ILIKE '%$search%' OR deskripsi ILIKE '%$search%' OR teknologi ILIKE '%$search%'";
}

// Get total
$count_query = "SELECT COUNT(*) as total FROM produk $where";
$count_result = pg_query($conn, $count_query);
$total_items = pg_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get data with personil AND kategori
$query = "SELECT p.*, pr.nama as personil_nama, kp.nama_kategori as kat_nama, kp.warna as kat_warna
          FROM produk p
          LEFT JOIN personil pr ON p.personil_id = pr.id
          LEFT JOIN kategori_produk kp ON p.kategori_id = kp.id
          $where 
          ORDER BY p.tahun DESC, p.created_at DESC 
          LIMIT $items_per_page OFFSET $offset";
$result = pg_query($conn, $query);

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>

<div class="admin-content">
    <div class="admin-topbar">
        <div>
            <h4 class="mb-0">Kelola Produk</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Kelola Produk</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="container-fluid">
        
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" data-aos="fade-down">
            <i class="bi bi-check-circle me-2"></i>
            <?php 
            if ($success == 'add') echo 'Produk berhasil ditambahkan!';
            elseif ($success == 'edit') echo 'Produk berhasil diupdate!';
            elseif ($success == 'delete') echo 'Produk berhasil dihapus!';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" data-aos="fade-down">
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
                        <a href="produk_form.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Tambah Produk
                        </a>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" placeholder="Cari produk..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                            <?php if ($search): ?>
                            <a href="manage_produk.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Data Table -->
        <div class="card" data-aos="fade-up" data-aos-delay="100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Daftar Produk (<?php echo $total_items; ?>)</h5>
            </div>
            <div class="card-body">
                
                <?php if (pg_num_rows($result) > 0): ?>
                
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="10%">Gambar</th>
                                <th width="25%">Nama Produk</th>
                                <th width="8%">Tahun</th>
                                <th width="10%">Kategori</th>
                                <th width="15%">Personil</th>
                                <th width="17%">Teknologi</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = $offset + 1;
                            while ($row = pg_fetch_assoc($result)): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                               <td>
                                    <?php 
                                    // LOGIKA BARU: Cek Gambar & Gunakan Default
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
                                         /* SCRIPT ANTI-ERROR: Jika gambar rusak, ganti ke no-image.png */
                                         onerror="this.onerror=null; this.src='<?php echo BASE_URL; ?>/public/img/no-image.png';">
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['nama_produk']); ?></strong></td>
                                <td><span class="badge bg-primary"><?php echo $row['tahun']; ?></span></td>
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
                                <td><small><?php echo htmlspecialchars(substr($row['teknologi'], 0, 40)) . (strlen($row['teknologi']) > 40 ? '...' : ''); ?></small></td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="produk_form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_produk'])); ?>')" 
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
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <?php else: ?>
                
                <div class="text-center py-5">
                    <i class="bi bi-box-seam text-muted" style="font-size: 4rem;"></i>
                    <h5 class="text-muted mt-3">Tidak ada data produk</h5>
                    <a href="produk_form.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Produk
                    </a>
                </div>
                
                <?php endif; ?>
                
            </div>
        </div>
        
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus produk:</p>
                <h6 id="deleteNama" class="text-danger"></h6>
                <p class="text-muted mb-0"><small>Data yang dihapus tidak dapat dikembalikan!</small></p>
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

<script>
function confirmDelete(id, nama) {
    document.getElementById('deleteNama').textContent = nama;
    document.getElementById('confirmDeleteBtn').href = 'delete_produk.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

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
