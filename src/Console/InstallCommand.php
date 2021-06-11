<?php

namespace Nrikiji\BreezeApi\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'breeze-api:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Breeze Api controllers';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Controllers...
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Controllers/Auth'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/App/Http/Controllers/Auth', app_path('Http/Controllers/Auth'));

        // Requests...
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Requests/Auth'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/App/Http/Requests/Auth', app_path('Http/Requests/Auth'));

        // Middleware...
        copy(__DIR__.'/../../stubs/App/Http/Middleware/HandleAuthApiRequests.php', app_path('Http/Middleware/HandleApiAuthRequests.php'));

        // Providers...
        copy(__DIR__.'/../../stubs/App/Providers/AuthServiceProvider.php', app_path('Providers/AuthServiceProvider.php'));

        // Tests...
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/tests/Feature', base_path('tests/Feature'));

        // Routes...
        copy(__DIR__.'/../../stubs/routes/api.php', base_path('routes/api.php'));
        copy(__DIR__.'/../../stubs/routes/auth.php', base_path('routes/auth.php'));

        $this->info('Breeze Api scaffolding installed successfully.');
    }
}