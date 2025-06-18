<?php
date_default_timezone_set('Asia/Jakarta');

// Ambil data pembayaran dari API
$pembayaran_data = [];
$pembayaran_error = null;
$api_url_pembayaran = 'http://localhost:5000/pembayaran';
$ch = curl_init($api_url_pembayaran);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $pembayaran_data = json_decode($response, true);
    // Urutkan data berdasarkan tanggal_bayar secara descending
    usort($pembayaran_data, function($a, $b) {
        return strtotime($b['tanggal_bayar']) - strtotime($a['tanggal_bayar']);
    });
} else {
    $pembayaran_error = 'Gagal mengambil data: HTTP ' . $http_code;
}

$total_pembayaran = 0;
$jumlah_transaksi = count($pembayaran_data);
$tanggal_terbaru = '-';

if ($jumlah_transaksi > 0) {
    foreach ($pembayaran_data as $item) {
        $total_pembayaran += floatval($item['total_bayar']);
    }
    $tanggal_terbaru = $pembayaran_data[0]['tanggal_bayar'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pembayaran Zakat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit-id.js" crossorigin="anonymous"></script>
</head>
<body class="bg-gray-900 text-gray-100 font-sans min-h-screen">
    <div class="container mx-auto p-4 md:p-8">
        <!-- Header -->
        <header class="bg-blue-800 rounded-lg shadow-lg p-6 mb-8">
            <h1 class="text-3xl font-bold text-center text-white">Dashboard Pembayaran Zakat</h1>
            <nav class="mt-4 flex justify-center space-x-4">
                <a href="dashboard.php" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-full text-sm text-white transition">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>
                <a href="tambahpembayaran.php" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-full text-sm text-white transition">
                    <i class="fas fa-plus mr-1"></i> Tambah Pembayaran
                </a>
                <a href="pembayaran.php" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-full text-sm text-white transition">
                    <i class="fas fa-history mr-1"></i> History
                </a>
                <a href="beras.php" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 rounded-full text-sm text-white transition">
                    <i class="fas fa-seedling mr-1"></i> Data Beras
                </a>
            </nav>
        </header>

        <!-- Error Message -->
        <?php if ($pembayaran_error): ?>
            <div class="bg-red-600 text-white p-4 rounded-lg mb-6 text-center">
                <?= htmlspecialchars($pembayaran_error); ?>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-gray-800 rounded-lg p-6 shadow-lg hover:shadow-xl transition">
                <div class="flex items-center">
                    <i class="fas fa-wallet text-4xl text-yellow-400 mr-4"></i>
                    <div>
                        <p class="text-sm text-gray-400">Total Pembayaran</p>
                        <p class="text-2xl font-bold text-white">Rp <?= number_format($total_pembayaran, 2); ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 shadow-lg hover:shadow-xl transition">
                <div class="flex items-center">
                    <i class="fas fa-list-ul text-4xl text-green-400 mr-4"></i>
                    <div>
                        <p class="text-sm text-gray-400">Jumlah Transaksi</p>
                        <p class="text-2xl font-bold text-white"><?= $jumlah_transaksi; ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-800 rounded-lg p-6 shadow-lg hover:shadow-xl transition">
                <div class="flex items-center">
                    <i class="fas fa-calendar-check text-4xl text-blue-400 mr-4"></i>
                    <div>
                        <p class="text-sm text-gray-400">Tanggal Terbaru</p>
                        <p class="text-2xl font-bold text-white"><?= htmlspecialchars($tanggal_terbaru); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Pembayaran Terbaru</h2>
            <?php if ($jumlah_transaksi === 0): ?>
                <p class="text-center text-gray-400 py-4">Belum ada data pembayaran.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left table-auto">
                        <thead>
                            <tr class="bg-gray-700 text-gray-300 text-sm">
                                <th class="px-4 py-3 font-semibold">Nama</th>
                                <th class="px-4 py-3 font-semibold">Jenis Zakat</th>
                                <th class="px-4 py-3 font-semibold text-right">Total Bayar (Rp)</th>
                                <th class="px-4 py-3 font-semibold">Tanggal Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($pembayaran_data, 0, 5) as $data): ?>
                                <tr class="border-b border-gray-700 hover:bg-gray-600 transition">
                                    <td class="px-4 py-3 text-gray-200"><?= htmlspecialchars($data['nama']); ?></td>
                                    <td class="px-4 py-3 text-gray-200"><?= htmlspecialchars($data['jenis_zakat']); ?></td>
                                    <td class="px-4 py-3 text-right text-gray-200"><?= number_format($data['total_bayar'], 2); ?></td>
                                    <td class="px-4 py-3 text-gray-200"><?= htmlspecialchars($data['tanggal_bayar']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>