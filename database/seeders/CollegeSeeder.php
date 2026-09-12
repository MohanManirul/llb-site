<?php

namespace Database\Seeders;

use App\Models\College;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Keyed on slug, never truncates, and runs in one transaction. Unlike
 * PermissionSeeder and AcademicStructureSeeder this one is a full sync, so it
 * is the source of truth for the colleges table: re-running it creates rows
 * that are missing, refreshes name, district and address on rows that exist,
 * and removes rows whose slug has left the list -- including a college added
 * by hand in Admin -> Colleges.
 *
 * A row is only removed when nothing points at it. Any college still carrying
 * a student, teacher, notice, routine or note is kept and reported instead,
 * both because those foreign keys are restrictOnDelete and because real data
 * must never disappear behind a seeder.
 *
 * Fields the list does not carry -- is_active, phone, email, website,
 * eiin_code, short names -- are never touched, so operational edits survive.
 *
 * The NU-affiliated law colleges in the order National University lists them;
 * college_code is the four-digit NU code.
 */
class CollegeSeeder extends Seeder
{
    private const array COLLEGES = [
        ['slug' => 'bagerhat-law-college', 'college_code' => '0119', 'name_bn' => 'বাগেরহাট ল কলেজ', 'name_en' => 'Bagerhat Law College', 'district_bn' => 'বাগেরহাট', 'district_en' => 'Bagerhat'],
        ['slug' => 'satkhira-law-college', 'college_code' => '0246', 'name_bn' => 'সাতক্ষীরা ল কলেজ', 'name_en' => 'Satkhira Law College', 'district_bn' => 'সাতক্ষীরা', 'district_en' => 'Satkhira'],
        ['slug' => 'city-law-college-khulna', 'college_code' => '0351', 'name_bn' => 'সিটি ল কলেজ', 'name_en' => 'City Law College', 'district_bn' => 'খুলনা', 'district_en' => 'Khulna'],
        ['slug' => 'central-law-college-khulna', 'college_code' => '0352', 'name_bn' => 'সেন্ট্রাল ল কলেজ', 'name_en' => 'Central Law College', 'district_bn' => 'খুলনা', 'district_en' => 'Khulna'],
        ['slug' => 'smr-law-college', 'college_code' => '0538', 'name_bn' => 'এস.এম.আর ল কলেজ', 'name_en' => 'S.M.R Law College', 'district_bn' => 'যশোর', 'district_en' => 'Jessore'],
        ['slug' => 'shaheed-ziaur-rahman-law-college', 'college_code' => '0618', 'name_bn' => 'শহীদ জিয়াউর রহমান ল কলেজ', 'name_en' => 'Shaheed Ziaur Rahman Law College', 'district_bn' => 'ঝিনাইদহ', 'district_en' => 'Jhenaidah'],
        ['slug' => 'magura-law-college', 'college_code' => '0714', 'name_bn' => 'মাগুরা ল কলেজ', 'name_en' => 'Magura Law College', 'district_bn' => 'মাগুরা', 'district_en' => 'Magura', 'address_bn' => 'মাগুরা সদর', 'address_en' => 'Magura Sadar'],
        ['slug' => 'barishal-law-college', 'college_code' => '1137', 'name_bn' => 'বরিশাল ল কলেজ', 'name_en' => 'Barishal Law College', 'district_bn' => 'বরিশাল', 'district_en' => 'Barishal', 'address_bn' => 'হাসপাতাল রোড', 'address_en' => 'Hospital Road'],
        ['slug' => 'pirojpur-law-college', 'college_code' => '1221', 'name_bn' => 'পিরোজপুর ল কলেজ', 'name_en' => 'Pirojpur Law College', 'district_bn' => 'পিরোজপুর', 'district_en' => 'Pirojpur'],
        ['slug' => 'kushtia-law-college', 'college_code' => '1026', 'name_bn' => 'কুষ্টিয়া ল কলেজ', 'name_en' => 'Kushtia Law College', 'district_bn' => 'কুষ্টিয়া', 'district_en' => 'Kushtia', 'address_bn' => 'কোর্ট পাড়া', 'address_en' => 'Court Para'],
        ['slug' => 'patuakhali-law-college', 'college_code' => '1517', 'name_bn' => 'পটুয়াখালী ল কলেজ', 'name_en' => 'Patuakhali Law College', 'district_bn' => 'পটুয়াখালী', 'district_en' => 'Patuakhali'],
        ['slug' => 'hss-law-college', 'college_code' => '1612', 'name_bn' => 'এইচ.এস.এস ল কলেজ', 'name_en' => 'H.S.S Law College', 'district_bn' => 'বরগুনা', 'district_en' => 'Barguna'],
        ['slug' => 'sylhet-law-college', 'college_code' => '1719', 'name_bn' => 'সিলেট ল কলেজ', 'name_en' => 'Sylhet Law College', 'district_bn' => 'সিলেট', 'district_en' => 'Sylhet'],
        ['slug' => 'metropolitan-law-college', 'college_code' => '1721', 'name_bn' => 'মেট্রোপলিটন ল কলেজ', 'name_en' => 'Metropolitan Law College', 'district_bn' => 'সিলেট', 'district_en' => 'Sylhet'],
        ['slug' => 'habiganj-law-college', 'college_code' => '1813', 'name_bn' => 'হবিগঞ্জ ল কলেজ', 'name_en' => 'Habiganj Law College', 'district_bn' => 'হবিগঞ্জ', 'district_en' => 'Habiganj'],
        ['slug' => 'shaheed-amin-uddin-law-college', 'college_code' => '2131', 'name_bn' => 'শহীদ আমিন উদ্দিন ল কলেজ', 'name_en' => 'Shaheed Amin Uddin Law College', 'district_bn' => 'পাবনা', 'district_en' => 'Pabna'],
        ['slug' => 'sirajganj-law-college', 'college_code' => '2246', 'name_bn' => 'সিরাজগঞ্জ ল কলেজ', 'name_en' => 'Sirajganj Law College', 'district_bn' => 'সিরাজগঞ্জ', 'district_en' => 'Sirajganj'],
        ['slug' => 'naogaon-law-college', 'college_code' => '2422', 'name_bn' => 'নওগাঁ ল কলেজ', 'name_en' => 'Naogaon Law College', 'district_bn' => 'নওগাঁ', 'district_en' => 'Naogaon'],
        ['slug' => 'rajshahi-law-college', 'college_code' => '2563', 'name_bn' => 'রাজশাহী ল কলেজ', 'name_en' => 'Rajshahi Law College', 'district_bn' => 'রাজশাহী', 'district_en' => 'Rajshahi'],
        ['slug' => 'bogra-law-college', 'college_code' => '2744', 'name_bn' => 'বগুড়া ল কলেজ', 'name_en' => 'Bogra Law College', 'district_bn' => 'বগুড়া', 'district_en' => 'Bogra'],
        ['slug' => 'joypurhat-law-college', 'college_code' => '2812', 'name_bn' => 'জয়পুরহাট ল কলেজ', 'name_en' => 'Joypurhat Law College', 'district_bn' => 'জয়পুরহাট', 'district_en' => 'Joypurhat'],
        ['slug' => 'lalmonirhat-law-college', 'college_code' => '2915', 'name_bn' => 'লালমনিরহাট ল কলেজ', 'name_en' => 'Lalmonirhat Law College', 'district_bn' => 'লালমনিরহাট', 'district_en' => 'Lalmonirhat'],
        ['slug' => 'kurigram-law-college', 'college_code' => '3023', 'name_bn' => 'কুড়িগ্রাম ল কলেজ', 'name_en' => 'Kurigram Law College', 'district_bn' => 'কুড়িগ্রাম', 'district_en' => 'Kurigram'],
        ['slug' => 'rangpur-law-college', 'college_code' => '3242', 'name_bn' => 'রংপুর ল কলেজ', 'name_en' => 'Rangpur Law College', 'district_bn' => 'রংপুর', 'district_en' => 'Rangpur'],
        ['slug' => 'gaibandha-law-college', 'college_code' => '3323', 'name_bn' => 'গাইবান্ধা ল কলেজ', 'name_en' => 'Gaibandha Law College', 'district_bn' => 'গাইবান্ধা', 'district_en' => 'Gaibandha'],
        ['slug' => 'thakurgaon-law-college', 'college_code' => '3520', 'name_bn' => 'ঠাকুরগাঁও ল কলেজ', 'name_en' => 'Thakurgaon Law College', 'district_bn' => 'ঠাকুরগাঁও', 'district_en' => 'Thakurgaon'],
        ['slug' => 'comilla-law-college', 'college_code' => '3750', 'name_bn' => 'কুমিল্লা ল কলেজ', 'name_en' => 'Comilla Law College', 'district_bn' => 'কুমিল্লা', 'district_en' => 'Comilla'],
        ['slug' => 'bangabandhu-law-college-comilla', 'college_code' => '3751', 'name_bn' => 'বঙ্গবন্ধু ল কলেজ', 'name_en' => 'Bangabandhu Law College', 'district_bn' => 'কুমিল্লা', 'district_en' => 'Comilla'],
        ['slug' => 'brahmanbaria-law-college', 'college_code' => '3819', 'name_bn' => 'ব্রাহ্মণবাড়িয়া ল কলেজ', 'name_en' => 'Brahmanbaria Law College', 'district_bn' => 'ব্রাহ্মণবাড়িয়া', 'district_en' => 'Brahmanbaria'],
        ['slug' => 'chandpur-law-college', 'college_code' => '3921', 'name_bn' => 'চাঁদপুর ল কলেজ', 'name_en' => 'Chandpur Law College', 'district_bn' => 'চাঁদপুর', 'district_en' => 'Chandpur'],
        ['slug' => 'luxmipur-law-college', 'college_code' => '4013', 'name_bn' => 'লক্ষ্মীপুর ল কলেজ', 'name_en' => 'Luxmipur Law College', 'district_bn' => 'লক্ষ্মীপুর', 'district_en' => 'Lakshmipur'],
        ['slug' => 'lakshmipur-ideal-law-college', 'college_code' => '4015', 'name_bn' => 'লক্ষ্মীপুর আইডিয়াল ল কলেজ', 'name_en' => 'Lakshmipur Ideal Law College', 'district_bn' => 'লক্ষ্মীপুর', 'district_en' => 'Lakshmipur'],
        ['slug' => 'feni-law-college', 'college_code' => '4113', 'name_bn' => 'ফেনী ল কলেজ', 'name_en' => 'Feni Law College', 'district_bn' => 'ফেনী', 'district_en' => 'Feni'],
        ['slug' => 'noakhali-law-college', 'college_code' => '4221', 'name_bn' => 'নোয়াখালী ল কলেজ', 'name_en' => 'Noakhali Law College', 'district_bn' => 'নোয়াখালী', 'district_en' => 'Noakhali'],
        ['slug' => 'bangabandhu-law-temple', 'college_code' => '4376', 'name_bn' => 'বঙ্গবন্ধু ল টেম্পল', 'name_en' => 'Bangabandhu Law Temple', 'district_bn' => 'চট্টগ্রাম', 'district_en' => 'Chittagong'],
        ['slug' => 'chittagong-law-college', 'college_code' => '4377', 'name_bn' => 'চট্টগ্রাম ল কলেজ', 'name_en' => 'Chittagong Law College', 'district_bn' => 'চট্টগ্রাম', 'district_en' => 'Chittagong'],
        ['slug' => 'coxs-bazar-law-college', 'college_code' => '4409', 'name_bn' => 'কক্সবাজার ল কলেজ', 'name_en' => 'Cox\'s Bazar Law College', 'district_bn' => 'কক্সবাজার', 'district_en' => 'Cox\'s Bazar'],
        ['slug' => 'rangamati-law-college', 'college_code' => '4604', 'name_bn' => 'রাঙ্গামাটি ল কলেজ', 'name_en' => 'Rangamati Law College', 'district_bn' => 'রাঙ্গামাটি', 'district_en' => 'Rangamati'],
        ['slug' => 'khagrachari-law-college', 'college_code' => '4706', 'name_bn' => 'খাগড়াছড়ি ল কলেজ', 'name_en' => 'Khagrachari Law College', 'district_bn' => 'খাগড়াছড়ি', 'district_en' => 'Khagrachari'],
        ['slug' => 'netrokona-law-college', 'college_code' => '4816', 'name_bn' => 'নেত্রকোনা ল কলেজ', 'name_en' => 'Netrokona Law College', 'district_bn' => 'নেত্রকোনা', 'district_en' => 'Netrokona'],
        ['slug' => 'jamalpur-law-college', 'college_code' => '5019', 'name_bn' => 'জামালপুর ল কলেজ', 'name_en' => 'Jamalpur Law College', 'district_bn' => 'জামালপুর', 'district_en' => 'Jamalpur'],
        ['slug' => 'momenshahi-law-college', 'college_code' => '5230', 'name_bn' => 'মোমেনশাহী ল কলেজ', 'name_en' => 'Momenshahi Law College', 'district_bn' => 'ময়মনসিংহ', 'district_en' => 'Mymensingh'],
        ['slug' => 'supreme-law-college', 'college_code' => '5250', 'name_bn' => 'সুপ্রীম ল কলেজ', 'name_en' => 'Supreme Law College', 'district_bn' => 'ময়মনসিংহ', 'district_en' => 'Mymensingh'],
        ['slug' => 'narsingdi-law-college', 'college_code' => '5417', 'name_bn' => 'নরসিংদী ল কলেজ', 'name_en' => 'Narsingdi Law College', 'district_bn' => 'নরসিংদী', 'district_en' => 'Narsingdi'],
        ['slug' => 'tangail-law-college', 'college_code' => '5332', 'name_bn' => 'টাঙ্গাইল ল কলেজ', 'name_en' => 'Tangail Law College', 'district_bn' => 'টাঙ্গাইল', 'district_en' => 'Tangail'],
        ['slug' => 'gazipur-law-college', 'college_code' => '5519', 'name_bn' => 'গাজীপুর ল কলেজ', 'name_en' => 'Gazipur Law College', 'district_bn' => 'গাজীপুর', 'district_en' => 'Gazipur'],
        ['slug' => 'narayanganj-law-college', 'college_code' => '5613', 'name_bn' => 'নারায়ণগঞ্জ ল কলেজ', 'name_en' => 'Narayanganj Law College', 'district_bn' => 'নারায়ণগঞ্জ', 'district_en' => 'Narayanganj'],
        ['slug' => 'munshiganj-law-college', 'college_code' => '5707', 'name_bn' => 'মুন্সিগঞ্জ ল কলেজ', 'name_en' => 'Munshiganj Law College', 'district_bn' => 'মুন্সিগঞ্জ', 'district_en' => 'Munshiganj'],
        ['slug' => 'khandakar-nurul-hossain-law-academy', 'college_code' => '5818', 'name_bn' => 'খন্দকার নুরুল হোসাইন ল একাডেমি', 'name_en' => 'Khandakar Nurul Hossain Law Academy', 'district_bn' => 'মানিকগঞ্জ', 'district_en' => 'Manikganj'],
        ['slug' => 'faridpur-law-college', 'college_code' => '6017', 'name_bn' => 'ফরিদপুর ল কলেজ', 'name_en' => 'Faridpur Law College', 'district_bn' => 'ফরিদপুর', 'district_en' => 'Faridpur'],
        ['slug' => 'sheikh-fajlul-karim-selim-law-college', 'college_code' => '6116', 'name_bn' => 'শেখ ফজলুল করিম সেলিম ল কলেজ', 'name_en' => 'Sheikh Fajlul Karim Selim Law College', 'district_bn' => 'গোপালগঞ্জ', 'district_en' => 'Gopalganj'],
        ['slug' => 'bangabandhu-law-college-madaripur', 'college_code' => '6310', 'name_bn' => 'বঙ্গবন্ধু ল কলেজ', 'name_en' => 'Bangabandhu Law College', 'district_bn' => 'মাদারীপুর', 'district_en' => 'Madaripur'],
        ['slug' => 'central-law-college-dhaka', 'college_code' => '6512', 'name_bn' => 'সেন্ট্রাল ল কলেজ', 'name_en' => 'Central Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'বিজয়নগর', 'address_en' => 'Bijoynagar'],
        ['slug' => 'dhaka-law-college', 'college_code' => '6513', 'name_bn' => 'ঢাকা ল কলেজ', 'name_en' => 'Dhaka Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka'],
        ['slug' => 'mirpur-law-college', 'college_code' => '6515', 'name_bn' => 'মিরপুর ল কলেজ', 'name_en' => 'Mirpur Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'মিরপুর', 'address_en' => 'Mirpur'],
        ['slug' => 'jatiyo-law-college', 'college_code' => '6516', 'name_bn' => 'জাতীয় ল কলেজ', 'name_en' => 'Jatiyo Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'মগবাজার', 'address_en' => 'Moghbazar'],
        ['slug' => 'greenview-law-college', 'college_code' => '6517', 'name_bn' => 'গ্রীনভিউ ল কলেজ', 'name_en' => 'Greenview Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'মনিপুর', 'address_en' => 'Monipur'],
        ['slug' => 'mohammadpur-law-college', 'college_code' => '6518', 'name_bn' => 'মোহাম্মদপুর ল কলেজ', 'name_en' => 'Mohammadpur Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'নূরজাহান রোড', 'address_en' => 'Nurjahan Road'],
        ['slug' => 'fatema-law-college', 'college_code' => '6519', 'name_bn' => 'ফাতেমা ল কলেজ', 'name_en' => 'Fatema Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'জিগাতলা', 'address_en' => 'Zigatola'],
        ['slug' => 'metropolitan-ideal-law-college', 'college_code' => '6520', 'name_bn' => 'মেট্রোপলিটন আইডিয়াল ল কলেজ', 'name_en' => 'Metropolitan Ideal Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'ফার্মগেট', 'address_en' => 'Farmgate'],
        ['slug' => 'bangabandhu-law-college-dhaka', 'college_code' => '6521', 'name_bn' => 'বঙ্গবন্ধু ল কলেজ', 'name_en' => 'Bangabandhu Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'মতিঝিল', 'address_en' => 'Motijheel'],
        ['slug' => 'demra-law-college', 'college_code' => '6522', 'name_bn' => 'ডেমরা ল কলেজ', 'name_en' => 'Demra Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'যাত্রাবাড়ী', 'address_en' => 'Jatrabari'],
        ['slug' => 'jane-alam-sarkar-law-college', 'college_code' => '6523', 'name_bn' => 'জান-আলম-সরকার ল কলেজ', 'name_en' => 'Jane-Alam-Sarkar Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka'],
        ['slug' => 'rupnagar-law-college', 'college_code' => '6524', 'name_bn' => 'রূপনগর ল কলেজ', 'name_en' => 'Rupnagar Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'রূপনগর', 'address_en' => 'Rupnagar'],
        ['slug' => 'eidelkhani-law-college', 'college_code' => '6525', 'name_bn' => 'ইদেলখানী ল কলেজ', 'name_en' => 'Eidelkhani Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka'],
        ['slug' => 'dhanmondi-law-college', 'college_code' => '6526', 'name_bn' => 'ধানমন্ডি ল কলেজ', 'name_en' => 'Dhanmondi Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'কলাবাগান', 'address_en' => 'Kalabagan'],
        ['slug' => 'mohanagar-law-college', 'college_code' => '6527', 'name_bn' => 'মহানগর ল কলেজ', 'name_en' => 'Mohanagar Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'সেগুনবাগিচা', 'address_en' => 'Segunbagicha'],
        ['slug' => 'bangladesh-law-college', 'college_code' => '6528', 'name_bn' => 'বাংলাদেশ ল কলেজ', 'name_en' => 'Bangladesh Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'হাতিরপুল', 'address_en' => 'Hatirpool'],
        ['slug' => 'ideal-law-college', 'college_code' => '6529', 'name_bn' => 'আইডিয়াল ল কলেজ', 'name_en' => 'Ideal Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'ফার্মগেট', 'address_en' => 'Farmgate'],
        ['slug' => 'liberty-law-college', 'college_code' => '6553', 'name_bn' => 'লিবার্টি ল কলেজ', 'name_en' => 'Liberty Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'এলিফ্যান্ট রোড', 'address_en' => 'Elephant Road'],
        ['slug' => 'dewan-idris-law-college', 'college_code' => '6554', 'name_bn' => 'দেওয়ান ইদ্রিস ল কলেজ', 'name_en' => 'Dewan Idris Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'সাভার', 'address_en' => 'Savar'],
        ['slug' => 'capital-law-college', 'college_code' => '6555', 'name_bn' => 'ক্যাপিটাল ল কলেজ', 'name_en' => 'Capital Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka', 'address_bn' => 'মহাখালী', 'address_en' => 'Mohakhali'],
        ['slug' => 'nobel-law-college', 'college_code' => '6556', 'name_bn' => 'নোবেল ল কলেজ', 'name_en' => 'Nobel Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka'],
        ['slug' => 'new-era-law-college', 'college_code' => '6579', 'name_bn' => 'নিউ এরা ল কলেজ', 'name_en' => 'New Era Law College', 'district_bn' => 'ঢাকা', 'district_en' => 'Dhaka'],
        ['slug' => 'patiya-law-college', 'college_code' => '7014', 'name_bn' => 'পটিয়া ল কলেজ', 'name_en' => 'Patiya Law College', 'district_bn' => 'চট্টগ্রাম', 'district_en' => 'Chittagong', 'address_bn' => 'পটিয়া', 'address_en' => 'Patiya'],
        ['slug' => 'nawabganj-law-college', 'college_code' => '2623', 'name_bn' => 'নবাবগঞ্জ ল কলেজ', 'name_en' => 'Nawabganj Law College', 'district_bn' => 'চাঁপাইনবাবগঞ্জ', 'district_en' => 'Chapainawabganj'],
        ['slug' => 'dinajpur-law-college', 'college_code' => '3441', 'name_bn' => 'দিনাজপুর ল কলেজ', 'name_en' => 'Dinajpur Law College', 'district_bn' => 'দিনাজপুর', 'district_en' => 'Dinajpur'],
        ['slug' => 'city-law-college-khulna-6514', 'college_code' => '6514', 'name_bn' => 'সিটি ল কলেজ', 'name_en' => 'City Law College', 'district_bn' => 'খুলনা', 'district_en' => 'Khulna'],
    ];

    public function run(): void
    {
        $created = 0;
        $updated = 0;
        $deleted = 0;
        $skipped = 0;

        DB::transaction(function () use (&$created, &$updated, &$deleted, &$skipped) {
            foreach (self::COLLEGES as $index => $college) {
                $record = College::firstOrNew(['slug' => $college['slug']]);

                $record->fill([...$college, 'sort_order' => $index + 1]);

                if (! $record->exists) {
                    $created++;
                } elseif ($record->isDirty()) {
                    $updated++;
                }

                $record->save();
            }

            $stale = College::whereNotIn('slug', array_column(self::COLLEGES, 'slug'))->get();

            foreach ($stale as $college) {
                if ($this->isInUse($college)) {
                    $this->command->warn('kept '.$college->slug.': still referenced by student, teacher, notice, routine or note records');
                    $skipped++;

                    continue;
                }

                $college->delete();
                $deleted++;
            }
        });

        $this->command->info($created.' NU law colleges created, '.$updated.' refreshed, '.$deleted.' removed, '.$skipped.' kept out of the list, from a list of '.count(self::COLLEGES));
    }

    private function isInUse(College $college): bool
    {
        return $college->students()->exists()
            || $college->teachers()->exists()
            || $college->notices()->exists()
            || $college->classRoutines()->exists()
            || $college->classNotes()->exists();
    }
}
