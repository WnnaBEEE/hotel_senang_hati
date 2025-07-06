<?php
// api.php - Main API Endpoints
require_once 'php_config.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
try {
    // Get request method and action/endpoint
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_GET['endpoint'] ?? '';

    // Route requests
    switch ($action) {
        case 'dashboard':
            handleDashboard();
            break;
        case 'rooms':
            handleRooms();
            break;
        case 'guests':
            handleGuests();
            break;
        case 'reservations':
            handleReservations();
            break;
        case 'payments':
            handlePayments();
            break;
        case 'checkin':
            handleCheckin();
            break;
        case 'checkout':
            handleCheckout();
            break;
        case 'available-rooms':
            handleAvailableRooms();
            break;
        case 'rooms/get':
            handleGetRoom();
            break;
        case 'todays-reservations':
            handleTodaysReservations();
            break;
        case 'reservations/get':
            handleGetReservation();
            break;    
        case 'payments/get':
            handleGetPayment();
            break;

        default:
            sendResponse(['error' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    sendResponse(['error' => $e->getMessage()], 500);
}
// Dashboard functions
function handleDashboard() {
    global $conn;
    
    try {
        $stats = [];
        
        // Total rooms
        $result = $conn->query("SELECT COUNT(*) as total FROM kamar");
        $stats['total_rooms'] = $result->fetch_assoc()['total'];
        
        // Available rooms
        $result = $conn->query("SELECT COUNT(*) as available FROM kamar WHERE status_kamar = 'tersedia'");
        $stats['available_rooms'] = $result->fetch_assoc()['available'];
        
        // Total guests
        $result = $conn->query("SELECT COUNT(*) as total FROM tamu");
        $stats['total_guests'] = $result->fetch_assoc()['total'];
        
        // Active reservations
        $result = $conn->query("SELECT COUNT(*) as active FROM pemesanan WHERE status_pemesanan IN ('confirmed', 'checkedin')");
        $stats['active_reservations'] = $result->fetch_assoc()['active'];
        
        // Available rooms today
        $today = date('Y-m-d');
        $sql = "SELECT k.nomor_kamar, tk.nama_tipe, tk.kapasitas_orang, tk.harga_per_malam, k.status_kamar
                FROM kamar k
                JOIN tipe_kamar tk ON k.id_tipe_kamar = tk.id_tipe_kamar
                WHERE k.status_kamar = 'tersedia'
                AND k.nomor_kamar NOT IN (
                    SELECT DISTINCT nomor_kamar 
                    FROM pemesanan 
                    WHERE tanggal_checkin <= '$today' 
                    AND tanggal_checkout > '$today'
                    AND status_pemesanan IN ('confirmed', 'checkedin')
                )
                ORDER BY k.nomor_kamar";
        
        $result = $conn->query($sql);
        $available_rooms = [];
        while ($row = $result->fetch_assoc()) {
            $available_rooms[] = $row;
        }
        
        sendResponse([
            'stats' => $stats,
            'available_rooms' => $available_rooms
        ]);
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// Rooms functions
function handleRooms() {
    global $conn, $method;
    
    switch ($method) {
        case 'GET':
            getRooms();
            break;
        case 'POST':
            createRoom();
            break;
        case 'PUT':
            updateRoom();
            break;
        case 'DELETE':
            deleteRoom();
            break;
        default:
            sendResponse(['error' => 'Method not allowed'], 405);
    }
}

function getRooms() {
    global $conn;
    
    try {
        $sql = "SELECT k.nomor_kamar, tk.nama_tipe, k.lantai, k.status_kamar, k.keterangan
                FROM kamar k
                JOIN tipe_kamar tk ON k.id_tipe_kamar = tk.id_tipe_kamar
                ORDER BY k.nomor_kamar";
        
        $result = $conn->query($sql);
        $rooms = [];
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
        
        sendResponse($rooms);
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

function createRoom() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validasi field wajib
        if (!validateRequired($data, ['nomor_kamar', 'id_tipe_kamar', 'lantai'])) {
            sendResponse(['success' => false, 'error' => 'Harap isi semua field wajib'], 400);
        }

        // Cek apakah tipe kamar valid
        $id_tipe_kamar = (int)$data['id_tipe_kamar'];
        $checkType = $conn->query("SELECT id_tipe_kamar FROM tipe_kamar WHERE id_tipe_kamar = $id_tipe_kamar");
        
        if ($checkType->num_rows === 0) {
            sendResponse(['success' => false, 'error' => 'Tipe kamar tidak valid'], 400);
        }

        // Proses insert
        $nomor_kamar = sanitize($data['nomor_kamar']);
        $lantai = (int)$data['lantai'];
        $status_kamar = sanitize($data['status_kamar'] ?? 'tersedia');
        $keterangan = sanitize($data['keterangan'] ?? '');

        $sql = "INSERT INTO kamar (nomor_kamar, id_tipe_kamar, lantai, status_kamar, keterangan) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('siiss', $nomor_kamar, $id_tipe_kamar, $lantai, $status_kamar, $keterangan);
        
        if ($stmt->execute()) {
            sendResponse(['success' => true, 'message' => 'Kamar berhasil ditambahkan']);
        } else {
            sendResponse(['success' => false, 'error' => 'Gagal menambahkan kamar: '.$conn->error], 500);
        }
        
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

function handleGetRoom() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $nomorKamar = $_GET['nomor_kamar'] ?? '';
    
    if (empty($nomorKamar)) {
        sendResponse(['success' => false, 'message' => 'Nomor kamar harus diisi'], 400);
    }
    
    try {
        $sql = "SELECT k.nomor_kamar, k.id_tipe_kamar, k.lantai, k.status_kamar, k.keterangan, 
                       tk.nama_tipe, tk.harga_per_malam
                FROM kamar k
                JOIN tipe_kamar tk ON k.id_tipe_kamar = tk.id_tipe_kamar
                WHERE k.nomor_kamar = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $nomorKamar);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(['success' => false, 'message' => 'Kamar tidak ditemukan'], 404);
        }
        
        $room = $result->fetch_assoc();
        sendResponse([
            'success' => true,
            'data' => $room,
            'message' => 'Data kamar berhasil dimuat'
        ]);
        
    } catch (Exception $e) {
        sendResponse([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ], 500);
    }
}

// Guests functions
function handleGuests() {
    global $conn, $method;
    
    switch ($method) {
        case 'GET':
            getGuests();
            break;
        case 'POST':
            createGuest();
            break;
        case 'PUT':
            updateGuest();
            break;
        case 'DELETE':
            deleteGuest();
            break;
        default:
            sendResponse(['error' => 'Method not allowed'], 405);
    }
}

function getGuests() {
    global $conn;
    
    try {
        $sql = "SELECT id_tamu, nama_lengkap, no_identitas, jenis_identitas, 
                       no_telepon, email, alamat, jenis_kelamin, 
                       DATE_FORMAT(tanggal_lahir, '%Y-%m-%d') as tanggal_lahir, 
                       kewarganegaraan
                FROM tamu 
                ORDER BY nama_lengkap";
        
        $result = $conn->query($sql);
        $guests = [];
        while ($row = $result->fetch_assoc()) {
            $guests[] = $row;
        }
        
        sendResponse($guests);
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

function createGuest() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!validateRequired($data, ['nama_lengkap', 'no_identitas', 'jenis_identitas'])) {
            sendResponse(['error' => 'Missing required fields'], 400);
        }
        
        $nama_lengkap = sanitize($data['nama_lengkap']);
        $no_identitas = sanitize($data['no_identitas']);
        $jenis_identitas = sanitize($data['jenis_identitas']);
        $no_telepon = sanitize($data['no_telepon'] ?? '');
        $email = sanitize($data['email'] ?? '');
        $alamat = sanitize($data['alamat'] ?? '');
        $jenis_kelamin = sanitize($data['jenis_kelamin'] ?? '');
        $tanggal_lahir = $data['tanggal_lahir'] ?? null;
        $kewarganegaraan = sanitize($data['kewarganegaraan'] ?? 'Indonesia');
        
        $sql = "INSERT INTO tamu (nama_lengkap, no_identitas, jenis_identitas, no_telepon, 
                                 email, alamat, jenis_kelamin, tanggal_lahir, kewarganegaraan) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssssss', $nama_lengkap, $no_identitas, $jenis_identitas, 
                         $no_telepon, $email, $alamat, $jenis_kelamin, $tanggal_lahir, $kewarganegaraan);
        
        if ($stmt->execute()) {
            sendResponse(['success' => true, 'message' => 'Guest created successfully', 'id' => $conn->insert_id]);
        } else {
            sendResponse(['error' => 'Failed to create guest'], 500);
        }
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// Reservations functions
function handleReservations() {
    global $conn, $method;
    
    switch ($method) {
        case 'GET':
            getReservations();
            break;
        case 'POST':
            createReservation();
            break;
        case 'PUT':
            updateReservation();
            break;
        case 'DELETE':
            deleteReservation();
            break;
        default:
            sendResponse(['error' => 'Method not allowed'], 405);
    }
}

function handleGetReservation() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $kode_pemesanan = $_GET['kode_pemesanan'] ?? '';
    
    if (empty($kode_pemesanan)) {
        sendResponse(['success' => false, 'message' => 'Kode pemesanan harus diisi'], 400);
    }
    
    try {
        $sql = "SELECT p.kode_pemesanan, t.nama_lengkap, p.nomor_kamar, tk.nama_tipe,
                       p.tanggal_checkin, p.tanggal_checkout, p.total_harga,
                       (SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran 
                        WHERE kode_pemesanan = p.kode_pemesanan 
                        AND status_pembayaran = 'berhasil') as total_paid
                FROM pemesanan p
                JOIN tamu t ON p.id_tamu = t.id_tamu
                LEFT JOIN kamar k ON p.nomor_kamar = k.nomor_kamar
                LEFT JOIN tipe_kamar tk ON k.id_tipe_kamar = tk.id_tipe_kamar
                WHERE p.kode_pemesanan = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $kode_pemesanan);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(['success' => false, 'message' => 'Reservasi tidak ditemukan'], 404);
        }
        
        $reservation = $result->fetch_assoc();
        sendResponse([
            'success' => true,
            'data' => $reservation,
            'message' => 'Data reservasi berhasil dimuat'
        ]);
        
    } catch (Exception $e) {
        sendResponse([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ], 500);
    }
}

function getReservations() {
    global $conn;
    
    try {
        $sql = "SELECT p.kode_pemesanan, t.nama_lengkap, p.nomor_kamar, tk.nama_tipe,
                       DATE_FORMAT(p.tanggal_checkin, '%Y-%m-%d') as tanggal_checkin,
                       DATE_FORMAT(p.tanggal_checkout, '%Y-%m-%d') as tanggal_checkout,
                       p.jumlah_malam, p.jumlah_tamu, p.status_pemesanan, p.total_harga,
                       p.cara_pemesanan, p.catatan_khusus
                FROM pemesanan p
                JOIN tamu t ON p.id_tamu = t.id_tamu
                LEFT JOIN kamar k ON p.nomor_kamar = k.nomor_kamar
                LEFT JOIN tipe_kamar tk ON k.id_tipe_kamar = tk.id_tipe_kamar
                ORDER BY p.tanggal_pemesanan DESC";
        
        $result = $conn->query($sql);
        $reservations = [];
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }
        
        sendResponse($reservations);
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}
function createReservation() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!validateRequired($data, ['id_tamu', 'nomor_kamar', 'tanggal_checkin', 'tanggal_checkout', 'jumlah_tamu', 'cara_pemesanan'])) {
            sendResponse(['error' => 'Missing required fields'], 400);
        }
        
        $kode_pemesanan = generateBookingCode();
        $id_tamu = (int)$data['id_tamu'];
        $nomor_kamar = sanitize($data['nomor_kamar']);
        $tanggal_checkin = $data['tanggal_checkin'];
        $tanggal_checkout = $data['tanggal_checkout'];
        $jumlah_malam = calculateNights($tanggal_checkin, $tanggal_checkout);
        $jumlah_tamu = (int)$data['jumlah_tamu'];
        $cara_pemesanan = sanitize($data['cara_pemesanan']);
        $catatan_khusus = sanitize($data['catatan_khusus'] ?? '');
        $tanggal_pemesanan = date('Y-m-d H:i:s');
        
        // Get room price
        $price_sql = "SELECT tk.harga_per_malam 
                      FROM kamar k 
                      JOIN tipe_kamar tk ON k.id_tipe_kamar = tk.id_tipe_kamar 
                      WHERE k.nomor_kamar = ?";
        $price_stmt = $conn->prepare($price_sql);
        $price_stmt->bind_param('s', $nomor_kamar);
        $price_stmt->execute();
        $price_result = $price_stmt->get_result();
        $price_row = $price_result->fetch_assoc();
        $total_harga = $price_row['harga_per_malam'] * $jumlah_malam;
        
        $sql = "INSERT INTO pemesanan (kode_pemesanan, id_tamu, nomor_kamar, tanggal_checkin, 
                                     tanggal_checkout, jumlah_malam, jumlah_tamu, status_pemesanan, 
                                     total_harga, cara_pemesanan, catatan_khusus, tanggal_pemesanan) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sisssiiisss', $kode_pemesanan, $id_tamu, $nomor_kamar, $tanggal_checkin, 
                         $tanggal_checkout, $jumlah_malam, $jumlah_tamu, $total_harga, 
                         $cara_pemesanan, $catatan_khusus, $tanggal_pemesanan);
        
        if ($stmt->execute()) {
            sendResponse(['success' => true, 'message' => 'Reservation created successfully', 'booking_code' => $kode_pemesanan]);
        } else {
            sendResponse(['error' => 'Failed to create reservation'], 500);
        }
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

function updateReservation() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!validateRequired($data, ['kode_pemesanan'])) {
            sendResponse(['error' => 'Missing booking code'], 400);
        }
        
        $kode_pemesanan = sanitize($data['kode_pemesanan']);
        $fields = [];
        $values = [];
        $types = '';
        
        if (isset($data['status_pemesanan'])) {
            // Validasi status yang diperbolehkan
            $allowedStatuses = ['pending', 'confirmed', 'checkedin', 'checkedout', 'cancelled'];
            if (!in_array($data['status_pemesanan'], $allowedStatuses)) {
                sendResponse(['error' => 'Invalid reservation status'], 400);
            }
            
            $fields[] = 'status_pemesanan = ?';
            $values[] = sanitize($data['status_pemesanan']);
            $types .= 's';
        }
        
        // Tambahkan field lain yang bisa diupdate jika perlu
        if (isset($data['catatan_khusus'])) {
            $fields[] = 'catatan_khusus = ?';
            $values[] = sanitize($data['catatan_khusus']);
            $types .= 's';
        }
        
        if (empty($fields)) {
            sendResponse(['error' => 'No fields to update'], 400);
        }
        
        $values[] = $kode_pemesanan;
        $types .= 's';
        
        $sql = "UPDATE pemesanan SET " . implode(', ', $fields) . " WHERE kode_pemesanan = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            // Jika mengupdate status ke confirmed, cek apakah kamar masih tersedia
            if (isset($data['status_pemesanan']) && $data['status_pemesanan'] === 'confirmed') {
                verifyRoomAvailability($kode_pemesanan);
            }
            
            sendResponse(['success' => true, 'message' => 'Reservation updated successfully']);
        } else {
            throw new Exception('Failed to update reservation: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

function verifyRoomAvailability($kode_pemesanan) {
    global $conn;
    
    $sql = "SELECT p.nomor_kamar, p.tanggal_checkin, p.tanggal_checkout
            FROM pemesanan p
            WHERE p.kode_pemesanan = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $kode_pemesanan);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    
    // Cek ketersediaan kamar
    $checkSql = "SELECT COUNT(*) as conflict 
                 FROM pemesanan 
                 WHERE nomor_kamar = ? 
                 AND status_pemesanan IN ('confirmed', 'checkedin')
                 AND (
                     (tanggal_checkin <= ? AND tanggal_checkout > ?)
                     OR (tanggal_checkin < ? AND tanggal_checkout >= ?)
                     OR (tanggal_checkin >= ? AND tanggal_checkout <= ?)
                 )
                 AND kode_pemesanan != ?";
    
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('ssssssss', 
        $reservation['nomor_kamar'],
        $reservation['tanggal_checkout'],
        $reservation['tanggal_checkin'],
        $reservation['tanggal_checkout'],
        $reservation['tanggal_checkin'],
        $reservation['tanggal_checkin'],
        $reservation['tanggal_checkout'],
        $kode_pemesanan
    );
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $conflict = $checkResult->fetch_assoc();
    
    if ($conflict['conflict'] > 0) {
        // Jika ada konflik, kembalikan status ke pending
        $updateSql = "UPDATE pemesanan SET status_pemesanan = 'pending' WHERE kode_pemesanan = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param('s', $kode_pemesanan);
        $updateStmt->execute();
        
        throw new Exception('Kamar tidak tersedia untuk tanggal yang diminta. Reservasi tetap dalam status pending.');
    }
}

function deleteReservation() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!validateRequired($data, ['kode_pemesanan'])) {
            sendResponse(['error' => 'Missing booking code'], 400);
        }
        
        $kode_pemesanan = sanitize($data['kode_pemesanan']);
        
        // Mulai transaction
        $conn->begin_transaction();
        
        try {
            // 1. Hapus data terkait di check_in_out terlebih dahulu
            if (tableExists($conn, 'check_in_out')) {
                $deleteCheckIn = "DELETE FROM check_in_out WHERE kode_pemesanan = ?";
                $stmtCheckIn = $conn->prepare($deleteCheckIn);
                $stmtCheckIn->bind_param('s', $kode_pemesanan);
                $stmtCheckIn->execute();
            }
            
            // 2. Hapus data terkait di pembayaran
            if (tableExists($conn, 'pembayaran')) {
                $deletePayment = "DELETE FROM pembayaran WHERE kode_pemesanan = ?";
                $stmtPayment = $conn->prepare($deletePayment);
                $stmtPayment->bind_param('s', $kode_pemesanan);
                $stmtPayment->execute();
            }
            
            // 3. Hapus data pemesanan
            $deleteReservation = "DELETE FROM pemesanan WHERE kode_pemesanan = ?";
            $stmtReservation = $conn->prepare($deleteReservation);
            $stmtReservation->bind_param('s', $kode_pemesanan);
            $stmtReservation->execute();
            
            $conn->commit();
            
            sendResponse(['success' => true, 'message' => 'Reservation deleted successfully']);
            
        } catch (Exception $e) {
            $conn->rollback();
            sendResponse(['error' => 'Failed to delete reservation: ' . $e->getMessage()], 500);
        }
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// Update Room Function
function updateRoom() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!validateRequired($data, ['nomor_kamar_lama', 'nomor_kamar', 'id_tipe_kamar', 'lantai'])) {
            sendResponse(['success' => false, 'error' => 'Missing required fields'], 400);
        }
        
        // Cek apakah kamar baru sudah ada (jika nomor kamar berubah)
        if ($data['nomor_kamar_lama'] !== $data['nomor_kamar']) {
            $checkSql = "SELECT nomor_kamar FROM kamar WHERE nomor_kamar = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('s', $data['nomor_kamar']);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows > 0) {
                sendResponse(['success' => false, 'error' => 'Nomor kamar sudah digunakan'], 400);
            }
        }
        
        // Cek tipe kamar valid
        $checkType = $conn->query("SELECT id_tipe_kamar FROM tipe_kamar WHERE id_tipe_kamar = ".$data['id_tipe_kamar']);
        if ($checkType->num_rows === 0) {
            sendResponse(['success' => false, 'error' => 'Tipe kamar tidak valid'], 400);
        }
        
        // Update data
        $sql = "UPDATE kamar SET 
                nomor_kamar = ?,
                id_tipe_kamar = ?,
                lantai = ?,
                status_kamar = ?,
                keterangan = ?
                WHERE nomor_kamar = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('siisss', 
            $data['nomor_kamar'],
            $data['id_tipe_kamar'],
            $data['lantai'],
            $data['status_kamar'],
            $data['keterangan'] ?? '',
            $data['nomor_kamar_lama']
        );
        
        if ($stmt->execute()) {
            sendResponse(['success' => true, 'message' => 'Kamar berhasil diperbarui']);
        } else {
            sendResponse(['success' => false, 'error' => 'Gagal memperbarui kamar: '.$conn->error], 500);
        }
        
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

function deleteRoom() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!validateRequired($data, ['nomor_kamar'])) {
            sendResponse(['error' => 'Missing room number'], 400);
        }
        
        $nomor_kamar = sanitize($data['nomor_kamar']);
        
        // Mulai transaction
        $conn->begin_transaction();
        
        try {
            // 1. Cek apakah kamar memiliki reservasi aktif
            $checkSql = "SELECT COUNT(*) as total FROM pemesanan 
                        WHERE nomor_kamar = ? 
                        AND status_pemesanan IN ('confirmed', 'checkedin')";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('s', $nomor_kamar);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $row = $checkResult->fetch_assoc();
            
            if ($row['total'] > 0) {
                throw new Exception('Kamar memiliki reservasi aktif dan tidak dapat dihapus');
            }
            
            // 2. Hapus kamar
            $deleteSql = "DELETE FROM kamar WHERE nomor_kamar = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param('s', $nomor_kamar);
            
            if (!$deleteStmt->execute()) {
                throw new Exception('Gagal menghapus kamar');
            }
            
            $conn->commit();
            
            sendResponse(['success' => true, 'message' => 'Kamar berhasil dihapus']);
            
        } catch (Exception $e) {
            $conn->rollback();
            sendResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
        
    } catch (Exception $e) {
        sendResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// Update Guest Function
function updateGuest() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!validateRequired($data, ['id_tamu'])) {
            sendResponse(['error' => 'Missing guest ID'], 400);
        }
        
        $id_tamu = (int)$data['id_tamu'];
        $fields = [];
        $values = [];
        $types = '';
        
        // Dynamic update fields
        if (isset($data['nama_lengkap'])) {
            $fields[] = 'nama_lengkap = ?';
            $values[] = sanitize($data['nama_lengkap']);
            $types .= 's';
        }
        
        if (isset($data['no_identitas'])) {
            $fields[] = 'no_identitas = ?';
            $values[] = sanitize($data['no_identitas']);
            $types .= 's';
        }
        
        if (isset($data['jenis_identitas'])) {
            $fields[] = 'jenis_identitas = ?';
            $values[] = sanitize($data['jenis_identitas']);
            $types .= 's';
        }
        
        if (isset($data['no_telepon'])) {
            $fields[] = 'no_telepon = ?';
            $values[] = sanitize($data['no_telepon']);
            $types .= 's';
        }
        
        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $values[] = sanitize($data['email']);
            $types .= 's';
        }
        
        if (isset($data['alamat'])) {
            $fields[] = 'alamat = ?';
            $values[] = sanitize($data['alamat']);
            $types .= 's';
        }
        
        if (isset($data['jenis_kelamin'])) {
            $fields[] = 'jenis_kelamin = ?';
            $values[] = sanitize($data['jenis_kelamin']);
            $types .= 's';
        }
        
        if (isset($data['tanggal_lahir'])) {
            $fields[] = 'tanggal_lahir = ?';
            $values[] = $data['tanggal_lahir'];
            $types .= 's';
        }
        
        if (isset($data['kewarganegaraan'])) {
            $fields[] = 'kewarganegaraan = ?';
            $values[] = sanitize($data['kewarganegaraan']);
            $types .= 's';
        }
        
        if (empty($fields)) {
            sendResponse(['error' => 'No fields to update'], 400);
        }
        
        $values[] = $id_tamu;
        $types .= 'i';
        
        $sql = "UPDATE tamu SET " . implode(', ', $fields) . " WHERE id_tamu = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            sendResponse(['success' => true, 'message' => 'Guest updated successfully']);
        } else {
            sendResponse(['error' => 'Failed to update guest'], 500);
        }
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

function deleteGuest() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!validateRequired($data, ['id_tamu'])) {
            sendResponse(['error' => 'Missing guest ID'], 400);
        }
        
        $id_tamu = (int)$data['id_tamu'];
        
        // Mulai transaction
        $conn->begin_transaction();
        
        try {
            // Cek apakah tamu memiliki reservasi
            $checkSql = "SELECT COUNT(*) as total FROM pemesanan WHERE id_tamu = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('i', $id_tamu);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['total'] > 0) {
                throw new Exception('Tamu memiliki reservasi yang aktif dan tidak dapat dihapus');
            }
            
            // Hapus tamu
            $deleteSql = "DELETE FROM tamu WHERE id_tamu = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param('i', $id_tamu);
            
            if (!$deleteStmt->execute()) {
                throw new Exception('Gagal menghapus data tamu');
            }
            
            $conn->commit();
            
            sendResponse(['success' => true, 'message' => 'Tamu berhasil dihapus']);
            
        } catch (Exception $e) {
            $conn->rollback();
            sendResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
        
    } catch (Exception $e) {
        sendResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// Payments functions
function handlePayments() {
    global $conn, $method;
    
    switch ($method) {
        case 'GET':
            getPayments();
            break;
        case 'POST':
            createPayment();
            break;
        case 'PUT':
            updatePayment();
            break;
        default:
            sendResponse(['error' => 'Method not allowed'], 405);
    }
}

function handleGetPayment() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $id_pembayaran = $_GET['id_pembayaran'] ?? '';
    
    if (empty($id_pembayaran)) {
        sendResponse(['success' => false, 'message' => 'ID pembayaran harus diisi'], 400);
    }
    
    try {
        $sql = "SELECT pb.*, p.status_pemesanan, t.nama_lengkap, p.nomor_kamar,
                       p.tanggal_checkin, p.tanggal_checkout, p.total_harga
                FROM pembayaran pb
                JOIN pemesanan p ON pb.kode_pemesanan = p.kode_pemesanan
                JOIN tamu t ON p.id_tamu = t.id_tamu
                WHERE pb.id_pembayaran = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id_pembayaran);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(['success' => false, 'message' => 'Pembayaran tidak ditemukan'], 404);
        }
        
        $payment = $result->fetch_assoc();
        sendResponse([
            'success' => true,
            'data' => $payment,
            'message' => 'Data pembayaran berhasil dimuat'
        ]);
        
    } catch (Exception $e) {
        sendResponse([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ], 500);
    }
}

function getPayments() {
    global $conn;
    
    try {
        $sql = "SELECT pb.*, p.status_pemesanan, 
                    p.tanggal_checkin_actual, p.tanggal_checkout_actual,
                    t.nama_lengkap, p.nomor_kamar
                FROM pembayaran pb
                JOIN pemesanan p ON pb.kode_pemesanan = p.kode_pemesanan
                JOIN tamu t ON p.id_tamu = t.id_tamu
                ORDER BY pb.tanggal_pembayaran DESC";
        
        $result = $conn->query($sql);
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        sendResponse($payments);
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

function createPayment() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!validateRequired($data, ['kode_pemesanan', 'jumlah_bayar', 'metode_pembayaran'])) {
            sendResponse(['error' => 'Missing required fields'], 400);
        }
        
        if (empty($data['nomor_referensi'])) {
        // Jika kosong, generate otomatis
        $nomor_referensi = 'PYMT-' . date('YmdHis') . rand(100, 999);
        } else {
            $nomor_referensi = sanitize($data['nomor_referensi']);
        }

        $kode_pemesanan = sanitize($data['kode_pemesanan']);
        $jumlah_bayar = (float)$data['jumlah_bayar'];
        $metode_pembayaran = sanitize($data['metode_pembayaran']);
        $status_pembayaran = sanitize($data['status_pembayaran'] ?? 'pending');
        $keterangan = sanitize($data['keterangan'] ?? '');
        $tanggal_pembayaran = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO pembayaran (kode_pemesanan, jumlah_bayar, metode_pembayaran, 
                                       status_pembayaran, keterangan, tanggal_pembayaran) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sdssss', $kode_pemesanan, $jumlah_bayar, $metode_pembayaran, 
                         $status_pembayaran, $keterangan, $tanggal_pembayaran);
        
        if ($stmt->execute()) {
            sendResponse(['success' => true, 'message' => 'Payment created successfully']);
        } else {
            sendResponse(['error' => 'Failed to create payment'], 500);
        }
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

function updatePayment() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!validateRequired($data, ['id_pembayaran'])) {
            sendResponse(['error' => 'Missing payment ID'], 400);
        }
        
        $id_pembayaran = (int)$data['id_pembayaran'];
        $status_pembayaran = sanitize($data['status_pembayaran']);
        $keterangan = sanitize($data['keterangan'] ?? '');
        
        $sql = "UPDATE pembayaran SET status_pembayaran = ?, keterangan = ? WHERE id_pembayaran = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $status_pembayaran, $keterangan, $id_pembayaran);
        
        if ($stmt->execute()) {
            sendResponse(['success' => true, 'message' => 'Payment updated successfully']);
        } else {
            sendResponse(['error' => 'Failed to update payment'], 500);
        }
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// Check-in functions
function handleCheckin() {
    global $conn, $method;
    
    if ($method !== 'POST') {
        sendResponse(['error' => 'Method not allowed'], 405);
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!validateRequired($data, ['kode_pemesanan'])) {
            sendResponse(['error' => 'Missing booking code'], 400);
        }
        
        $kode_pemesanan = sanitize($data['kode_pemesanan']);
        $tanggal_checkin_actual = date('Y-m-d H:i:s');
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // 1. Check reservation status and get room number
            $checkSql = "SELECT status_pemesanan, nomor_kamar FROM pemesanan WHERE kode_pemesanan = ?";
            $checkStmt = $conn->prepare($checkSql);
            if (!$checkStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $checkStmt->bind_param('s', $kode_pemesanan);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                throw new Exception('Reservation not found');
            }
            
            $reservation = $checkResult->fetch_assoc();
            
            // Allow both 'confirmed' and 'pending' statuses to check-in
            if (!in_array($reservation['status_pemesanan'], ['confirmed', 'pending'])) {
                throw new Exception('Only reservations with confirmed or pending status can check-in');
            }
            
            // 2. Update reservation status
            $updateSql = "UPDATE pemesanan 
                         SET status_pemesanan = 'checkedin', 
                             tanggal_checkin_actual = ? 
                         WHERE kode_pemesanan = ?";
            $updateStmt = $conn->prepare($updateSql);
            if (!$updateStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $updateStmt->bind_param('ss', $tanggal_checkin_actual, $kode_pemesanan);
            
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to update reservation status: ' . $updateStmt->error);
            }
            
            // 3. Update room status
            $roomSql = "UPDATE kamar 
                       SET status_kamar = 'terisi' 
                       WHERE nomor_kamar = ?";
            $roomStmt = $conn->prepare($roomSql);
            if (!$roomStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $roomStmt->bind_param('s', $reservation['nomor_kamar']);
            
            if (!$roomStmt->execute()) {
                throw new Exception('Failed to update room status: ' . $roomStmt->error);
            }
            
            // 4. Record check-in (if using check_in_out table)
            if (tableExists($conn, 'check_in_out')) {
                $checkinSql = "INSERT INTO check_in_out 
                              (kode_pemesanan, nomor_kamar, tanggal_checkin, status)
                              VALUES (?, ?, ?, 'checkedin')";
                $checkinStmt = $conn->prepare($checkinSql);
                if (!$checkinStmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $checkinStmt->bind_param('sss', $kode_pemesanan, $reservation['nomor_kamar'], $tanggal_checkin_actual);
                
                if (!$checkinStmt->execute()) {
                    throw new Exception('Failed to record check-in: ' . $checkinStmt->error);
                }
            }
            
            $conn->commit();
            
            sendResponse([
                'success' => true, 
                'message' => 'Check-in successful',
                'room_number' => $reservation['nomor_kamar'],
                'checkin_time' => $tanggal_checkin_actual
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            sendResponse(['success' => false, 'error' => $e->getMessage()], 400);
        }
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }

    $checkPaymentSql = "SELECT COUNT(*) as count FROM pembayaran WHERE kode_pemesanan = ?";
    $checkStmt = $conn->prepare($checkPaymentSql);
    $checkStmt->bind_param('s', $kode_pemesanan);
    $checkStmt->execute();
    $paymentExists = $checkStmt->get_result()->fetch_assoc()['count'] > 0;
    
    if (!$paymentExists) {
        $insertPaymentSql = "INSERT INTO pembayaran 
                            (kode_pemesanan, jumlah_bayar, metode_pembayaran, 
                             status_pembayaran, tanggal_pembayaran)
                            SELECT 
                                p.kode_pemesanan, 
                                p.total_harga * 0.5,  // DP 50%
                                'tunai',
                                'pending',
                                NOW()
                            FROM pemesanan p
                            WHERE p.kode_pemesanan = ?";
        $insertStmt = $conn->prepare($insertPaymentSql);
        $insertStmt->bind_param('s', $kode_pemesanan);
        $insertStmt->execute();
    }
}

function handleTodaysReservations() {
    global $conn;
    
    try {
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $sql = "SELECT p.kode_pemesanan, t.nama_lengkap, p.nomor_kamar, 
                       DATE_FORMAT(p.tanggal_checkin, '%Y-%m-%d') as tanggal_checkin,
                       DATE_FORMAT(p.tanggal_checkout, '%Y-%m-%d') as tanggal_checkout,
                       p.status_pemesanan, p.total_harga
                FROM pemesanan p
                JOIN tamu t ON p.id_tamu = t.id_tamu
                WHERE p.tanggal_checkin = ?
                AND p.status_pemesanan IN ('confirmed', 'checkedin')
                ORDER BY p.tanggal_checkin";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reservations = [];
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }
        
        sendResponse($reservations);
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// Helper function to check if table exists
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result->num_rows > 0;
}

// Check-out functions
function handleCheckout() {
    global $conn, $method;
    
    if ($method !== 'POST') {
        sendResponse(['error' => 'Method not allowed'], 405);
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!validateRequired($data, ['kode_pemesanan'])) {
            sendResponse(['error' => 'Missing booking code'], 400);
        }
        
        $kode_pemesanan = sanitize($data['kode_pemesanan']);
        $tanggal_checkout_actual = date('Y-m-d H:i:s');
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // 1. Check reservation status and get room number
            $checkSql = "SELECT status_pemesanan, nomor_kamar FROM pemesanan WHERE kode_pemesanan = ?";
            $checkStmt = $conn->prepare($checkSql);
            if (!$checkStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $checkStmt->bind_param('s', $kode_pemesanan);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                throw new Exception('Reservation not found');
            }
            
            $reservation = $checkResult->fetch_assoc();
            
            if ($reservation['status_pemesanan'] !== 'checkedin') {
                throw new Exception('Only checked-in reservations can checkout');
            }
            
            // 2. Update reservation status
            $updateSql = "UPDATE pemesanan 
                         SET status_pemesanan = 'checkedout', 
                             tanggal_checkout_actual = ? 
                         WHERE kode_pemesanan = ?";
            $updateStmt = $conn->prepare($updateSql);
            if (!$updateStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $updateStmt->bind_param('ss', $tanggal_checkout_actual, $kode_pemesanan);
            
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to update reservation status: ' . $updateStmt->error);
            }
            
            // 3. Update room status
            $roomSql = "UPDATE kamar SET status_kamar = 'tersedia' WHERE nomor_kamar = ?";
            $roomStmt = $conn->prepare($roomSql);
            if (!$roomStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $roomStmt->bind_param('s', $reservation['nomor_kamar']);
            
            if (!$roomStmt->execute()) {
                throw new Exception('Failed to update room status: ' . $roomStmt->error);
            }
            
            // 4. Record checkout (if using check_in_out table)
            if (tableExists($conn, 'check_in_out')) {
                $checkoutSql = "UPDATE check_in_out 
                               SET tanggal_checkout = ?, 
                                   status = 'checkedout'
                               WHERE kode_pemesanan = ? 
                               AND status = 'checkedin'";
                $checkoutStmt = $conn->prepare($checkoutSql);
                if (!$checkoutStmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $checkoutStmt->bind_param('ss', $tanggal_checkout_actual, $kode_pemesanan);
                
                if (!$checkoutStmt->execute()) {
                    throw new Exception('Failed to record checkout: ' . $checkoutStmt->error);
                }
            }
            
            $conn->commit();
            
            sendResponse([
                'success' => true, 
                'message' => 'Check-out successful',
                'room_number' => $reservation['nomor_kamar'],
                'checkout_time' => $tanggal_checkout_actual
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            sendResponse(['success' => false, 'error' => $e->getMessage()], 400);
        }
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// Available rooms functions
function handleAvailableRooms() {
    global $conn, $method;
    
    if ($method !== 'GET') {
        sendResponse(['error' => 'Method not allowed'], 405);
    }
    
    try {
        $checkin = $_GET['checkin'] ?? date('Y-m-d');
        $checkout = $_GET['checkout'] ?? date('Y-m-d', strtotime('+1 day'));
        
        $sql = "SELECT k.nomor_kamar, tk.nama_tipe, tk.kapasitas_orang, tk.harga_per_malam, 
                       k.status_kamar, k.lantai, k.keterangan
                FROM kamar k
                JOIN tipe_kamar tk ON k.id_tipe_kamar = tk.id_tipe_kamar
                WHERE k.status_kamar = 'tersedia'
                AND k.nomor_kamar NOT IN (
                    SELECT DISTINCT nomor_kamar 
                    FROM pemesanan 
                    WHERE ((tanggal_checkin BETWEEN ? AND ?) 
                           OR (tanggal_checkout BETWEEN ? AND ?)
                           OR (tanggal_checkin <= ? AND tanggal_checkout >= ?))
                    AND status_pemesanan IN ('confirmed', 'checkedin')
                )
                ORDER BY tk.harga_per_malam ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssss', $checkin, $checkout, $checkin, $checkout, $checkin, $checkout);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $available_rooms = [];
        while ($row = $result->fetch_assoc()) {
            $available_rooms[] = $row;
        }
        
        sendResponse($available_rooms);
        
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}
?>