<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\User;
use App\Notifications\BillDueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFamilies;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase, CreatesFamilies;

    public function test_lists_notifications_with_unread_count(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyFor($user);
        $bill = Bill::factory()->create(['family_id' => $family->id]);

        $user->notify(new BillDueNotification($bill));

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.0.type', 'BillDueNotification')
            ->assertJsonPath('data.0.route', '/bills')
            ->assertJsonPath('data.0.read_at', null);
    }

    public function test_can_mark_a_notification_as_read(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyFor($user);
        $bill = Bill::factory()->create(['family_id' => $family->id]);

        $user->notify(new BillDueNotification($bill));
        $notificationId = $user->notifications()->first()->id;

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/notifications/{$notificationId}/read");

        $response->assertStatus(200);
        $this->assertNotNull($user->notifications()->first()->read_at);
    }

    public function test_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $family = $this->createFamilyFor($owner);
        $bill = Bill::factory()->create(['family_id' => $family->id]);
        $owner->notify(new BillDueNotification($bill));
        $notificationId = $owner->notifications()->first()->id;

        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/notifications/{$notificationId}/read")
            ->assertStatus(404);
    }

    public function test_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyFor($user);
        $bill = Bill::factory()->create(['family_id' => $family->id]);

        $user->notify(new BillDueNotification($bill));
        $user->notify(new BillDueNotification($bill));

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/notifications/read-all')
            ->assertStatus(200);

        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
