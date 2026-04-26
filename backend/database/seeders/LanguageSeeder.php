<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_active' => true, 'is_default' => false, 'sort_order' => 2],
            ['code' => 'ur', 'name' => 'Urdu', 'native_name' => 'اردو', 'direction' => 'rtl', 'is_active' => true, 'is_default' => false, 'sort_order' => 3],
            ['code' => 'eu', 'name' => 'Basque', 'native_name' => 'Euskara', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 4],
        ];

        DB::table('languages')->insert($languages);

        $translations = $this->getTranslations();
        DB::table('translations')->insert($translations);
    }

    private function getTranslations(): array
    {
        $translations = [];
        
        $groups = [
'common' => [
                'appName' => ['en' => 'The Oasis', 'ar' => 'الواحة', 'ur' => 'اویسس', 'eu' => 'Oasia'],
                'quickActions' => ['en' => 'Quick Actions', 'ar' => 'إجراءات سريعة', 'ur' => 'کوئیک ایکشن', 'eu' => 'Ekintza azkarrak'],
                'success' => ['en' => 'Success', 'ar' => 'نجاح', 'ur' => 'کامیاب', 'eu' => 'Arrakasta'],
                'error' => ['en' => 'Error', 'ar' => 'خطأ', 'ur' => 'خرابی', 'eu' => 'Errorea'],
            ],
            'dashboard' => [
                'title' => ['en' => 'Dashboard', 'ar' => 'لوحة التحكم', 'ur' => 'ڈیش بورڈ', 'eu' => 'Azpiegitura'],
                'totalAnimals' => ['en' => 'Total Animals', 'ar' => 'إجمالي الحيوانات', 'ur' => 'کل جانور', 'eu' => 'Animalia kopurua'],
                'activeAlerts' => ['en' => 'Active Alerts', 'ar' => 'التنبيهات النشطة', 'ur' => 'فعال الرسلے', 'eu' => 'Alerta aktiboak'],
                'grazingZones' => ['en' => 'Grazing Zones', 'ar' => 'مراعي', 'ur' => 'چرنے والے علاقے', 'eu' => 'Larreetan'],
                'pendingTasks' => ['en' => 'Pending Tasks', 'ar' => 'المهام المعلقة', 'ur' => 'زیر کار کام', 'eu' => 'Zereginak'],
            ],
            'animals' => [
                'title' => ['en' => 'Animals', 'ar' => 'الحيوانات', 'ur' => 'جانور', 'eu' => 'Animaliak'],
                'addAnimal' => ['en' => 'Add Animal', 'ar' => 'إضافة حيوان', 'ur' => 'جانور شامل کریں', 'eu' => 'Animalia gehitu'],
                'editAnimal' => ['en' => 'Edit Animal', 'ar' => 'تعديل الحيوان', 'ur' => 'جانور ترمیم', 'eu' => 'Animalia editatu'],
                'name' => ['en' => 'Name', 'ar' => 'الاسم', 'ur' => 'نام', 'eu' => 'Izena'],
                'species' => ['en' => 'Species', 'ar' => 'النوع', 'ur' => 'قسم', 'eu' => 'Speziea'],
                'breed' => ['en' => 'Breed', 'ar' => 'السلالة', 'ur' => 'نسل', 'eu' => 'Arraza'],
                'status' => ['en' => 'Status', 'ar' => 'الحالة', 'ur' => 'حالت', 'eu' => 'Egoera'],
                'age' => ['en' => 'Age', 'ar' => 'العمر', 'ur' => 'عمر', 'eu' => 'Adina'],
                'weight' => ['en' => 'Weight', 'ar' => 'الوزن', 'ur' => 'وزن', 'eu' => 'Pisua'],
            ],
            'devices' => [
                'title' => ['en' => 'Devices', 'ar' => 'الأجهزة', 'ur' => 'آلات', 'eu' => 'Gailuak'],
                'addDevice' => ['en' => 'Add Device', 'ar' => 'إضافة جهاز', 'ur' => 'آلہ شامل کریں', 'eu' => 'Gailua gehitu'],
                'deviceId' => ['en' => 'Device ID', 'ar' => 'معرف الجهاز', 'ur' => 'آلہ کی شناخت', 'eu' => 'Gailuaren ID'],
                'batteryLevel' => ['en' => 'Battery Level', 'ar' => 'مستوى البطارية', 'ur' => 'بیٹری کی سطح', 'eu' => 'Bateria maila'],
                'firmware' => ['en' => 'Firmware', 'ar' => 'البرنامج الثابت', 'ur' => ' فرم ویئر', 'eu' => 'Firmwarea'],
            ],
'auth' => [
                'login' => ['en' => 'Sign In', 'ar' => 'تسجيل الدخول', 'ur' => 'لاگ ان', 'eu' => 'Saioa hasi'],
                'register' => ['en' => 'Sign Up', 'ar' => 'التسجيل', 'ur' => 'رجسٹر', 'eu' => 'Erregistratu'],
                'welcomeBack' => ['en' => 'Welcome Back', 'ar' => 'مرحباً بعودتك', 'ur' => 'واپس خوش آمدید', 'eu' => 'Ongi itzuli'],
                'loginSubtitle' => ['en' => 'Sign in to continue monitoring your livestock', 'ar' => 'سجل للمتابعة مراقبة ماشيتك', 'ur' => 'اپنے مویشیوں کی نگرانی جاری رکھنے کے لیے لاگ ان کریں', 'eu' => 'Saioa hasi zure abereak ikusten jarraitzeko'],
                'email' => ['en' => 'Email', 'ar' => 'البريد الإلكتروني', 'ur' => 'ای میل', 'eu' => 'Posta elektronikoa'],
                'password' => ['en' => 'Password', 'ar' => 'كلمة المرور', 'ur' => 'پاس ورڈ', 'eu' => 'Pasahitza'],
                'confirmPassword' => ['en' => 'Confirm Password', 'ar' => 'تأكيد كلمة المرور', 'ur' => 'پاس ورڈ کی تصدیق', 'eu' => 'Pasahitza berretsi'],
                'rememberMe' => ['en' => 'Remember me', 'ar' => 'تذكرني', 'ur' => 'مجھے یاد رکھیں', 'eu' => 'Gogorau'],
                'forgotPassword' => ['en' => 'Forgot Password?', 'ar' => 'هل نسيت كلمة المرور؟', 'ur' => 'پاس ورڈ بھول گئے؟', 'eu' => 'Pasahitza ahaztu?'],
                'noAccount' => ['en' => "Don't have an account?", 'ar' => 'ليس لديك حساب؟', 'ur' => 'کیا آپ کا اکاؤنٹ نہیں ہے؟', 'eu' => 'Ez daukazu konturik?'],
                'haveAccount' => ['en' => 'Already have an account?', 'ar' => 'لديك حساب بالفعل؟', 'ur' => 'کیا آپ کا پہلے سے اکاؤنٹ ہے؟', 'eu' => 'Daukazu dagoeneko konturik?'],
                'logout' => ['en' => 'Logout', 'ar' => 'تسجيل الخروج', 'ur' => 'لاگ آؤٹ', 'eu' => 'Saioa amaitu'],
                'enterEmail' => ['en' => 'Enter your email', 'ar' => 'أدخل بريدك الإلكتروني', 'ur' => 'اپنا ایمیل درج کریں', 'eu' => 'Sartu zure posta elektronikoa'],
                'enterPassword' => ['en' => 'Enter your password', 'ar' => 'أدخل كلمة المرور', 'ur' => 'اپنا پاس ورڈ درج کریں', 'eu' => 'Sartu zure pasahitza'],
                'emailRequired' => ['en' => 'Email is required', 'ar' => 'البريد الإلكتروني مطلوب', 'ur' => 'ای میل ضروری ہے', 'eu' => 'Posta elektronikoa beharrezkoa da'],
                'invalidEmail' => ['en' => 'Enter a valid email', 'ar' => 'أدخل بريد إلكتروني صالح', 'ur' => 'درست ایمیل درج کریں', 'eu' => 'Sartu posta elektroniko bali bat'],
                'passwordRequired' => ['en' => 'Password is required', 'ar' => 'كلمة المرور مطلوبة', 'ur' => 'پاس ورڈ ضروری ہے', 'eu' => 'Pasahitza beharrezkoa da'],
                'passwordMinLength' => ['en' => 'Password must be at least 4 characters', 'ar' => 'يجب أن تكون كلمة المرور 4 أحرف على الأقل', 'ur' => 'پاس ورڈ میں کم از کم 4 حروف ہونے چاہییے', 'eu' => 'Pasahitzak 4 karaktere izan behar ditu gutxieneko'],
            ],
            'nav' => [
                'dashboard' => ['en' => 'Dashboard', 'ar' => 'لوحة التحكم', 'ur' => 'ڈیش بورڈ', 'eu' => 'Azpiegitura'],
                'users' => ['en' => 'Users', 'ar' => 'المستخدمون', 'ur' => 'صارفین', 'eu' => 'Erabiltzaileak'],
                'animals' => ['en' => 'Animals', 'ar' => 'الحيوانات', 'ur' => 'جانور', 'eu' => 'Animaliak'],
                'geofences' => ['en' => 'Geofences', 'ar' => 'الأسوار', 'ur' => 'جغرافیائی حدود', 'eu' => 'Geofenceak'],
                'medicalRecords' => ['en' => 'Medical Records', 'ar' => 'السجلات الطبية', 'ur' => 'میڈیکل ریکارڈز', 'eu' => 'Eraso medikuak'],
                'vaccinations' => ['en' => 'Vaccinations', 'ar' => 'التلقيحات', 'ur' => 'ویکسینیشن', 'eu' => 'Txertaketak'],
                'devices' => ['en' => 'Devices', 'ar' => 'الأجهزة', 'ur' => 'آلات', 'eu' => 'Gailuak'],
                'mapView' => ['en' => 'Map View', 'ar' => 'عرض الخريطة', 'ur' => 'نقشہ', 'eu' => 'Mapa ikuspegia'],
                'auctions' => ['en' => 'Auctions', 'ar' => 'المزادات', 'ur' => 'مزاد', 'eu' => 'Molkak'],
                'alerts' => ['en' => 'Alerts', 'ar' => 'التنبيهات', 'ur' => 'الرسلے', 'eu' => 'Alertsak'],
                'tasks' => ['en' => 'Tasks', 'ar' => 'المهام', 'ur' => 'کام', 'eu' => 'Zereginak'],
                'reports' => ['en' => 'Reports', 'ar' => 'التقارير', 'ur' => 'رپورٹس', 'eu' => 'Txostenak'],
                'addNewEntry' => ['en' => 'Add New', 'ar' => 'إضافة جديد', 'ur' => 'نیا انٹری', 'eu' => 'Gehitu berria'],
                'settings' => ['en' => 'Settings', 'ar' => 'الإعدادات', 'ur' => 'سیٹنگز', 'eu' => 'Ezarpenak'],
                'profile' => ['en' => 'Profile', 'ar' => 'الملف الشخصي', 'ur' => 'پروفائل', 'eu' => 'Profila'],
                'team' => ['en' => 'Team', 'ar' => 'الفريق', 'ur' => 'ٹیم', 'eu' => 'Taldea'],
            ],
            'settings' => [
                'title' => ['en' => 'Settings', 'ar' => 'الإعدادات', 'ur' => 'سیٹنگز', 'eu' => 'Ezarpenak'],
                'account' => ['en' => 'Account', 'ar' => 'الحساب', 'ur' => 'اکاؤنٹ', 'eu' => 'Kontua'],
                'notifications' => ['en' => 'Notifications', 'ar' => 'الإشعارات', 'ur' => 'الرسلے', 'eu' => 'Jakinarazpenak'],
                'appSettings' => ['en' => 'App Settings', 'ar' => 'إعدادات التطبيق', 'ur' => 'ایپ سیٹنگز', 'eu' => 'Apparen ezarpenak'],
                'about' => ['en' => 'About', 'ar' => 'حول', 'ur' => 'کے بارے میں', 'eu' => 'Honi buruz'],
                'pushNotifications' => ['en' => 'Push Notifications', 'ar' => 'إشعارات فورية', 'ur' => 'پش نوٹیفیکیشن', 'eu' => 'Push jakinarazpenak'],
                'pushNotificationsSubtitle' => ['en' => 'Receive alerts on your device', 'ar' => 'تلقي التنبيهات على جهازك', 'ur' => 'اپنے آلہ پر الرسلے وصول کریں', 'eu' => 'Alertsak jaso zure gailuan'],
                'emailNotifications' => ['en' => 'Email Notifications', 'ar' => 'إشعارات البريد الإلكتروني', 'ur' => 'ای میل نوٹیفیکیشن', 'eu' => 'Posta jakinarazpenak'],
                'emailNotificationsSubtitle' => ['en' => 'Receive updates via email', 'ar' => 'تلقي التحديثات عبر البريد', 'ur' => 'ای میل کے ذریعے اپڈیٹس حاصل کریں', 'eu' => 'Eguneratzeak posta bidali'],
                'darkMode' => ['en' => 'Dark Mode', 'ar' => 'الوضع الداكن', 'ur' => 'ڈارک موڈ', 'eu' => 'Ilun modua'],
                'darkModeSubtitle' => ['en' => 'Use dark theme', 'ar' => 'استخدم السمة الداكنة', 'ur' => 'ڈارک تھیم کا use کریں', 'eu' => 'Ilun gaia erabili'],
                'locationTracking' => ['en' => 'Location Tracking', 'ar' => 'تتبع الموقع', 'ur' => 'لوکیشن ٹریکنگ', 'eu' => 'Kokapena jarraitzea'],
                'locationTrackingSubtitle' => ['en' => 'Track animal locations', 'ar' => 'تتبع مواقع الحيوانات', 'ur' => 'جانورں کے مقامات ٹریک کریں', 'eu' => 'Animalien kokapena jarraitu'],
                'temperatureUnit' => ['en' => 'Temperature Unit', 'ar' => 'وحدة الحرارة', 'ur' => 'درجہ حرارت کی اکائی', 'eu' => 'Tenperatura unitatea'],
                'language' => ['en' => 'Language', 'ar' => 'اللغة', 'ur' => 'زبان', 'eu' => 'Hizkuntza'],
                'appVersion' => ['en' => 'App Version', 'ar' => 'إصدار التطبيق', 'ur' => 'ایپ ورزن', 'eu' => 'App bertsioa'],
                'privacyPolicy' => ['en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية', 'ur' => 'پرائیویسی پالیسی', 'eu' => 'Pribatutasun politika'],
                'termsOfService' => ['en' => 'Terms of Service', 'ar' => 'شروط الخدمة', 'ur' => 'سروس کی شرائط', 'eu' => 'Zerbitzu baldintzak'],
                'signOut' => ['en' => 'Sign Out', 'ar' => 'تسجيل الخروج', 'ur' => 'لاگ آؤٹ', 'eu' => 'Saioa amaitu'],
            ],
            'users' => [
                'title' => ['en' => 'Users', 'ar' => 'المستخدمون', 'ur' => 'صارفین', 'eu' => 'Erabiltzaileak'],
                'addUser' => ['en' => 'Add User', 'ar' => 'إضافة مستخدم', 'ur' => 'صارف شامل کریں', 'eu' => 'Erabiltzailea gehitu'],
                'name' => ['en' => 'Name', 'ar' => 'الاسم', 'ur' => 'نام', 'eu' => 'Izena'],
                'role' => ['en' => 'Role', 'ar' => 'الدور', 'ur' => 'کردار', 'eu' => 'Funtzioa'],
                'subscription' => ['en' => 'Subscription', 'ar' => 'الاشتراك', 'ur' => 'سبسکرپشن', 'eu' => 'Harpidetza'],
                'phone' => ['en' => 'Phone', 'ar' => 'الهاتف', 'ur' => 'فون', 'eu' => 'Telefonoa'],
                'owner' => ['en' => 'Owner', 'ar' => 'المالك', 'ur' => 'مالک', 'eu' => 'Jabea'],
                'createdAt' => ['en' => 'Created', 'ar' => 'تم الإنشاء', 'ur' => ' بنایا گیا', 'eu' => 'Sortuta'],
            ],
            'errors' => [
                'unauthorized' => ['en' => 'Unauthorized', 'ar' => 'غير مصرح', 'ur' => 'غیر مجاز', 'eu' => 'Baimendu gabe'],
                'serverError' => ['en' => 'Server Error', 'ar' => 'خطأ في الخادم', 'ur' => 'سرور کی خرابی', 'eu' => 'Zerbitzarierrorea'],
                'networkError' => ['en' => 'Network Error', 'ar' => 'خطأ في الشبكة', 'ur' => 'نیٹ ورک کی خرابی', 'eu' => 'Sare errorea'],
            ],
            'team' => [
                'title' => ['en' => 'Team', 'ar' => 'الفريق', 'ur' => 'ٹیم', 'eu' => 'Taldea'],
                'teamMembers' => ['en' => 'Team Members', 'ar' => 'أعضاء الفريق', 'ur' => 'ٹیم ممبران', 'eu' => 'Taldekideak'],
            ],
            'alerts' => [
                'title' => ['en' => 'Alerts', 'ar' => 'التنبيهات', 'ur' => 'الرسلے', 'eu' => 'Alertsak'],
                'geofenceEntry' => ['en' => 'Geofence Entry', 'ar' => 'دخول المنطقة', 'ur' => 'جیو فینس انٹری', 'eu' => 'Geofence sarrera'],
                'geofenceExit' => ['en' => 'Geofence Exit', 'ar' => 'خروج المنطقة', 'ur' => 'جیو فینس ایکسٹ', 'eu' => 'Geofence irteera'],
                'temperature' => ['en' => 'Temperature', 'ar' => 'درجة الحرارة', 'ur' => 'درجہ حرارت', 'eu' => 'Tenperatura'],
                'deviceOffline' => ['en' => 'Device Offline', 'ar' => 'الجهاز غير متصل', 'ur' => 'آلہ آف لائن', 'eu' => 'Gailua konektatu gabe'],
                'critical' => ['en' => 'Critical', 'ar' => 'حرج', 'ur' => 'فوقیہ', 'eu' => 'Kritikoa'],
            ],
            'subscription' => [
                'title' => ['en' => 'Subscription', 'ar' => 'الاشتراك', 'ur' => 'سبسکرپشن', 'eu' => 'Harpidetza'],
                'active' => ['en' => 'Active', 'ar' => 'نشط', 'ur' => 'فعال', 'eu' => 'Aktiboa'],
                'selectPlan' => ['en' => 'Select Plan', 'ar' => 'اختر الخطة', 'ur' => 'پلان انتخاب کریں', 'eu' => 'Plan bat aukeratu'],
            ],
        ];

        foreach ($groups as $group => $keys) {
            foreach ($keys as $key => $langValues) {
                foreach ($langValues as $code => $value) {
                    $translations[] = [
                        'language_code' => $code,
                        'group' => $group,
                        'key' => $key,
                        'value' => $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        return $translations;
    }
}