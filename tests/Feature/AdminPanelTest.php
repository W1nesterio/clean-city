<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrganization(array $attributes = []): Organization
    {
        return Organization::create(array_merge([
            'name' => 'Тестовое ЖКХ',
            'district' => 'Барановичи',
            'address' => 'ул. Тестовая, 1',
            'contact_info' => '+375 00 000-00-00',
            'active' => true,
        ], $attributes));
    }

    private function makeUser(string $role, ?Organization $organization = null, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Тестовый пользователь',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => Hash::make('password'),
            'role' => $role,
            'organization_id' => $organization?->id,
        ], $attributes));
    }

    private function makeCategory(array $attributes = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Мусор',
            'active' => true,
        ], $attributes));
    }

    private function makeTicket(Organization $organization, array $attributes = []): Ticket
    {
        $resident = $attributes['resident'] ?? $this->makeUser('resident');
        unset($attributes['resident']);

        $category = $attributes['category'] ?? $this->makeCategory();
        unset($attributes['category']);

        return Ticket::create(array_merge([
            'user_id' => $resident->id,
            'category_id' => $category->id,
            'assigned_org_id' => $organization->id,
            'assigned_worker_id' => null,
            'status' => 'created',
            'priority' => 'normal',
            'lat' => 53.1327000,
            'lng' => 26.0139000,
            'address_text' => 'г. Барановичи, тестовый адрес',
            'description' => 'Тестовая заявка для проверки админки',
        ], $attributes));
    }

    public function test_org_admin_can_login_to_admin_panel(): void
    {
        $organization = $this->makeOrganization();

        $admin = $this->makeUser('org_admin', $organization, [
            'email' => 'org-admin@example.com',
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'email' => 'org-admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_resident_cannot_login_to_admin_panel(): void
    {
        $this->makeUser('resident', null, [
            'email' => 'resident@example.com',
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login.submit'), [
            'email' => 'resident@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_org_admin_can_open_dashboard(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeUser('org_admin', $organization);

        $this->makeTicket($organization);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSeeText('Рабочий обзор');
    }

    public function test_org_admin_can_open_ticket_from_own_organization(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeUser('org_admin', $organization);
        $ticket = $this->makeTicket($organization);

        $response = $this->actingAs($admin)->get(route('admin.tickets.show', $ticket));

        $response->assertOk();
        $response->assertSeeText('Тестовая заявка для проверки админки');
    }

    public function test_org_admin_cannot_open_ticket_from_another_organization(): void
    {
        $ownOrganization = $this->makeOrganization(['name' => 'Своя организация']);
        $otherOrganization = $this->makeOrganization(['name' => 'Чужая организация']);

        $admin = $this->makeUser('org_admin', $ownOrganization);
        $ticket = $this->makeTicket($otherOrganization);

        $response = $this->actingAs($admin)->get(route('admin.tickets.show', $ticket));

        $response->assertForbidden();
    }

    public function test_org_admin_can_assign_ticket_to_worker(): void
    {
        $organization = $this->makeOrganization();

        $admin = $this->makeUser('org_admin', $organization);
        $worker = $this->makeUser('worker', $organization);
        $ticket = $this->makeTicket($organization);

        $response = $this->actingAs($admin)->post(route('admin.tickets.assign', $ticket), [
            'assigned_org_id' => $organization->id,
            'assigned_worker_id' => $worker->id,
            'comment' => 'Назначено исполнителю в тесте',
        ]);

        $response->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_worker_id' => $worker->id,
            'assigned_org_id' => $organization->id,
            'status' => 'assigned',
        ]);

        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'old_status' => 'created',
            'new_status' => 'assigned',
            'changed_by_user_id' => $admin->id,
            'comment' => 'Назначено исполнителю в тесте',
        ]);
    }

    public function test_org_admin_can_change_ticket_status_to_completed(): void
    {
        $organization = $this->makeOrganization();

        $admin = $this->makeUser('org_admin', $organization);

        $ticket = $this->makeTicket($organization, [
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tickets.status', $ticket), [
            'status' => 'completed',
            'comment' => 'Работы выполнены',
        ]);

        $response->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'completed',
        ]);

        $this->assertNotNull($ticket->fresh()->closed_at);

        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'old_status' => 'in_progress',
            'new_status' => 'completed',
            'changed_by_user_id' => $admin->id,
            'comment' => 'Работы выполнены',
        ]);
    }
}
