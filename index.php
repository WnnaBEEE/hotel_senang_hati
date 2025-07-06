<?php
// index.php - File utama sistem manajemen hotel
require_once 'php_config.php';

// Set header untuk CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Senang Hati - Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p>Memuat data...</p>
    </div>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">Hotel Senang Hati</div>
                <div class="nav-tabs">
                    <button class="nav-tab" onclick="showTab('dashboard')">Dashboard</button>
                    <button class="nav-tab" onclick="showTab('rooms')">Kamar</button>
                    <button class="nav-tab" onclick="showTab('guests')">Tamu</button>
                    <button class="nav-tab" onclick="showTab('reservations')">Reservasi</button>
                    <button class="nav-tab" onclick="showTab('checkin')">Check-in/out</button>
                    <button class="nav-tab" onclick="showTab('payments')">Pembayaran</button>
                    <button class="nav-tab" onclick="toggleSidebar()">
                        <i class="fas fa-user"></i> <?= $_SESSION['nama_pegawai'] ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <div id="dashboard" class="tab-content">
                <h2>Dashboard Hotel Senang Hati</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number" id="totalRooms">-</div>
                        <div class="stat-label">Total Kamar</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="availableRooms">-</div>
                        <div class="stat-label">Kamar Tersedia</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="totalGuests">-</div>
                        <div class="stat-label">Total Tamu</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="activeReservations">-</div>
                        <div class="stat-label">Reservasi Aktif</div>
                    </div>
                </div>
                <div class="card">
                    <h3>Kamar Tersedia Hari Ini</h3>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nomor Kamar</th>
                                    <th>Tipe</th>
                                    <th>Kapasitas</th>
                                    <th>Harga/Malam</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="availableRoomsTable">
                                <!-- Data akan dimuat dari database -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="rooms" class="tab-content">
                <div class="card">
                    <h3>Manajemen Kamar</h3>
                    <button class="btn btn-primary" onclick="openModal('roomModal')">Tambah Kamar</button>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nomor Kamar</th>
                                    <th>Tipe</th>
                                    <th>Lantai</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="roomsTable">
                                <!-- Data akan dimuat dari database -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div id="guests" class="tab-content">
                <div class="card">
                    <h3>Manajemen Tamu</h3>
                    <button class="btn btn-primary" onclick="openModal('guestModal')">Tambah Tamu</button>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>No. Identitas</th>
                                    <th>Telepon</th>
                                    <th>Email</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="guestsTable">
                                <!-- Data akan dimuat dari database -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="reservations" class="tab-content">
                <div class="card">
                    <h3>Manajemen Reservasi</h3>
                    <button class="btn btn-primary" onclick="openModal('reservationModal')">Buat Reservasi</button>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Tamu</th>
                                    <th>Kamar</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="reservationsTable">
                                <!-- Data akan dimuat dari database -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="checkin" class="tab-content">
                <div class="card">
                    <h3>Check-in / Check-out</h3>
                    <div class="form-row">
                        <div>
                            <label>Kode Pemesanan:</label>
                            <input type="text" id="checkinCode" placeholder="Masukkan kode pemesanan">
                        </div>
                        <div>
                            <button class="btn btn-primary" onclick="processCheckin()">Proses Check-in</button>
                            <button class="btn btn-secondary" onclick="processCheckout()">Proses Check-out</button>
                        </div>
                    </div>
                    <div class="card" style="margin-top: 20px;">
                        <h4>Reservasi Hari Ini</h4>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Tamu</th>
                                        <th>Kamar</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="todaysReservations">
                                    <!-- Data akan dimuat dari database -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div id="payments" class="tab-content">
                <div class="card">
                    <h3>Manajemen Pembayaran</h3>
                    <button class="btn btn-primary" onclick="openModal('paymentModal')">Tambah Pembayaran</button>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kode Pemesanan</th>
                                    <th>Jumlah</th>
                                    <th>Metode</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="paymentsTable">
                                <!-- Data akan dimuat dari database -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div id="roomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Tambah Kamar</div>
                <span class="close" onclick="closeModal('roomModal')">&times;</span>
            </div>
            <form id="roomForm">
                <div class="form-group">
                    <label>Nomor Kamar:</label>
                    <input type="text" name="nomor_kamar" required>
                </div>
                <div class="form-group">
                    <label>Tipe Kamar:</label>
                    <select name="id_tipe_kamar" required>
                        <option value="">Pilih Tipe</option>
                        <option value="1">Standard Single</option>
                        <option value="2">Standard Double</option>
                        <option value="3">Deluxe Twin</option>
                        <option value="4">Suite Family</option>
                        <option value="5">Executive Suite</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lantai:</label>
                    <input type="number" name="lantai" required>
                </div>
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status_kamar">
                        <option value="tersedia">Tersedia</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>

    <div id="guestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Tambah Tamu</div>
                <span class="close" onclick="closeModal('guestModal')">&times;</span>
            </div>
            <form id="guestForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Lengkap:</label>
                        <input type="text" name="nama_lengkap" required>
                    </div>
                    <div class="form-group">
                        <label>No. Identitas:</label>
                        <input type="text" name="no_identitas" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Jenis Identitas:</label>
                        <select name="jenis_identitas" required>
                            <option value="">Pilih Jenis</option>
                            <option value="KTP">KTP</option>
                            <option value="SIM">SIM</option>
                            <option value="Passport">Passport</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jenis Kelamin:</label>
                        <select name="jenis_kelamin">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>No. Telepon:</label>
                        <input type="tel" name="no_telepon">
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email">
                    </div>
                </div>
                <div class="form-group">
                    <label>Alamat:</label>
                    <textarea name="alamat" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>

    <div id="reservationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Buat Reservasi</div>
                <span class="close" onclick="closeModal('reservationModal')">&times;</span>
            </div>
            <form id="reservationForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tamu:</label>
                        <select name="id_tamu" required>
                            <option value="">Pilih Tamu</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kamar:</label>
                        <select name="nomor_kamar" required>
                            <option value="">Pilih Kamar</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Check-in:</label>
                        <input type="date" name="tanggal_checkin" required>
                    </div>
                    <div class="form-group">
                        <label>Check-out:</label>
                        <input type="date" name="tanggal_checkout" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Jumlah Tamu:</label>
                        <input type="number" name="jumlah_tamu" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Cara Pemesanan:</label>
                        <select name="cara_pemesanan" required>
                            <option value="langsung">Langsung</option>
                            <option value="telepon">Telepon</option>
                            <option value="email">Email</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Catatan Khusus:</label>
                    <textarea name="catatan_khusus" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Buat Reservasi</button>
            </form>
        </div>
    </div>

    <div id="paymentModal" class="modal">
        <div class="modal-content" style="width: 80%; max-width: 800px;">
            <div class="modal-header">
                <div class="modal-title">Manajemen Pembayaran</div>
                <span class="close" onclick="closeModal('paymentModal')">&times;</span>
            </div>
            <form id="paymentForm">
                <input type="hidden" name="id_pembayaran" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Kode Pemesanan:</label>
                        <select name="kode_pemesanan" required onchange="loadReservationPayments(this.value)">
                            <option value="">Pilih Kode Pemesanan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jumlah Bayar:</label>
                        <input type="number" name="jumlah_bayar" step="0.01" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Metode Pembayaran:</label>
                        <select name="metode_pembayaran" required>
                            <option value="tunai">Tunai</option>
                            <option value="kartu_kredit">Kartu Kredit</option>
                            <option value="kartu_debit">Kartu Debit</option>
                            <option value="transfer">Transfer</option>
                            <option value="voucher">Voucher</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status Pembayaran:</label>
                        <select name="status_pembayaran" required>
                            <option value="pending">Pending</option>
                            <option value="berhasil">Berhasil</option>
                            <option value="gagal">Gagal</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Keterangan:</label>
                    <textarea name="keterangan" rows="2"></textarea>
                </div>
                
                <div id="paymentInfoContainer" class="payment-info-container">
                    <!-- Informasi reservasi akan ditampilkan di sini -->
                </div>
                
                <div id="existingPaymentsContainer" style="margin-top: 20px; display: none;">
                    <h4>Pembayaran Sebelumnya</h4>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jumlah</th>
                                    <th>Metode</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="existingPaymentsTable">
                                <!-- Data pembayaran akan dimuat di sini -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Pembayaran</button>
                    <button type="button" class="btn btn-secondary" onclick="resetPaymentForm()">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p>Memuat data...</p>
    </div>

    <script>
        // Global variables
        let currentTab = 'dashboard';

        // Initialize app with DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            // Set default tab
            showTab('dashboard', true);
            setupEventListeners();
            document.getElementById('roomForm').addEventListener('submit', handleRoomSubmit);
        });

        // Logout function
        async function logout() {
            try {
                const response = await fetch('logout.php');
                if (response.ok) {
                    window.location.href = 'login.php';
                }
            } catch (error) {
                console.error('Logout error:', error);
            }
        }

        // Function to call API
        async function callApi(endpoint, method = 'GET', data = null) {
            try {
                let url = `hotel_api_complete.php?action=${endpoint}`;
                const options = {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                    }
                };
                
                if (data) {
                    if (method === 'GET') {
                        // For GET, add parameters to URL
                        const params = new URLSearchParams(data);
                        url += `&${params.toString()}`;
                    } else {
                        // For other methods, send as body
                        options.body = JSON.stringify(data);
                    }
                }
                
                const response = await fetch(url, options);
                
                // First check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Invalid response: ${text}`);
                }
                
                const responseData = await response.json();
                
                if (!response.ok) {
                    throw new Error(responseData.error || `HTTP error! status: ${response.status}`);
                }
                
                return responseData;
            } catch (error) {
                console.error('API Error:', error);
                showAlert(`API Error: ${error.message}`, 'error');
                throw error; // Re-throw so caller can handle if needed
            }
        }


        function setupEventListeners() {
            // Form submissions
            document.getElementById('roomForm')?.addEventListener('submit', handleRoomSubmit);
            document.getElementById('guestForm')?.addEventListener('submit', handleGuestSubmit);
            document.getElementById('reservationForm')?.addEventListener('submit', handleReservationSubmit);
            document.getElementById('paymentForm')?.addEventListener('submit', handlePaymentSubmit);

            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', function(event) {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar && !sidebar.contains(event.target) && !event.target.classList.contains('nav-tab')) {
                    sidebar.style.display = 'none';
                }
            });
                

            document.getElementById('paymentForm').addEventListener('input', function(e) {
                if (e.target.name === 'kode_pemesanan') {
                    loadReservationForPayment(e.target.value);
                }
            });
        }

        // Modified showTab function
        function showTab(tabName, isInitialLoad = false) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Remove active class from all nav tabs
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Add active class to selected tab
            if (!isInitialLoad) {
                event.target.classList.add('active');
            } else {
                document.querySelector(`.nav-tab[onclick*="${tabName}"]`).classList.add('active');
            }
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            currentTab = tabName;

            // Load data for specific tabs
            switch(tabName) {
                case 'dashboard':
                    loadDashboardData();
                    break;
                case 'rooms':
                    loadRoomsData();
                    break;
                case 'guests':
                    loadGuestsData();
                    break;
                case 'reservations':
                    loadReservationsData();
                    break;
                case 'payments':
                    loadPaymentsData();
                    break;
                case 'checkin':
                    loadTodaysReservations();
                    break;
            }
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            
            if (modalId === 'paymentModal') {
                loadReservationsForPayment();
            }

            // Load dropdown data for specific modals
            if (modalId === 'reservationModal') {
                loadGuestsDropdown();
                loadAvailableRoomsDropdown();
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }

        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }

        function resetPaymentForm() {
            document.getElementById('paymentForm').reset();
            document.querySelector('#paymentForm input[name="id_pembayaran"]').value = '';
            document.getElementById('existingPaymentsContainer').style.display = 'none';
            document.getElementById('paymentInfoContainer').innerHTML = '';
        }

        // ==================== Fungsi yang Diubah untuk Integrasi API ====================

        // Dashboard functions
        async function loadDashboardData() {
            showLoading();
            
            try {
                const data = await callApi('dashboard');
                if (data) {
                    document.getElementById('totalRooms').textContent = data.stats.total_rooms;
                    document.getElementById('availableRooms').textContent = data.stats.available_rooms;
                    document.getElementById('totalGuests').textContent = data.stats.total_guests;
                    document.getElementById('activeReservations').textContent = data.stats.active_reservations;
                    
                    // Update tabel kamar tersedia
                    const tableBody = document.getElementById('availableRoomsTable');
                    tableBody.innerHTML = data.available_rooms.map(room => `
                        <tr>
                            <td>${room.nomor_kamar}</td>
                            <td>${room.nama_tipe}</td>
                            <td>${room.kapasitas_orang} orang</td>
                            <td>Rp ${room.harga_per_malam.toLocaleString('id-ID')}</td>
                            <td><span class="status ${room.status_kamar}">${room.status_kamar}</span></td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Error:', error);
            } finally {
                hideLoading();
            }
        }

        // Rooms functions
        async function loadRoomsData() {
            showLoading();
            try {
                const tableBody = document.getElementById('roomsTable');
                const data = await callApi('rooms');
                
                if (data) {
                    tableBody.innerHTML = data.map(room => `
                        <tr>
                            <td>${room.nomor_kamar}</td>
                            <td>${room.nama_tipe}</td>
                            <td>${room.lantai}</td>
                            <td><span class="status ${room.status_kamar}">${room.status_kamar}</span></td>
                            <td>
                                <button class="btn btn-danger" onclick="deleteRoom('${room.nomor_kamar}')">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading rooms:', error);
                showAlert('Gagal memuat data kamar', 'error');
            } finally {
                hideLoading();
            }
        }

        async function deleteRoom(nomor_kamar) {
            if (!confirm(`Apakah Anda yakin ingin menghapus kamar ${nomor_kamar}?`)) {
                return;
            }

            showLoading();
            
            try {
                const result = await callApi('rooms', 'DELETE', { nomor_kamar });
                
                if (result && result.success) {
                    showAlert(`Kamar ${nomor_kamar} berhasil dihapus`);
                    loadRoomsData(); // Refresh data kamar
                } else {
                    showAlert(result.error || 'Gagal menghapus kamar', 'error');
                }
            } catch (error) {
                console.error('Error deleting room:', error);
                showAlert(`Gagal menghapus kamar: ${error.message}`, 'error');
            } finally {
                hideLoading();
            }
        }

        async function handleRoomSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {
                nomor_kamar: formData.get('nomor_kamar'),
                id_tipe_kamar: formData.get('id_tipe_kamar'),
                lantai: formData.get('lantai'),
                status_kamar: formData.get('status_kamar')
            };

            try {
                const result = await callApi('rooms', 'POST', data);
                
                if (result && result.success) {
                    showAlert(result.message || 'Kamar berhasil ditambahkan!');
                    closeModal('roomModal');
                    e.target.reset();
                    loadRoomsData();
                } else {
                    showAlert(result.error || 'Gagal menambahkan kamar', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error: ' + error.message, 'error');
            }
        }

        // Guests functions
        async function loadGuestsData() {
            const tableBody = document.getElementById('guestsTable');
            const data = await callApi('guests');
            
            if (data) {
                tableBody.innerHTML = data.map(guest => `
                    <tr>
                        <td>${guest.nama_lengkap}</td>
                        <td>${guest.no_identitas}</td>
                        <td>${guest.no_telepon}</td>
                        <td>${guest.email}</td>
                        <td>
                            <button class="btn btn-danger" onclick="deleteGuest(${guest.id_tamu})">Hapus</button>
                        </td>
                    </tr>
                `).join('');
            }
        }

        async function handleGuestSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            const result = await callApi('guests', 'POST', data);
            if (result && result.success) {
                showAlert('Tamu berhasil ditambahkan!');
                closeModal('guestModal');
                e.target.reset();
                loadGuestsData();
            }
        }

        async function loadGuestsDropdown() {
            const select = document.querySelector('#reservationModal select[name="id_tamu"]');
            const data = await callApi('guests');
            
            if (data) {
                select.innerHTML = '<option value="">Pilih Tamu</option>' +
                    data.map(guest => `<option value="${guest.id_tamu}">${guest.nama_lengkap}</option>`).join('');
            }
        }
        async function deleteGuest(id_tamu) {
            if (!confirm('Apakah Anda yakin ingin menghapus tamu ini?')) {
                return;
            }

            showLoading();
            
            try {
                const result = await callApi('guests', 'DELETE', { id_tamu });
                
                if (result && result.success) {
                    showAlert(result.message || 'Tamu berhasil dihapus');
                    loadGuestsData();
                } else {
                    showAlert(result.message || 'Gagal menghapus tamu', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }
        // Reservations functions
        async function loadReservationsData() {
            const tableBody = document.getElementById('reservationsTable');
            const data = await callApi('reservations');
            
            if (data) {
                tableBody.innerHTML = data.map(reservation => `
                    <tr>
                        <td>${reservation.kode_pemesanan}</td>
                        <td>${reservation.nama_lengkap}</td>
                        <td>${reservation.nomor_kamar}</td>
                        <td>${reservation.tanggal_checkin}</td>
                        <td>${reservation.tanggal_checkout}</td>
                        <td><span class="status ${reservation.status_pemesanan}">${reservation.status_pemesanan}</span></td>
                        <td>Rp ${reservation.total_harga.toLocaleString('id-ID')}</td>
                        <td>
                            ${reservation.status_pemesanan === 'pending' || reservation.status_pemesanan === 'confirmed' ? 
                            `<button class="btn btn-danger" onclick="deleteReservation('${reservation.kode_pemesanan}')">Hapus</button>` : 
                            ''}
                            ${reservation.status_pemesanan === 'checkedout' ? 
                            `<button class="btn btn-danger" onclick="deleteReservation('${reservation.kode_pemesanan}')">Hapus</button>` : 
                            ''}
                        </td>
                    </tr>
                `).join('');
            }
        }

        async function loadReservationsForPayment() {
            const select = document.querySelector('#paymentModal select[name="kode_pemesanan"]');
            
            try {
                // Hanya memuat jika dropdown masih kosong
                if (select.options.length <= 1) {
                    const data = await callApi('reservations');
                    
                    if (data && data.length > 0) {
                        // Kosongkan dropdown kecuali option pertama
                        select.innerHTML = '<option value="">Pilih Kode Pemesanan</option>';
                        
                        // Tambahkan reservasi yang belum lunas
                        data.forEach(reservation => {
                            if (reservation.status_pemesanan !== 'cancelled') {
                                const option = document.createElement('option');
                                option.value = reservation.kode_pemesanan;
                                option.textContent = `${reservation.kode_pemesanan} - ${reservation.nama_lengkap} (Rp ${reservation.total_harga.toLocaleString('id-ID')})`;
                                select.appendChild(option);
                            }
                        });
                    }
                }
            } catch (error) {
                console.error('Error loading reservations:', error);
            }
        }

        // Fungsi untuk memuat pembayaran yang sudah ada
        async function loadReservationPayments(kode_pemesanan) {
            try {
                // 1. Dapatkan info reservasi termasuk status check-in
                const reservationData = await callApi('reservations/get', 'GET', {kode_pemesanan});
                
                if (reservationData.success) {
                    const reservation = reservationData.data;
                    const infoContainer = document.getElementById('paymentInfoContainer');
                    
                    // Tampilkan status check-in
                    let statusInfo = '';
                    if (reservation.status_pemesanan === 'checkedin') {
                        statusInfo = `<p class="text-success"><strong>Status:</strong> Sudah Check-in</p>`;
                    } else if (reservation.status_pemesanan === 'checkedout') {
                        statusInfo = `<p class="text-warning"><strong>Status:</strong> Sudah Check-out</p>`;
                    }
                    
                    infoContainer.innerHTML = `
                        <div class="reservation-info">
                            <h4>Informasi Reservasi</h4>
                            ${statusInfo}
                            <p><strong>Tamu:</strong> ${reservation.nama_lengkap}</p>
                            <p><strong>Kamar:</strong> ${reservation.nomor_kamar}</p>
                            <p><strong>Total:</strong> Rp ${reservation.total_harga.toLocaleString('id-ID')}</p>
                            <p><strong>Sudah Dibayar:</strong> Rp ${reservation.total_paid.toLocaleString('id-ID')}</p>
                            <p><strong>Sisa:</strong> Rp ${(reservation.total_harga - reservation.total_paid).toLocaleString('id-ID')}</p>
                        </div>
                    `;
                    
                    // 2. Load pembayaran yang sudah ada
                    const paymentsData = await callApi('payments');
                    if (paymentsData) {
                        const filteredPayments = paymentsData.filter(p => p.kode_pemesanan === kode_pemesanan);
                        const tableBody = document.getElementById('existingPaymentsTable');
                        
                        tableBody.innerHTML = filteredPayments.map(payment => `
                            <tr>
                                <td>${payment.tanggal_pembayaran}</td>
                                <td>Rp ${payment.jumlah_bayar.toLocaleString('id-ID')}</td>
                                <td>${payment.metode_pembayaran}</td>
                                <td><span class="status ${payment.status_pembayaran}">${payment.status_pembayaran}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="editPayment(${payment.id_pembayaran})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('');
                        
                        document.getElementById('existingPaymentsContainer').style.display = 
                            filteredPayments.length > 0 ? 'block' : 'none';
                    }
                }
            } catch (error) {
                console.error('Error loading reservation payments:', error);
            }
        }

        async function editPayment(id_pembayaran) {
            try {
                const paymentData = await callApi('payments/get', 'GET', { id_pembayaran });
                
                if (paymentData && paymentData.success) {
                    const payment = paymentData.data;
                    const form = document.getElementById('paymentForm');
                    
                    form.querySelector('input[name="id_pembayaran"]').value = payment.id_pembayaran;
                    form.querySelector('select[name="kode_pemesanan"]').value = payment.kode_pemesanan;
                    form.querySelector('input[name="jumlah_bayar"]').value = payment.jumlah_bayar;
                    form.querySelector('select[name="metode_pembayaran"]').value = payment.metode_pembayaran;
                    form.querySelector('select[name="status_pembayaran"]').value = payment.status_pembayaran;
                    form.querySelector('textarea[name="keterangan"]').value = payment.keterangan || '';
                    
                    openModal('paymentModal');
                } else {
                    showAlert(paymentData.error || 'Gagal memuat data pembayaran', 'error');
                }
            } catch (error) {
                console.error('Error loading payment:', error);
                showAlert('Error: ' + error.message, 'error');
            }
        }

        async function deleteReservation(kode_pemesanan) {
            if (!confirm('Apakah Anda yakin ingin menghapus reservasi ini?')) {
                return;
            }
            showLoading();
            try {
                const result = await callApi('reservations', 'DELETE', { kode_pemesanan });
                if (result && result.success) {
                    showAlert(result.message || 'Reservasi berhasil dihapus');
                    loadReservationsData();
                } else {
                    showAlert(result.error || 'Gagal menghapus reservasi', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }
        
        async function handleReservationSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            const result = await callApi('reservations', 'POST', data);
            if (result && result.success) {
                showAlert('Reservasi berhasil dibuat! Kode: ' + result.booking_code);
                closeModal('reservationModal');
                e.target.reset();
                loadReservationsData();
            }
        }

        async function loadAvailableRoomsDropdown() {
            const select = document.querySelector('#reservationModal select[name="nomor_kamar"]');
            const today = new Date().toISOString().split('T')[0];
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowStr = tomorrow.toISOString().split('T')[0];
            
            const data = await callApi('available-rooms', 'GET', null, {
                checkin: today,
                checkout: tomorrowStr
            });
            
            if (data) {
                select.innerHTML = '<option value="">Pilih Kamar</option>' +
                    data.map(room => `<option value="${room.nomor_kamar}">${room.nomor_kamar} - ${room.nama_tipe}</option>`).join('');
            }
        }
        async function cancelReservation(kode_pemesanan) {
            if (!confirm('Apakah Anda yakin ingin membatalkan reservasi ini?')) {
                return;
            }
            showLoading(); 
            try {
                const result = await callApi('reservations', 'DELETE', { kode_pemesanan });
                if (result && result.success) {
                    showAlert(result.message || 'Reservasi berhasil dibatalkan');
                    loadReservationsData();
                } else {
                    showAlert(result.error || 'Gagal membatalkan reservasi', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }
        // Payments functions
        async function loadPaymentsData() {
            showLoading();
            try {
                const data = await callApi('payments');
                if (!data) {
                    throw new Error('No data received from server');
                }
                
                const tableBody = document.getElementById('paymentsTable');
                tableBody.innerHTML = data.map(payment => `
                    <tr>
                        <td>${payment.kode_pemesanan}</td>
                        <td>Rp ${payment.jumlah_bayar?.toLocaleString('id-ID') || '0'}</td>
                        <td>${payment.metode_pembayaran || '-'}</td>
                        <td><span class="status ${payment.status_pembayaran || 'pending'}">${payment.status_pembayaran || 'pending'}</span></td>
                        <td>${payment.tanggal_pembayaran || '-'}</td>
                        <td>
                            <button class="btn btn-secondary" onclick="editPayment(${payment.id_pembayaran})">
                                Edit
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('Error loading payments:', error);
                showAlert('Gagal memuat data pembayaran: ' + error.message, 'error');
                document.getElementById('paymentsTable').innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">Error loading data</td>
                    </tr>
                `;
            } finally {
                hideLoading();
            }
        }

        async function handlePaymentSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            const isEdit = !!data.id_pembayaran;
            
            try {
                let result;
                if (isEdit) {
                    result = await callApi('payments', 'PUT', data);
                } else {
                    result = await callApi('payments', 'POST', data);
                }
                
                if (result && result.success) {
                    showAlert(`Pembayaran berhasil ${isEdit ? 'diperbarui' : 'ditambahkan'}!`);
                    closeModal('paymentModal');
                    resetPaymentForm();
                    loadPaymentsData();
                    loadReservationsData(); // Refresh data reservasi untuk update total paid
                } else {
                    showAlert(result.error || `Gagal ${isEdit ? 'memperbarui' : 'menambahkan'} pembayaran`, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error: ' + error.message, 'error');
            }
        }

        // Check-in/out functions
        async function loadTodaysReservations() {
            showLoading();
            try {
                const today = new Date().toISOString().split('T')[0];
                const data = await callApi('todays-reservations', 'GET', { date: today });
                
                const tableBody = document.getElementById('todaysReservations');
                
                if (data && data.length > 0) {
                    tableBody.innerHTML = data.map(reservation => `
                        <tr>
                            <td>${reservation.kode_pemesanan}</td>
                            <td>${reservation.nama_lengkap}</td>
                            <td>${reservation.nomor_kamar}</td>
                            <td>${reservation.tanggal_checkin}</td>
                            <td>${reservation.tanggal_checkout}</td>
                            <td><span class="status ${reservation.status_pemesanan}">${reservation.status_pemesanan}</span></td>
                            <td>
                                ${reservation.status_pemesanan === 'confirmed' ? 
                                    `<button class="btn btn-sm btn-primary" onclick="processCheckin('${reservation.kode_pemesanan}')">Check-in</button>` : ''}
                                ${reservation.status_pemesanan === 'checkedin' ? 
                                    `<button class="btn btn-sm btn-secondary" onclick="processCheckout('${reservation.kode_pemesanan}')">Check-out</button>` : ''}
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada reservasi hari ini</td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Error loading today\'s reservations:', error);
                showAlert('Gagal memuat data reservasi hari ini', 'error');
            } finally {
                hideLoading();
            }
        }

        async function processCheckin(kode_pemesanan = null) {
            const code = kode_pemesanan || document.getElementById('checkinCode').value;
            
            if (!code) {
                showAlert('Please enter booking code!', 'error');
                return;
            }
            
            showLoading();
            
            try {
                const result = await callApi('checkin', 'POST', { kode_pemesanan: code });
                
                if (result && result.success) {
                    showAlert(`Check-in successful for room ${result.room_number}`);
                    document.getElementById('checkinCode').value = '';
                    loadTodaysReservations();
                    loadReservationsData();
                    loadDashboardData();
                } else {
                    showAlert(result.error || 'Failed to process check-in', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert(`Check-in failed: ${error.message}`, 'error');
                
                // Special handling for status requirement error
                if (error.message.includes('Only reservations with confirmed')) {
                    // Show confirmation to confirm the reservation first
                    if (confirm('This reservation needs to be confirmed first. Confirm now?')) {
                        await confirmReservation(code);
                    }
                }
            } finally {
                hideLoading();
            }
        }

        async function confirmReservation(kode_pemesanan) {
            try {
                const result = await callApi('reservations', 'PUT', {
                    kode_pemesanan: kode_pemesanan,
                    status_pemesanan: 'confirmed'
                });
                
                if (result && result.success) {
                    showAlert('Reservation confirmed successfully!');
                    // Retry check-in
                    await processCheckin(kode_pemesanan);
                } else {
                    showAlert(result.error || 'Failed to confirm reservation', 'error');
                }
            } catch (error) {
                console.error('Error confirming reservation:', error);
                showAlert(`Failed to confirm reservation: ${error.message}`, 'error');
            }
        }

        async function processCheckout(kode_pemesanan = null) {
            const code = kode_pemesanan || document.getElementById('checkinCode').value;
            
            if (!code) {
                showAlert('Masukkan kode pemesanan!', 'error');
                return;
            }
            
            showLoading();
            
            try {
                const result = await callApi('checkout', 'POST', { kode_pemesanan: code });
                
                if (result && result.success) {
                    showAlert('Check-out berhasil diproses!');
                    document.getElementById('checkinCode').value = '';
                    loadTodaysReservations();
                    loadReservationsData();
                    loadDashboardData();
                } else {
                    showAlert(result.error || 'Gagal memproses check-out', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }
        //sidebar functions
        // Tambahkan di bagian script
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const body = document.body;
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            body.classList.toggle('sidebar-open');
            
            // Blur/unblur content
            const mainContent = document.querySelector('.main-content');
            if (sidebar.classList.contains('open')) {
                mainContent.style.filter = 'blur(3px)';
            } else {
                mainContent.style.filter = 'none';
            }
        }

        // Tutup sidebar saat klik overlay
        document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);

    </script>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="profile-header">
            <div class="profile-avatar">
                <?= strtoupper(substr($_SESSION['nama_pegawai'], 0, 1)) ?>
            </div>
            <div class="profile-info">
                <h3><?= $_SESSION['nama_pegawai'] ?></h3>
                <p>Staff</p>
            </div>
        </div>
        
        <ul class="profile-menu">
            <li><a href="#"><i class="fas fa-user-circle"></i> Profil Saya</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Pengaturan</a></li>
            <li><a href="#"><i class="fas fa-bell"></i> Notifikasi <span class="notification-badge">4</span></a></li>
            <li><a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
        </ul>
    </div>

</body>
</html>