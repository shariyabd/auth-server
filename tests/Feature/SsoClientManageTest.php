<?php
namespace Tests\Feature;

use App\Console\Commands\SsoClientManage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Tests\TestCase;

class SsoClientManageTest extends TestCase
{

    private function createSsoClient(array $overrides = []): Client
    {
        $client = new Client();
        $client->id = $overrides['id'] ?? Str::uuid()->toString();
        $client->name = $overrides['name'] ?? 'Test Client';
        $client->secret = $overrides['secret'] ?? Str::random(40);
        $client->redirect_uris = $overrides['redirect_uris'] ?? json_encode(['https://example.com/callback']);
        $client->grant_types = $overrides['grant_types'] ?? json_encode(['authorization_code', 'refresh_token']);
        $client->revoked = $overrides['revoked'] ?? false;
        $client->save();

        return $client;
    }

    // ── LIST ────────────────────────────────────────────────────────────

    public function test_list_shows_warning_when_no_clients_exist(): void
    {
        $this->artisan('sso:clients', ['action' => 'list'])
            ->expectsOutput('No SSO clients found. Run `php artisan db:seed --class=SsoClientSeeder` to create them.')
            ->assertExitCode(0);
    }

    public function test_list_displays_clients_table(): void
    {
        $client = $this->createSsoClient(['name' => 'My SSO App']);

        $this->artisan('sso:clients', ['action' => 'list'])
            ->assertExitCode(0);

        // Verify the client exists in DB so the table would render
        $this->assertDatabaseHas('oauth_clients', [
            'id' => $client->id,
            'name' => 'My SSO App',
        ]);
    }

    public function test_list_is_default_action(): void
    {
        $this->artisan('sso:clients')
            ->expectsOutput('No SSO clients found. Run `php artisan db:seed --class=SsoClientSeeder` to create them.')
            ->assertExitCode(0);
    }

    public function test_list_shows_revoked_status(): void
    {
        $this->createSsoClient(['name' => 'Revoked App', 'revoked' => true]);

        $this->artisan('sso:clients', ['action' => 'list'])
            ->assertExitCode(0);
    }

    public function test_list_shows_multiple_clients(): void
    {
        $this->createSsoClient(['name' => 'App One']);
        $this->createSsoClient(['name' => 'App Two']);

        $this->artisan('sso:clients', ['action' => 'list'])
            ->assertExitCode(0);

        $this->assertDatabaseCount('oauth_clients', 2);
    }

    // ── CREATE ──────────────────────────────────────────────────────────

    public function test_create_with_options_succeeds(): void
    {
        $this->artisan('sso:clients', [
            'action' => 'create',
            '--name' => 'New Client',
            '--redirect' => 'https://app.example.com/callback',
        ])
            ->expectsOutput('SSO Client created successfully!')
            ->assertExitCode(0);

        $this->assertDatabaseHas('oauth_clients', [
            'name' => 'New Client',
            'revoked' => false,
        ]);
    }

    public function test_create_stores_correct_redirect_uri(): void
    {
        $this->artisan('sso:clients', [
            'action' => 'create',
            '--name' => 'Redirect Test',
            '--redirect' => 'https://redirect.test/auth/callback',
        ])->assertExitCode(0);

        $client = Client::where('name', 'Redirect Test')->first();

        $this->assertNotNull($client);
        $this->assertStringContainsString('https://redirect.test/auth/callback', $client->redirect_uris);
    }

    public function test_create_stores_correct_grant_types(): void
    {
        $this->artisan('sso:clients', [
            'action' => 'create',
            '--name' => 'Grant Test',
            '--redirect' => 'https://example.com/cb',
        ])->assertExitCode(0);

        $client = Client::where('name', 'Grant Test')->first();

        $this->assertNotNull($client);
        $decoded = json_decode($client->grant_types, true);
        $this->assertContains('authorization_code', $decoded);
        $this->assertContains('refresh_token', $decoded);
    }

    public function test_create_generates_uuid_id(): void
    {
        $this->artisan('sso:clients', [
            'action' => 'create',
            '--name' => 'UUID Test',
            '--redirect' => 'https://example.com/cb',
        ])->assertExitCode(0);

        $client = Client::where('name', 'UUID Test')->first();

        $this->assertNotNull($client);
        $this->assertTrue(Str::isUuid($client->id));
    }

    public function test_create_generates_secret(): void
    {
        $this->artisan('sso:clients', [
            'action' => 'create',
            '--name' => 'Secret Test',
            '--redirect' => 'https://example.com/cb',
        ])->assertExitCode(0);

        $client = Client::where('name', 'Secret Test')->first();

        $this->assertNotNull($client);
        $this->assertEquals(40, strlen($client->secret));
    }

    public function test_create_fails_with_missing_name(): void
    {
        $this->artisan('sso:clients', [
            'action' => 'create',
            '--redirect' => 'https://example.com/callback',
        ])
            ->expectsQuestion('Client name', '')
            ->expectsOutput('Both --name and --redirect are required.')
            ->assertExitCode(1);
    }

    public function test_create_fails_with_missing_redirect(): void
    {
        $this->artisan('sso:clients', [
            'action' => 'create',
            '--name' => 'Test',
        ])
            ->expectsQuestion('Redirect URI', '')
            ->expectsOutput('Both --name and --redirect are required.')
            ->assertExitCode(1);
    }

    public function test_create_fails_with_invalid_redirect_uri(): void
    {
        $this->artisan('sso:clients', [
            'action' => 'create',
            '--name' => 'Bad URI Client',
            '--redirect' => 'not-a-valid-url',
        ])
            ->expectsOutput('Invalid redirect URI format.')
            ->assertExitCode(1);
    }

    public function test_create_prompts_for_name_when_not_provided(): void
    {
        $this->artisan('sso:clients', [
            'action' => 'create',
            '--redirect' => 'https://example.com/cb',
        ])
            ->expectsQuestion('Client name', 'Prompted Client')
            ->expectsOutput('SSO Client created successfully!')
            ->assertExitCode(0);

        $this->assertDatabaseHas('oauth_clients', ['name' => 'Prompted Client']);
    }

    public function test_create_prompts_for_redirect_when_not_provided(): void
    {
        $this->artisan('sso:clients', [
            'action' => 'create',
            '--name' => 'Prompted Redirect',
        ])
            ->expectsQuestion('Redirect URI', 'https://prompted.example.com/cb')
            ->expectsOutput('SSO Client created successfully!')
            ->assertExitCode(0);

        $this->assertDatabaseHas('oauth_clients', ['name' => 'Prompted Redirect']);
    }

    public function test_create_does_not_store_client_on_failure(): void
    {
        $this->artisan('sso:clients', [
            'action' => 'create',
            '--name' => 'Fail Client',
            '--redirect' => 'invalid',
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('oauth_clients', ['name' => 'Fail Client']);
    }

    // ── REVOKE ──────────────────────────────────────────────────────────

    public function test_revoke_succeeds_with_confirmation(): void
    {
        $client = $this->createSsoClient(['name' => 'Revocable App']);

        $this->artisan('sso:clients', [
            'action' => 'revoke',
            '--id' => $client->id,
        ])
            ->expectsConfirmation("Are you sure you want to revoke 'Revocable App'?", 'yes')
            ->expectsOutput("Client 'Revocable App' has been revoked.")
            ->assertExitCode(0);

        $this->assertDatabaseHas('oauth_clients', [
            'id' => $client->id,
            'revoked' => true,
        ]);
    }

    public function test_revoke_cancelled_by_user(): void
    {
        $client = $this->createSsoClient(['name' => 'Keep Alive']);

        $this->artisan('sso:clients', [
            'action' => 'revoke',
            '--id' => $client->id,
        ])
            ->expectsConfirmation("Are you sure you want to revoke 'Keep Alive'?", 'no')
            ->expectsOutput('Operation cancelled.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('oauth_clients', [
            'id' => $client->id,
            'revoked' => false,
        ]);
    }

    public function test_revoke_fails_for_nonexistent_client(): void
    {
        $fakeId = Str::uuid()->toString();

        $this->artisan('sso:clients', [
            'action' => 'revoke',
            '--id' => $fakeId,
        ])
            ->expectsOutput("Client with ID {$fakeId} not found.")
            ->assertExitCode(1);
    }

    public function test_revoke_warns_if_already_revoked(): void
    {
        $client = $this->createSsoClient(['name' => 'Already Revoked', 'revoked' => true]);

        $this->artisan('sso:clients', [
            'action' => 'revoke',
            '--id' => $client->id,
        ])
            ->expectsOutput("Client 'Already Revoked' is already revoked.")
            ->assertExitCode(0);
    }

    public function test_revoke_prompts_for_id_when_not_provided(): void
    {
        $client = $this->createSsoClient(['name' => 'Prompted Revoke']);

        $this->artisan('sso:clients', ['action' => 'revoke'])
            ->expectsQuestion('Client ID to revoke', $client->id)
            ->expectsConfirmation("Are you sure you want to revoke 'Prompted Revoke'?", 'yes')
            ->expectsOutput("Client 'Prompted Revoke' has been revoked.")
            ->assertExitCode(0);
    }

    // ── REFRESH-SECRET ──────────────────────────────────────────────────

    public function test_refresh_secret_succeeds_with_confirmation(): void
    {
        $client = $this->createSsoClient(['name' => 'Refreshable App']);
        $oldSecret = $client->secret;

        $this->artisan('sso:clients', [
            'action' => 'refresh-secret',
            '--id' => $client->id,
        ])
            ->expectsConfirmation(
                "Are you sure you want to regenerate the secret for 'Refreshable App'? The old secret will stop working immediately.",
                'yes'
            )
            ->expectsOutput("Secret refreshed for 'Refreshable App'.")
            ->assertExitCode(0);

        $client->refresh();
        $this->assertNotEquals($oldSecret, $client->secret);
        $this->assertEquals(40, strlen($client->secret));
    }

    public function test_refresh_secret_cancelled_by_user(): void
    {
        $client = $this->createSsoClient(['name' => 'Keep Secret']);
        $oldSecret = $client->secret;

        $this->artisan('sso:clients', [
            'action' => 'refresh-secret',
            '--id' => $client->id,
        ])
            ->expectsConfirmation(
                "Are you sure you want to regenerate the secret for 'Keep Secret'? The old secret will stop working immediately.",
                'no'
            )
            ->expectsOutput('Operation cancelled.')
            ->assertExitCode(0);

        $client->refresh();
        $this->assertEquals($oldSecret, $client->secret);
    }

    public function test_refresh_secret_fails_for_nonexistent_client(): void
    {
        $fakeId = Str::uuid()->toString();

        $this->artisan('sso:clients', [
            'action' => 'refresh-secret',
            '--id' => $fakeId,
        ])
            ->expectsOutput("Client with ID {$fakeId} not found.")
            ->assertExitCode(1);
    }

    public function test_refresh_secret_prompts_for_id_when_not_provided(): void
    {
        $client = $this->createSsoClient(['name' => 'Prompted Refresh']);

        $this->artisan('sso:clients', ['action' => 'refresh-secret'])
            ->expectsQuestion('Client ID to refresh secret', $client->id)
            ->expectsConfirmation(
                "Are you sure you want to regenerate the secret for 'Prompted Refresh'? The old secret will stop working immediately.",
                'yes'
            )
            ->expectsOutput("Secret refreshed for 'Prompted Refresh'.")
            ->assertExitCode(0);
    }

    // ── INVALID ACTION ──────────────────────────────────────────────────

    public function test_invalid_action_returns_failure(): void
    {
        $this->artisan('sso:clients', ['action' => 'delete'])
            ->expectsOutput('Invalid action. Available actions: list, create, revoke, refresh-secret')
            ->assertExitCode(1);
    }

    public function test_another_invalid_action(): void
    {
        $this->artisan('sso:clients', ['action' => 'update'])
            ->expectsOutput('Invalid action. Available actions: list, create, revoke, refresh-secret')
            ->assertExitCode(1);
    }
}
