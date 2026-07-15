<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Buat pengguna dengan peran tertentu (kolom role tidak punya default). */
    protected function makeUser(string $role, ?int $departmentId = null): User
    {
        return User::factory()->create([
            'role'          => $role,
            'department_id' => $departmentId,
        ]);
    }
}
