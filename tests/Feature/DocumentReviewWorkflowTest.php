<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejected_document_can_be_resubmitted_and_history_is_preserved(): void
    {
        Storage::fake('local');
        $student = User::query()->create(['username' => 'student', 'ic_number' => 'student-ic', 'full_name' => 'Student', 'email' => 'student@example.test', 'password' => bcrypt('password'), 'role' => 'Student']);
        $supervisor = User::query()->create(['username' => 'supervisor', 'ic_number' => 'supervisor-ic', 'full_name' => 'Supervisor', 'email' => 'supervisor@example.test', 'password' => bcrypt('password'), 'role' => 'Supervisor']);
        $other = User::query()->create(['username' => 'other', 'ic_number' => 'other-ic', 'full_name' => 'Other', 'email' => 'other@example.test', 'password' => bcrypt('password'), 'role' => 'Supervisor']);
        $project = DB::table('projects')->insertGetId(['created_by' => $student->id, 'student_id' => $student->id, 'supervisor_id' => $supervisor->id, 'title' => 'Project', 'category' => 'Web application', 'session' => '2026', 'description' => 'Project']);
        DB::table('project_members')->insert(['project_id' => $project, 'student_id' => $student->id, 'role' => 'Leader']);

        $upload = fn () => ['doc_type' => 'A', 'document_file' => UploadedFile::fake()->create('proposal.pdf', 10, 'application/pdf')];
        $this->actingAs($student)->post('/student/documents', $upload())->assertSessionHasNoErrors();
        $first = DB::table('project_documents')->first();
        $this->get('/documents/'.$first->id.'/download')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($other)->get('/documents/'.$first->id.'/download')->assertForbidden();
        $this->get('/supervisor/documents/'.$project.'/files/'.$first->id)->assertNotFound();
        $this->actingAs($supervisor)->get('/supervisor/documents/'.$project.'/files/'.$first->id)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($student);
        $this->post('/student/documents', $upload())->assertSessionHasErrors('document_file');
        $this->actingAs($other)->patch('/supervisor/documents/'.$first->id, ['status' => 'Rejected', 'review_feedback' => 'Revise'])->assertNotFound();
        $this->actingAs($supervisor)->patch('/supervisor/documents/'.$first->id, ['status' => 'Rejected'])->assertSessionHasErrors('review_feedback');
        $this->patch('/supervisor/documents/'.$first->id, ['status' => 'Rejected', 'review_feedback' => 'Please revise'])->assertSessionHasNoErrors();
        $this->actingAs($student)->get('/student/documents')->assertOk()->assertSee('Please revise')->assertSee('Resubmission requested');
        $this->post('/student/documents', $upload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('project_documents', 2);
        $this->assertDatabaseHas('project_documents', ['id' => $first->id, 'status' => 'Rejected', 'review_feedback' => 'Please revise']);
        Storage::disk('local')->assertExists($first->file_path);
        $this->actingAs($supervisor)->patch('/supervisor/documents/'.$first->id, ['status' => 'Approved'])->assertStatus(409);
        $this->get('/supervisor/documents/'.$project)->assertOk()->assertSee('Previous submission')->assertSee('Save review');
    }
}

