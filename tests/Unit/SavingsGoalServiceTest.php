<?php

namespace Tests\Unit;

use App\Events\SavingsGoalCompleted;
use App\Models\Family;
use App\Models\SavingsGoal;
use App\Models\User;
use App\Services\SavingsGoalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SavingsGoalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SavingsGoalService $service;
    protected Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SavingsGoalService();

        $user = User::factory()->create();
        $this->family = Family::factory()->create(['owner_id' => $user->id]);
    }

    public function test_contribute_increases_current_amount(): void
    {
        $goal = SavingsGoal::factory()->create([
            'family_id' => $this->family->id,
            'target_amount' => 10000,
            'current_amount' => 1000,
        ]);

        $this->service->contribute($goal, 2500);

        $this->assertEquals(3500, (float) $goal->fresh()->current_amount);
    }

    public function test_contribution_is_clamped_at_target(): void
    {
        $goal = SavingsGoal::factory()->create([
            'family_id' => $this->family->id,
            'target_amount' => 10000,
            'current_amount' => 9800,
        ]);

        $this->service->contribute($goal, 5000);

        $this->assertEquals(10000, (float) $goal->fresh()->current_amount);
    }

    public function test_completion_event_fires_when_target_reached(): void
    {
        Event::fake([SavingsGoalCompleted::class]);

        $goal = SavingsGoal::factory()->create([
            'family_id' => $this->family->id,
            'target_amount' => 5000,
            'current_amount' => 4000,
        ]);

        $this->service->contribute($goal, 1000);

        Event::assertDispatched(SavingsGoalCompleted::class);
    }

    public function test_contribution_to_completed_goal_is_ignored(): void
    {
        $goal = SavingsGoal::factory()->create([
            'family_id' => $this->family->id,
            'target_amount' => 5000,
            'current_amount' => 5000,
        ]);

        $this->service->contribute($goal, 1000);

        $this->assertEquals(5000, (float) $goal->fresh()->current_amount);
    }
}
