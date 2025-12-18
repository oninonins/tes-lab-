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
    $where = "WHERE judul ILIKE '%$search%' OR penyelenggara ILIKE '%$search%' OR lokasi ILIKE '%$search%'";
}

// Get total
$count_query = "SELECT COUNT(*) as total FROM pengabdian $where";
$count_result = pg_query($conn, $count_query);
$total_items = pg_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get data
$query = "SELECT * FROM pengabdian $where ORDER BY tanggal DESC, created_at DESC LIMIT $items_per_page OFFSET $offset";
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
            <h4 class="mb-0">Kelola Pengabdian Masyarakat</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Kelola Pengabdian</li>
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
                if ($success == 'add') echo 'Kegiatan pengabdian berhasil ditambahkan!';
                elseif ($success == 'edit') echo 'Kegiatan pengabdian berhasil diupdate!';
                elseif ($success == 'delete') echo 'Kegiatan pengabdian berhasil dihapus!';
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
                        <a href="pengabdian_form.php" class="btn btn-success">
                            <i class="bi bi-plus-circle me-2"></i>Tambah Kegiatan
                        </a>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" placeholder="Cari judul, lokasi, atau penyelenggara..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-outline-success">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if ($search): ?>
                                <a href="manage_pengabdian.php" class="btn btn-outline-secondary">
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
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>Daftar Kegiatan Pengabdian</h5>
            </div>
            <div class="card-body">

                <?php if (pg_num_rows($result) > 0): ?>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="10%">Gambar</th>
                                    <th width="20%">Judul</th>
                                    <th width="25%">Deskripsi</th>
                                    <th width="10%">Tanggal</th>
                                    <th width="12%">Lokasi</th>
                                    <th width="10%">Penyelenggara</th>
                                    <th width="8%" class="text-center">Aksi</th>
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
                                    if (!empty($row['gambar'])) {
                                        $img_src = BASE_URL . '/public/uploads/pengabdian/' . htmlspecialchars($row['gambar']);
                                    } else {
                                        $img_src = BASE_URL . '/public/img/no-image.png';
                                    }
                                    ?>
                                    
                                    <img src="<?php echo $img_src; ?>" 
                                         class="img-thumbnail" 
                                         style="width: 80px; height: 60px; object-fit: cover;"
                                         alt="Gambar"
                                         onerror="this.onerror=null; this.src='<?php echo BASE_URL; ?>/public/img/no-image.png';">
                                </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['judul']); ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php
                                                $deskripsi = strip_tags($row['deskripsi']);
                                                echo strlen($deskripsi) > 100 ? substr($deskripsi, 0, 100) . '...' : $deskripsi;
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d M Y', strtotime($row['tanggal'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($row['lokasi']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($row['penyelenggara']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="pengabdian_form.php?id=<?php echo $row['id']; ?>"
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
                                <!-- Previous -->
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>

                                <!-- Pages -->
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <!-- Next -->
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>

                <?php else: ?>

                    <div class="text-center py-5">
                        <i class="bi bi-people-fill text-muted" style="font-size: 4rem;"></i>
                        <h5 class="text-muted mt-3">Tidak ada data kegiatan pengabdian</h5>
                        <p class="text-muted">Silakan tambahkan kegiatan pengabdian baru</p>
                        <a href="pengabdian_form.php" class="btn btn-success">
                            <i class="bi bi-plus-circle me-2"></i>Tambah Kegiatan
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
                <p>Apakah Anda yakin ingin menghapus kegiatan:</p>
                <h6 id="deleteJudul" class="text-danger"></h6>
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

<style>
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
    function confirmDelete(id, judul) {
        document.getElementById('deleteJudul').textContent = judul;
        document.getElementById('confirmDeleteBtn').href = 'delete_pengabdian.php?id=' + id;
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