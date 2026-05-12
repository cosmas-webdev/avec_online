<!DOCTYPE html>
<html lang="<?php echo get_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo APP_NAME; ?> - Système de gestion pour Associations Villageoises d'Épargne et de Crédit">
    <meta name="author" content="Ir. Cosmas MUSAFIRI MUGONGO">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        .sidebar { width: 260px; height: 100vh; position: fixed; top: 0; left: 0; z-index: 1000; background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); color: #e2e8f0; transition: all 0.3s ease; box-shadow: 4px 0 20px rgba(0,0,0,0.1); overflow-y: auto; }
        .main-content { margin-left: 260px; padding: 24px 32px; min-height: 100vh; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
        }
        .flash-message { animation: slideIn 0.4s cubic-bezier(0.4,0,0.2,1) forwards; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border-radius: 12px; }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php if (isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php'): ?>
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
    <?php endif; ?>
    
    <?php if ($message = flash_get('message')): ?>
        <div class="fixed top-4 right-4 z-50 max-w-md flash-message">
            <div class="bg-<?php echo flash_get('message_type') ?? 'info'; ?>-100 border border-<?php echo flash_get('message_type') ?? 'info'; ?>-400 text-<?php echo flash_get('message_type') ?? 'info'; ?>-700 px-4 py-3 rounded-lg shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex-1 font-medium"><?php echo $message; ?></div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-400 hover:text-gray-600 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>