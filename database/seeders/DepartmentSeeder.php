<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['id' => 1, 'code' => 'KEU',  'name' => 'Bidang Keuangan',    'head_user_id' => 6, 'description' => ''],
            ['id' => 2, 'code' => 'SDM',  'name' => 'Bidang Kepegawaian', 'head_user_id' => 7, 'description' => ''],
            ['id' => 3, 'code' => 'TIK',  'name' => 'Bidang TIK',         'head_user_id' => 2, 'description' => ''],
            ['id' => 4, 'code' => 'UMUM', 'name' => 'Bidang Umum',        'head_user_id' => 8, 'description' => ''],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }

        // Set department_id on kepala bidang users after departments exist
        User::where('id', 2)->update(['department_id' => 3]);
        User::where('id', 6)->update(['department_id' => 1]);
        User::where('id', 7)->update(['department_id' => 2]);
        User::where('id', 8)->update(['department_id' => 4]);
    }
}
