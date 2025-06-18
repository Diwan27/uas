<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// URL endpoint API Flask
$api_url = 'http://localhost:5000/beras';

// Inisialisasi variabel
$data = [];
$error = null;

// Inisialisasi cURL
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Eksekusi permintaan cURL
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Dekode respons JSON
if ($response !== false && $http_code === 200) {
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error = 'Gagal mendekode data JSON: ' . json_last_error_msg();
    }
} else {
    $error = 'Gagal mengambil data beras: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
}

// Fungsi untuk menambah data beras
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_harga'])) {
    $data_to_send = ['harga' => floatval($_POST['add_harga'])];
    $api_url_post = 'http://localhost:5000/beras';
    $ch = curl_init($api_url_post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_to_send));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 201) {
        $success = "Data beras berhasil ditambahkan.";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response !== false && $http_code === 200) {
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'Gagal mendekode data JSON setelah penambahan: ' . json_last_error_msg();
            }
        } else {
            $error = 'Gagal mengambil data beras setelah penambahan: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
        }
    } else {
        $error = "Gagal menambahkan data beras: HTTP $http_code";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Beras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit-id.js" crossorigin="anonymous"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-gray-100 text-gray-800 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg p-6 fixed h-full">
            <h2 class="text-2xl font-bold text-blue-600 mb-6">Zakat Dashboard</h2>
            <nav class="space-y-2">
                <a href="dashboard.php" class="flex items-center p-3 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition">
                    <i class="fas fa-home mr-2"></i> Dashboard
                </a>
                <a href="tambahpembayaran.php" class="flex items-center p-3 hover:bg-blue-100 rounded-lg transition">
                    <i class="fas fa-money-bill-wave mr-2"></i> Pembayaran
                </a>
                <a href="pembayaran.php" class="flex items-center p-3 hover:bg-blue-100 rounded-lg transition">
                    <i class="fas fa-history mr-2"></i> History
                </a>
                <a href="beras.php" class="flex items-center p-3 hover:bg-blue-100 rounded-lg transition">
                    <i class="fas fa-seedling mr-2"></i> Data Beras
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 p-8 w-full">
            <header class="flex justify-between items-center mb-8">
                <div class="flex items-center">
                    <i class="fas fa-sack text-3xl text-yellow-500 mr-3"></i>
                    <h1 class="text-3xl font-bold">Data Harga Beras</h1>
                </div>
                <div class="space-x-3">
                    <button onclick="openAddModal()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 text-sm transition">Tambah Data</button>
                </div>
            </header>

            <!-- Notifications -->
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php elseif (isset($success)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <!-- Data Table -->
            <div class="bg-white rounded-xl shadow-lg p-6 overflow-x-auto">
                <?php if (!empty($data) && is_array($data)): ?>
                    <table class="min-w-full text-left table-auto">
                        <thead>
                            <tr class="bg-blue-50 text-sm text-gray-600">
                                <th class="px-4 py-3 font-semibold">ID</th>
                                <th class="px-4 py-3 font-semibold">Harga (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $record): ?>
                                <tr class="border-b hover:bg-blue-50 transition">
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($record['id']); ?></td>
                                    <td class="px-4 py-3"><?php echo number_format($record['harga'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center text-gray-500 py-4">Tidak ada data ditemukan</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 shadow-lg w-full max-w-md">
            <h2 class="text-xl font-bold mb-4 text-gray-700 text-center">Tambah Data Beras</h2>
            <form id="addForm" onsubmit="submitAddForm(event)" class="space-y-4">
                <div>
                    <label for="add_harga" class="block text-sm font-medium text-gray-700">Harga (Rp)</label>
                    <input type="number" id="add_harga" name="add_harga" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Masukkan harga" step="0.01" required>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 text-sm">Simpan</button>
                    <button type="button" onclick="closeAddModal()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('add_harga').value = '';
            document.getElementById('addModal').classList.remove('hidden');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }

        function submitAddForm(event) {
            event.preventDefault();
            const harga = document.getElementById('add_harga').value;
            if (!harga || isNaN(harga)) {
                alert("Harap masukkan harga yang valid!");
                return;
            }
            fetch('http://localhost:5000/beras', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ harga: parseFloat(harga) })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal menyimpan data');
                }
                return response.json();
            })
            .then(result => {
                if (result.message === "Beras created successfully") {
                    alert("Data beras berhasil ditambahkan!");
                    location.reload();
                } else {
                    throw new Error(result.message || 'Error tidak diketahui');
                }
            })
            .catch(error => {
                alert("Terjadi kesalahan: " + error.message);
            });
        }
    </script>
</body>
</html>