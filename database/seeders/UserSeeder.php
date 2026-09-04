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
        'impersonate users',
        'create academic structure',
        'edit academic structure',
        'delete academic structure',
        'delete study materials',
        'publish study materials',
        'delete notices',
        'publish notices',
    ];

    public const array PEOPLE = [
        [
            'name' => 'Nusrat Jahan',
            'email' => 'nusrat.jahan@llbstudy.test',
            'phone' => '+8801713000201',
            'role' => self::ADMIN,
        ],
        [
            'name' => 'Mahfuzur Rahman',
            'email' => 'mahfuzur.rahman@llbstudy.test',
            'phone' => '+8801713000202',
            'role' => self::STAFF,
        ],
        [
            'name' => 'Sharmin Akter',
            'email' => 'sharmin.akter@llbstudy.test',
            'phone' => '+8801713000203',
            'role' => self::STAFF,
        ],
        [
            'name' => 'Imran Hossain',
            'email' => 'imran.hossain@llbstudy.test',
            'phone' => '+8801713000208',
            'role' => self::STAFF,
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
