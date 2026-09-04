<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientSeeder extends Seeder
{
    public const array CLIENTS = [
        [
            'name' => 'Zahir Textiles Ltd.',
            'email' => 'contact@zahirtextiles.com.bd',
            'phone' => '+8801611111111',
            'address' => 'House 12, Road 5, Banani, Dhaka 1213, Bangladesh',
            'description' => 'Premium textile manufacturing and export company specializing in cotton fabrics.',
        ],
        [
            'name' => 'Farida RMG Solutions',
            'email' => 'info@faridarmg.com.bd',
            'phone' => '+8801622222222',
            'address' => 'Plot 45, DEPZ, Savar, Dhaka 1340, Bangladesh',
            'description' => 'Readymade garment manufacturer producing quality apparel for international brands.',
        ],
        [
            'name' => 'Karim & Co. Jute Industries',
            'email' => 'sales@karimjute.com.bd',
            'phone' => '+8801633333333',
            'address' => 'Warehouse 78, Narayanganj Port, Narayanganj 1400, Bangladesh',
            'description' => 'Jute products manufacturer and exporter with operations across Bangladesh.',
        ],
        [
            'name' => 'Rana Leather Works',
            'email' => 'business@ranaleather.com.bd',
            'phone' => '+8801644444444',
            'address' => 'Factory 23, Hazaribagh, Dhaka 1209, Bangladesh',
            'description' => 'Leather processing and finished products manufacturing for footwear industry.',
        ],
        [
            'name' => 'Rahman Seafood Export',
            'email' => 'export@rahmanseafood.com.bd',
            'phone' => '+8801655555555',
            'address' => 'Cold Storage 9, Chattogram Port, Chattogram 4000, Bangladesh',
            'description' => 'Shrimp and fish processing with cold chain facilities for export markets.',
        ],
        [
            'name' => 'Nasrin Fashion Group',
            'email' => 'hr@nasrinfashion.com.bd',
            'phone' => '+8801666666666',
            'address' => 'Office Block D, Mirpur 12, Dhaka 1216, Bangladesh',
            'description' => 'Fashion design and manufacturing focusing on contemporary women apparel.',
        ],
        [
            'name' => 'Hakim Agricultural Exports',
            'email' => 'procurement@hakimagriculture.com.bd',
            'phone' => '+8801677777777',
            'address' => 'Warehouse Complex, Gazipur, Gazipur 1700, Bangladesh',
            'description' => 'Agricultural produce export including spices, tea, and dried fruits.',
        ],
        [
            'name' => 'Ismail Ceramics Industries',
            'email' => 'sales@ismailceramics.com.bd',
            'phone' => '+8801688888888',
            'address' => 'Factory Zone 15, Tangail, Tangail 1900, Bangladesh',
            'description' => 'Ceramic tiles and pottery production with advanced manufacturing facilities.',
        ],
        [
            'name' => 'Biplob Steel & Engineering',
            'email' => 'operations@biplobsteel.com.bd',
            'phone' => '+8801699999999',
            'address' => 'Industrial Area 7, Narayanganj 1400, Bangladesh',
            'description' => 'Steel fabrication and engineering solutions for construction projects.',
        ],
        [
            'name' => 'Salma Handicrafts Collective',
            'email' => 'contact@salmahandicrafts.com.bd',
            'phone' => '+8801711111111',
            'address' => 'Workshop 34, Pabna, Pabna 6600, Bangladesh',
            'description' => 'Traditional and contemporary handicrafts production and global distribution.',
        ],
        [
            'name' => 'Omar Pharmaceuticals Ltd.',
            'email' => 'info@omarpharm.com.bd',
            'phone' => '+8801722222222',
            'address' => 'Research Park, Savar, Dhaka 1340, Bangladesh',
            'description' => 'Pharmaceutical manufacturing and generic medicine production.',
        ],
        [
            'name' => 'Fatima Frozen Foods',
            'email' => 'sales@fatimafrozen.com.bd',
            'phone' => '+8801733333333',
            'address' => 'Food Processing Complex, Bogra, Bogra 5800, Bangladesh',
            'description' => 'Frozen food processing and export specializing in vegetables and poultry.',
        ],
        [
            'name' => 'Rashid Footwear Company',
            'email' => 'business@rashidfootwear.com.bd',
            'phone' => '+8801744444444',
            'address' => 'Manufacturing Unit 56, Dhaka 1230, Bangladesh',
            'description' => 'Footwear design and manufacturing for domestic and export markets.',
        ],
        [
            'name' => 'Jahanara Plastics Solutions',
            'email' => 'contact@jahanaraplastics.com.bd',
            'phone' => '+8801755555555',
            'address' => 'Industrial Park 19, Gazipur 1700, Bangladesh',
            'description' => 'Plastic products manufacturing including packaging and consumer goods.',
        ],
        [
            'name' => 'Samir Global Trading',
            'email' => 'trade@samirtrading.com.bd',
            'phone' => '+8801766666666',
            'address' => 'Trading House, Kawran Bazar, Dhaka 1215, Bangladesh',
            'description' => 'Import-export trading company dealing in diverse commodity segments.',
        ],
    ];

    public function run(): void
    {
        $clientsCreated = 0;

        foreach (self::CLIENTS as $clientData) {
            Client::firstOrCreate(
                ['email' => $clientData['email']],
                [
                    'name' => $clientData['name'],
                    'phone' => $clientData['phone'],
                    'address' => $clientData['address'],
                    'description' => $clientData['description'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ],
            );

            $clientsCreated++;
        }

        $this->command->info("{$clientsCreated} clients created");
    }
}
