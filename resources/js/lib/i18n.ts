export type Locale = 'bn' | 'en';

export interface TranslatedField {
    bn: string | null;
    en: string | null;
}

const bn = {
    'nav.home': 'হোম',
    'nav.browse': 'ব্রাউজ',
    'nav.suggestions': 'সাজেশন',
    'nav.books': 'বই',
    'nav.notes': 'ক্লাস নোট',
    'nav.notices': 'নোটিশ',
    'nav.programs': 'প্রোগ্রাম',
    'nav.menu': 'মেনু',
    'nav.close': 'বন্ধ করুন',
    'nav.admin_login': 'অ্যাডমিন লগইন',

    'home.tagline': 'এলএলবি শিক্ষার্থীদের জন্য সাজেশন, বই ও ক্লাস নোট',
    'home.subtitle': 'সেশন ও বিষয় অনুযায়ী সাজানো পড়ার উপকরণ — সম্পূর্ণ বিনামূল্যে, রেজিস্ট্রেশন ছাড়াই।',
    'home.pick_program': 'আপনার প্রোগ্রাম বেছে নিন',
    'home.featured': 'নির্বাচিত কনটেন্ট',
    'home.latest': 'সাম্প্রতিক কনটেন্ট',
    'home.view_all': 'সব দেখুন',
    'home.subjects': 'বিষয়',

    'search.placeholder': 'বিষয়, বই বা সাজেশন খুঁজুন…',
    'search.label': 'খুঁজুন',

    'browse.title': 'ব্রাউজ করুন',
    'browse.results': '{count}টি ফলাফল',
    'browse.no_results': 'কোনো ফলাফল পাওয়া যায়নি।',
    'browse.reset': 'ফিল্টার মুছে সব দেখুন',
    'browse.load_error': 'তালিকা আনা যায়নি।',
    'browse.retry': 'আবার চেষ্টা করুন',
    'browse.all': 'সব',

    'filter.program': 'প্রোগ্রাম',
    'filter.type': 'ধরন',
    'filter.all_programs': 'সব প্রোগ্রাম',

    'type.suggestion': 'সাজেশন',
    'type.book': 'বই',
    'type.note': 'ক্লাস নোট',

    'material.download': 'ডাউনলোড করুন ({size})',
    'material.download_plain': 'ডাউনলোড করুন',
    'material.open_new_tab': 'নতুন ট্যাবে খুলুন',
    'material.file': 'PDF ফাইল',
    'material.view': 'দেখুন',
    'material.view_hint': 'দেখুন চাপলে PDF ব্রাউজারেই খুলবে — পছন্দ হলে সেখান থেকে ডাউনলোড করুন, না হলে বন্ধ করে দিন।',
    'material.files': 'ফাইল',
    'material.pages': '{count} পৃষ্ঠা',
    'material.downloads': '{count} বার ডাউনলোড',
    'material.views': '{count} বার দেখা হয়েছে',
    'material.author': 'লেখক',
    'material.publisher': 'প্রকাশক',
    'material.edition': 'সংস্করণ',
    'material.exam_year': 'পরীক্ষার বছর',
    'material.session': 'সেশন',
    'material.subject': 'বিষয়',
    'material.related': 'সম্পর্কিত কনটেন্ট',
    'material.published': 'প্রকাশিত',
    'material.not_found': 'কনটেন্টটি পাওয়া যায়নি।',

    'subject.materials': 'এই বিষয়ের কনটেন্ট',
    'subject.marks': 'পূর্ণমান {marks}',
    'subject.code': 'পেপার কোড {code}',

    'program.subjects': 'বিষয়সমূহ',
    'program.browse_all': 'এই প্রোগ্রামের সব কনটেন্ট',

    'notice.pinned': 'পিন করা',
    'notice.attachment': 'সংযুক্তি ডাউনলোড করুন ({size})',
    'notice.all': 'সব নোটিশ',
    'notice.empty': 'এখনও কোনো নোটিশ নেই।',

    'common.loading': 'লোড হচ্ছে…',
    'common.back_home': 'হোমে ফিরে যান',
    'common.free_badge': 'সম্পূর্ণ ফ্রি',

    'footer.about': 'এলএলবি শিক্ষার্থীদের জন্য বিনামূল্যের স্টাডি পোর্টাল।',
    'footer.rights': 'সর্বস্বত্ব সংরক্ষিত।',
} as const;

export type TranslationKey = keyof typeof bn;

const en: Record<TranslationKey, string> = {
    'nav.home': 'Home',
    'nav.browse': 'Browse',
    'nav.suggestions': 'Suggestions',
    'nav.books': 'Books',
    'nav.notes': 'Class Notes',
    'nav.notices': 'Notices',
    'nav.programs': 'Programs',
    'nav.menu': 'Menu',
    'nav.close': 'Close',
    'nav.admin_login': 'Admin login',

    'home.tagline': 'Suggestions, books & class notes for LLB students',
    'home.subtitle': 'Study materials organised by session and subject — completely free, no registration.',
    'home.pick_program': 'Pick your program',
    'home.featured': 'Featured materials',
    'home.latest': 'Latest materials',
    'home.view_all': 'View all',
    'home.subjects': 'Subjects',

    'search.placeholder': 'Search subjects, books or suggestions…',
    'search.label': 'Search',

    'browse.title': 'Browse',
    'browse.results': '{count} results',
    'browse.no_results': 'No results found.',
    'browse.reset': 'Clear filters and show everything',
    'browse.load_error': 'Could not load the list.',
    'browse.retry': 'Try again',
    'browse.all': 'All',

    'filter.program': 'Program',
    'filter.type': 'Type',
    'filter.all_programs': 'All programs',

    'type.suggestion': 'Suggestion',
    'type.book': 'Book',
    'type.note': 'Class Note',

    'material.download': 'Download ({size})',
    'material.download_plain': 'Download',
    'material.open_new_tab': 'Open in a new tab',
    'material.file': 'PDF file',
    'material.view': 'View',
    'material.view_hint': 'View opens the PDF right in your browser — download it from there if you want it, or just close it.',
    'material.files': 'Files',
    'material.pages': '{count} pages',
    'material.downloads': '{count} downloads',
    'material.views': '{count} views',
    'material.author': 'Author',
    'material.publisher': 'Publisher',
    'material.edition': 'Edition',
    'material.exam_year': 'Exam year',
    'material.session': 'Session',
    'material.subject': 'Subject',
    'material.related': 'Related materials',
    'material.published': 'Published',
    'material.not_found': 'This material could not be found.',

    'subject.materials': 'Materials for this subject',
    'subject.marks': 'Full marks {marks}',
    'subject.code': 'Paper code {code}',

    'program.subjects': 'Subjects',
    'program.browse_all': 'All materials in this program',

    'notice.pinned': 'Pinned',
    'notice.attachment': 'Download attachment ({size})',
    'notice.all': 'All notices',
    'notice.empty': 'No notices yet.',

    'common.loading': 'Loading…',
    'common.back_home': 'Back to home',
    'common.free_badge': 'Completely free',

    'footer.about': 'A free study portal for LLB students.',
    'footer.rights': 'All rights reserved.',
};

const dictionary: Record<Locale, Record<TranslationKey, string>> = { bn, en };

export function translate(
    locale: Locale,
    key: TranslationKey,
    vars?: Record<string, string | number>,
): string {
    const template = dictionary[locale]?.[key] ?? dictionary.bn[key] ?? key;

    if (!vars) return template;

    return template.replace(/\{(\w+)\}/g, (match, name) => String(vars[name] ?? match));
}

export function pickTranslated(locale: Locale, field: TranslatedField | null | undefined): string {
    if (!field) return '';

    return (locale === 'bn' ? (field.bn ?? field.en) : (field.en ?? field.bn)) ?? '';
}
