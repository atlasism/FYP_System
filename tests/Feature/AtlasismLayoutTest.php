<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtlasismLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_auth_pages_use_the_restored_layout(): void
    {
        $this->get('/login')->assertOk()->assertSee('login-glass')->assertSee('FYP Inventory');
        $this->get('/register')->assertOk()->assertSee('New Account Registration');
    }

    public function test_role_pages_render_with_the_atlasism_portal_frame(): void
    {
        foreach (['Student' => ['/student/dashboard', '/student/projects/create', '/student/documents', '/student/project', '/student/milestones', '/student/deadlines', '/student/groups', '/student/archive'],
            'Supervisor' => ['/supervisor/dashboard'],
            'Panel' => ['/panel/dashboard'],
            'Admin' => ['/admin/dashboard', '/admin/users', '/admin/projects', '/admin/deadlines', '/admin/reports', '/admin/settings'],
        ] as $role => $paths) {
            $user = User::query()->create([
                'username' => strtolower($role).'_layout',
                'full_name' => $role.' Layout User',
                'email' => strtolower($role).'_layout@example.test',
                'ic_number' => '000000000000',
                'password' => bcrypt('layout-test-password'),
                'role' => $role,
            ]);

            foreach ($paths as $path) {
                $this->actingAs($user)->get($path)->assertOk()->assertSee('portal-sidebar');
            }
        }
    }
}
