<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Ambil data beras dari API
$beras_data = [];
$beras_error = null;
$api_url_beras = 'http://localhost:5000/beras';
$ch_beras = curl_init($api_url_beras);
curl_setopt($ch_beras, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_beras, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response_beras = curl_exec($ch_beras);
$http_code_beras = curl_getinfo($ch_beras, CURLINFO_HTTP_CODE);
curl_close($ch_beras);
if ($http_code_beras === 200) {
    $beras_data = json_decode($response_beras, true);
} else {
    $beras_error = 'Gagal mengambil data beras: HTTP ' . $http_code_beras;
}

// Proses pengiriman pembayaran
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nama' => $_POST['nama'],
        'jumlah_jiwa' => intval($_POST['jumlah_jiwa']),
        'jenis_zakat' => $_POST['jenis_zakat'],
        'metode_pembayaran' => $_POST['metode_pembayaran'],
        'total_bayar' => floatval($_POST['total_bayar']),
        'nominal_dibayar' => floatval($_POST['nominal_dibayar']),
        'kembalian' => floatval($_POST['kembalian']),
        'tanggal_bayar' => $_POST['tanggal_bayar']
    ];

    // Tambahkan keterangan otomatis
    if ($_POST['jenis_zakat'] === 'beras' && isset($_POST['beras_pilihan']) && !empty($_POST['beras_pilihan'])) {
        $id_beras = $_POST['beras_pilihan'];
        $harga_beras = null;
        foreach ($beras_data as $beras) {
            if ($beras['id'] == $id_beras) {
                $harga_beras = $beras['harga'];
                break;
            }
        }
        if (!$harga_beras) {
            $error = "Error: ID beras tidak valid!";
        } else {
            $total_bayar_beras = 3.5 * floatval($harga_beras) * $data['jumlah_jiwa'];
            $data['total_bayar'] = $total_bayar_beras;
            $data['keterangan'] = "Beras ID $id_beras: " . (3.5 * $data['jumlah_jiwa']) . " Liter";
        }
    } elseif ($_POST['jenis_zakat'] === 'uang' && isset($_POST['pendapatan_tahunan'])) {
        $pendapatan = floatval($_POST['pendapatan_tahunan']);
        $total_bayar_uang = $pendapatan * 0.025;
        $data['total_bayar'] = $total_bayar_uang;
        $data['keterangan'] = "Uang: 2.5% dari Rp " . number_format($pendapatan, 2);
    }

    if (!$error) {
        $api_url = 'http://localhost:5000/pembayaran';
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $error = 'cURL Error: ' . curl_error($ch);
        } else {
            file_put_contents('debug.log', "HTTP Code: $http_code\nResponse: $response\nData Sent: " . json_encode($data) . "\n\n", FILE_APPEND);
        }
        curl_close($ch);

        if ($http_code === 201) {
            $success = "Pembayaran berhasil disimpan.";
        } else {
            $error = "Gagal menyimpan pembayaran: HTTP $http_code\nResponse: $response";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Melakukan Pembayaran Zakat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const jenisZakat = document.getElementById('jenis_zakat');
            const berasSection = document.getElementById('beras_section');
            const pendapatanSection = document.getElementById('pendapatan_section');
            const berasPilihan = document.getElementById('beras_pilihan');
            const totalBayar = document.getElementById('total_bayar');
            const kembalian = document.getElementById('kembalian');

            if (berasPilihan.options.length > 1) {
                berasPilihan.removeAttribute('disabled');
            }

            function updateTotal() {
                const jumlahJiwa = parseFloat(document.getElementById('jumlah_jiwa').value) || 0;
                const nominalDibayar = parseFloat(document.getElementById('nominal_dibayar').value) || 0;

                if (jenisZakat.value === 'beras' && berasPilihan.value) {
                    const hargaBeras = parseFloat(berasPilihan.options[berasPilihan.selectedIndex].dataset.harga) || 0;
                    const total = jumlahJiwa * 3.5 * hargaBeras;
                    totalBayar.value = total.toFixed(2);
                } else if (jenisZakat.value === 'uang') {
                    const pendapatan = parseFloat(document.getElementById('pendapatan_tahunan').value) || 0;
                    const total = pendapatan * 0.025;
                    totalBayar.value = total.toFixed(2);
                } else {
                    totalBayar.value = '0.00';
                }
                kembalian.value = (nominalDibayar - parseFloat(totalBayar.value) || 0).toFixed(2);
            }

            jenisZakat.addEventListener('change', function() {
                berasSection.classList.toggle('hidden', this.value !== 'beras');
                pendapatanSection.classList.toggle('hidden', this.value !== 'uang');
                if (this.value === 'beras' && berasPilihan.options.length > 1) {
                    berasPilihan.removeAttribute('disabled');
                } else {
                    berasPilihan.setAttribute('disabled', 'disabled');
                }
                updateTotal();
            });

            document.getElementById('jumlah_jiwa').addEventListener('input', updateTotal);
            berasPilihan.addEventListener('change', updateTotal);
            document.getElementById('pendapatan_tahunan').addEventListener('input', updateTotal);
            document.getElementById('nominal_dibayar').addEventListener('input', updateTotal);
        });
    </script>
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
            <h1 class="text-3xl font-bold mb-8 text-center md:text-left">Melakukan Pembayaran Zakat</h1>

            <!-- Notifications -->
            <?php if ($beras_error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg">
                    <?php echo htmlspecialchars($beras_error); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-lg p-6 max-w-3xl mx-auto">
                <form id="paymentForm" method="post" action="" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama</label>
                            <input type="text" name="nama" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-blue-500 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jumlah Jiwa</label>
                            <input type="number" id="jumlah_jiwa" name="jumlah_jiwa" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-blue-500 text-sm" required min="1">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jenis Zakat</label>
                            <select id="jenis_zakat" name="jenis_zakat" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-blue-500 text-sm" required>
                                <option value="">Pilih Jenis Zakat</option>
                                <option value="beras">Beras</option>
                                <option value="uang">Uang</option>
                            </select>
                        </div>
                        <div id="beras_section" class="hidden">
                            <label class="block text-sm font-medium text-gray-700">Pilih Jenis Beras</label>
                            <select id="beras_pilihan" name="beras_pilihan" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-blue-500 text-sm">
                                <option value="">Pilih Beras</option>
                                <?php if (!empty($beras_data)): ?>
                                    <?php foreach ($beras_data as $beras): ?>
                                        <option value="<?php echo htmlspecialchars($beras['id']); ?>" data-harga="<?php echo htmlspecialchars($beras['harga']); ?>">
                                            <?php echo htmlspecialchars($beras['id']) . ' - Rp ' . number_format($beras['harga'], 2); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Tidak ada data beras</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div id="pendapatan_section" class="hidden">
                            <label class="block text-sm font-medium text-gray-700">Pendapatan Tahunan (Rp)</label>
                            <input type="number" id="pendapatan_tahunan" name="pendapatan_tahunan" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-blue-500 text-sm" min="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Metode Pembayaran</label>
                            <input type="text" name="metode_pembayaran" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-blue-500 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Total Bayar (Rp)</label>
                            <input type="number" step="0.01" id="total_bayar" name="total_bayar" class="mt-1 p-2 border rounded w-full bg-gray-100 text-sm" readonly required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nominal Dibayar (Rp)</label>
                            <input type="number" step="0.01" id="nominal_dibayar" name="nominal_dibayar" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-blue-500 text-sm" required min="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kembalian (Rp)</label>
                            <input type="number" step="0.01" id="kembalian" name="kembalian" class="mt-1 p-2 border rounded w-full bg-gray-100 text-sm" readonly>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Tanggal Bayar</label>
                            <input type="datetime-local" name="tanggal_bayar" class="mt-1 p-2 border rounded w-full focus:ring-2 focus:ring-blue-500 text-sm" required>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-4">
                        <a href="dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 text-sm transition">Kembali</a>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 text-sm transition">Simpan Pembayaran</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>