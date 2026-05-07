<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $itDepartment = Department::firstOrCreate(['name' => 'IT']);
        $salesDepartment = Department::firstOrCreate(['name' => 'Sales']);

        User::updateOrCreate([
            'email' => 'admin@example.test',
        ], [
            'name' => 'Admin User',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department_id' => $itDepartment->id,
        ]);

        User::updateOrCreate([
            'email' => 'agent@example.test',
        ], [
            'name' => 'Support Agent',
            'password' => Hash::make('password'),
            'role' => 'support_agent',
            'department_id' => $itDepartment->id,
        ]);

        User::updateOrCreate([
            'email' => 'employee@example.test',
        ], [
            'name' => 'Employee User',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'department_id' => $salesDepartment->id,
        ]);

        $categories = collect([
            ['name' => 'Accounts and Access', 'description' => 'Account creation and permission requests.'],
            ['name' => 'PC and Devices', 'description' => 'Company laptops, desktops, and peripherals.'],
            ['name' => 'Network', 'description' => 'VPN, Wi-Fi, and office network issues.'],
            ['name' => 'Business Systems', 'description' => 'Internal application access and incidents.'],
            ['name' => 'Other', 'description' => 'General IT service requests.'],
        ])->map(fn (array $category) => Category::updateOrCreate([
            'name' => $category['name'],
        ], [
            'description' => $category['description'],
            'is_active' => true,
        ]));

        $employee = User::where('email', 'employee@example.test')->firstOrFail();
        $agent = User::where('email', 'agent@example.test')->firstOrFail();

        $ticket = Ticket::updateOrCreate([
            'ticket_no' => 'TKT-20260504-0001',
        ], [
            'title' => 'Request access to GitHub repository',
            'description' => 'Please grant access to the internal infrastructure repository.',
            'type' => Ticket::TYPE_ACCESS_REQUEST,
            'status' => Ticket::STATUS_OPEN,
            'priority' => Ticket::PRIORITY_NORMAL,
            'category_id' => $categories->firstWhere('name', 'Accounts and Access')->id,
            'requester_id' => $employee->id,
            'assignee_id' => $agent->id,
            'closed_at' => null,
        ]);

        TicketComment::updateOrCreate([
            'ticket_id' => $ticket->id,
            'user_id' => $employee->id,
            'body' => 'I need access for the deployment documentation update.',
        ]);
    }
}
