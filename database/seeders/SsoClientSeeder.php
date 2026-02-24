<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Laravel\Passport\Client;

class SsoClientSeeder extends Seeder
{
    /**
     * SSO client applications configuration.
     */
    private array $clients = [
        [
            'name' => 'Ecommerce App',
            'redirect_uris' => ['http://127.0.0.1:8001/auth/callback'],
            'env_prefix' => 'ECOMMERCE',
        ],
        [
            'name' => 'Foodpanda App',
            'redirect_uris' => ['http://127.0.0.1:8002/auth/callback'],
            'env_prefix' => 'FOODPANDA',
        ],
    ];


    public function run(): void
    {
        foreach ($this->clients as $clientConfig) {
            $existingClient = Client::where('name', $clientConfig['name'])->first();

            if ($existingClient) {
                $redirectUris = is_array($existingClient->redirect_uris)
                    ? implode(', ', $existingClient->redirect_uris)
                    : (string) $existingClient->redirect_uris;

                $this->command->info("Client '{$clientConfig['name']}' already exists (ID: {$existingClient->id}).");
                $this->command->info("  Secret: {$existingClient->secret}");
                $this->command->info("  Redirect URIs: {$redirectUris}");
                $this->command->newLine();
                continue;
            }

            $client = $this->createClient($clientConfig);

            $redirectUris = implode(', ', $clientConfig['redirect_uris']);

            $this->command->info("Created client: {$client->name}");
            $this->command->info("  Client ID: {$client->id}");
            $this->command->info("  Client Secret: {$client->secret}");
            $this->command->info("  Redirect URIs: {$redirectUris}");
            $this->command->newLine();
            $this->command->warn("Add these to your {$clientConfig['env_prefix']} app's .env file:");
            $this->command->line("  SSO_CLIENT_ID={$client->id}");
            $this->command->line("  SSO_CLIENT_SECRET={$client->secret}");
            $this->command->newLine();
        }
    }


    private function createClient(array $config): Client
    {
        $client = new Client();
        $client->id = Str::uuid()->toString();
        $client->name = $config['name'];
        $client->secret = Str::random(40);
        $client->redirect_uris = $config['redirect_uris'];
        $client->grant_types = ['authorization_code', 'refresh_token'];
        $client->revoked = false;
        $client->save();

        return $client;
    }
}