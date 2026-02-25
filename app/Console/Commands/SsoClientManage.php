<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Passport\Client;

class SsoClientManage extends Command
{
    

    protected $signature = 'sso:clients
        {action=list : Action to perform (list, create, revoke, refresh-secret)}
        {--name= : Client name (for create)}
        {--redirect= : Redirect URI (for create)}
        {--id= : Client ID (for revoke, refresh-secret)}';

    

    protected $description = 'Manage SSO OAuth2 clients for connected applications';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'list' => $this->listClients(),
            'create' => $this->createClient(),
            'revoke' => $this->revokeClient(),
            'refresh-secret' => $this->refreshSecret(),
            default => $this->invalidAction(),
        };
    }

    private function listClients(): int
    {
        $clients = Client::where('grant_types', 'LIKE', '%authorization_code%')
            ->get();

        if ($clients->isEmpty()) {
            $this->warn('No SSO clients found. Run `php artisan db:seed --class=SsoClientSeeder` to create them.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Redirect URIs', 'Grant Types', 'Revoked', 'Created At'],
            $clients->map(function (Client $client) {
                $redirectUris = is_array($client->redirect_uris)
                    ? implode(', ', $client->redirect_uris)
                    : (string) $client->redirect_uris;

                $grantTypes = is_array($client->grant_types)
                    ? implode(', ', $client->grant_types)
                    : (string) $client->grant_types;

                return [
                    $client->id,
                    $client->name,
                    $redirectUris,
                    $grantTypes,
                    $client->revoked ? '✗ Yes' : '✓ No',
                    $client->created_at->format('Y-m-d H:i:s'),
                ];
            })
        );

        return self::SUCCESS;
    }

    private function createClient(): int
    {
        $name = $this->option('name') ?? $this->ask('Client name');
        $redirect = $this->option('redirect') ?? $this->ask('Redirect URI');

        if (!$name || !$redirect) {
            $this->error('Both --name and --redirect are required.');
            return self::FAILURE;
        }

        if (!filter_var($redirect, FILTER_VALIDATE_URL)) {
            $this->error('Invalid redirect URI format.');
            return self::FAILURE;
        }

        $client = new Client();
        $client->id = Str::uuid()->toString();
        $client->name = $name;
        $client->secret = Str::random(40);
        $client->redirect_uris = [$redirect];
        $client->grant_types = ['authorization_code', 'refresh_token'];
        $client->revoked = false;
        $client->save();

        $this->info('SSO Client created successfully!');
        $this->newLine();
        $this->line("  Client ID:     {$client->id}");
        $this->line("  Client Secret: {$client->plainSecret}");
        $this->line("  Redirect URIs: " . implode(', ', $client->redirect_uris));
        $this->newLine();
        $this->warn('Store the Client Secret securely — it cannot be retrieved later.');

        return self::SUCCESS;
    }

    private function revokeClient(): int
    {
        $id = $this->option('id') ?? $this->ask('Client ID to revoke');

        $client = Client::find($id);

        if (!$client) {
            $this->error("Client with ID {$id} not found.");
            return self::FAILURE;
        }

        if ($client->revoked) {
            $this->warn("Client '{$client->name}' is already revoked.");
            return self::SUCCESS;
        }

        if (!$this->confirm("Are you sure you want to revoke '{$client->name}'?")) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        $client->revoked = true;
        $client->save();

        $this->info("Client '{$client->name}' has been revoked.");

        return self::SUCCESS;
    }

    private function refreshSecret(): int
    {
        $id = $this->option('id') ?? $this->ask('Client ID to refresh secret');

        $client = Client::find($id);

        if (!$client) {
            $this->error("Client with ID {$id} not found.");
            return self::FAILURE;
        }

        if (!$this->confirm("Are you sure you want to regenerate the secret for '{$client->name}'? The old secret will stop working immediately.")) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        $client->secret = Str::random(40);
        $client->save();

        $this->info("Secret refreshed for '{$client->name}'.");
        $this->newLine();
        $this->line("  New Client Secret: {$client->plainSecret}");
        $this->newLine();
        $this->warn('Update the client app .env file with the new secret.');

        return self::SUCCESS;
    }

    private function invalidAction(): int
    {
        $this->error('Invalid action. Available actions: list, create, revoke, refresh-secret');
        return self::FAILURE;
    }
}