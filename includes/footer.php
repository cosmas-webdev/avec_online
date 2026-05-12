    <?php if (isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php'): ?>
        </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-auto py-4">
        <div class="container mx-auto px-4 text-center">
            <div class="text-sm text-gray-300">
                <i class="fas fa-copyright mr-1"></i>
                <?php echo date('Y'); ?> <span class="font-semibold">Ir. Cosmas MUSAFIRI MUGONGO</span>. All rights reserved.
            </div>
            <div class="text-xs text-gray-400 mt-1">
                <?php echo APP_NAME; ?> - Version 1.0.0
            </div>
        </div>
    </footer>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>