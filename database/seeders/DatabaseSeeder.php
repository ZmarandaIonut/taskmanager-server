<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::factory(10)->create();
        \App\Models\Board::factory(10)->create();
        \App\Models\Status::factory(10)->create();
        \App\Models\BoardMembers::factory(10)->create();
        \App\Models\Task::factory(10)->create();
        \App\Models\TaskAssignedTo::factory(10)->create();
    }
}
