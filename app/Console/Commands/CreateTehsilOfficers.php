<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Models\Tehsil;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateTehsilOfficers extends Command
{
    protected $signature = 'users:tehsil-officers
        {--credentials= : CSV of email,password to use; created with generated passwords if it does not exist}
        {--password= : Use this same password for every tehsil officer (overrides the CSV)}
        {--reset : Replace passwords of officers that already exist}';

    protected $description = 'Create one Field Officer per tehsil (field.<tehsil>@prmsc.punjab.gov.pk) assigned only to that tehsil';

    public function handle(): int
    {
        $credentialsPath = $this->option('credentials') ?: storage_path('app/private/tehsil-officers.csv');
        $knownPasswords = $this->readCredentials($credentialsPath);

        $tehsils = Tehsil::orderBy('name')
            ->whereIn('id', Site::whereIn('status', ['ongoing', 'tendering'])->select('tehsil_id'))
            ->get();

        $rows = [];
        foreach ($tehsils as $tehsil) {
            $email = 'field.'.Str::slug($tehsil->name, '').'@prmsc.punjab.gov.pk';
            $user = User::where('email', $email)->first();
            $password = $this->option('password') ?: ($knownPasswords[$email] ?? null);
            $setPassword = $password !== null || ! $user || $this->option('reset');
            $password ??= $setPassword ? Str::password(10, symbols: false) : null;

            $user ??= new User(['email' => $email]);
            $user->fill([
                'name' => "Field Officer {$tehsil->name}",
                'designation' => 'Field Inspector',
                'is_active' => true,
                'force_password_change' => false,
            ]);
            if ($setPassword) {
                $user->password = Hash::make($password);
                $user->failed_login_attempts = 0;
                $user->locked_until = null;
            }
            $wasNew = ! $user->exists;
            $user->save();
            $user->syncRoles(['Field Officer']);
            $user->tehsils()->sync([$tehsil->id]);

            $rows[] = [
                'tehsil' => $tehsil->name,
                'email' => $email,
                'password' => $password ?? '(unchanged)',
                'status' => $wasNew ? 'created' : ($setPassword ? 'updated' : 'kept'),
            ];
        }

        $this->table(['Tehsil', 'Email', 'Password', 'Status'], $rows);
        $this->writeCredentials($credentialsPath, $rows, $knownPasswords);
        $this->info("Credentials saved to {$credentialsPath}");

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function readCredentials(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $passwords = [];
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '\\');
        while (($line = fgetcsv($handle, escape: '\\')) !== false) {
            $record = array_combine($header, $line);
            if (! empty($record['email']) && ! empty($record['password'])) {
                $passwords[$record['email']] = $record['password'];
            }
        }
        fclose($handle);

        return $passwords;
    }

    /**
     * @param  array<int, array{tehsil: string, email: string, password: string, status: string}>  $rows
     * @param  array<string, string>  $knownPasswords
     */
    private function writeCredentials(string $path, array $rows, array $knownPasswords): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $handle = fopen($path, 'w');
        fputcsv($handle, ['tehsil', 'email', 'password'], escape: '\\');
        foreach ($rows as $row) {
            $password = $row['password'] === '(unchanged)' ? ($knownPasswords[$row['email']] ?? '') : $row['password'];
            fputcsv($handle, [$row['tehsil'], $row['email'], $password], escape: '\\');
        }
        fclose($handle);
    }
}
