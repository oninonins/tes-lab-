<?php
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/session.php';

class PengabdianController
{
    private $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    // --- PATH ABSOLUT ---
    private function getUploadPath() {
        return dirname(__DIR__, 2) . '/public/uploads/pengabdian/';
    }

    // Add new pengabdian
    public function add()
    {
        $error = '';
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $judul = pg_escape_string($this->conn, trim($_POST['judul']));
            $deskripsi = pg_escape_string($this->conn, trim($_POST['deskripsi']));
            $tanggal = pg_escape_string($this->conn, trim($_POST['tanggal']));
            $lokasi = pg_escape_string($this->conn, trim($_POST['lokasi']));
            $penyelenggara = pg_escape_string($this->conn, trim($_POST['penyelenggara']));
            $personil_id = isset($_POST['personil_id']) && !empty($_POST['personil_id']) ? (int)$_POST['personil_id'] : null;

            if (empty($judul) || empty($deskripsi) || empty($tanggal) || empty($lokasi) || empty($penyelenggara)) {
                $error = 'Semua field wajib diisi!';
            } else {
                $gambar = '';
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['gambar']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed)) {
                        $new_filename = 'pengabdian_' . time() . '_' . uniqid() . '.' . $ext;
                        
                        // Path Absolut
                        $upload_dir = $this->getUploadPath();

                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $new_filename)) {
                            $gambar = $new_filename;
                        }
                    } else {
                        $error = 'Format file tidak diizinkan. Gunakan JPG, PNG, atau GIF.';
                    }
                }

                if (empty($error)) {
                    $query = "INSERT INTO pengabdian (judul, deskripsi, tanggal, lokasi, penyelenggara, gambar, personil_id, created_at, updated_at)
                              VALUES ($1, $2, $3, $4, $5, $6, $7, NOW(), NOW())";

                    $params = array($judul, $deskripsi, $tanggal, $lokasi, $penyelenggara, $gambar, $personil_id);
                    $result = pg_query_params($this->conn, $query, $params);

                    if ($result) {
                        header('Location: manage_pengabdian.php?success=add');
                        exit();
                    } else {
                        $error = 'Gagal menambahkan pengabdian: ' . pg_last_error($this->conn);
                    }
                }
            }
        }

        return ['error' => $error, 'success' => $success];
    }

    public function getAllPersonil()
    {
        $query = "SELECT id, nama FROM personil ORDER BY nama ASC";
        $result = pg_query($this->conn, $query);

        $personil = [];
        while ($row = pg_fetch_assoc($result)) {
            $personil[] = $row;
        }
        return $personil;
    }

    public function edit($id)
    {
        $error = '';
        $success = false;
        $pengabdian = null;

        if ($id) {
            $query = "SELECT * FROM pengabdian WHERE id = $1";
            $result = pg_query_params($this->conn, $query, array($id));
            $pengabdian = pg_fetch_assoc($result);

            if (!$pengabdian) {
                return ['error' => 'Data tidak ditemukan!', 'pengabdian' => null];
            }
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $judul = pg_escape_string($this->conn, trim($_POST['judul']));
            $deskripsi = pg_escape_string($this->conn, trim($_POST['deskripsi']));
            $tanggal = pg_escape_string($this->conn, trim($_POST['tanggal']));
            $lokasi = pg_escape_string($this->conn, trim($_POST['lokasi']));
            $penyelenggara = pg_escape_string($this->conn, trim($_POST['penyelenggara']));

            if (empty($judul)) {
                $error = 'Semua field wajib diisi!';
            } else {
                $gambar = $pengabdian['gambar'];
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['gambar']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed)) {
                        $new_filename = 'pengabdian_' . time() . '_' . uniqid() . '.' . $ext;
                        
                        // Path Absolut
                        $upload_dir = $this->getUploadPath();

                        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

                        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $new_filename)) {
                            // Delete old image
                            if ($pengabdian['gambar'] && file_exists($upload_dir . $pengabdian['gambar'])) {
                                unlink($upload_dir . $pengabdian['gambar']);
                            }
                            $gambar = $new_filename;
                        }
                    } else {
                        $error = 'Format file tidak diizinkan.';
                    }
                }

                if (empty($error)) {
                    $query = "UPDATE pengabdian SET judul = $1, deskripsi = $2, tanggal = $3, lokasi = $4, penyelenggara = $5, gambar = $6, updated_at = NOW() 
                              WHERE id = $7";
                    $result = pg_query_params($this->conn, $query, array($judul, $deskripsi, $tanggal, $lokasi, $penyelenggara, $gambar, $id));

                    if ($result) {
                        header('Location: manage_pengabdian.php?success=edit');
                        exit();
                    } else {
                        $error = 'Gagal update: ' . pg_last_error($this->conn);
                    }
                }
            }
        }

        return ['error' => $error, 'success' => $success, 'pengabdian' => $pengabdian];
    }

    public function delete($id)
    {
        if ($id) {
            $query = "SELECT gambar FROM pengabdian WHERE id = $1";
            $result = pg_query_params($this->conn, $query, array($id));
            $pengabdian = pg_fetch_assoc($result);

            if ($pengabdian) {
                $delete_query = "DELETE FROM pengabdian WHERE id = $1";
                $delete_result = pg_query_params($this->conn, $delete_query, array($id));

                if ($delete_result) {
                    // Path Absolut
                    $upload_dir = $this->getUploadPath();
                    if ($pengabdian['gambar'] && file_exists($upload_dir . $pengabdian['gambar'])) {
                        unlink($upload_dir . $pengabdian['gambar']);
                    }
                    header('Location: manage_pengabdian.php?success=delete');
                } else {
                    header('Location: manage_pengabdian.php?error=delete');
                }
            } else {
                header('Location: manage_pengabdian.php?error=notfound');
            }
        }
        exit();
    }

    public function getAll($page = 1, $limit = 10, $search = '')
    {
        $offset = ($page - 1) * $limit;
        $where = $search ? "WHERE judul ILIKE '%$search%' OR penyelenggara ILIKE '%$search%'" : '';

        $count_query = "SELECT COUNT(*) as total FROM pengabdian $where";
        $count_result = pg_query($this->conn, $count_query);
        $total_records = pg_fetch_assoc($count_result)['total'];
        $total_pages = ceil($total_records / $limit);

        $query = "SELECT * FROM pengabdian $where ORDER BY tanggal DESC, created_at DESC LIMIT $limit OFFSET $offset";
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
}
?>