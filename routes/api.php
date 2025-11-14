<?php

declare(strict_types=1);

// Routes are now loaded from modules
// This file is kept for backward compatibility but routes are registered via modules
// See bootstrap/modules-register.php for registered modules

return function ($app): void {
    // All routes are now loaded from modules via Service Providers
    // Modules register their routes in their ServiceProvider::boot() method
};
