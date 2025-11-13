<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AdminAction;
use App\Models\GlobalFee;
use App\Enums\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'type' => UserType::ADMIN->value,
        ]);

        Sanctum::actingAs($this->admin);
    }

    public function test_update_user_rights()
    {
        $user = User::factory()->create();

        $response = $this->putJson("/api/admin/users/{$user->id}/rights", [
            'transfer_enabled' => false,
            'can_transfer_to_client' => true,
            'can_pay_merchant' => false,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        $user->refresh();
        $this->assertFalse($user->transfer_enabled);
        $this->assertTrue($user->can_transfer_to_client);
        $this->assertFalse($user->can_pay_merchant);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $this->admin->id,
            'action_type' => 'update_user_rights',
            'target_user_id' => $user->id,
        ]);
    }

    public function test_ban_user()
    {
        $user = User::factory()->create(['banned' => false]);

        $response = $this->postJson("/api/admin/users/{$user->id}/ban");

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        $user->refresh();
        $this->assertTrue($user->banned);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $this->admin->id,
            'action_type' => 'ban_user',
            'target_user_id' => $user->id,
        ]);
    }

    public function test_unban_user()
    {
        $user = User::factory()->create(['banned' => true]);

        $response = $this->postJson("/api/admin/users/{$user->id}/unban");

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        $user->refresh();
        $this->assertFalse($user->banned);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $this->admin->id,
            'action_type' => 'unban_user',
            'target_user_id' => $user->id,
        ]);
    }

    public function test_get_daily_statistics()
    {
        $response = $this->getJson('/api/admin/statistics/daily');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'data' => [
                         'transfers',
                         'deposits',
                         'withdrawals',
                         'merchant_payments',
                     ]
                 ]);
    }

    public function test_update_global_fees()
    {
        $response = $this->putJson('/api/admin/fees/global', [
            'transaction_fee' => 100.50,
            'merchant_percentage' => 5.25,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('global_fees', [
            'transaction_fee' => 100.50,
            'merchant_percentage' => 5.25,
        ]);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $this->admin->id,
            'action_type' => 'update_global_fees',
        ]);
    }

    public function test_set_user_tax()
    {
        $user = User::factory()->create();

        $response = $this->putJson("/api/admin/users/{$user->id}/tax", [
            'tax_percentage' => 10.5,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        $user->refresh();
        $this->assertEquals(10.5, $user->tax_percentage);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $this->admin->id,
            'action_type' => 'set_user_tax',
            'target_user_id' => $user->id,
        ]);
    }

    public function test_unauthorized_access()
    {
        $client = User::factory()->create(['type' => UserType::CLIENT->value]);
        Sanctum::actingAs($client);

        $response = $this->getJson('/api/admin/statistics/daily');

        $response->assertStatus(403);
    }
}
