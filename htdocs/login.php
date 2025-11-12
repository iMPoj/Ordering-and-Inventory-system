<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory Management</title>
    <script src="https://cdn.tailwindcss.com"></script> <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> </head>
<body class="bg-background-color"> <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-card-background p-8 rounded-xl shadow-lg">
            
            <div class="flex items-center justify-center gap-2 mb-4">
                <img src="reckitt-logo.png" alt="Reckitt Logo" class="h-14 w-auto flex-shrink-0">
                <span class="font-bold text-3xl" style="color: var(--primary-color); font-family: 'Lato', sans-serif;">Reckitt</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-text-primary text-center">Inventory System Login</h1>
            <p class="text-text-secondary text-center mb-8">Please enter your credentials to proceed.</p>

            <form id="login-form" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-text-primary">Username</label>
                    <input type="text" id="username" name="username" required 
                           class="mt-1 block w-full px-3 py-2 bg-white border border-border-color rounded-md shadow-sm placeholder-stone-400 focus:outline-none focus:ring-primary-color focus:border-primary-color sm:text-sm">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-text-primary">Password</label>
                    <input type="password" id="password" name="password" required
                           class="mt-1 block w-full px-3 py-2 bg-white border border-border-color rounded-md shadow-sm placeholder-stone-400 focus:outline-none focus:ring-primary-color focus:border-primary-color sm:text-sm">
                </div>

                <div>
                    <p id="error-message" class="text-sm text-red-600 text-center hidden"></p>
                </div>

                <div>
                    <button type="submit" class="w-full btn btn-primary">
                        Login
                    </button>
                </div>
            </form>
            <div class="mt-6 relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-border-color"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-card-background text-text-secondary">Or</span>
                </div>
            </div>

            <div class="mt-6">
                 <a href="index.php" class="w-full text-center btn btn-secondary">
                    Proceed as Viewer
                </a>
            </div>
        </div>
    </div>

    <script src="login.js"></script>
</body>
</html>