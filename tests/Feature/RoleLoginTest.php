<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role, string $suffix): User
    {
        return User::query()->create([
            'username' => 'user_'.$suffix,
            'full_name' => $role.' User',
            'email' => $suffix.'@example.test',
            'ic_number' => '060319110434',
            'matric_no' => $role === 'Student' ? '34DIT24F1044' : null,
            'password' => Hash::make('old-shared-password'),
            'role' => $role,
        ]);
    }

    public function test_student_uses_matric_number_and_ic_password_only(): void
    {
        $student = $this->account('Student', 'student');

        $this->post('/login', ['identifier' => $student->email, 'password' => $student->ic_number])
            ->assertSessionHasErrors('identifier');
        $this->post('/login', ['identifier' => $student->matric_no, 'password' => 'old-shared-password'])
            ->assertSessionHasErrors('identifier');
        $this->post('/login', ['identifier' => $student->matric_no, 'password' => $student->ic_number])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($student);
        $this->assertTrue(Hash::check($student->ic_number, $student->fresh()->password));
    }

    public function test_staff_and_panel_use_email_and_ic_password(): void
    {
        foreach (['Supervisor', 'Admin', 'Panel'] as $role) {
            $account = $this->account($role, strtolower($role));

            $this->post('/login', ['identifier' => $account->email, 'password' => $account->ic_number])
                ->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($account);
            $this->assertTrue(Hash::check($account->ic_number, $account->fresh()->password));
            $this->post('/logout')->assertRedirect(route('login'));
        }
    }

    public function test_new_student_registration_hashes_ic_without_asking_for_another_password(): void
    {
        $this->post('/register', [
            'full_name' => 'New Student',
            'email' => 'new-student@example.test',
            'ic_number' => '060319110434',
            'matric_no' => '34DIT24F1044',
        ])->assertRedirect(route('dashboard'));

        $account = User::query()->where('matric_no', '34DIT24F1044')->firstOrFail();
        $this->assertTrue(Hash::check($account->ic_number, $account->password));
    }

    public function test_admin_created_account_uses_ic_password(): void
    {
        $admin = $this->account('Admin', 'creator');

        $this->actingAs($admin)->post('/admin/users', [
            'full_name' => 'New Supervisor',
            'email' => 'new-supervisor@example.test',
            'ic_number' => '790101000004',
            'role' => 'Supervisor',
        ])->assertSessionHas('status');

        $account = User::query()->where('email', 'new-supervisor@example.test')->firstOrFail();
        $this->assertTrue(Hash::check($account->ic_number, $account->password));
    }
}
