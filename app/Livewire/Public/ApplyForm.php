<?php

namespace App\Livewire\Public;

use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithToast;
use App\Models\PartnerApplication;
use App\Services\Content\ContentBlockService;
use App\Services\Country\Contracts\CountryServiceInterface;
use App\Services\Partner\PartnerService;
use App\Services\Referral\ReferralProgramSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class ApplyForm extends Component
{
    use WithLocalizedTitle;
    use WithToast;

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    public array $platforms = [];

    public string $instagram = '';

    public string $telegram = '';

    public string $tiktok = '';

    public string $youtube = '';

    public string $followers = '';

    public string $niche = '';

    public string $country = '';

    public string $about = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public bool $submitted = false;

    public function togglePlatform(string $platform): void
    {
        if (in_array($platform, $this->platforms, true)) {
            $this->platforms = array_values(array_diff($this->platforms, [$platform]));
        } else {
            $this->platforms[] = $platform;
        }
    }

    public function submit(PartnerService $partners): void
    {
        $this->validate([
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:partners,email'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'string'],
            'platforms' => ['required', 'array', 'min:1'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'tiktok' => ['nullable', 'string', 'max:255'],
            'youtube' => ['nullable', 'string', 'max:255'],
            'followers' => ['required', 'string'],
            'niche' => ['required', 'string'],
            'country' => ['nullable', 'string', 'size:2', Rule::exists('countries', 'code')->where('is_active', true)],
            'about' => ['nullable', 'string', 'max:2000'],
        ], [
            'firstName.required' => __('apply.validation.required_fields'),
            'email.required' => __('apply.validation.required_fields'),
            'email.email' => __('apply.validation.email_invalid'),
            'email.unique' => __('apply.validation.email_taken'),
            'phone.required' => __('apply.validation.required_fields'),
            'password.required' => __('apply.validation.required_fields'),
            'password.min' => __('apply.validation.password_min'),
            'password.same' => __('apply.validation.password_confirmed'),
            'passwordConfirmation.required' => __('apply.validation.required_fields'),
            'platforms.required' => __('apply.validation.platform_required'),
            'platforms.min' => __('apply.validation.platform_required'),
            'followers.required' => __('apply.validation.followers_required'),
            'niche.required' => __('apply.validation.niche_required'),
            'country.size' => __('apply.validation.country_invalid'),
            'country.exists' => __('apply.validation.country_invalid'),
        ]);

        // PDF requirement: contact (Telegram/Instagram) + link to their account.
        // Our UI collects `telegram` and `instagram` URLs depending on selected platforms.
        $contactErrors = [];
        if (in_array('telegram', $this->platforms, true) && blank($this->telegram)) {
            $contactErrors['telegram'] = __('apply.validation.required_fields');
        }
        if (in_array('instagram', $this->platforms, true) && blank($this->instagram)) {
            $contactErrors['instagram'] = __('apply.validation.required_fields');
        }
        if (empty($contactErrors) && blank($this->telegram) && blank($this->instagram)) {
            // Defensive: ensure at least one contact is provided.
            $contactErrors['telegram'] = __('apply.validation.required_fields');
        }

        if ($contactErrors !== []) {
            throw ValidationException::withMessages($contactErrors);
        }

        DB::transaction(function () use ($partners) {
            $application = PartnerApplication::query()->create([
                'first_name' => $this->firstName,
                'last_name' => $this->lastName !== '' ? $this->lastName : null,
                'email' => $this->email,
                'phone' => $this->phone,
                'platforms' => array_values($this->platforms),
                'instagram' => $this->instagram !== '' ? $this->instagram : null,
                'telegram' => $this->telegram !== '' ? $this->telegram : null,
                'tiktok' => $this->tiktok !== '' ? $this->tiktok : null,
                'youtube' => $this->youtube !== '' ? $this->youtube : null,
                'followers' => $this->followers,
                'niche' => $this->niche,
                'country' => $this->country !== '' ? strtoupper($this->country) : null,
                'about' => $this->about !== '' ? $this->about : null,
                'status' => 'pending',
            ]);

            $created = $partners->createFromApplication($application, $this->password, 'pending');

            $application->update([
                'partner_id' => $created['partner']->id,
            ]);
        });

        $this->password = '';
        $this->passwordConfirmation = '';
        $this->submitted = true;
    }

    public function render(ContentBlockService $content, ReferralProgramSettings $program, CountryServiceInterface $countries)
    {
        $hero = $content->pair(
            'apply.hero.title',
            __('apply.hero.title', ['brand' => __('apply.hero.title_brand')]),
            __('apply.hero.subtitle'),
        );

        return $this->withLocalizedTitle(view('livewire.public.apply-form', [
            'heroTitle' => $hero['title'],
            'heroSubtitle' => $hero['body'],
            'registrationReward' => '$'.$program->defaultRegistrationReward(),
            'purchaseCommission' => $program->percentLabel($program->firstPurchaseCommissionPercent()),
            'userBonus' => $program->defaultUserBonusMb().' MB',
            'countries' => $countries->active(),
        ]), 'apply.form.title');
    }
}
