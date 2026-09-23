<?php

use App\Models\ProgramSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->settings() as $data) {
            $setting = ProgramSetting::query()->firstOrNew(['key' => $data['key']]);

            if ($setting->exists) {
                $legacy = [
                    'user_referral_referrer_reward' => '1.50',
                    'user_referral_invitee_reward' => '1.00',
                ];

                if (($legacy[$data['key']] ?? null) === (string) $setting->value) {
                    $setting->value = $data['value'];
                    $setting->save();
                }

                continue;
            }

            $setting->value = $data['value'];
            $setting->sort_order = $data['sort_order'];
            $setting->setTranslations('label', $data['label']);
            $setting->setTranslations('description', $data['description']);
            $setting->save();
        }
    }

    public function down(): void
    {
        ProgramSetting::query()
            ->whereIn('key', [
                'user_referral_referrer_reward',
                'user_referral_invitee_reward',
            ])
            ->delete();
    }

    /**
     * @return list<array{key: string, value: string, sort_order: int, label: array<string, string>, description: array<string, string>}>
     */
    protected function settings(): array
    {
        return [
            [
                'key' => 'user_referral_referrer_reward',
                'value' => '1.00',
                'sort_order' => 13,
                'label' => [
                    'en' => 'User referral referrer reward',
                    'es' => 'Recompensa del referente (usuarios)',
                    'ru' => 'Награда пригласившему (пользователи)',
                    'fr' => 'Récompense du parrain (utilisateurs)',
                    'de' => 'Empfehler-Prämie (Nutzer)',
                    'tg' => 'Мукофоти даъваткунанда (корбарон)',
                    'ar' => 'مكافأة المُحيل (المستخدمون)',
                ],
                'description' => [
                    'en' => 'USD credited to a user when someone registers with their referral code.',
                    'es' => 'USD acreditado a un usuario cuando alguien se registra con su código.',
                    'ru' => 'USD на кошелёк пользователя, когда кто-то регистрируется по его коду.',
                    'fr' => 'USD crédité à un utilisateur lorsqu’une personne s’inscrit avec son code.',
                    'de' => 'USD-Gutschrift für Nutzer, wenn sich jemand mit ihrem Code registriert.',
                    'tg' => 'USD ба ҳамёни корбар ҳангоми бақайдгирӣ бо рамзи ӯ.',
                    'ar' => 'مبلغ بالدولار يُضاف للمستخدم عندما يسجل شخص برمز إحالته.',
                ],
            ],
            [
                'key' => 'user_referral_invitee_reward',
                'value' => '1.50',
                'sort_order' => 14,
                'label' => [
                    'en' => 'User referral invitee reward',
                    'es' => 'Recompensa del invitado (usuarios)',
                    'ru' => 'Награда приглашённому (пользователи)',
                    'fr' => 'Récompense du filleul (utilisateurs)',
                    'de' => 'Eingeladenen-Prämie (Nutzer)',
                    'tg' => 'Мукофоти даъватшуда (корбарон)',
                    'ar' => 'مكافأة المدعو (المستخدمون)',
                ],
                'description' => [
                    'en' => 'USD credited to a new user who registers with another user’s referral code.',
                    'es' => 'USD acreditado al nuevo usuario que se registra con el código de otro usuario.',
                    'ru' => 'USD новому пользователю при регистрации по коду другого пользователя.',
                    'fr' => 'USD crédité au nouvel utilisateur qui s’inscrit avec le code d’un autre utilisateur.',
                    'de' => 'USD-Gutschrift für neue Nutzer, die sich mit einem Nutzer-Code registrieren.',
                    'tg' => 'USD ба корбари нав ҳангоми бақайдгирӣ бо рамзи корбари дигар.',
                    'ar' => 'مبلغ بالدولار يُضاف للمستخدم الجديد عند التسجيل برمز مستخدم آخر.',
                ],
            ],
        ];
    }
};
