<?php

namespace Tests\Feature;

use App\Mail\AcademicInterventionApproved;
use App\Mail\AcademicInterventionCreated;
use App\Models\AcademicIntervention;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AcademicInterventionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['name' => 'Test Admin']);
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    private function student(array $overrides = []): Student
    {
        return Student::create(array_merge([
            'student_id' => 'S-' . fake()->unique()->numerify('#####'),
            'name' => 'Alice Reyes',
            'program' => 'BS Computer Science',
            'year_level' => 2,
            'units' => 18,
            'gpa' => 3.00,
            'attendance' => 90,
            'pending_assignments' => 6,
        ], $overrides));
    }

    private function intervention(Student $student, array $overrides = []): AcademicIntervention
    {
        return AcademicIntervention::create(array_merge([
            'student_id' => $student->id,
            'reference_number' => 'INT-TEST-' . fake()->unique()->numerify('####'),
            'intervention_type' => 'Academic Support',
            'priority' => 'High',
            'status' => 'pending',
            'pending_assignments' => $student->pending_assignments,
        ], $overrides));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('interventions.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_view_interventions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('interventions.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_interventions(): void
    {
        $this->actingAs($this->admin())
            ->get(route('interventions.index'))
            ->assertOk();
    }

    public function test_admin_can_create_intervention_and_email_is_sent(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $student = $this->student();

        $this->actingAs($admin)
            ->post(route('interventions.store'), [
                'student_id' => $student->id,
                'intervention_type' => 'Tutoring',
                'priority' => 'High',
                'description' => 'Needs help with backlog',
            ])
            ->assertRedirect(route('interventions.index'));

        $this->assertDatabaseHas('academic_interventions', [
            'student_id' => $student->id,
            'status' => 'pending',
            'created_by' => $admin->id,
        ]);

        Mail::assertSent(AcademicInterventionCreated::class);
    }

    public function test_admin_can_approve_pending_intervention_and_email_is_sent(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $intervention = $this->intervention($this->student());

        $this->actingAs($admin)
            ->post(route('interventions.approve', $intervention))
            ->assertRedirect();

        $this->assertDatabaseHas('academic_interventions', [
            'id' => $intervention->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        Mail::assertSent(AcademicInterventionApproved::class);
    }

    public function test_cannot_approve_an_intervention_that_is_not_pending(): void
    {
        $intervention = $this->intervention($this->student(), ['status' => 'approved']);

        $this->actingAs($this->admin())
            ->post(route('interventions.approve', $intervention))
            ->assertForbidden();
    }

    public function test_intervention_can_be_soft_deleted_and_restored(): void
    {
        $intervention = $this->intervention($this->student());
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('interventions.destroy', $intervention))
            ->assertRedirect(route('interventions.index'));

        $this->assertSoftDeleted($intervention);

        $this->actingAs($admin)
            ->post(route('interventions.restore', $intervention->id))
            ->assertRedirect();

        $this->assertNotSoftDeleted($intervention);
    }

    public function test_search_filters_the_intervention_list(): void
    {
        $alice = $this->student(['name' => 'Alice Reyes']);
        $bob = $this->student(['name' => 'Bob Cruz']);

        $this->intervention($alice);
        $this->intervention($bob);

        $this->actingAs($this->admin())
            ->get(route('interventions.index', ['search' => 'Alice']))
            ->assertOk()
            ->assertSee('Alice Reyes')
            ->assertDontSee('Bob Cruz');
    }

    public function test_csv_export_downloads_filtered_records(): void
    {
        $student = $this->student(['name' => 'Alice Reyes']);
        $intervention = $this->intervention($student);

        $response = $this->actingAs($this->admin())
            ->get(route('interventions.export'));

        $response->assertOk();

        $this->assertStringContainsString(
            'attachment',
            $response->headers->get('content-disposition')
        );

        $this->assertStringContainsString(
            $intervention->reference_number,
            $response->streamedContent()
        );
    }

    public function test_dashboard_stats_api_returns_expected_json(): void
    {
        $this->student(['pending_assignments' => 8]);

        $this->actingAs($this->admin())
            ->getJson('/api/academic-dashboard/stats')
            ->assertOk()
            ->assertJsonStructure([
                'heavy_backlog_count',
                'pending_intervention_count',
                'approved_intervention_count',
                'total_intervention_count',
                'last_updated',
            ])
            ->assertJsonPath('heavy_backlog_count', 1);
    }

    public function test_cached_stats_refresh_when_data_changes(): void
    {
        $admin = $this->admin();
        $student = $this->student();

        $this->actingAs($admin)
            ->getJson('/api/academic-dashboard/stats')
            ->assertJsonPath('total_intervention_count', 0);

        $this->intervention($student);

        $this->actingAs($admin)
            ->getJson('/api/academic-dashboard/stats')
            ->assertJsonPath('total_intervention_count', 1);
    }
}