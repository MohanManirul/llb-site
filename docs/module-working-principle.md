# AinPath — মডিউলভিত্তিক কার্যনীতি

একটিই Laravel অ্যাপ দুটো কাজ করে: Inertia দিয়ে React পেজ render করে, আর `/v1/*` JSON API সার্ভ করে। পেজ কোনো ডেটা বহন করে না — React mount হয়ে নিজেই API থেকে ডেটা আনে।
প্রতিটি লেখার প্রবাহ একই: **Route → Controller → FormRequest → Service → Model → Resource**।

## মডিউল ও তাদের কার্যনীতি

| # | মডিউল | কার্যনীতি (এক লাইনে) |
|---|---|---|
| 1 | Staff Authentication | `/admin/login` → `web` guard session; একই session cookie দিয়েই `/v1` API চলে (`statefulApi`), ব্যর্থ চেষ্টাও activity log-এ ওঠে। |
| 2 | Password Reset | staff-এর ক্ষেত্রে কেবল super-admin পারে; মেইল queue-তে যায়। |
| 3 | RBAC | spatie/laravel-permission — ১৪টি permission, ৩টি seeded role; `Gate::before` দিয়ে super-admin সব পাশ করে; আসল প্রহরী route/controller middleware, sidebar কেবল লুকায়। |
| 4 | Access Provisioning | staff অ্যাকাউন্ট এখান থেকে তৈরি হয়; permission তালিকাও এখানেই। |
| 5 | User | login পরিচয় — নাম, ইমেইল, ফোন, ছবি, Password ও একটি role; চাকরির তথ্য এখানে নয়। |
| 6 | Dashboard | `view dashboard` থাকলে overview section বসে — user-এর গণনা, date range দিয়ে ছাঁকা। |
| 7 | Profile | নাম/ইমেইল/ছবি/Password — Password ফাঁকা রাখলে অপরিবর্তিত থাকে। |
| 8 | Notification | `notifications` টেবিল + bell; অবকাঠামো প্রস্তুত, তবে এখন কোনো উৎস এতে লেখে না। |
| 9 | Activity Log | কাস্টম audit — কে (causer), কী (description), কার উপর (subject); মুছতে পারে কেবল super-admin। |
| 10 | System Monitoring | `storage/logs`-এর Log Viewer; আলাদা permission, seed অনুযায়ী কেবল super-admin। |
| 11 | Settings Hub | `/admin/settings` ব্যবহারকারীর অনুমতিতে খোলা প্রথম section-এ পাঠায়, কোনোটিই না থাকলে ৪০৩। |

## স্বয়ংক্রিয় নিয়ম (মানুষ ছাড়াই যা ঘটে)

- **Queue-তে চলে** — Password reset mail; worker হাতে চালাতে হয়, না চললে কোনো UI সংকেত নেই।

## ERD

সম্পূর্ণ ডায়াগ্রাম: [`docs/erd.mmd`](erd.mmd) (উৎস) ও [`docs/erd.pdf`](erd.pdf) (রেন্ডার করা)।
