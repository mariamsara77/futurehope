<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_provisions_member_voting_access(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Workflow Member',
            'email' => 'workflow@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.roles.0', 'member')
            ->assertJsonPath('user.is_member', false);

        $this->assertContains(
            'work-vote',
            $response->json('user.permissions', [])
        );
    }

    public function test_visitor_can_submit_a_work_without_a_user_id(): void
    {
        $response = $this->post('/api/works', [
            'title' => 'Visitor proposal',
            'description' => 'A proposal submitted without an account.',
            'submitted_name' => 'Visitor Person',
            'submitted_email' => 'visitor@example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('work.title', 'Visitor proposal')
            ->assertJsonPath('work.status', 'voting');

        $this->assertDatabaseHas('works', [
            'title' => 'Visitor proposal',
            'user_id' => null,
            'submitted_name' => 'Visitor Person',
            'submitted_email' => 'visitor@example.com',
            'status' => 'voting',
            'is_published' => 0,
        ]);
    }

    public function test_pending_work_is_visible_to_members_and_owner_but_not_public(): void
    {
        [$memberRole] = $this->prepareVotingRole();

        $owner = User::factory()->create(['status' => 'active']);
        $owner->assignRole($memberRole);
        $owner->profile()->create(['status' => 'active']);
        $owner->profile()->create(['status' => 'active']);

        $work = Work::create([
            'user_id' => $owner->id,
            'title' => 'Private pending work',
            'description' => 'Pending detail access test.',
            'status' => 'voting',
            'is_published' => false,
            'votes_count' => 0,
            'required_votes' => 10,
        ]);

        $this->getJson('/api/works/' . $work->id)
            ->assertNotFound();

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/works/' . $work->id . '/view')
            ->assertOk()
            ->assertJsonPath('work.id', $work->id);

        $member = User::factory()->create(['status' => 'active']);
        $member->assignRole($memberRole);
        $member->profile()->create(['status' => 'active']);

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/works/pending')
            ->assertOk()
            ->assertJsonFragment(['id' => $work->id]);

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/works/' . $work->id . '/view')
            ->assertOk()
            ->assertJsonPath('work.has_voted', false);
    }

    public function test_public_work_listing_contains_only_published_works(): void
    {
        [$memberRole] = $this->prepareVotingRole();

        $member = User::factory()->create(['status' => 'active']);
        $member->assignRole($memberRole);
        $member->profile()->create(['status' => 'active']);

        $published = Work::create([
            'user_id' => $member->id,
            'title' => 'Published work',
            'description' => 'Public activity test.',
            'status' => 'approved',
            'is_published' => true,
            'votes_count' => 10,
            'required_votes' => 10,
        ]);

        $pending = Work::create([
            'user_id' => $member->id,
            'title' => 'Pending work',
            'description' => 'Must remain in voting only.',
            'status' => 'voting',
            'is_published' => false,
            'votes_count' => 2,
            'required_votes' => 10,
        ]);

        $response = $this->getJson('/api/works');

        $response->assertOk()
            ->assertJsonFragment(['id' => $published->id])
            ->assertJsonMissing(['id' => $pending->id]);
    }

    public function test_member_can_cancel_vote_and_pending_work_returns_to_voting_state(): void
    {
        [$memberRole] = $this->prepareVotingRole();

        $owner = User::factory()->create(['status' => 'active']);
        $owner->assignRole($memberRole);
        $owner->profile()->create(['status' => 'active']);

        $voter = User::factory()->create(['status' => 'active']);
        $voter->assignRole($memberRole);
        $voter->profile()->create(['status' => 'active']);

        $work = Work::create([
            'user_id' => $owner->id,
            'title' => 'Undo vote work',
            'description' => 'Vote cancellation test.',
            'status' => 'voting',
            'is_published' => false,
            'votes_count' => 0,
            'required_votes' => 2,
        ]);

        $this->actingAs($voter, 'sanctum')
            ->postJson('/api/works/' . $work->id . '/vote')
            ->assertOk()
            ->assertJsonPath('has_voted', true)
            ->assertJsonPath('votes_count', 1);

        $this->actingAs($voter, 'sanctum')
            ->deleteJson('/api/works/' . $work->id . '/vote')
            ->assertOk()
            ->assertJsonPath('has_voted', false)
            ->assertJsonPath('votes_count', 0)
            ->assertJsonPath('is_published', false);

        $work->refresh();

        $this->assertSame(0, $work->votes_count);
        $this->assertSame('voting', $work->status);
        $this->assertFalse((bool) $work->is_published);
        $this->assertDatabaseMissing('work_votes', [
            'work_id' => $work->id,
            'user_id' => $voter->id,
        ]);
    }

    public function test_member_can_vote_once_and_ten_votes_auto_publish_the_work(): void
    {
        [$memberRole] = $this->prepareVotingRole();

        $owner = User::factory()->create(['status' => 'active']);
        $owner->assignRole($memberRole);

        $work = Work::create([
            'user_id' => $owner->id,
            'title' => 'Voting work',
            'description' => 'Voting and auto approval test.',
            'status' => 'voting',
            'is_published' => false,
            'votes_count' => 0,
            'required_votes' => 10,
        ]);

        $firstVoter = User::factory()->create(['status' => 'active']);
        $firstVoter->assignRole($memberRole);
        $firstVoter->profile()->create(['status' => 'active']);

        $firstVote = $this->actingAs($firstVoter, 'sanctum')
            ->postJson('/api/works/' . $work->id . '/vote');

        $firstVote->assertOk()
            ->assertJsonPath('has_voted', true)
            ->assertJsonPath('votes_count', 1);

        $this->actingAs($firstVoter, 'sanctum')
            ->postJson('/api/works/' . $work->id . '/vote')
            ->assertStatus(422)
            ->assertJsonPath('message', 'আপনি ইতিমধ্যে এই কাজে ভোট দিয়েছেন।');

        for ($i = 2; $i <= 10; $i++) {
            $voter = User::factory()->create(['status' => 'active']);
            $voter->assignRole($memberRole);
            $voter->profile()->create(['status' => 'active']);

            $response = $this->actingAs($voter, 'sanctum')
                ->postJson('/api/works/' . $work->id . '/vote');

            $response->assertOk();
        }

        $work->refresh();

        $this->assertSame(10, $work->votes_count);
        $this->assertSame('approved', $work->status);
        $this->assertTrue((bool) $work->is_published);

        $this->getJson('/api/works/' . $work->id)
            ->assertOk()
            ->assertJsonPath('work.status', 'approved')
            ->assertJsonPath('work.is_published', true);
    }

    private function prepareVotingRole(): array
    {
        $permission = Permission::firstOrCreate(['name' => 'work-vote']);
        $role = Role::firstOrCreate(['name' => 'member']);
        $role->givePermissionTo($permission);

        return [$role, $permission];
    }
}
