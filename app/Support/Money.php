<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Integer minor-unit money. Never use for FX conversion.
 */
final readonly class Money
{
    public function __construct(
        public int $cents,
        public string $currency,
    ) {
        if ($this->cents < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }
    }

    public static function fromCents(int $cents, string $currency): self
    {
        return new self($cents, strtoupper($currency));
    }

    public static function fromDecimal(string|int|float $amount, string $currency): self
    {
        return new self(self::decimalToCents($amount), strtoupper($currency));
    }

    public static function decimalToCents(string|int|float $amount): int
    {
        $normalized = self::normalizeDecimal($amount);

        return (int) bcmul($normalized, '100', 0);
    }

    public function toDecimal(): string
    {
        return bcdiv((string) $this->cents, '100', 2);
    }

    public function toFloat(): float
    {
        return (float) $this->toDecimal();
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        if ($other->cents > $this->cents) {
            throw new InvalidArgumentException('Money subtraction would be negative.');
        }

        return new self($this->cents - $other->cents, $this->currency);
    }

    public function greaterThanOrEqual(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->cents >= $other->cents;
    }

    /**
     * Markup from a percentage such as "5.00". Uses integer cents and half-up rounding.
     */
    public function percentageOf(string $percentage): self
    {
        $normalized = self::normalizeDecimal($percentage);
        $basisPoints = (int) bcmul($normalized, '100', 0);
        $markupCents = (int) round($this->cents * $basisPoints / 10000);

        return new self($markupCents, $this->currency);
    }

    public function format(): string
    {
        $amount = $this->toDecimal();

        return match ($this->currency) {
            'USD' => '$'.$amount,
            'INR' => '₹'.$amount,
            default => $this->currency.' '.$amount,
        };
    }

    public static function normalizeDecimal(string|int|float $amount): string
    {
        if (is_int($amount)) {
            return bcadd((string) $amount, '0', 2);
        }

        if (is_float($amount)) {
            $amount = number_format($amount, 2, '.', '');
        }

        $amount = trim((string) $amount);

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('Invalid money amount.');
        }

        return bcadd($amount, '0', 2);
    }

    protected function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Currency mismatch.');
        }
    }
}
