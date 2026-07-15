<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'id'          => 1,
                'name'        => 'Andi Pratama',
                'email'       => 'admin@siatk.test',
                'password'    => Hash::make('password'),
                'role'        => 'admin',
                'department_id' => null,
                'is_active'   => true,
            ],
            [
                'id'          => 2,
                'name'        => 'Budi Santoso',
                'email'       => 'kabid.tik@siatk.test',
                'password'    => Hash::make('password'),
                'role'        => 'kepala_bidang',
                'department_id' => null,
                'is_active'   => true,
            ],
            [
                'id'          => 3,
                'name'        => 'Sri Wahyuni',
                'email'       => 'kasubag@siatk.test',
                'password'    => Hash::make('password'),
                'role'        => 'kasubag_umum',
                'department_id' => null,
                'is_active'   => true,
            ],
            [
                'id'          => 4,
                'name'        => 'Joko Susilo',
                'email'       => 'gudang@siatk.test',
                'password'    => Hash::make('password'),
                'role'        => 'petugas_gudang',
                'department_id' => null,
                'is_active'   => true,
            ],
            [
                'id'          => 5,
                'name'        => 'Dewi Anggraini',
                'email'       => 'pimpinan@siatk.test',
                'password'    => Hash::make('password'),
                'role'        => 'pimpinan',
                'department_id' => null,
                'is_active'   => true,
            ],
            [
                'id'          => 6,
                'name'        => 'Rina Marlina',
                'email'       => 'kabid.keu@siatk.test',
                'password'    => Hash::make('password'),
                'role'        => 'kepala_bidang',
                'department_id' => null,
                'is_active'   => true,
            ],
            [
                'id'          => 7,
                'name'        => 'Hadi Pranoto',
                'email'       => 'kabid.sdm@siatk.test',
                'password'    => Hash::make('password'),
                'role'        => 'kepala_bidang',
                'department_id' => null,
                'is_active'   => true,
            ],
            [
                'id'          => 8,
                'name'        => 'Tono Suryadi',
                'email'       => 'kabid.umum@siatk.test',
                'password'    => Hash::make('password'),
                'role'        => 'kepala_bidang',
                'department_id' => null,
                'is_active'   => true,
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
