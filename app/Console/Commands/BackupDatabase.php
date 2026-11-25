<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'clinic:backup';
    protected $description = 'Encrypted database backup (scheduled every 6 hours)';

    public function handle(): int
    {
        $cfg = config('clinic.backup');
        $conn = config('database.connections.'.config('database.default'));
        $name = $cfg['directory'].'/'.now()->format('Ymd-His').'.sql.enc';

        if ($conn['driver'] === 'sqlite') {
            $raw = file_get_contents($conn['database']);
        } else {
            // Password goes through the environment, never the command line (visible in `ps`).
            $p = new Process(['mysqldump', '--single-transaction', '-h', $conn['host'], '-u', $conn['username'], $conn['database']], null, ['MYSQL_PWD' => $conn['password']]);
            $p->setTimeout(600)->mustRun();
            $raw = $p->getOutput();
        }

        Storage::disk($cfg['disk'])->put($name, Crypt::encryptString(base64_encode($raw)));
        $this->prune($cfg);
        $this->info("Backup stored: $name");

        return self::SUCCESS;
    }

    private function prune(array $cfg): void
    {
        $disk = Storage::disk($cfg['disk']);
        $files = collect($disk->files($cfg['directory']))->sortDesc()->values();
        $files->slice($cfg['keep'])->each(fn ($f) => $disk->delete($f));
    }
}

/*
SYSTEM DESIGN:
Encrypted backup every 6 h (Rule 43) with retention (keep 28 = 7 days). Stored on a private disk; ship it off-site with rclone/S3 in production. Restore: Crypt::decryptString -> base64_decode.

DATA STRUCTURE:
Rolling window of N newest files (sort desc + slice). Ciphertext = AES-256 via APP_KEY.
*/
