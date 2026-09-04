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
