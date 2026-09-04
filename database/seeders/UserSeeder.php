<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public const string SUPER_ADMIN = 'super-admin';

    public const string ADMIN = 'admin';

    public const string STAFF = 'staff';

    public const string CALL_CENTER_SUPERVISOR = 'call-center-supervisor';

    public const string CALL_CENTER_AGENT = 'call-center-agent';

    private const array ADMIN_EXCLUDED = [
        'delete activity logs',
        'view system monitoring',
        'impersonate users',
    ];

    private const array STAFF_EXCLUDED = [
        'manage access',
        'view activity logs',
        'delete activity logs',
        'view system monitoring',
        'manage payments',
        'impersonate users',
    ];

    private const array CALL_CENTER_AGENT_PERMISSIONS = [
        'view dashboard',
        'view call center',
        'pick call center orders',
        'unpick call center orders',
        'update call center order status',
        'edit call center orders',
    ];

    private const array CALL_CENTER_SUPERVISOR_EXTRA = [
        'view all call center orders',
        'manage call center agents',
        'view call center performance',
        'view employees',
    ];

    public const array PEOPLE = [
        [
            'name' => 'Nusrat Jahan',
            'email' => 'nusrat.jahan@stepuptech.com.bd',
            'phone' => '+8801713000201',
            'role' => self::ADMIN,
            'company' => 'StepUp Technologies Ltd.',
            'department' => 'Human Resources',
            'designation' => 'Operations Manager',
        ],
        [
            'name' => 'Mahfuzur Rahman',
            'email' => 'mahfuzur.rahman@stepuptech.com.bd',
            'phone' => '+8801713000202',
            'role' => self::STAFF,
            'company' => 'StepUp Technologies Ltd.',
            'department' => 'Engineering',
            'designation' => 'Senior Software Engineer',
        ],
        [
            'name' => 'Sharmin Akter',
            'email' => 'sharmin.akter@stepuptech.com.bd',
            'phone' => '+8801713000203',
            'role' => self::STAFF,
            'company' => 'StepUp Technologies Ltd.',
            'department' => 'Digital Marketing',
            'designation' => 'Digital Marketing Manager',
        ],
        [
            'name' => 'Tanvir Ahmed',
            'email' => 'tanvir.ahmed@boneek.com.bd',
            'phone' => '+8801713000204',
            'role' => self::CALL_CENTER_SUPERVISOR,
            'company' => 'Boneek Commerce Ltd.',
            'department' => DepartmentSeeder::CALL_CENTER,
            'designation' => 'Call Center Supervisor',
            'call_center' => true,
        ],
        [
            'name' => 'Sadia Islam',
            'email' => 'sadia.islam@boneek.com.bd',
            'phone' => '+8801713000205',
            'role' => self::CALL_CENTER_AGENT,
            'company' => 'Boneek Commerce Ltd.',
            'department' => DepartmentSeeder::CALL_CENTER,
            'designation' => 'Call Center Agent',
            'call_center' => true,
        ],
        [
            'name' => 'Rakibul Hasan',
            'email' => 'rakibul.hasan@boneek.com.bd',
            'phone' => '+8801713000206',
            'role' => self::CALL_CENTER_AGENT,
            'company' => 'Boneek Commerce Ltd.',
            'department' => DepartmentSeeder::CALL_CENTER,
            'designation' => 'Call Center Agent',
            'call_center' => true,
        ],
        [
            'name' => 'Farhana Yasmin',
            'email' => 'farhana.yasmin@boneek.com.bd',
            'phone' => '+8801713000207',
            'role' => self::CALL_CENTER_AGENT,
            'company' => 'Boneek Commerce Ltd.',
            'department' => DepartmentSeeder::CALL_CENTER,
            'designation' => 'Call Center Agent',
            'call_center' => true,
        ],
        [
            'name' => 'Imran Hossain',
            'email' => 'imran.hossain@stepuplogistics.com.bd',
            'phone' => '+8801713000208',
            'role' => self::STAFF,
            'company' => 'StepUp Logistics Ltd.',
            'department' => 'Operations',
            'designation' => 'Operations Manager',
        ],
    ];

    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        $permissions = config('admin-permissions.admin');

        Role::findOrCreate(self::SUPER_ADMIN, 'web')->syncPermissions($permissions);

        Role::findOrCreate(self::ADMIN, 'web')->syncPermissions(
            array_values(array_diff($permissions, self::ADMIN_EXCLUDED)),
        );

        Role::findOrCreate(self::STAFF, 'web')->syncPermissions(
            array_values(array_diff($permissions, self::STAFF_EXCLUDED)),
        );

        Role::findOrCreate(self::CALL_CENTER_AGENT, 'web')->syncPermissions(
            self::CALL_CENTER_AGENT_PERMISSIONS,
        );

        Role::findOrCreate(self::CALL_CENTER_SUPERVISOR, 'web')->syncPermissions(
            array_merge(self::CALL_CENTER_AGENT_PERMISSIONS, self::CALL_CENTER_SUPERVISOR_EXTRA),
        );

        $this->seedPeople();

        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            ['name' => 'Administrator', 'password' => 'password'],
        )->syncRoles(self::SUPER_ADMIN);

        $this->command->info('Roles, permissions and '.count(self::PEOPLE).' staff accounts are in place');
    }

    private function seedPeople(): void
    {
        $password = (string) config('seeding.user_password');

        foreach (self::PEOPLE as $person) {
            $user = User::firstOrCreate(
                ['email' => $person['email']],
                [
                    'name' => $person['name'],
                    'phone' => $person['phone'],
                    'password' => $password,
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles($person['role']);
        }
    }
}
