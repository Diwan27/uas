<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Impor namespace PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// URL of the Flask API endpoint
$api_url = 'http://localhost:5000/pembayaran';

// Initialize cURL
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Execute cURL request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Decode JSON response
$data = json_decode($response, true);
$error = null;
if ($http_code !== 200 || json_last_error() !== JSON_ERROR_NONE) {
    $error = 'Gagal mengambil data pembayaran: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
}

// Fungsi untuk memperbarui data pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $data = [
        'nama' => $_POST['nama'],
        'jumlah_jiwa' => intval($_POST['jumlah_jiwa']),
        'jenis_zakat' => $_POST['jenis_zakat'],
        'metode_pembayaran' => $_POST['metode_pembayaran'],
        'total_bayar' => floatval($_POST['total_bayar']),
        'nominal_dibayar' => floatval($_POST['nominal_dibayar']),
        'kembalian' => floatval($_POST['kembalian']),
        'keterangan' => $_POST['keterangan'],
        'tanggal_bayar' => $_POST['tanggal_bayar']
    ];

    $api_url_put = "http://localhost:5000/pembayaran/$id";
    $ch = curl_init($api_url_put);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $success = "Data pembayaran berhasil diperbarui.";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response, true);
    } else {
        $error = "Gagal memperbarui data pembayaran: HTTP $http_code";
    }
}

// Fungsi untuk menghapus data pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $api_url_delete = "http://localhost:5000/pembayaran/$id";
    $ch = curl_init($api_url_delete);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $success = "Data pembayaran berhasil dihapus.";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response, true);
    } else {
        $error = "Gagal menghapus data pembayaran: HTTP $http_code";
    }
}

// Fungsi untuk generate Excel
if (isset($_GET['generate_excel']) && !$error && !empty($data)) {
    require 'vendor/autoload.php';

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Jumlah Jiwa');
    $sheet->setCellValue('C1', 'Jenis Zakat');
    $sheet->setCellValue('D1', 'Nama');
    $sheet->setCellValue('E1', 'Metode Pembayaran');
    $sheet->setCellValue('F1', 'Total Bayar');
    $sheet->setCellValue('G1', 'Nominal Dibayar');
    $sheet->setCellValue('H1', 'Kembalian');
    $sheet->setCellValue('I1', 'Keterangan');
    $sheet->setCellValue('J1', 'Tanggal Bayar');

    // Data
    $row = 2;
    foreach ($data as $record) {
        $sheet->setCellValue('A' . $row, $record['id']);
        $sheet->setCellValue('B' . $row, $record['jumlah_jiwa']);
        $sheet->setCellValue('C' . $row, $record['jenis_zakat']);
        $sheet->setCellValue('D' . $row, $record['nama']);
        $sheet->setCellValue('E' . $row, $record['metode_pembayaran']);
        $sheet->setCellValue('F' . $row, $record['total_bayar']);
        $sheet->setCellValue('G' . $row, $record['nominal_dibayar']);
        $sheet->setCellValue('H' . $row, $record['kembalian']);
        $sheet->setCellValue('I' . $row, $record['keterangan']);
        $sheet->setCellValue('J' . $row, $record['tanggal_bayar']);
        $row++;
    }

    // Styling
    $sheet->getStyle('A1:J1')->getFont()->setBold(true);
    $sheet->getStyle('A1:J' . ($row-1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    // Unduh file
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="pembayaran_zakat_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Zakat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function openEditModal(id, nama, jumlah_jiwa, jenis_zakat, metode_pembayaran, total_bayar, nominal_dibayar, kembalian, keterangan, tanggal_bayar) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_jumlah_jiwa').value = jumlah_jiwa;
            document.getElementById('edit_jenis_zakat').value = jenis_zakat;
            document.getElementById('edit_metode_pembayaran').value = metode_pembayaran;
            document.getElementById('edit_total_bayar').value = total_bayar;
            document.getElementById('edit_nominal_dibayar').value = nominal_dibayar;
            document.getElementById('edit_kembalian').value = kembalian;
            document.getElementById('edit_keterangan').value = keterangan;
            document.getElementById('edit_tanggal_bayar').value = tanggal_bayar.replace(' ', 'T');
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('opacity-100', 'scale-100');
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.add('opacity-0', 'scale-95');
            setTimeout(() => modal.classList.add('hidden'), 150);
        }

        function submitEditForm(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('editForm'));
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            fetch(`http://localhost:5000/pembayaran/${data.edit_id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    nama: data.edit_nama,
                    jumlah_jiwa: parseInt(data.edit_jumlah_jiwa),
                    jenis_zakat: data.edit_jenis_zakat,
                    metode_pembayaran: data.edit_metode_pembayaran,
                    total_bayar: parseFloat(data.edit_total_bayar),
                    nominal_dibayar: parseFloat(data.edit_nominal_dibayar),
                    kembalian: parseFloat(data.edit_kembalian),
                    keterangan: data.edit_keterangan,
                    tanggal_bayar: data.edit_tanggal_bayar
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal menyimpan data');
                }
                return response.json();
            })
            .then(result => {
                if (result.message === "Pembayaran updated successfully") {
                    alert("Data berhasil diperbarui!");
                    location.reload();
                } else {
                    throw new Error(result.message || 'Error tidak diketahui');
                }
            })
            .catch(error => {
                alert("Terjadi kesalahan: " + error.message);
            });
        }

        function deletePayment(id) {
            if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                fetch(`http://localhost:5000/pembayaran/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Gagal menghapus data');
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.message === "Pembayaran deleted successfully") {
                        alert("Data berhasil dihapus!");
                        location.reload();
                    } else {
                        throw new Error(result.message || 'Error tidak diketahui');
                    }
                })
                .catch(error => {
                    alert("Terjadi kesalahan: " + error.message);
                });
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-blue-100 to-gray-200 text-gray-800 min-h-screen">
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
                <h1 class="text-3xl font-bold">Pembayaran Zakat</h1>
                <div class="space-x-3">
                    <a href="?generate_excel=1" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition text-sm">Generate Excel</a>
                </div>
            </header>

            <!-- Notifications -->
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Data Table -->
            <div class="bg-white rounded-xl shadow-lg p-6 overflow-x-auto">
                <?php if ($error || empty($data)): ?>
                    <p class="text-center text-gray-500 py-4"><?php echo $error ? htmlspecialchars($error) : 'Tidak ada data ditemukan'; ?></p>
                <?php else: ?>
                    <table class="min-w-full text-left table-auto">
                        <thead>
                            <tr class="bg-blue-50 text-sm text-gray-600">
                                <th class="px-4 py-3 font-semibold">ID</th>
                                <th class="px-4 py-3 font-semibold">Jumlah Jiwa</th>
                                <th class="px-4 py-3 font-semibold">Jenis Zakat</th>
                                <th class="px-4 py-3 font-semibold">Nama</th>
                                <th class="px-4 py-3 font-semibold">Metode Pembayaran</th>
                                <th class="px-4 py-3 font-semibold text-right">Total Bayar</th>
                                <th class="px-4 py-3 font-semibold text-right">Nominal Dibayar</th>
                                <th class="px-4 py-3 font-semibold text-right">Kembalian</th>
                                <th class="px-4 py-3 font-semibold">Keterangan</th>
                                <th class="px-4 py-3 font-semibold">Tanggal Bayar</th>
                                <th class="px-4 py-3 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $record): ?>
                                <tr class="border-b hover:bg-blue-50 transition">
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($record['id']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($record['jumlah_jiwa']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($record['jenis_zakat']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($record['nama']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($record['metode_pembayaran']); ?></td>
                                    <td class="px-4 py-3 text-right"><?php echo number_format($record['total_bayar'], 2); ?></td>
                                    <td class="px-4 py-3 text-right"><?php echo number_format($record['nominal_dibayar'], 2); ?></td>
                                    <td class="px-4 py-3 text-right"><?php echo number_format($record['kembalian'], 2); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($record['keterangan']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($record['tanggal_bayar']); ?></td>
                                    <td class="px-4 py-3 flex space-x-2">
                                        <button onclick="openEditModal(
                                            <?php echo $record['id']; ?>,
                                            '<?php echo htmlspecialchars(addslashes($record['nama'])); ?>',
                                            <?php echo $record['jumlah_jiwa']; ?>,
                                            '<?php echo htmlspecialchars(addslashes($record['jenis_zakat'])); ?>',
                                            '<?php echo htmlspecialchars(addslashes($record['metode_pembayaran'])); ?>',
                                            <?php echo $record['total_bayar']; ?>,
                                            <?php echo $record['nominal_dibayar']; ?>,
                                            <?php echo $record['kembalian']; ?>,
                                            '<?php echo htmlspecialchars(addslashes($record['keterangan'])); ?>',
                                            '<?php echo $record['tanggal_bayar']; ?>'
                                        )" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 text-sm">Edit</button>
                                        <button onclick="deletePayment(<?php echo $record['id']; ?>)" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">Hapus</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal untuk Edit -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden opacity-0 scale-95 transition-opacity transition-scale duration-200 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-xl shadow-lg w-full max-w-2xl">
            <h2 class="text-xl font-bold mb-4 text-center">Edit Data Pembayaran</h2>
            <form id="editForm" onsubmit="submitEditForm(event)" class="space-y-4">
                <input type="hidden" id="edit_id" name="edit_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" id="edit_nama" name="edit_nama" class="mt-1 p-2 border rounded w-full text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jumlah Jiwa</label>
                        <input type="number" id="edit_jumlah_jiwa" name="edit_jumlah_jiwa" class="mt-1 p-2 border rounded w-full text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jenis Zakat</label>
                        <input type="text" id="edit_jenis_zakat" name="edit_jenis_zakat" class="mt-1 p-2 border rounded w-full text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Metode Pembayaran</label>
                        <input type="text" id="edit_metode_pembayaran" name="edit_metode_pembayaran" class="mt-1 p-2 border rounded w-full text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Total Bayar</label>
                        <input type="number" step="0.01" id="edit_total_bayar" name="edit_total_bayar" class="mt-1 p-2 border rounded w-full text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nominal Dibayar</label>
                        <input type="number" step="0.01" id="edit_nominal_dibayar" name="edit_nominal_dibayar" class="mt-1 p-2 border rounded w-full text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kembalian</label>
                        <input type="number" step="0.01" id="edit_kembalian" name="edit_kembalian" class="mt-1 p-2 border rounded w-full text-sm" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                        <textarea id="edit_keterangan" name="edit_keterangan" class="mt-1 p-2 border rounded w-full text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Tanggal Bayar</label>
                        <input type="datetime-local" id="edit_tanggal_bayar" name="edit_tanggal_bayar" class="mt-1 p-2 border rounded w-full text-sm" required>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 text-sm">Simpan</button>
                    <button type="button" onclick="closeEditModal()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>