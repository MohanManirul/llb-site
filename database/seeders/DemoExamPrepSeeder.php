<?php

namespace Database\Seeders;

use App\Enums\AttemptStatus;
use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\ModelTest;
use App\Models\Program;
use App\Models\Question;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TestAttempt;
use App\Models\User;
use App\Support\Slug;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sample question bank, model tests, students and their attempts so a fresh
 * local install can exercise the exam-prep flows end to end. Idempotent —
 * keyed on question text, slug and email, re-running adds nothing.
 *
 * Demo data only: refuses to run in production.
 */
class DemoExamPrepSeeder extends Seeder
{
    public const string STUDENT_PASSWORD = 'password';

    private const array STUDENTS = [
        ['name' => 'রাকিব হাসান', 'email' => 'student1@example.com', 'phone' => '01711000001', 'program' => 'bar-council'],
        ['name' => 'ফারহানা আক্তার', 'email' => 'student2@example.com', 'phone' => '01711000002', 'program' => 'nu-llb-pass'],
        ['name' => 'তানভীর আহমেদ', 'email' => 'student3@example.com', 'phone' => null, 'program' => null, 'is_active' => false],
    ];

    private const array QUESTIONS = [
        [
            'program' => 'bar-council', 'subject' => 'Penal Code', 'exam_stage' => 'mcq', 'exam_year' => 2023,
            'question_bn' => 'দণ্ডবিধি, ১৮৬০ কার্যকর হয় কত সালে?', 'question_en' => 'In which year did the Penal Code, 1860 come into force?',
            'options' => ['১৮৬০', '১৮৬১', '১৮৬২', '১৮৬৪'], 'correct' => 3,
            'explanation_bn' => 'দণ্ডবিধি ১৮৬০ সালে প্রণীত হলেও ১ জানুয়ারি ১৮৬২ থেকে কার্যকর হয়।', 'reference' => 'দণ্ডবিধি, ধারা ১',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Penal Code', 'exam_stage' => 'mcq', 'exam_year' => 2023,
            'question_bn' => 'দণ্ডবিধির কোন ধারায় খুনের সংজ্ঞা দেওয়া হয়েছে?', 'question_en' => 'Which section of the Penal Code defines murder?',
            'options' => ['ধারা ২৯৯', 'ধারা ৩০০', 'ধারা ৩০২', 'ধারা ৩০৪'], 'correct' => 2,
            'explanation_bn' => 'ধারা ২৯৯-এ অপরাধজনক নরহত্যা এবং ধারা ৩০০-এ খুনের সংজ্ঞা; ধারা ৩০২-এ খুনের শাস্তি।', 'reference' => 'দণ্ডবিধি, ধারা ৩০০',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Penal Code', 'exam_stage' => 'mcq', 'exam_year' => 2022,
            'question_bn' => 'খুনের শাস্তি দণ্ডবিধির কোন ধারায় বর্ণিত হয়েছে?', 'question_en' => 'Which section of the Penal Code prescribes the punishment for murder?',
            'options' => ['ধারা ৩০০', 'ধারা ৩০২', 'ধারা ৩০৪', 'ধারা ৩০৭'], 'correct' => 2,
            'reference' => 'দণ্ডবিধি, ধারা ৩০২',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Penal Code', 'exam_stage' => 'mcq', 'exam_year' => 2022,
            'question_bn' => 'দণ্ডবিধির কোন ধারায় চুরির সংজ্ঞা দেওয়া হয়েছে?', 'question_en' => 'Which section of the Penal Code defines theft?',
            'options' => ['ধারা ৩৭৮', 'ধারা ৩৭৯', 'ধারা ৩৮৩', 'ধারা ৩৯০'], 'correct' => 1,
            'explanation_bn' => 'ধারা ৩৭৮-এ চুরির সংজ্ঞা এবং ধারা ৩৭৯-এ শাস্তি।', 'reference' => 'দণ্ডবিধি, ধারা ৩৭৮',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Penal Code', 'exam_stage' => 'mcq', 'exam_year' => 2021,
            'question_bn' => 'দণ্ডবিধি অনুযায়ী কত বছরের কম বয়সী শিশুর কোনো কাজ অপরাধ নয়?', 'question_en' => 'Under the Penal Code, an act of a child below what age is not an offence?',
            'options' => ['৭ বছর', '৯ বছর', '১২ বছর', '১৪ বছর'], 'correct' => 2,
            'explanation_bn' => '২০০৪ সালের সংশোধনীর পর বাংলাদেশে ধারা ৮২ অনুযায়ী বয়সসীমা ৯ বছর।', 'reference' => 'দণ্ডবিধি, ধারা ৮২',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Code of Civil Procedure', 'exam_stage' => 'mcq', 'exam_year' => 2023,
            'question_bn' => 'দেওয়ানি কার্যবিধির কোন ধারায় রেস জুডিকাটা বর্ণিত হয়েছে?', 'question_en' => 'Which section of the CPC deals with res judicata?',
            'options' => ['ধারা ১০', 'ধারা ১১', 'ধারা ১২', 'ধারা ১৫১'], 'correct' => 2,
            'explanation_bn' => 'ধারা ১০-এ রেস সাব-জুডিস এবং ধারা ১১-এ রেস জুডিকাটা।', 'reference' => 'দেওয়ানি কার্যবিধি, ধারা ১১',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Code of Civil Procedure', 'exam_stage' => 'mcq', 'exam_year' => 2023,
            'question_bn' => 'দেওয়ানি কার্যবিধির কোন ধারায় রেস সাব-জুডিস বর্ণিত হয়েছে?', 'question_en' => 'Which section of the CPC deals with res sub judice?',
            'options' => ['ধারা ৯', 'ধারা ১০', 'ধারা ১১', 'ধারা ১৩'], 'correct' => 2,
            'reference' => 'দেওয়ানি কার্যবিধি, ধারা ১০',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Code of Civil Procedure', 'exam_stage' => 'mcq', 'exam_year' => 2022,
            'question_bn' => 'দেওয়ানি কার্যবিধির কোন আদেশে অস্থায়ী নিষেধাজ্ঞার বিধান আছে?', 'question_en' => 'Which Order of the CPC provides for temporary injunction?',
            'options' => ['আদেশ ৩৭', 'আদেশ ৩৮', 'আদেশ ৩৯', 'আদেশ ৪০'], 'correct' => 3,
            'explanation_bn' => 'আদেশ ৩৯, বিধি ১ ও ২ অনুযায়ী অস্থায়ী নিষেধাজ্ঞা মঞ্জুর করা হয়।', 'reference' => 'দেওয়ানি কার্যবিধি, আদেশ ৩৯',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Code of Civil Procedure', 'exam_stage' => 'mcq', 'exam_year' => 2021,
            'question_bn' => 'দেওয়ানি কার্যবিধির কোন আদেশে সেট-অফ (set-off) এর বিধান রয়েছে?', 'question_en' => 'Which Order of the CPC provides for set-off?',
            'options' => ['আদেশ ৬, বিধি ১৭', 'আদেশ ৭, বিধি ১১', 'আদেশ ৮, বিধি ৬', 'আদেশ ৯, বিধি ১৩'], 'correct' => 3,
            'reference' => 'দেওয়ানি কার্যবিধি, আদেশ ৮ বিধি ৬',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Code of Criminal Procedure', 'exam_stage' => 'mcq', 'exam_year' => 2023,
            'question_bn' => 'ফৌজদারি কার্যবিধির কোন ধারায় এফআইআর (FIR) এর বিধান রয়েছে?', 'question_en' => 'Which section of the CrPC deals with the FIR?',
            'options' => ['ধারা ১৫৪', 'ধারা ১৫৬', 'ধারা ১৬১', 'ধারা ১৬৪'], 'correct' => 1,
            'explanation_bn' => 'ধারা ১৫৪ অনুযায়ী আমলযোগ্য অপরাধের তথ্য থানায় লিপিবদ্ধ করা হয়।', 'reference' => 'ফৌজদারি কার্যবিধি, ধারা ১৫৪',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Code of Criminal Procedure', 'exam_stage' => 'mcq', 'exam_year' => 2022,
            'question_bn' => 'জামিনযোগ্য অপরাধে জামিন ফৌজদারি কার্যবিধির কোন ধারায়?', 'question_en' => 'Bail in bailable offences is provided in which section of the CrPC?',
            'options' => ['ধারা ৪৯৬', 'ধারা ৪৯৭', 'ধারা ৪৯৮', 'ধারা ৫০৩'], 'correct' => 1,
            'explanation_bn' => 'ধারা ৪৯৬ জামিনযোগ্য এবং ধারা ৪৯৭ জামিন-অযোগ্য অপরাধে জামিন সম্পর্কিত।', 'reference' => 'ফৌজদারি কার্যবিধি, ধারা ৪৯৬',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Code of Criminal Procedure', 'exam_stage' => 'mcq', 'exam_year' => 2021,
            'question_bn' => 'আগাম জামিনের বিধান ফৌজদারি কার্যবিধির কোন ধারায়?', 'question_en' => 'Anticipatory bail is granted under which section of the CrPC?',
            'options' => ['ধারা ৪৯৬', 'ধারা ৪৯৭', 'ধারা ৪৯৮', 'ধারা ৫৬১ক'], 'correct' => 3,
            'reference' => 'ফৌজদারি কার্যবিধি, ধারা ৪৯৮',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Law of Evidence', 'exam_stage' => 'mcq', 'exam_year' => 2023,
            'question_bn' => 'পুলিশ কর্মকর্তার কাছে করা স্বীকারোক্তি সাক্ষ্য আইনের কোন ধারায় অগ্রহণযোগ্য?', 'question_en' => 'A confession to a police officer is inadmissible under which section of the Evidence Act?',
            'options' => ['ধারা ২৪', 'ধারা ২৫', 'ধারা ২৬', 'ধারা ২৭'], 'correct' => 2,
            'explanation_bn' => 'ধারা ২৫ অনুযায়ী পুলিশের কাছে করা স্বীকারোক্তি অভিযুক্তের বিরুদ্ধে প্রমাণ করা যায় না।', 'reference' => 'সাক্ষ্য আইন, ধারা ২৫',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Law of Evidence', 'exam_stage' => 'mcq', 'exam_year' => 2022,
            'question_bn' => 'মৃত্যুকালীন ঘোষণা সাক্ষ্য আইনের কোন ধারায় প্রাসঙ্গিক?', 'question_en' => 'Dying declaration is relevant under which section of the Evidence Act?',
            'options' => ['ধারা ৩০', 'ধারা ৩২(১)', 'ধারা ৩৩', 'ধারা ৪৫'], 'correct' => 2,
            'reference' => 'সাক্ষ্য আইন, ধারা ৩২(১)',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Law of Evidence', 'exam_stage' => 'mcq', 'exam_year' => 2021,
            'question_bn' => 'প্রমাণের দায়িত্ব (burden of proof) সাক্ষ্য আইনের কোন ধারায় বর্ণিত?', 'question_en' => 'Which section of the Evidence Act deals with burden of proof?',
            'options' => ['ধারা ১০১', 'ধারা ১০৩', 'ধারা ১০৬', 'ধারা ১১৪'], 'correct' => 1,
            'reference' => 'সাক্ষ্য আইন, ধারা ১০১',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Limitation Act', 'exam_stage' => 'mcq', 'exam_year' => 2023,
            'question_bn' => 'তামাদি আইনের কোন ধারায় বিলম্ব মওকুফের বিধান রয়েছে?', 'question_en' => 'Which section of the Limitation Act provides for condonation of delay?',
            'options' => ['ধারা ৩', 'ধারা ৫', 'ধারা ১৪', 'ধারা ১৮'], 'correct' => 2,
            'explanation_bn' => 'ধারা ৫ অনুযায়ী পর্যাপ্ত কারণ দেখাতে পারলে আপিল বা দরখাস্তের বিলম্ব মওকুফ করা যায়।', 'reference' => 'তামাদি আইন, ধারা ৫',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Limitation Act', 'exam_stage' => 'mcq', 'exam_year' => 2022,
            'question_bn' => 'তামাদি আইন কত সালের?', 'question_en' => 'The Limitation Act is of which year?',
            'options' => ['১৮৭২', '১৮৭৭', '১৯০৮', '১৯০৯'], 'correct' => 3,
            'reference' => 'তামাদি আইন, ১৯০৮',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Specific Relief Act', 'exam_stage' => 'mcq', 'exam_year' => 2023,
            'question_bn' => 'ঘোষণামূলক মোকদ্দমা সুনির্দিষ্ট প্রতিকার আইনের কোন ধারায়?', 'question_en' => 'Declaratory suits are provided in which section of the Specific Relief Act?',
            'options' => ['ধারা ৮', 'ধারা ৯', 'ধারা ৪২', 'ধারা ৫৪'], 'correct' => 3,
            'explanation_bn' => 'ধারা ৪২ অনুযায়ী আইনগত মর্যাদা বা অধিকারের ঘোষণা চেয়ে মোকদ্দমা করা যায়।', 'reference' => 'সুনির্দিষ্ট প্রতিকার আইন, ধারা ৪২',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Specific Relief Act', 'exam_stage' => 'mcq', 'exam_year' => 2021,
            'question_bn' => 'সুনির্দিষ্ট প্রতিকার আইন কত সালের?', 'question_en' => 'The Specific Relief Act is of which year?',
            'options' => ['১৮৭২', '১৮৭৭', '১৮৮২', '১৯০৮'], 'correct' => 2,
            'reference' => 'সুনির্দিষ্ট প্রতিকার আইন, ১৮৭৭',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Bar Council Order and Legal Ethics', 'exam_stage' => 'mcq', 'exam_year' => 2020,
            'question_bn' => 'বাংলাদেশ লিগ্যাল প্র্যাকটিশনার্স অ্যান্ড বার কাউন্সিল অর্ডার কত সালের?', 'question_en' => 'The Bangladesh Legal Practitioners and Bar Council Order is of which year?',
            'options' => ['১৯৭১', '১৯৭২', '১৯৭৩', '১৯৭৫'], 'correct' => 2,
            'reference' => 'বার কাউন্সিল অর্ডার, ১৯৭২', 'status' => 'archived',
        ],
        [
            'program' => 'nu-llb-pass', 'subject' => 'Law of Contract', 'exam_year' => 2024,
            'question_bn' => 'চুক্তি আইন, ১৮৭২ এর কোন ধারায় চুক্তির সংজ্ঞা দেওয়া হয়েছে?', 'question_en' => 'Which section of the Contract Act, 1872 defines a contract?',
            'options' => ['ধারা ২(ক)', 'ধারা ২(ঙ)', 'ধারা ২(জ)', 'ধারা ১০'], 'correct' => 3,
            'explanation_bn' => 'ধারা ২(জ) অনুযায়ী আইনত বলবৎযোগ্য সম্মতিই চুক্তি; ধারা ১০-এ বৈধ চুক্তির উপাদান।', 'reference' => 'চুক্তি আইন, ধারা ২(জ)',
        ],
        [
            'program' => 'nu-llb-pass', 'subject' => 'Law of Contract', 'exam_year' => 2024,
            'question_bn' => 'চুক্তি করার যোগ্যতা চুক্তি আইনের কোন ধারায় বর্ণিত?', 'question_en' => 'Capacity to contract is dealt with in which section of the Contract Act?',
            'options' => ['ধারা ১০', 'ধারা ১১', 'ধারা ১৩', 'ধারা ১৪'], 'correct' => 2,
            'reference' => 'চুক্তি আইন, ধারা ১১',
        ],
        [
            'program' => 'nu-llb-pass', 'subject' => 'Law of Contract', 'exam_year' => 2023,
            'question_bn' => 'প্রতিদান (consideration) এর সংজ্ঞা চুক্তি আইনের কোন ধারায়?', 'question_en' => 'Consideration is defined in which section of the Contract Act?',
            'options' => ['ধারা ২(খ)', 'ধারা ২(ঘ)', 'ধারা ২৩', 'ধারা ২৫'], 'correct' => 2,
            'reference' => 'চুক্তি আইন, ধারা ২(ঘ)',
        ],
        [
            'program' => 'nu-llb-pass', 'subject' => 'Jurisprudence', 'exam_year' => 2024,
            'question_bn' => '"আইন হলো সার্বভৌমের আদেশ" — উক্তিটি কার?', 'question_en' => '"Law is the command of the sovereign" — who said this?',
            'options' => ['জন অস্টিন', 'স্যাভিনি', 'জেরেমি বেন্থাম', 'হ্যান্স কেলসেন'], 'correct' => 1,
            'explanation_bn' => 'জন অস্টিন বিশ্লেষণাত্মক ইতিবাচক আইনতত্ত্বে আইনকে সার্বভৌমের আদেশ হিসেবে দেখেছেন।', 'reference' => 'Austin, The Province of Jurisprudence Determined',
        ],
        [
            'program' => 'nu-llb-pass', 'subject' => 'Jurisprudence', 'exam_year' => 2023,
            'question_bn' => 'বিশুদ্ধ আইন তত্ত্বের (Pure Theory of Law) প্রবক্তা কে?', 'question_en' => 'Who propounded the Pure Theory of Law?',
            'options' => ['রোস্কো পাউন্ড', 'হ্যান্স কেলসেন', 'এইচ. এল. এ. হার্ট', 'হেনরি মেইন'], 'correct' => 2,
            'reference' => 'Kelsen, Pure Theory of Law',
        ],
        [
            'program' => 'nu-llb-pass', 'subject' => 'Muslim Law', 'exam_year' => 2024,
            'question_bn' => 'মুসলিম পারিবারিক আইন অধ্যাদেশ কত সালের?', 'question_en' => 'The Muslim Family Laws Ordinance is of which year?',
            'options' => ['১৯৩৯', '১৯৬১', '১৯৭৪', '১৯৮৫'], 'correct' => 2,
            'reference' => 'মুসলিম পারিবারিক আইন অধ্যাদেশ, ১৯৬১',
        ],
        [
            'program' => 'nu-llb-pass', 'subject' => 'Muslim Law', 'exam_year' => null,
            'question_bn' => 'মুসলিম আইনে দেনমোহর কী? (খসড়া প্রশ্ন)', 'question_en' => 'What is dower in Muslim law? (draft question)',
            'options' => ['স্ত্রীর প্রাপ্য অর্থ বা সম্পত্তি', 'স্বামীর প্রাপ্য অর্থ', 'উত্তরাধিকার', 'দান'], 'correct' => 1,
            'status' => 'draft',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Code of Civil Procedure', 'type' => 'written', 'exam_stage' => 'written', 'exam_year' => 2023,
            'question_bn' => 'রেস জুডিকাটার নীতি ব্যাখ্যা করুন। এই নীতি প্রয়োগের শর্তগুলো কী কী?',
            'question_en' => 'Explain the doctrine of res judicata. What are the conditions for its application?',
            'explanation_bn' => 'ধারা ১১-এর পাঁচটি শর্ত: একই বিষয়, একই পক্ষ, একই স্বত্বে মামলা, উপযুক্ত এখতিয়ারসম্পন্ন আদালত এবং চূড়ান্ত সিদ্ধান্ত।',
            'reference' => 'দেওয়ানি কার্যবিধি, ধারা ১১',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Penal Code', 'type' => 'written', 'exam_stage' => 'written', 'exam_year' => 2022,
            'question_bn' => 'খুন ও অপরাধজনক নরহত্যার মধ্যে পার্থক্য লিখুন।',
            'question_en' => 'Distinguish between murder and culpable homicide.',
            'reference' => 'দণ্ডবিধি, ধারা ২৯৯ ও ৩০০',
        ],
        [
            'program' => 'nu-llb-pass', 'subject' => 'Law of Contract', 'type' => 'written', 'exam_year' => 2024,
            'question_bn' => 'একটি বৈধ চুক্তির অপরিহার্য উপাদানসমূহ আলোচনা করুন।',
            'question_en' => 'Discuss the essential elements of a valid contract.',
            'reference' => 'চুক্তি আইন, ধারা ১০',
        ],
        [
            'program' => 'bar-council', 'subject' => 'Limitation Act', 'type' => 'written', 'exam_stage' => 'written', 'exam_year' => 2021,
            'question_bn' => 'তামাদি আইনের ধারা ৫ এর প্রয়োগ ব্যাখ্যা করুন। (খসড়া)',
            'question_en' => 'Explain the application of section 5 of the Limitation Act. (draft)',
            'status' => 'draft',
        ],
    ];

    private const array MODEL_TESTS = [
        [
            'program' => 'bar-council', 'exam_stage' => 'mcq',
            'title_bn' => 'বার কাউন্সিল এমসিকিউ মডেল টেস্ট ১', 'title_en' => 'Bar Council MCQ Model Test 1',
            'description_bn' => 'বিগত বছরের প্রশ্ন থেকে বাছাই করা ২০টি এমসিকিউ। প্রতিটি সঠিক উত্তরে ১ নম্বর, ভুল উত্তরে ০.২৫ কাটা।',
            'description_en' => '20 MCQs picked from past papers. 1 mark per correct answer, 0.25 deducted per wrong one.',
            'duration_minutes' => 30, 'negative_mark' => 0.25, 'status' => 'published',
            'subjects' => ['Penal Code', 'Code of Civil Procedure', 'Code of Criminal Procedure', 'Law of Evidence', 'Limitation Act', 'Specific Relief Act'],
        ],
        [
            'program' => 'bar-council', 'exam_stage' => 'mcq',
            'title_bn' => 'দণ্ডবিধি ও সাক্ষ্য আইন মিনি টেস্ট', 'title_en' => 'Penal Code and Evidence Mini Test',
            'description_bn' => 'দণ্ডবিধি ও সাক্ষ্য আইনের ওপর সংক্ষিপ্ত ১০ মিনিটের টেস্ট।',
            'duration_minutes' => 10, 'negative_mark' => 0, 'status' => 'published',
            'subjects' => ['Penal Code', 'Law of Evidence'],
        ],
        [
            'program' => 'nu-llb-pass',
            'title_bn' => 'এলএলবি ১ম পর্ব মডেল টেস্ট (খসড়া)', 'title_en' => 'LLB 1st Part Model Test (Draft)',
            'description_bn' => 'চুক্তি আইন, আইনতত্ত্ব ও মুসলিম আইনের ওপর প্রস্তুতিমূলক টেস্ট — এখনও প্রকাশিত হয়নি।',
            'duration_minutes' => 20, 'negative_mark' => 0.5, 'status' => 'draft',
            'subjects' => ['Law of Contract', 'Jurisprudence', 'Muslim Law'],
        ],
    ];

    private const array ATTEMPTS = [
        ['student' => 'student1@example.com', 'test' => 'Bar Council MCQ Model Test 1', 'status' => 'submitted', 'days_ago' => 5, 'pattern' => 'strong'],
        ['student' => 'student1@example.com', 'test' => 'Penal Code and Evidence Mini Test', 'status' => 'expired', 'days_ago' => 2, 'pattern' => 'partial'],
        ['student' => 'student2@example.com', 'test' => 'Bar Council MCQ Model Test 1', 'status' => 'submitted', 'days_ago' => 1, 'pattern' => 'weak'],
    ];

    private const array PRACTICE_SESSIONS = [
        ['student' => 'student1@example.com', 'program' => 'bar-council', 'subject' => 'Penal Code', 'question_count' => 10, 'correct_count' => 8, 'days_ago' => 6],
        ['student' => 'student1@example.com', 'program' => 'bar-council', 'subject' => 'Code of Civil Procedure', 'question_count' => 10, 'correct_count' => 6, 'days_ago' => 4],
        ['student' => 'student1@example.com', 'program' => 'bar-council', 'subject' => 'Law of Evidence', 'question_count' => 20, 'correct_count' => 15, 'days_ago' => 1],
        ['student' => 'student2@example.com', 'program' => 'nu-llb-pass', 'subject' => 'Law of Contract', 'question_count' => 10, 'correct_count' => 7, 'days_ago' => 3],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoExamPrepSeeder is demo data only — skipped in production.');

            return;
        }

        $adminId = User::where('email', 'admin@gmail.com')->value('id');

        $created = [
            'students' => $this->seedStudents(),
            'questions' => $this->seedQuestions($adminId),
            'model_tests' => $this->seedModelTests($adminId),
            'attempts' => $this->seedAttempts(),
            'practice_sessions' => $this->seedPracticeSessions(),
        ];

        $this->command?->info(sprintf(
            'Demo exam prep in place (%d students, %d questions, %d model tests, %d attempts, %d practice sessions added).',
            $created['students'],
            $created['questions'],
            $created['model_tests'],
            $created['attempts'],
            $created['practice_sessions'],
        ));
    }

    private function seedStudents(): int
    {
        $count = 0;

        foreach (self::STUDENTS as $definition) {
            if (Student::where('email', $definition['email'])->exists()) {
                continue;
            }

            Student::create([
                'name' => $definition['name'],
                'email' => $definition['email'],
                'phone' => $definition['phone'],
                'password' => self::STUDENT_PASSWORD,
                'program_id' => $definition['program'] !== null
                    ? Program::whereLike('slug', $definition['program'], caseSensitive: false)->value('id')
                    : null,
                'is_active' => $definition['is_active'] ?? true,
                'email_verified_at' => now(),
                'last_login_at' => now()->subDays(random_int(0, 10)),
            ]);

            $count++;
        }

        return $count;
    }

    private function seedQuestions(?int $adminId): int
    {
        $count = 0;

        foreach (self::QUESTIONS as $definition) {
            $subjectId = $this->subjectId($definition['program'], $definition['subject']);

            if ($subjectId === null) {
                continue;
            }

            if (Question::withTrashed()->where('question_bn', $definition['question_bn'])->exists()) {
                continue;
            }

            $type = QuestionType::from($definition['type'] ?? QuestionType::Mcq->value);

            DB::transaction(function () use ($definition, $subjectId, $type, $adminId) {
                $question = Question::create([
                    'type' => $type,
                    'subject_id' => $subjectId,
                    'exam_stage' => $definition['exam_stage'] ?? null,
                    'exam_year' => $definition['exam_year'] ?? null,
                    'question_bn' => $definition['question_bn'],
                    'question_en' => $definition['question_en'] ?? null,
                    'explanation_bn' => $definition['explanation_bn'] ?? null,
                    'explanation_en' => $definition['explanation_en'] ?? null,
                    'reference' => $definition['reference'] ?? null,
                    'status' => ContentStatus::from($definition['status'] ?? ContentStatus::Published->value),
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]);

                if ($type === QuestionType::Mcq) {
                    foreach (array_values($definition['options']) as $index => $optionBn) {
                        $question->options()->create([
                            'option_bn' => $optionBn,
                            'is_correct' => $index + 1 === $definition['correct'],
                            'sort_order' => $index + 1,
                        ]);
                    }
                }
            });

            $count++;
        }

        return $count;
    }

    private function seedModelTests(?int $adminId): int
    {
        $count = 0;

        foreach (self::MODEL_TESTS as $definition) {
            if (ModelTest::withTrashed()->where('title_bn', $definition['title_bn'])->exists()) {
                continue;
            }

            $program = Program::whereLike('slug', $definition['program'], caseSensitive: false)->first();

            if ($program === null) {
                continue;
            }

            $status = ContentStatus::from($definition['status']);

            $questionIds = Question::query()
                ->where('type', QuestionType::Mcq)
                ->where('status', ContentStatus::Published)
                ->whereHas('subject', fn ($query) => $query
                    ->where('program_id', $program->id)
                    ->whereIn('name_en', $definition['subjects']))
                ->orderBy('id')
                ->pluck('id');

            if ($status === ContentStatus::Published && $questionIds->isEmpty()) {
                continue;
            }

            DB::transaction(function () use ($definition, $program, $status, $questionIds, $adminId) {
                $modelTest = ModelTest::create([
                    'slug' => Slug::for(ModelTest::class, $definition['title_en'], fallbackPrefix: 'model-test'),
                    'title_bn' => $definition['title_bn'],
                    'title_en' => $definition['title_en'],
                    'description_bn' => $definition['description_bn'] ?? null,
                    'description_en' => $definition['description_en'] ?? null,
                    'program_id' => $program->id,
                    'exam_stage' => $definition['exam_stage'] ?? null,
                    'duration_minutes' => $definition['duration_minutes'],
                    'negative_mark' => $definition['negative_mark'],
                    'status' => $status,
                    'published_at' => $status === ContentStatus::Published ? now()->subDays(random_int(3, 30)) : null,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]);

                foreach ($questionIds->values() as $index => $questionId) {
                    $modelTest->questions()->attach($questionId, [
                        'sort_order' => $index + 1,
                        'marks' => 1,
                    ]);
                }
            });

            $count++;
        }

        return $count;
    }

    private function seedAttempts(): int
    {
        $count = 0;

        foreach (self::ATTEMPTS as $definition) {
            $student = Student::where('email', $definition['student'])->first();
            $modelTest = ModelTest::where('title_en', $definition['test'])->first();

            if ($student === null || $modelTest === null) {
                continue;
            }

            if (TestAttempt::where('student_id', $student->id)->where('model_test_id', $modelTest->id)->exists()) {
                continue;
            }

            $questions = $modelTest->questions()->with('options')->get();

            if ($questions->isEmpty()) {
                continue;
            }

            DB::transaction(function () use ($definition, $student, $modelTest, $questions) {
                $startedAt = now()->subDays($definition['days_ago'])->subMinutes($modelTest->duration_minutes);
                $status = AttemptStatus::from($definition['status']);

                $attempt = TestAttempt::create([
                    'student_id' => $student->id,
                    'model_test_id' => $modelTest->id,
                    'status' => $status,
                    'active' => null,
                    'started_at' => $startedAt,
                    'expires_at' => $startedAt->copy()->addMinutes($modelTest->duration_minutes),
                    'submitted_at' => $status === AttemptStatus::Expired
                        ? $startedAt->copy()->addMinutes($modelTest->duration_minutes)
                        : $startedAt->copy()->addMinutes(max(1, $modelTest->duration_minutes - 3)),
                ]);

                $score = 0.0;
                $correct = 0;
                $wrong = 0;
                $skipped = 0;

                foreach ($questions->values() as $index => $question) {
                    $choice = $this->choiceFor($definition['pattern'], $index);

                    if ($choice === 'skip') {
                        $skipped++;

                        continue;
                    }

                    $option = $choice === 'correct'
                        ? $question->options->firstWhere('is_correct', true)
                        : $question->options->firstWhere('is_correct', false);

                    $isCorrect = (bool) $option?->is_correct;

                    $attempt->answers()->create([
                        'question_id' => $question->id,
                        'question_option_id' => $option?->id,
                        'is_correct' => $isCorrect,
                    ]);

                    if ($isCorrect) {
                        $correct++;
                        $score += (float) $question->pivot->marks;
                    } else {
                        $wrong++;
                        $score -= (float) $modelTest->negative_mark;
                    }
                }

                $attempt->update([
                    'score' => round($score, 2),
                    'correct_count' => $correct,
                    'wrong_count' => $wrong,
                    'skipped_count' => $skipped,
                ]);
            });

            $count++;
        }

        return $count;
    }

    private function choiceFor(string $pattern, int $index): string
    {
        return match ($pattern) {
            'strong' => $index % 6 === 5 ? 'wrong' : ($index % 8 === 7 ? 'skip' : 'correct'),
            'weak' => $index % 3 === 0 ? 'correct' : ($index % 3 === 1 ? 'wrong' : 'skip'),
            default => $index % 2 === 0 ? 'correct' : 'skip',
        };
    }

    private function seedPracticeSessions(): int
    {
        $count = 0;

        foreach (self::PRACTICE_SESSIONS as $definition) {
            $student = Student::where('email', $definition['student'])->first();
            $subjectId = $this->subjectId($definition['program'], $definition['subject']);

            if ($student === null || $subjectId === null) {
                continue;
            }

            $exists = $student->practiceSessions()
                ->where('subject_id', $subjectId)
                ->where('question_count', $definition['question_count'])
                ->where('correct_count', $definition['correct_count'])
                ->exists();

            if ($exists) {
                continue;
            }

            $student->practiceSessions()->create([
                'subject_id' => $subjectId,
                'question_count' => $definition['question_count'],
                'correct_count' => $definition['correct_count'],
                'created_at' => now()->subDays($definition['days_ago']),
                'updated_at' => now()->subDays($definition['days_ago']),
            ]);

            $count++;
        }

        return $count;
    }

    private function subjectId(string $programSlug, string $subjectName): ?int
    {
        return Subject::query()
            ->where('name_en', $subjectName)
            ->whereHas('program', fn ($query) => $query->whereLike('slug', $programSlug, caseSensitive: false))
            ->value('id');
    }
}
