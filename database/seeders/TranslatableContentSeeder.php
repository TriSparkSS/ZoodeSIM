<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use App\Models\ProgramSetting;
use Illuminate\Database\Seeder;

class TranslatableContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedContentBlocks();
        $this->seedProgramSettings();
    }

    protected function seedContentBlocks(): void
    {
        $blocks = [
            [
                'slug' => 'apply.hero.title',
                'title' => [
                    'en' => 'Become a ZoodeSIM Partner',
                    'es' => 'Conviértete en socio de ZoodeSIM',
                    'ru' => 'Станьте партнёром ZoodeSIM',
                    'fr' => 'Devenez partenaire ZoodeSIM',
                    'de' => 'Werden Sie ZoodeSIM-Partner',
                    'tg' => 'Шарики ZoodeSIM шавед',
                ],
                'body' => [
                    'en' => 'Earn rewards by referring travelers to ZoodeSIM eSIM.',
                    'es' => 'Gana recompensas recomendando ZoodeSIM eSIM a viajeros.',
                    'ru' => 'Зарабатывайте, рекомендуя ZoodeSIM eSIM путешественникам.',
                    'fr' => 'Gagnez des récompenses en recommandant ZoodeSIM eSIM aux voyageurs.',
                    'de' => 'Verdienen Sie Belohnungen, indem Sie Reisenden ZoodeSIM eSIM empfehlen.',
                    'tg' => 'Бо тавсияи ZoodeSIM eSIM ба мусофирон мукофот ба даст оред.',
                ],
            ],
        ];

        foreach ($blocks as $data) {
            $block = ContentBlock::query()->firstOrNew(['slug' => $data['slug']]);
            $block->is_active = true;
            $block->setTranslations('title', $data['title']);
            $block->setTranslations('body', $data['body']);
            $block->save();
        }
    }

    protected function seedProgramSettings(): void
    {
        $settings = [
            [
                'key' => 'registration_reward',
                'value' => '1.50',
                'sort_order' => 1,
                'label' => [
                    'en' => 'Registration reward',
                    'es' => 'Recompensa por registro',
                    'ru' => 'Вознаграждение за регистрацию',
                    'fr' => 'Récompense d’inscription',
                    'de' => 'Registrierungsprämie',
                    'tg' => 'Мукофот барои бақайдгирӣ',
                ],
                'description' => [
                    'en' => 'Amount credited to the partner per successful registration.',
                    'es' => 'Importe acreditado al socio por cada registro exitoso.',
                    'ru' => 'Сумма, начисляемая партнёру за успешную регистрацию.',
                    'fr' => 'Montant crédité au partenaire pour chaque inscription réussie.',
                    'de' => 'Betrag, der dem Partner je erfolgreicher Registrierung gutgeschrieben wird.',
                    'tg' => 'Маблағе, ки барои ҳар бақайдгирии муваффақ ба шарик ҳисоб карда мешавад.',
                ],
            ],
            [
                'key' => 'purchase_commission',
                'value' => '10',
                'sort_order' => 2,
                'label' => [
                    'en' => 'Purchase commission',
                    'es' => 'Comisión por compra',
                    'ru' => 'Комиссия с покупки',
                    'fr' => 'Commission sur achat',
                    'de' => 'Kaufprovision',
                    'tg' => 'Комиссия аз харид',
                ],
                'description' => [
                    'en' => 'Percentage commission on referred user purchases.',
                    'es' => 'Porcentaje de comisión sobre compras de usuarios referidos.',
                    'ru' => 'Процент комиссии с покупок приглашённых пользователей.',
                    'fr' => 'Pourcentage de commission sur les achats des utilisateurs parrainés.',
                    'de' => 'Prozentuale Provision auf Käufe geworbener Nutzer.',
                    'tg' => 'Фоизи комиссия аз харидҳои истифодабарандагони даъватшуда.',
                ],
            ],
            [
                'key' => 'user_bonus',
                'value' => '200',
                'sort_order' => 3,
                'label' => [
                    'en' => 'Default user bonus',
                    'es' => 'Bono de usuario predeterminado',
                    'ru' => 'Бонус пользователю по умолчанию',
                    'fr' => 'Bonus utilisateur par défaut',
                    'de' => 'Standard-Nutzerbonus',
                    'tg' => 'Бонуси пешфарзии корбар',
                ],
                'description' => [
                    'en' => 'Default data bonus (MB) granted to users via promo codes.',
                    'es' => 'Bono de datos (MB) predeterminado otorgado a usuarios con códigos promo.',
                    'ru' => 'Бонус данных (МБ) по умолчанию для пользователей по промокоду.',
                    'fr' => 'Bonus data (Mo) par défaut accordé aux utilisateurs via codes promo.',
                    'de' => 'Standard-Datenbonus (MB) für Nutzer über Promo-Codes.',
                    'tg' => 'Бонуси пешфарзии маълумот (МБ) барои корбарон тавассути промокод.',
                ],
            ],
            [
                'key' => 'min_withdrawal',
                'value' => '50',
                'sort_order' => 4,
                'label' => [
                    'en' => 'Minimum withdrawal',
                    'es' => 'Retiro mínimo',
                    'ru' => 'Минимальный вывод',
                    'fr' => 'Retrait minimum',
                    'de' => 'Mindestauszahlung',
                    'tg' => 'Ҳадди ақали бозпасгирӣ',
                ],
                'description' => [
                    'en' => 'Minimum balance required before a partner can request payout.',
                    'es' => 'Saldo mínimo requerido antes de solicitar un pago.',
                    'ru' => 'Минимальный баланс для запроса выплаты.',
                    'fr' => 'Solde minimum requis avant une demande de paiement.',
                    'de' => 'Mindestguthaben vor einer Auszahlungsanfrage.',
                    'tg' => 'Ҳадди ақали тавозун пеш аз дархости пардохт.',
                ],
            ],
            [
                'key' => 'subsequent_purchase_commission',
                'value' => '5',
                'sort_order' => 5,
                'label' => [
                    'en' => 'Subsequent purchase commission',
                    'es' => 'Comisión de compras siguientes',
                    'ru' => 'Комиссия с повторных покупок',
                    'fr' => 'Commission des achats suivants',
                    'de' => 'Provision für Folgekäufe',
                    'tg' => 'Комиссияи харидҳои баъдӣ',
                    'ar' => 'عمولة المشتريات التالية',
                ],
                'description' => [
                    'en' => 'Percentage commission on later purchases by a referred user.',
                    'es' => 'Porcentaje de comisión en compras posteriores de un usuario referido.',
                    'ru' => 'Процент комиссии с последующих покупок приглашённого пользователя.',
                    'fr' => 'Pourcentage de commission sur les achats suivants d’un filleul.',
                    'de' => 'Prozentuale Provision auf spätere Käufe eines geworbenen Nutzers.',
                    'tg' => 'Фоизи комиссия аз харидҳои баъдии корбари даъватшуда.',
                    'ar' => 'نسبة العمولة على المشتريات اللاحقة للمستخدم المحال.',
                ],
            ],
            [
                'key' => 'first_purchase_discount',
                'value' => '5',
                'sort_order' => 6,
                'label' => [
                    'en' => 'First purchase discount',
                    'es' => 'Descuento de primera compra',
                    'ru' => 'Скидка на первую покупку',
                    'fr' => 'Remise première achat',
                    'de' => 'Erstkaufrabatt',
                    'tg' => 'Тахфифи хариди аввал',
                    'ar' => 'خصم أول شراء',
                ],
                'description' => [
                    'en' => 'Percent off the first paid eSIM purchase within the discount window.',
                    'es' => 'Porcentaje de descuento en la primera compra de eSIM dentro del plazo.',
                    'ru' => 'Процент скидки на первую оплаченную покупку eSIM в период действия.',
                    'fr' => 'Pourcentage de remise sur le premier achat eSIM payé pendant la fenêtre.',
                    'de' => 'Prozent Rabatt auf den ersten bezahlten eSIM-Kauf im Zeitfenster.',
                    'tg' => 'Фоизи тахфиф барои хариди аввалини eSIM дар муҳлат.',
                    'ar' => 'نسبة الخصم على أول شراء eSIM مدفوع خلال الفترة.',
                ],
            ],
            [
                'key' => 'first_purchase_discount_days',
                'value' => '7',
                'sort_order' => 7,
                'label' => [
                    'en' => 'First purchase discount days',
                    'es' => 'Días de descuento de primera compra',
                    'ru' => 'Дни скидки на первую покупку',
                    'fr' => 'Jours de remise première achat',
                    'de' => 'Tage für Erstkaufrabatt',
                    'tg' => 'Рӯзҳои тахфифи хариди аввал',
                    'ar' => 'أيام خصم أول شراء',
                ],
                'description' => [
                    'en' => 'Days after registration that the first-purchase discount remains valid.',
                    'es' => 'Días tras el registro en que el descuento de primera compra es válido.',
                    'ru' => 'Сколько дней после регистрации действует скидка на первую покупку.',
                    'fr' => 'Nombre de jours après l’inscription pendant lesquels la remise s’applique.',
                    'de' => 'Tage nach der Registrierung, in denen der Erstkaufrabatt gilt.',
                    'tg' => 'Шумораи рӯзҳо пас аз сабти ном, ки тахфифи хариди аввал амал мекунад.',
                    'ar' => 'عدد الأيام بعد التسجيل التي يبقى فيها خصم أول شراء ساريًا.',
                ],
            ],
            [
                'key' => 'cashback_percent',
                'value' => '10',
                'sort_order' => 8,
                'label' => [
                    'en' => 'Cashback percent',
                    'es' => 'Porcentaje de cashback',
                    'ru' => 'Процент кэшбэка',
                    'fr' => 'Pourcentage de cashback',
                    'de' => 'Cashback-Prozentsatz',
                    'tg' => 'Фоизи кэшбэк',
                    'ar' => 'نسبة الاسترداد',
                ],
                'description' => [
                    'en' => 'Percent credited to the user wallet when the charged amount exceeds the cashback minimum.',
                    'es' => 'Porcentaje acreditado al monedero si el importe cobrado supera el mínimo.',
                    'ru' => 'Процент на кошелёк пользователя, если сумма оплаты выше минимума.',
                    'fr' => 'Pourcentage crédité au portefeuille si le montant payé dépasse le minimum.',
                    'de' => 'Prozentsatz auf das Nutzer-Wallet, wenn der Zahlbetrag das Minimum übersteigt.',
                    'tg' => 'Фоиз ба ҳамёни корбар, агар маблағи пардохт аз ҳадди ақал зиёд бошад.',
                    'ar' => 'النسبة المضافة لمحفظة المستخدم عندما يتجاوز المبلغ المدفوع الحد الأدنى.',
                ],
            ],
            [
                'key' => 'cashback_min_amount',
                'value' => '10',
                'sort_order' => 9,
                'label' => [
                    'en' => 'Cashback minimum amount',
                    'es' => 'Importe mínimo de cashback',
                    'ru' => 'Минимальная сумма для кэшбэка',
                    'fr' => 'Montant minimum de cashback',
                    'de' => 'Cashback-Mindestbetrag',
                    'tg' => 'Ҳадди ақали маблағ барои кэшбэк',
                    'ar' => 'الحد الأدنى لمبلغ الاسترداد',
                ],
                'description' => [
                    'en' => 'Charged amount must be greater than this value to earn cashback.',
                    'es' => 'El importe cobrado debe ser mayor que este valor para obtener cashback.',
                    'ru' => 'Сумма оплаты должна быть больше этого значения для кэшбэка.',
                    'fr' => 'Le montant payé doit être supérieur à cette valeur pour le cashback.',
                    'de' => 'Der Zahlbetrag muss über diesem Wert liegen, um Cashback zu erhalten.',
                    'tg' => 'Маблағи пардохт бояд аз ин қимат зиёд бошад, то кэшбэк гиред.',
                    'ar' => 'يجب أن يتجاوز المبلغ المدفوع هذه القيمة للحصول على الاسترداد.',
                ],
            ],
            [
                'key' => 'milestone_10',
                'value' => '5',
                'sort_order' => 10,
                'label' => [
                    'en' => '10-referral milestone',
                    'es' => 'Hito de 10 referidos',
                    'ru' => 'Бонус за 10 рефералов',
                    'fr' => 'Palier de 10 filleuls',
                    'de' => 'Meilenstein 10 Empfehlungen',
                    'tg' => 'Мукофот барои 10 реферал',
                    'ar' => 'مكافأة 10 إحالات',
                ],
                'description' => [
                    'en' => 'USD bonus credited once when a partner reaches 10 referrals.',
                    'es' => 'Bono en USD acreditado una vez al alcanzar 10 referidos.',
                    'ru' => 'Разовый бонус в USD при достижении 10 рефералов.',
                    'fr' => 'Bonus USD crédité une fois à 10 filleuls.',
                    'de' => 'Einmaliger USD-Bonus bei 10 Empfehlungen.',
                    'tg' => 'Мукофоти яккарата бо USD ҳангоми 10 реферал.',
                    'ar' => 'مكافأة لمرة واحدة بالدولار عند الوصول إلى 10 إحالات.',
                ],
            ],
            [
                'key' => 'milestone_50',
                'value' => '30',
                'sort_order' => 11,
                'label' => [
                    'en' => '50-referral milestone',
                    'es' => 'Hito de 50 referidos',
                    'ru' => 'Бонус за 50 рефералов',
                    'fr' => 'Palier de 50 filleuls',
                    'de' => 'Meilenstein 50 Empfehlungen',
                    'tg' => 'Мукофот барои 50 реферал',
                    'ar' => 'مكافأة 50 إحالة',
                ],
                'description' => [
                    'en' => 'USD bonus credited once when a partner reaches 50 referrals.',
                    'es' => 'Bono en USD acreditado una vez al alcanzar 50 referidos.',
                    'ru' => 'Разовый бонус в USD при достижении 50 рефералов.',
                    'fr' => 'Bonus USD crédité une fois à 50 filleuls.',
                    'de' => 'Einmaliger USD-Bonus bei 50 Empfehlungen.',
                    'tg' => 'Мукофоти яккарата бо USD ҳангоми 50 реферал.',
                    'ar' => 'مكافأة لمرة واحدة بالدولار عند الوصول إلى 50 إحالة.',
                ],
            ],
            [
                'key' => 'milestone_100',
                'value' => '75',
                'sort_order' => 12,
                'label' => [
                    'en' => '100-referral milestone',
                    'es' => 'Hito de 100 referidos',
                    'ru' => 'Бонус за 100 рефералов',
                    'fr' => 'Palier de 100 filleuls',
                    'de' => 'Meilenstein 100 Empfehlungen',
                    'tg' => 'Мукофот барои 100 реферал',
                    'ar' => 'مكافأة 100 إحالة',
                ],
                'description' => [
                    'en' => 'USD bonus credited once when a partner reaches 100 referrals.',
                    'es' => 'Bono en USD acreditado una vez al alcanzar 100 referidos.',
                    'ru' => 'Разовый бонус в USD при достижении 100 рефералов.',
                    'fr' => 'Bonus USD crédité une fois à 100 filleuls.',
                    'de' => 'Einmaliger USD-Bonus bei 100 Empfehlungen.',
                    'tg' => 'Мукофоти яккарата бо USD ҳангоми 100 реферал.',
                    'ar' => 'مكافأة لمرة واحدة بالدولار عند الوصول إلى 100 إحالة.',
                ],
            ],
        ];

        foreach ($settings as $data) {
            $setting = ProgramSetting::query()->firstOrNew(['key' => $data['key']]);
            $setting->value = $data['value'];
            $setting->sort_order = $data['sort_order'];
            $setting->setTranslations('label', $data['label']);
            $setting->setTranslations('description', $data['description']);
            $setting->save();
        }
    }
}
