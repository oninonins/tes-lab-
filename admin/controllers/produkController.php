<?php
// Controller: Produk Controller (Admin)
// Fix: Path Upload Absolut (Anti-Error Docker)

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/session.php';

class ProdukController {
    
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    // --- FUNGSI UTAMA: PATH ABSOLUT ---
    private function getUploadPath() {
        // Menghasilkan path lengkap: /var/www/html/labse_web/public/uploads/produk/
        return dirname(__DIR__, 2) . '/public/uploads/produk/';
    }
    
    public function getKategoriList() {
        $query = "SELECT id, nama_kategori FROM kategori_produk WHERE is_active = TRUE ORDER BY nama_kategori ASC";
        $result = pg_query($this->conn, $query);
        $list = [];
        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $list[] = $row;
            }
        }
        return $list;
    }

    public function getAllPersonil() {
        $query = "SELECT id, nama FROM personil ORDER BY nama ASC";
        $result = pg_query($this->conn, $query);
        $personil = [];
        while ($row = pg_fetch_assoc($result)) {
            $personil[] = $row;
        }
        return $personil;
    }

    private function getNamaKategoriById($id) {
        if (!$id) return '';
        $query = "SELECT nama_kategori FROM kategori_produk WHERE id = $1";
        $result = pg_query_params($this->conn, $query, array($id));
        if ($result && pg_num_rows($result) > 0) {
            return pg_fetch_result($result, 0, 0);
        }
        return '';
    }

    // ADD DATA
    public function add() {
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nama_produk = pg_escape_string($this->conn, trim($_POST['nama_produk'] ?? ''));
            $deskripsi = pg_escape_string($this->conn, trim($_POST['deskripsi'] ?? ''));
            $tahun = isset($_POST['tahun']) ? (int)$_POST['tahun'] : date('Y');
            $kategori_id = isset($_POST['kategori_id']) && !empty($_POST['kategori_id']) ? (int)$_POST['kategori_id'] : null;
            $kategori_text = $this->getNamaKategoriById($kategori_id);
            $link_demo = pg_escape_string($this->conn, trim($_POST['link_demo'] ?? ''));
            $link_repository = pg_escape_string($this->conn, trim($_POST['link_repository'] ?? ''));
            $teknologi = pg_escape_string($this->conn, trim($_POST['teknologi'] ?? ''));
            $personil_id = isset($_POST['personil_id']) && !empty($_POST['personil_id']) ? (int)$_POST['personil_id'] : null;
            
            if (empty($nama_produk)) {
                $error = 'Nama produk wajib diisi!';
            } else {
                $gambar = '';
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                    $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $new_filename = 'produk_' . time() . '_' . uniqid() . '.' . $ext;
                        
                        // --- GUNAKAN PATH ABSOLUT ---
                        $upload_dir = $this->getUploadPath();
                        
                        // Buat folder jika belum ada
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Pindahkan file
                        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $new_filename)) {
                            $gambar = $new_filename;
                        }
                    }
                }
                
                $query = "INSERT INTO produk 
                          (nama_produk, deskripsi, kategori, kategori_id, tahun, gambar, link_demo, link_repository, teknologi, personil_id, created_at, updated_at)
                          VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, NOW(), NOW())";

                $result = pg_query_params($this->conn, $query, array(
                    $nama_produk, $deskripsi, $kategori_text, $kategori_id, $tahun, 
                    $gambar, $link_demo, $link_repository, $teknologi, $personil_id
                ));
                
                if ($result) {
                    header('Location: manage_produk.php?success=add');
                    exit();
                } else {
                    $error = 'Gagal menambahkan produk: ' . pg_last_error($this->conn);
                }
            }
        }
        return ['error' => $error];
    }
    
    // EDIT DATA
    public function edit($id) {
        $error = '';
        $produk = null;
        
        if ($id) {
            $query = "SELECT * FROM produk WHERE id = $1";
            $result = pg_query_params($this->conn, $query, array($id));
            $produk = pg_fetch_assoc($result);
            if (!$produk) {
                return ['error' => 'Data tidak ditemukan!', 'produk' => null];
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nama_produk = pg_escape_string($this->conn, trim($_POST['nama_produk'] ?? ''));
            $deskripsi = pg_escape_string($this->conn, trim($_POST['deskripsi'] ?? ''));
            $tahun = isset($_POST['tahun']) ? (int)$_POST['tahun'] : date('Y');
            $kategori_id = isset($_POST['kategori_id']) && !empty($_POST['kategori_id']) ? (int)$_POST['kategori_id'] : null;
            $kategori_text = $this->getNamaKategoriById($kategori_id);
            $link_demo = pg_escape_string($this->conn, trim($_POST['link_demo'] ?? ''));
            $link_repository = pg_escape_string($this->conn, trim($_POST['link_repository'] ?? ''));
            $teknologi = pg_escape_string($this->conn, trim($_POST['teknologi'] ?? ''));
            $personil_id = isset($_POST['personil_id']) && !empty($_POST['personil_id']) ? (int)$_POST['personil_id'] : null;
            
            if (empty($nama_produk)) {
                $error = 'Nama produk wajib diisi!';
            } else {
                $gambar = $produk['gambar'];
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                    $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $new_filename = 'produk_' . time() . '_' . uniqid() . '.' . $ext;
                        
                        // --- GUNAKAN PATH ABSOLUT ---
                        $upload_dir = $this->getUploadPath();
                        
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $new_filename)) {
                            // Hapus gambar lama
                            if ($gambar && file_exists($upload_dir . $gambar)) {
                                unlink($upload_dir . $gambar);
                            }
                            $gambar = $new_filename;
                        }
                    }
                }
                
                $query = "UPDATE produk 
                          SET nama_produk = $1, deskripsi = $2, kategori = $3, kategori_id = $4, tahun = $5, 
                              gambar = $6, link_demo = $7, link_repository = $8, teknologi = $9, personil_id = $10, 
                              updated_at = NOW() 
                          WHERE id = $11";
                          
                $result = pg_query_params($this->conn, $query, array(
                    $nama_produk, $deskripsi, $kategori_text, $kategori_id, $tahun, 
                    $gambar, $link_demo, $link_repository, $teknologi, $personil_id, $id
                ));
                
                if ($result) {
                    header('Location: manage_produk.php?success=edit');
                    exit();
                } else {
                    $error = 'Gagal update: ' . pg_last_error($this->conn);
                }
            }
        }
        
        return ['error' => $error, 'produk' => $produk];
    }

    public function getAll($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        $where = $search ? "WHERE p.nama_produk ILIKE '%$search%'" : '';
        
        $count_query = "SELECT COUNT(*) as total FROM produk p $where";
        $total_records = pg_fetch_assoc(pg_query($this->conn, $count_query))['total'];
        $total_pages = ceil($total_records / $limit);
        
        $query = "SELECT p.*, pers.nama as personil_nama, kp.nama_kategori as kat_nama 
                  FROM produk p
                  LEFT JOIN personil pers ON p.personil_id = pers.id
                  LEFT JOIN kategori_produk kp ON p.kategori_id = kp.id
                  $where 
                  ORDER BY p.created_at DESC 
                  LIMIT $limit OFFSET $offset";
        $result = pg_query($this->conn, $query);
        
        $items = [];
        while ($row = pg_fetch_assoc($result)) {
            $items[] = $row;
        }
        
        return [
            'items' => $items, 
            'total_records' => $total_records, 
            'total_pages' => $total_pages, 
            'current_page' => $page
        ];
    }
    
    public function delete($id) {
        if ($id) {
            $q_img = "SELECT gambar FROM produk WHERE id = $1";
            $res_img = pg_query_params($this->conn, $q_img, array($id));
            $img = pg_fetch_result($res_img, 0, 0);

            $q = "DELETE FROM produk WHERE id = $1";
            if (pg_query_params($this->conn, $q, array($id))) {
                // --- GUNAKAN PATH ABSOLUT UNTUK DELETE ---
                $upload_dir = $this->getUploadPath();
                if ($img && file_exists($upload_dir . $img)) {
                    unlink($upload_dir . $img);
                }
                header('Location: manage_produk.php?success=delete');
            } else {
                header('Location: manage_produk.php?error=delete');
            }
            exit();
        }
    }
    
    public function getById($id) {
        $query = "SELECT * FROM produk WHERE id = $1";
        $result = pg_query_params($this->conn, $query, array($id));
        return pg_fetch_assoc($result);
    }
}
?>