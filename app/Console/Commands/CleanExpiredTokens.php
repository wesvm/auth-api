<?php

namespace App\Console\Commands;

use App\Models\Token;
use Illuminate\Console\Command;

class CleanExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-expired-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired tokens';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleted = Token::where('expires_at', '<', now())->delete();
        $this->info("Deleted {$deleted} expired tokens");
        cache()->flush();

        return 0;
    }
}
