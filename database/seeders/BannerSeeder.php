<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        Banner::create([
            'type' => 'insight',
            'icon' => 'lightbulb',
            'color_scheme' => 'dark',
            'translations' => [
                'en' => ['title' => 'System Insight', 'description' => 'Regular updates to animal health benchmarks ensure AI anomaly detection remains accurate. We recommend auditing these fields every 3 months.'],
                'ar' => ['title' => 'رؤية النظام', 'description' => 'التحديثات المنتظمة لمعايير صحة الحيوان تضمن بقاء اكتشاف الحالات الشاذة بالذكاء الاصطناعي دقيقاً. نوصي بمراجعة هذه الحقول كل 3 أشهر.'],
                'ur' => ['title' => 'سسٹم کی بصیرت', 'description' => 'جانوروں کی صحت کے معیارات کی باقاعدہ اپ ڈیٹس AI بے ضابطگی کا پتہ لگانے کو درست رکھتی ہیں۔ ہم ان فیلڈز کا ہر 3 ماہ بعد آڈٹ کرنے کی تجویز کرتے ہیں۔'],
                'eu' => ['title' => 'Sistemaren ikuspegia', 'description' => 'Animalien osasun-erreferenteen eguneratze erregularrek AI anomaliak detektatzeko zehaztasuna bermatzen dute. Eremu hauek 3 hilabetez behin aztertzea gomendatzen dugu.'],
            ],
            'sort_order' => 0,
            'is_active' => true,
        ]);

        Banner::create([
            'type' => 'cta',
            'icon' => 'medical_services',
            'color_scheme' => 'brand',
            'translations' => [
                'en' => ['title' => 'Need a Vet?', 'description' => 'Connect with regional experts for complex vaccination procedures.', 'button_text' => 'Request Consult'],
                'ar' => ['title' => 'تحتاج طبيب بيطري؟', 'description' => 'تواصل مع الخبراء المحليين لإجراءات التطعيم المعقدة.', 'button_text' => 'طلب استشارة'],
                'ur' => ['title' => 'ویٹر کی ضرورت ہے؟', 'description' => 'پیچیدہ ویکسینیشن کے طریقہ کار کے لیے علاقائی ماہرین سے رابطہ کریں۔', 'button_text' => 'مشورہ کی درخواست'],
                'eu' => ['title' => 'Albaitari behar?', 'description' => 'Konektatu eskualdeko adituekin txerto-prozedura konplexuetarako.', 'button_text' => 'Kontsulta eskatu'],
            ],
            'button_url' => '/medical-records',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Banner::create([
            'type' => 'announcement',
            'icon' => 'campaign',
            'color_scheme' => 'amber',
            'translations' => [
                'en' => ['title' => 'New Feature: GPS Tracking', 'description' => 'Real-time location tracking is now available for all active devices. Check the map view to see your herd movements.'],
                'ar' => ['title' => 'ميزة جديدة: تتبع GPS', 'description' => 'تتبع الموقع في الوقت الفعلي متاح الآن لجميع الأجهزة النشطة. تحقق من عرض الخريطة لرؤية حركات قطيعك.'],
                'ur' => ['title' => 'نئی خصوصیت: GPS ٹریکنگ', 'description' => 'ریئل ٹائم لوکیشن ٹریکنگ اب تمام فعال آلات کے لیے دستیاب ہے۔ اپنے ریوڑ کی نقل و حرکت دیکھنے کے لیے نقشہ دیکھیں۔'],
                'eu' => ['title' => 'Ezaugarri berria: GPS jarraipena', 'description' => 'Denbora errealeko kokapenaren jarraipena orain eskuragarri dago gailu aktibo guztietan. Begiratu mapa-ikuspegia zure artaldearen mugimenduak ikusteko.'],
            ],
            'sort_order' => 2,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);
    }
}
