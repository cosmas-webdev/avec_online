<?php
/**
 * Reports Management
 * 
 * Professional reports with PDF export and QR Code.
 * 
 * @author Ir. Cosmas MUSAFIRI MUGONGO
 * @version 1.0.0
 */

require_once 'includes/auth.php';
requireLogin();

$page_title = __('reports');
include 'includes/header.php';

$db = db();
$report_type = $_GET['report'] ?? 'dashboard';
$export = $_GET['export'] ?? '';
$member_id = $_GET['member_id'] ?? '';

// ============================================
// LOAD QR CODE LIBRARY
// ============================================

$qr_code_path = dirname(__DIR__) . '/phpqrcode/qrlib.php';
if (file_exists($qr_code_path)) {
    require_once $qr_code_path;
    $qr_enabled = true;
} else {
    $qr_enabled = false;
}

// ============================================
// LOAD FPDF LIBRARY
// ============================================

$pdf_path = dirname(__DIR__) . '/fpdf/fpdf.php';
if (file_exists($pdf_path)) {
    require_once $pdf_path;
    $pdf_enabled = true;
} else {
    $pdf_enabled = false;
}

// ============================================
// REPORT TYPES
// ============================================

$reports = [
    'dashboard' => 'Dashboard Overview',
    'loans' => 'Loan Report',
    'savings' => 'Savings Report',
    'members' => 'Member Report',
    'financial' => 'Financial Report',
    'transactions' => 'Transaction Report',
    'audit' => 'Audit Log'
];

// ============================================
// GET DATE RANGE FILTERS
// ============================================

$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// ============================================
// HELPER FUNCTIONS
// ============================================

function formatReportDate($date) {
    return date('M d, Y', strtotime($date));
}

function getReportPeriod($date_from, $date_to) {
    return formatReportDate($date_from) . ' to ' . formatReportDate($date_to);
}

/**
 * Generate QR Code
 */
function generateQRCode($data, $size = 150) {
    global $qr_enabled;
    
    if (!$qr_enabled) {
        return '<div class="text-red-500 text-sm">QR Code library not found. Please install phpqrcode.</div>';
    }
    
    // Créer un fichier temporaire
    $temp_file = dirname(__DIR__) . '/assets/temp/qr_' . md5($data) . '.png';
    
    // Vérifier si le dossier temp existe
    $temp_dir = dirname(__DIR__) . '/assets/temp';
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }
    
    // Générer le QR code
    QRcode::png($data, $temp_file, QR_ECLEVEL_L, $size / 25);
    
    // Retourner l'URL de l'image
    $url = 'assets/temp/qr_' . md5($data) . '.png';
    return '<img src="' . $url . '" alt="QR Code" class="w-' . ($size/4) . ' h-' . ($size/4) . '">';
}

/**
 * Generate Report Data for Export
 */
function getReportData($db, $report_type, $date_from, $date_to, $member_id = '') {
    $data = [];
    
    switch ($report_type) {
        case 'loans':
            $stmt = $db->prepare("
                SELECT l.loan_number, m.first_name, m.last_name, l.amount, 
                       l.interest_rate, l.duration_months, l.loan_date, l.due_date,
                       COALESCE(SUM(r.amount), 0) as paid, l.status
                FROM loans l
                JOIN members m ON l.member_id = m.id
                LEFT JOIN repayments r ON l.id = r.loan_id AND r.status = 'completed'
                WHERE l.loan_date BETWEEN :date_from AND :date_to
                GROUP BY l.id
                ORDER BY l.created_at DESC
            ");
            $stmt->execute(['date_from' => $date_from, 'date_to' => $date_to]);
            $data = $stmt->fetchAll();
            break;
            
        case 'savings':
            $stmt = $db->prepare("
                SELECT s.account_number, m.first_name, m.last_name, s.balance,
                       COALESCE(d.total_deposits, 0) as deposits,
                       COALESCE(w.total_withdrawals, 0) as withdrawals,
                       s.status
                FROM savings_accounts s
                JOIN members m ON s.member_id = m.id
                LEFT JOIN (
                    SELECT savings_account_id, SUM(amount) as total_deposits
                    FROM transactions 
                    WHERE transaction_type = 'deposit'
                    AND created_at BETWEEN :date_from AND :date_to
                    GROUP BY savings_account_id
                ) d ON s.id = d.savings_account_id
                LEFT JOIN (
                    SELECT savings_account_id, SUM(amount) as total_withdrawals
                    FROM transactions 
                    WHERE transaction_type = 'withdrawal'
                    AND created_at BETWEEN :date_from AND :date_to
                    GROUP BY savings_account_id
                ) w ON s.id = w.savings_account_id
                WHERE s.status = 'active'
                ORDER BY s.created_at DESC
            ");
            $stmt->execute(['date_from' => $date_from, 'date_to' => $date_to]);
            $data = $stmt->fetchAll();
            break;
            
        case 'members':
            $stmt = $db->prepare("
                SELECT m.id, m.first_name, m.last_name, u.email, m.phone, 
                       m.address, g.name as group_name, m.joined_date, m.status
                FROM members m
                LEFT JOIN groups g ON m.group_id = g.id
                LEFT JOIN users u ON m.user_id = u.id
                WHERE m.status = 'active'
                ORDER BY m.joined_date DESC
            ");
            $stmt->execute();
            $data = $stmt->fetchAll();
            break;
            
        case 'transactions':
            $stmt = $db->prepare("
                SELECT t.created_at, m.first_name, m.last_name, 
                       t.transaction_type, t.amount, t.description
                FROM transactions t
                JOIN members m ON t.member_id = m.id
                WHERE t.created_at BETWEEN :date_from AND :date_to
                ORDER BY t.created_at DESC
            ");
            $stmt->execute(['date_from' => $date_from, 'date_to' => $date_to]);
            $data = $stmt->fetchAll();
            break;
            
        case 'financial':
            $stmt = $db->prepare("
                SELECT 
                    (SELECT SUM(balance) FROM savings_accounts WHERE status = 'active') as total_savings,
                    (SELECT SUM(amount) FROM loans WHERE status NOT IN ('repaid', 'rejected')) as total_loans,
                    (SELECT SUM(amount) FROM repayments WHERE status = 'completed') as total_repayments,
                    (SELECT SUM(amount) FROM transactions WHERE transaction_type = 'deposit' AND created_at BETWEEN :date_from AND :date_to) as total_deposits,
                    (SELECT SUM(amount) FROM transactions WHERE transaction_type = 'withdrawal' AND created_at BETWEEN :date_from AND :date_to) as total_withdrawals
            ");
            $stmt->execute(['date_from' => $date_from, 'date_to' => $date_to]);
            $data = $stmt->fetch();
            break;
    }
    
    return $data;
}

// ============================================
// EXPORT HANDLING
// ============================================

if ($export == 'pdf') {
    // PDF Export using FPDF
    if (!$pdf_enabled) {
        die('FPDF library not found. Please install fpdf in the root directory.');
    }
    
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    
    // Header
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, APP_NAME . ' - ' . $reports[$report_type], 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 10, 'Period: ' . getReportPeriod($date_from, $date_to), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Get data
    $data = getReportData($db, $report_type, $date_from, $date_to, $member_id);
    
    // Build table based on report type
    if ($report_type == 'loans' && !empty($data)) {
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(40, 8, 'Loan #', 1);
        $pdf->Cell(50, 8, 'Member', 1);
        $pdf->Cell(40, 8, 'Amount', 1);
        $pdf->Cell(25, 8, 'Paid', 1);
        $pdf->Cell(40, 8, 'Balance', 1);
        $pdf->Cell(25, 8, 'Status', 1);
        $pdf->Ln();
        
        $pdf->SetFont('Arial', '', 8);
        foreach ($data as $row) {
            $pdf->Cell(40, 6, $row['loan_number'], 1);
            $pdf->Cell(50, 6, $row['last_name'] . ' ' . $row['first_name'], 1);
            $pdf->Cell(40, 6, formatMoney($row['amount']), 1);
            $pdf->Cell(25, 6, formatMoney($row['paid']), 1);
            $pdf->Cell(40, 6, formatMoney($row['amount'] - $row['paid']), 1);
            $pdf->Cell(25, 6, $row['status'], 1);
            $pdf->Ln();
        }
    } elseif ($report_type == 'savings' && !empty($data)) {
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(40, 8, 'Account #', 1);
        $pdf->Cell(50, 8, 'Member', 1);
        $pdf->Cell(40, 8, 'Balance', 1);
        $pdf->Cell(30, 8, 'Deposits', 1);
        $pdf->Cell(30, 8, 'Withdrawals', 1);
        $pdf->Cell(25, 8, 'Status', 1);
        $pdf->Ln();
        
        $pdf->SetFont('Arial', '', 8);
        foreach ($data as $row) {
            $pdf->Cell(40, 6, $row['account_number'], 1);
            $pdf->Cell(50, 6, $row['last_name'] . ' ' . $row['first_name'], 1);
            $pdf->Cell(40, 6, formatMoney($row['balance']), 1);
            $pdf->Cell(30, 6, formatMoney($row['deposits']), 1);
            $pdf->Cell(30, 6, formatMoney($row['withdrawals']), 1);
            $pdf->Cell(25, 6, $row['status'], 1);
            $pdf->Ln();
        }
    } elseif ($report_type == 'financial' && !empty($data)) {
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(60, 10, 'Total Savings:', 0);
        $pdf->Cell(60, 10, formatMoney($data['total_savings'] ?? 0), 0);
        $pdf->Ln();
        $pdf->Cell(60, 10, 'Total Loans Outstanding:', 0);
        $pdf->Cell(60, 10, formatMoney($data['total_loans'] ?? 0), 0);
        $pdf->Ln();
        $pdf->Cell(60, 10, 'Total Repayments:', 0);
        $pdf->Cell(60, 10, formatMoney($data['total_repayments'] ?? 0), 0);
        $pdf->Ln();
    }
    
    $pdf->Output('D', APP_NAME . '_' . $report_type . '_' . date('Ymd') . '.pdf');
    exit();
    
} elseif ($export == 'excel') {
    // Excel Export using HTML table
    $data = getReportData($db, $report_type, $date_from, $date_to, $member_id);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . APP_NAME . '_' . $report_type . '_' . date('Ymd') . '.xls"');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><title>' . APP_NAME . ' Report</title></head>';
    echo '<body>';
    echo '<table border="1">';
    echo '<tr><th colspan="' . count($data[0] ?? []) . '" style="font-size:16px;font-weight:bold;text-align:center;background-color:#4472C4;color:white;">';
    echo APP_NAME . ' - ' . $reports[$report_type];
    echo '</th></tr>';
    echo '<tr><td colspan="' . count($data[0] ?? []) . '" style="text-align:center;">Period: ' . getReportPeriod($date_from, $date_to) . '</td></tr>';
    
    if (!empty($data)) {
        echo '<tr>';
        foreach (array_keys($data[0]) as $header) {
            echo '<th style="background-color:#D9E1F2;font-weight:bold;">' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
        
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $value) {
                echo '<td>' . htmlspecialchars($value) . '</td>';
            }
            echo '</tr>';
        }
    }
    echo '</table>';
    echo '</body></html>';
    exit();
}

// ============================================
// HTML DISPLAY
// ============================================

?>
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-chart-bar mr-2 text-indigo-500"></i>
                <?php echo __('reports'); ?>
            </h2>
            <p class="text-gray-500 text-sm">Generate and view comprehensive reports</p>
        </div>
        <div class="flex space-x-2">
            <a href="?report=<?php echo $report_type; ?>&export=pdf&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-file-pdf mr-2"></i> PDF
            </a>
            <a href="?report=<?php echo $report_type; ?>&export=excel&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-file-excel mr-2"></i> Excel
            </a>
        </div>
    </div>
    
    <!-- Report Navigation -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-4">
        <div class="p-3">
            <div class="flex flex-wrap gap-2">
                <?php foreach ($reports as $key => $label): ?>
                <a href="reports.php?report=<?php echo $key; ?>" class="px-4 py-1.5 rounded-lg text-sm transition <?php echo $report_type == $key ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    <?php echo $label; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Date Filter -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-4">
        <div class="p-3">
            <form method="get" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="report" value="<?php echo $report_type; ?>">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-0.5">From</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="px-3 py-1.5 rounded-lg border border-gray-300 focus:border-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-0.5">To</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="px-3 py-1.5 rounded-lg border border-gray-300 focus:border-indigo-500 text-sm">
                </div>
                <div>
                    <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-sm">
                        <i class="fas fa-filter mr-2"></i> Apply
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Report Content -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="px-5 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-800"><?php echo $reports[$report_type]; ?></h3>
            </div>
            <?php if ($qr_enabled): ?>
            <div>
                <?php
                $qr_data = APP_URL . '/reports.php?report=' . $report_type . '&date_from=' . $date_from . '&date_to=' . $date_to;
                echo generateQRCode($qr_data, 80);
                ?>
                <p class="text-xs text-gray-400 text-center">Scan</p>
            </div>
            <?php endif; ?>
        </div>
        <div class="p-4">
            <?php if ($report_type == 'dashboard'): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <?php
                    $total_members = $db->query("SELECT COUNT(*) FROM members WHERE status = 'active'")->fetchColumn();
                    $total_savings = $db->query("SELECT SUM(balance) FROM savings_accounts WHERE status = 'active'")->fetchColumn();
                    $total_loans = $db->query("SELECT COUNT(*) FROM loans WHERE status NOT IN ('repaid', 'rejected')")->fetchColumn();
                    $total_repayments = $db->query("SELECT SUM(amount) FROM repayments WHERE status = 'completed'")->fetchColumn();
                    ?>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <p class="text-sm text-gray-500">Total Members</p>
                        <p class="text-2xl font-bold"><?php echo $total_members ?? 0; ?></p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <p class="text-sm text-gray-500">Total Savings</p>
                        <p class="text-2xl font-bold"><?php echo formatMoney($total_savings ?? 0); ?></p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <p class="text-sm text-gray-500">Active Loans</p>
                        <p class="text-2xl font-bold"><?php echo $total_loans ?? 0; ?></p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <p class="text-sm text-gray-500">Total Repayments</p>
                        <p class="text-2xl font-bold"><?php echo formatMoney($total_repayments ?? 0); ?></p>
                    </div>
                </div>
            <?php elseif ($report_type == 'loans'): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Loan #</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $loans = $db->query("SELECT l.*, m.first_name, m.last_name FROM loans l JOIN members m ON l.member_id = m.id ORDER BY l.created_at DESC")->fetchAll();
                            foreach ($loans as $loan):
                            ?>
                            <tr>
                                <td><?php echo escape($loan['loan_number']); ?></td>
                                <td><?php echo escape($loan['last_name'] . ' ' . $loan['first_name']); ?></td>
                                <td><?php echo formatMoney($loan['amount']); ?></td>
                                <td><?php echo getStatusBadge($loan['status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($report_type == 'savings'): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account #</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $savings = $db->query("SELECT s.*, m.first_name, m.last_name FROM savings_accounts s JOIN members m ON s.member_id = m.id ORDER BY s.created_at DESC")->fetchAll();
                            foreach ($savings as $saving):
                            ?>
                            <tr>
                                <td><?php echo escape($saving['account_number']); ?></td>
                                <td><?php echo escape($saving['last_name'] . ' ' . $saving['first_name']); ?></td>
                                <td><?php echo formatMoney($saving['balance']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($report_type == 'transactions'): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $transactions = $db->query("SELECT t.*, m.first_name, m.last_name FROM transactions t JOIN members m ON t.member_id = m.id ORDER BY t.created_at DESC")->fetchAll();
                            foreach ($transactions as $transaction):
                            ?>
                            <tr>
                                <td><?php echo formatDate($transaction['created_at']); ?></td>
                                <td><?php echo escape($transaction['last_name'] . ' ' . $transaction['first_name']); ?></td>
                                <td><?php echo ucfirst($transaction['transaction_type']); ?></td>
                                <td><?php echo formatMoney($transaction['amount']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($report_type == 'financial'): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <?php
                    $total_savings = $db->query("SELECT SUM(balance) FROM savings_accounts WHERE status = 'active'")->fetchColumn();
                    $total_loans = $db->query("SELECT SUM(amount) FROM loans WHERE status NOT IN ('repaid', 'rejected')")->fetchColumn();
                    $total_repayments = $db->query("SELECT SUM(amount) FROM repayments WHERE status = 'completed'")->fetchColumn();
                    ?>
                    <div class="bg-green-50 p-3 rounded-lg">
                        <p class="text-sm text-green-800">Total Savings</p>
                        <p class="text-xl font-bold"><?php echo formatMoney($total_savings ?? 0); ?></p>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-sm text-blue-800">Total Loans Outstanding</p>
                        <p class="text-xl font-bold"><?php echo formatMoney($total_loans ?? 0); ?></p>
                    </div>
                    <div class="bg-purple-50 p-3 rounded-lg">
                        <p class="text-sm text-purple-800">Total Repayments</p>
                        <p class="text-xl font-bold"><?php echo formatMoney($total_repayments ?? 0); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>