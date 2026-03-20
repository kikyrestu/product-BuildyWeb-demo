<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_access_user_management_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('settings.users'));

        $response->assertForbidden();
    }

    public function test_owner_can_update_user_identity_fields(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
        ]);
        $target = User::factory()->create([
            'role' => 'kasir',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($owner)
            ->put(route('settings.users.update', $target), [
                'name' => 'Kasir Baru',
                'username' => 'kasir_baru',
                'email' => 'kasir.baru@example.com',
                'role' => 'admin',
                'is_active' => '1',
                'password' => '',
                'password_confirmation' => '',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $target->refresh();

        $this->assertSame('Kasir Baru', $target->name);
        $this->assertSame('kasir_baru', $target->username);
        $this->assertSame('kasir.baru@example.com', $target->email);
        $this->assertSame('admin', $target->role);
        $this->assertTrue($target->is_active);
    }

    public function test_owner_can_delete_non_owner_user(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
        ]);
        $target = User::factory()->create([
            'role' => 'kasir',
        ]);

        $response = $this
            ->actingAs($owner)
            ->delete(route('settings.users.destroy', $target));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertNull($target->fresh());
    }

    public function test_owner_cannot_delete_themselves(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->from(route('settings.users'))
            ->delete(route('settings.users.destroy', $owner));

        $response
            ->assertSessionHasErrors(['users'])
            ->assertRedirect(route('settings.users'));

        $this->assertNotNull($owner->fresh());
    }
}
