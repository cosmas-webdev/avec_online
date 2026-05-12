<?php
/**
 * Loans Management
 * 
 * Professional CRUD operations for loans.
 * 
 * @author Ir. Cosmas MUSAFIRI MUGONGO
 * @version 1.0.0
 */

require_once 'includes/auth.php';
requireLogin();

$page_title = __('loans');
include 'includes/header.php';

$db = db();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// ============================================
// CRUD ACTIONS
// ============================================

switch ($action) {
    
    // ============================================
    // CREATE - New Loan
    // ============================================
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $member_id = intval($_POST['member_id']);
            $amount = floatval($_POST['amount']);
            $duration_months = intval($_POST['duration_months']);
            $interest_rate = floatval($_POST['interest_rate'] ?? 5);
            
            if ($amount <= 0) {
                $error = 'Loan amount must be greater than 0.';
            } elseif ($duration_months <= 0) {
                $error = 'Duration must be greater than 0 months.';
            } else {
                try {
                    $db->beginTransaction();
                    
                    $loan_number = generateReference('LOAN');
                    $loan_date = date('Y-m-d');
                    $due_date = date('Y-m-d', strtotime("+$duration_months months"));
                    
                    $db->prepare("
                        INSERT INTO loans (member_id, loan_number, amount, interest_rate, duration_months, loan_date, due_date)
                        VALUES (:member_id, :loan_number, :amount, :interest_rate, :duration_months, :loan_date, :due_date)
                    ")->execute([
                        'member_id' => $member_id,
                        'loan_number' => $loan_number,
                        'amount' => $amount,
                        'interest_rate' => $interest_rate,
                        'duration_months' => $duration_months,
                        'loan_date' => $loan_date,
                        'due_date' => $due_date
                    ]);
                    
                    $loan_id = $db->lastInsertId();
                    
                    $db->commit();
                    logAction('CREATE', 'loans', $loan_id, "New loan of " . formatMoney($amount));
                    redirect('loans.php', 'Loan created successfully!', 'success');
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = $e->getMessage();
                }
            }
        }
        
        // Get members list for dropdown
        $members = $db->query("
            SELECT m.id, m.first_name, m.last_name 
            FROM members m 
            WHERE m.status = 'active'
            ORDER BY m.last_name
        ")->fetchAll();
        ?>
        <div class="container mx-auto px-4 py-6">
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-blue-400 to-blue-600">
                        <h3 class="text-lg font-bold text-white">
                            <i class="fas fa-hand-holding-usd mr-2"></i>
                            New Loan
                        </h3>
                        <p class="text-blue-100 text-xs">Grant a new loan to a member</p>
                    </div>
                    <div class="p-5">
                        <?php if (isset($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-lg mb-4 text-sm"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" class="space-y-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-user mr-2 text-gray-400"></i>
                                    Member <span class="text-red-500">*</span>
                                </label>
                                <select name="member_id" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-blue-500" required>
                                    <option value="">Select a member</option>
                                    <?php foreach ($members as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo escape($member['last_name'] . ' ' . $member['first_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-money-bill mr-2 text-gray-400"></i>
                                    Loan Amount (CDF) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="amount" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-blue-500" min="1" required placeholder="Ex: 100000">
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                                        <i class="fas fa-clock mr-2 text-gray-400"></i>
                                        Duration (months) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="duration_months" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-blue-500" min="1" value="6" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                                        <i class="fas fa-percent mr-2 text-gray-400"></i>
                                        Interest Rate (%) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="interest_rate" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-blue-500" step="0.01" value="5" required>
                                </div>
                            </div>
                            
                            <div class="pt-3 border-t border-gray-200">
                                <button type="submit" class="bg-gradient-to-r from-blue-400 to-blue-600 text-white px-5 py-2 rounded-lg hover:from-blue-500 hover:to-blue-700">
                                    <i class="fas fa-save mr-2"></i>
                                    Grant Loan
                                </button>
                                <a href="loans.php" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;
    
    // ============================================
    // UPDATE - Approve Loan
    // ============================================
    case 'approve':
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['loan_id'])) {
            $loan_id = intval($_POST['loan_id']);
            
            try {
                $db->beginTransaction();
                $db->prepare("UPDATE loans SET status = 'approved', approved_by = :approved_by, approved_at = NOW() WHERE id = :id")
                   ->execute(['approved_by' => session_get('user_id'), 'id' => $loan_id]);
                $db->commit();
                logAction('APPROVE', 'loans', $loan_id, "Loan approved");
                redirect('loans.php', 'Loan approved successfully!', 'success');
            } catch (Exception $e) {
                $db->rollBack();
                $error = $e->getMessage();
            }
        }
        break;
    
    // ============================================
    // UPDATE - Disburse Loan
    // ============================================
    case 'disburse':
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['loan_id'])) {
            $loan_id = intval($_POST['loan_id']);
            
            try {
                $db->beginTransaction();
                
                // Get loan details
                $loan = $db->prepare("SELECT * FROM loans WHERE id = :id");
                $loan->execute(['id' => $loan_id]);
                $loan_data = $loan->fetch();
                
                if (!$loan_data) {
                    throw new Exception('Loan not found.');
                }
                
                // Update loan status
                $db->prepare("UPDATE loans SET status = 'disbursed', disbursed_at = NOW() WHERE id = :id")
                   ->execute(['id' => $loan_id]);
                
                // Record transaction
                $db->prepare("
                    INSERT INTO transactions (savings_account_id, member_id, transaction_type, amount, description, created_by)
                    SELECT s.id, :member_id, 'loan_disbursement', :amount, 'Loan disbursement', :created_by
                    FROM savings_accounts s
                    WHERE s.member_id = :member_id
                ")->execute([
                    'member_id' => $loan_data['member_id'],
                    'amount' => $loan_data['amount'],
                    'created_by' => session_get('user_id')
                ]);
                
                $db->commit();
                logAction('DISBURSE', 'loans', $loan_id, "Loan disbursed of " . formatMoney($loan_data['amount']));
                redirect('loans.php', 'Loan disbursed successfully!', 'success');
            } catch (Exception $e) {
                $db->rollBack();
                $error = $e->getMessage();
            }
        }
        break;
    
    // ============================================
    // UPDATE - Reject Loan
    // ============================================
    case 'reject':
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['loan_id'])) {
            $loan_id = intval($_POST['loan_id']);
            
            try {
                $db->beginTransaction();
                $db->prepare("UPDATE loans SET status = 'rejected' WHERE id = :id")
                   ->execute(['id' => $loan_id]);
                $db->commit();
                logAction('REJECT', 'loans', $loan_id, "Loan rejected");
                redirect('loans.php', 'Loan rejected.', 'warning');
            } catch (Exception $e) {
                $db->rollBack();
                $error = $e->getMessage();
            }
        }
        break;
    
    // ============================================
    // DELETE - Delete Loan
    // ============================================
    case 'delete':
        if ($id > 0) {
            try {
                $db->beginTransaction();
                
                // Get loan details for logging
                $loan = $db->prepare("SELECT * FROM loans WHERE id = :id");
                $loan->execute(['id' => $id]);
                $loan_data = $loan->fetch();
                
                if (!$loan_data) {
                    throw new Exception('Loan not found.');
                }
                
                // Delete loan
                $db->prepare("DELETE FROM loans WHERE id = :id")->execute(['id' => $id]);
                
                $db->commit();
                logAction('DELETE', 'loans', $id, "Loan deleted - " . formatMoney($loan_data['amount']));
                redirect('loans.php', 'Loan deleted successfully!', 'success');
            } catch (Exception $e) {
                $db->rollBack();
                $error = $e->getMessage();
            }
        }
        break;
    
    // ============================================
    // READ - List Loans
    // ============================================
    default:
        ?>
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-hand-holding-usd mr-2 text-blue-500"></i>
                        <?php echo __('loans'); ?>
                    </h2>
                    <p class="text-gray-500 text-sm">Manage all loans and their status</p>
                </div>
                <a href="loans.php?action=add" class="bg-gradient-to-r from-blue-400 to-blue-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-plus mr-2"></i>
                    New Loan
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">N°</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loan Number</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Interest</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $loans = $db->query("
                                SELECT l.*, m.first_name, m.last_name 
                                FROM loans l
                                JOIN members m ON l.member_id = m.id
                                ORDER BY l.created_at DESC
                            ")->fetchAll();
                            
                            foreach ($loans as $index => $loan):
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $index + 1; ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500 font-mono"><?php echo escape($loan['loan_number']); ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold">
                                            <?php echo substr($loan['first_name'], 0, 1) . substr($loan['last_name'], 0, 1); ?>
                                        </div>
                                        <span class="ml-2"><?php echo escape($loan['last_name'] . ' ' . $loan['first_name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-800 font-semibold">
                                    <?php echo formatMoney($loan['amount']); ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $loan['interest_rate']; ?>%
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $loan['duration_months']; ?> months
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap"><?php echo getStatusBadge($loan['status']); ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <?php if ($loan['status'] == 'pending'): ?>
                                            <form method="post" action="loans.php?action=approve" class="inline">
                                                <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                                <button type="submit" class="text-green-600 hover:text-green-900">Approve</button>
                                            </form>
                                            <form method="post" action="loans.php?action=reject" class="inline">
                                                <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">Reject</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($loan['status'] == 'approved'): ?>
                                            <form method="post" action="loans.php?action=disburse" class="inline">
                                                <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                                <button type="submit" class="text-blue-600 hover:text-blue-900">Disburse</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="loans.php?action=delete&id=<?php echo $loan['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 bg-gray-50 border-t border-gray-200 text-center">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-database mr-2"></i>
                        Total: <?php echo count($loans); ?> loans
                    </p>
                </div>
            </div>
        </div>
        <?php
        break;
}

include 'includes/footer.php';