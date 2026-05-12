<?php
/**
 * Repayments Management
 * 
 * Professional CRUD operations for loan repayments.
 * 
 * @author Ir. Cosmas MUSAFIRI MUGONGO
 * @version 1.0.0
 */

require_once 'includes/auth.php';
requireLogin();

$page_title = __('repayments');
include 'includes/header.php';

$db = db();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// ============================================
// CRUD ACTIONS
// ============================================

switch ($action) {
    
    // ============================================
    // CREATE - New Repayment
    // ============================================
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $loan_id = intval($_POST['loan_id']);
            $member_id = intval($_POST['member_id']);
            $amount = floatval($_POST['amount']);
            $payment_method = $_POST['payment_method'];
            $reference = trim($_POST['reference'] ?? '');
            
            if ($amount <= 0) {
                $error = 'Amount must be greater than 0.';
            } else {
                try {
                    $db->beginTransaction();
                    
                    // Check loan exists and is not repaid
                    $loan = $db->prepare("SELECT * FROM loans WHERE id = :id");
                    $loan->execute(['id' => $loan_id]);
                    $loan_data = $loan->fetch();
                    
                    if (!$loan_data) {
                        throw new Exception('Loan not found.');
                    }
                    
                    if ($loan_data['status'] == 'repaid') {
                        throw new Exception('This loan is already fully repaid.');
                    }
                    
                    // Insert repayment
                    $db->prepare("
                        INSERT INTO repayments (loan_id, member_id, amount, payment_date, payment_method, reference, status)
                        VALUES (:loan_id, :member_id, :amount, NOW(), :payment_method, :reference, 'completed')
                    ")->execute([
                        'loan_id' => $loan_id,
                        'member_id' => $member_id,
                        'amount' => $amount,
                        'payment_method' => $payment_method,
                        'reference' => $reference
                    ]);
                    
                    $repayment_id = $db->lastInsertId();
                    
                    // Check if loan is fully repaid
                    $total_repaid = $db->prepare("
                        SELECT SUM(amount) FROM repayments WHERE loan_id = :loan_id AND status = 'completed'
                    ");
                    $total_repaid->execute(['loan_id' => $loan_id]);
                    $total = $total_repaid->fetchColumn();
                    
                    if ($total >= $loan_data['amount']) {
                        $db->prepare("UPDATE loans SET status = 'repaid' WHERE id = :id")
                           ->execute(['id' => $loan_id]);
                    }
                    
                    // Record transaction
                    $db->prepare("
                        INSERT INTO transactions (savings_account_id, member_id, transaction_type, amount, description, created_by)
                        SELECT s.id, :member_id, 'loan_repayment', :amount, 'Loan repayment', :created_by
                        FROM savings_accounts s
                        WHERE s.member_id = :member_id
                    ")->execute([
                        'member_id' => $member_id,
                        'amount' => $amount,
                        'created_by' => session_get('user_id')
                    ]);
                    
                    $db->commit();
                    logAction('CREATE', 'repayments', $repayment_id, "Repayment of " . formatMoney($amount));
                    redirect('repayments.php', 'Repayment recorded successfully!', 'success');
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = $e->getMessage();
                }
            }
        }
        
        // Get loans list for dropdown
        $loans = $db->query("
            SELECT l.id, l.loan_number, l.amount, m.first_name, m.last_name 
            FROM loans l
            JOIN members m ON l.member_id = m.id
            WHERE l.status NOT IN ('repaid', 'rejected')
            ORDER BY l.created_at DESC
        ")->fetchAll();
        ?>
        <div class="container mx-auto px-4 py-6">
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-purple-400 to-purple-600">
                        <h3 class="text-lg font-bold text-white">
                            <i class="fas fa-undo-alt mr-2"></i>
                            New Repayment
                        </h3>
                        <p class="text-purple-100 text-xs">Record a loan repayment from a member</p>
                    </div>
                    <div class="p-5">
                        <?php if (isset($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-lg mb-4 text-sm"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" class="space-y-4">
                            <input type="hidden" name="member_id" id="member_id" value="">
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-file-invoice mr-2 text-gray-400"></i>
                                    Loan <span class="text-red-500">*</span>
                                </label>
                                <select name="loan_id" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-purple-500" required>
                                    <option value="">Select a loan</option>
                                    <?php foreach ($loans as $loan): ?>
                                    <option value="<?php echo $loan['id']; ?>" data-member="<?php echo $loan['member_id']; ?>">
                                        <?php echo escape($loan['loan_number']); ?> - 
                                        <?php echo escape($loan['last_name'] . ' ' . $loan['first_name']); ?> 
                                        (<?php echo formatMoney($loan['amount']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-money-bill mr-2 text-gray-400"></i>
                                    Amount (CDF) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="amount" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-purple-500" min="1" required placeholder="Ex: 50000">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-credit-card mr-2 text-gray-400"></i>
                                    Payment Method <span class="text-red-500">*</span>
                                </label>
                                <select name="payment_method" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-purple-500" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                    <option value="mobile">Mobile Money</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-hashtag mr-2 text-gray-400"></i>
                                    Reference
                                </label>
                                <input type="text" name="reference" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-purple-500" placeholder="Transaction reference (optional)">
                            </div>
                            
                            <div class="pt-3 border-t border-gray-200">
                                <button type="submit" class="bg-gradient-to-r from-purple-400 to-purple-600 text-white px-5 py-2 rounded-lg hover:from-purple-500 hover:to-purple-700">
                                    <i class="fas fa-save mr-2"></i>
                                    Record Repayment
                                </button>
                                <a href="repayments.php" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Auto-fill member_id when loan is selected
        document.querySelector('select[name="loan_id"]').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const memberId = selected.getAttribute('data-member');
            document.getElementById('member_id').value = memberId;
        });
        </script>
        <?php
        break;
    
    // ============================================
    // UPDATE - Edit Repayment
    // ============================================
    case 'edit':
        $repayment = $db->prepare("
            SELECT r.*, l.loan_number, m.first_name, m.last_name 
            FROM repayments r
            JOIN loans l ON r.loan_id = l.id
            JOIN members m ON r.member_id = m.id
            WHERE r.id = :id
        ")->execute(['id' => $id])->fetch();
        
        if (!$repayment) {
            redirect('repayments.php', 'Repayment not found', 'danger');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $amount = floatval($_POST['amount']);
            $payment_method = $_POST['payment_method'];
            $reference = trim($_POST['reference'] ?? '');
            $status = $_POST['status'];
            
            try {
                $db->beginTransaction();
                $db->prepare("
                    UPDATE repayments SET 
                    amount = :amount, 
                    payment_method = :payment_method, 
                    reference = :reference, 
                    status = :status 
                    WHERE id = :id
                ")->execute([
                    'amount' => $amount,
                    'payment_method' => $payment_method,
                    'reference' => $reference,
                    'status' => $status,
                    'id' => $id
                ]);
                $db->commit();
                logAction('UPDATE', 'repayments', $id, "Updated repayment");
                redirect('repayments.php', 'Repayment updated successfully!', 'success');
            } catch (Exception $e) {
                $db->rollBack();
                $error = $e->getMessage();
            }
        }
        ?>
        <div class="container mx-auto px-4 py-6">
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-yellow-400 to-yellow-600">
                        <h3 class="text-lg font-bold text-white">
                            <i class="fas fa-edit mr-2"></i>
                            Edit Repayment
                        </h3>
                        <p class="text-yellow-100 text-xs">Update repayment details</p>
                    </div>
                    <div class="p-5">
                        <?php if (isset($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-lg mb-4 text-sm"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" class="space-y-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-money-bill mr-2 text-gray-400"></i>
                                    Amount (CDF) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="amount" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500" min="1" required value="<?php echo escape($repayment['amount']); ?>">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-credit-card mr-2 text-gray-400"></i>
                                    Payment Method <span class="text-red-500">*</span>
                                </label>
                                <select name="payment_method" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500" required>
                                    <option value="cash" <?php echo $repayment['payment_method'] == 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="bank" <?php echo $repayment['payment_method'] == 'bank' ? 'selected' : ''; ?>>Bank</option>
                                    <option value="mobile" <?php echo $repayment['payment_method'] == 'mobile' ? 'selected' : ''; ?>>Mobile Money</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-hashtag mr-2 text-gray-400"></i>
                                    Reference
                                </label>
                                <input type="text" name="reference" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500" value="<?php echo escape($repayment['reference']); ?>">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-info-circle mr-2 text-gray-400"></i>
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500" required>
                                    <option value="pending" <?php echo $repayment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?php echo $repayment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="overdue" <?php echo $repayment['status'] == 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                </select>
                            </div>
                            
                            <div class="pt-3 border-t border-gray-200">
                                <button type="submit" class="bg-gradient-to-r from-yellow-400 to-yellow-600 text-white px-5 py-2 rounded-lg hover:from-yellow-500 hover:to-yellow-700">
                                    <i class="fas fa-save mr-2"></i>
                                    Update
                                </button>
                                <a href="repayments.php" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300">
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
    // DELETE - Delete Repayment
    // ============================================
    case 'delete':
        if ($id > 0) {
            try {
                $db->beginTransaction();
                
                // Get repayment details for logging
                $repayment = $db->prepare("SELECT * FROM repayments WHERE id = :id");
                $repayment->execute(['id' => $id]);
                $repayment_data = $repayment->fetch();
                
                if (!$repayment_data) {
                    throw new Exception('Repayment not found.');
                }
                
                // Delete repayment
                $db->prepare("DELETE FROM repayments WHERE id = :id")->execute(['id' => $id]);
                
                // Check if loan status needs to be updated back
                $loan = $db->prepare("SELECT * FROM loans WHERE id = :id");
                $loan->execute(['id' => $repayment_data['loan_id']]);
                $loan_data = $loan->fetch();
                
                if ($loan_data['status'] == 'repaid') {
                    $total_repaid = $db->prepare("
                        SELECT SUM(amount) FROM repayments WHERE loan_id = :loan_id AND status = 'completed'
                    ");
                    $total_repaid->execute(['loan_id' => $repayment_data['loan_id']]);
                    $total = $total_repaid->fetchColumn();
                    
                    if ($total < $loan_data['amount']) {
                        $db->prepare("UPDATE loans SET status = 'disbursed' WHERE id = :id")
                           ->execute(['id' => $repayment_data['loan_id']]);
                    }
                }
                
                $db->commit();
                logAction('DELETE', 'repayments', $id, "Repayment deleted - " . formatMoney($repayment_data['amount']));
                redirect('repayments.php', 'Repayment deleted successfully!', 'success');
            } catch (Exception $e) {
                $db->rollBack();
                $error = $e->getMessage();
            }
        }
        break;
    
    // ============================================
    // READ - List Repayments
    // ============================================
    default:
        ?>
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-undo-alt mr-2 text-purple-500"></i>
                        <?php echo __('repayments'); ?>
                    </h2>
                    <p class="text-gray-500 text-sm">Track all loan repayments</p>
                </div>
                <a href="repayments.php?action=add" class="bg-gradient-to-r from-purple-400 to-purple-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-plus mr-2"></i>
                    New Repayment
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">N°</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loan</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $repayments = $db->query("
                                SELECT r.*, l.loan_number, m.first_name, m.last_name 
                                FROM repayments r
                                JOIN loans l ON r.loan_id = l.id
                                JOIN members m ON r.member_id = m.id
                                ORDER BY r.created_at DESC
                            ")->fetchAll();
                            
                            foreach ($repayments as $index => $repayment):
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $index + 1; ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500 font-mono"><?php echo escape($repayment['loan_number']); ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 text-xs font-bold">
                                            <?php echo substr($repayment['first_name'], 0, 1) . substr($repayment['last_name'], 0, 1); ?>
                                        </div>
                                        <span class="ml-2"><?php echo escape($repayment['last_name'] . ' ' . $repayment['first_name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-800 font-semibold">
                                    <?php echo formatMoney($repayment['amount']); ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($repayment['payment_date'], 'M d, Y'); ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="capitalize"><?php echo $repayment['payment_method']; ?></span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap"><?php echo getStatusBadge($repayment['status']); ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="repayments.php?action=edit&id=<?php echo $repayment['id']; ?>" class="text-yellow-600 hover:text-yellow-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="repayments.php?action=delete&id=<?php echo $repayment['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
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
                        Total: <?php echo count($repayments); ?> repayments
                    </p>
                </div>
            </div>
        </div>
        <?php
        break;
}

include 'includes/footer.php';