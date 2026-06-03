<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomersImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;
    public int $skipped = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name    = trim((string)($row['name'] ?? ''));
            $phone   = trim((string)($row['phone'] ?? ''));
            $email   = trim((string)($row['email'] ?? ''));
            $address = trim((string)($row['address'] ?? ''));

            // Name is required
            if ($name === '') {
                $this->skipped++;
                continue;
            }

            // Skip duplicates by email or phone
            $exists = Customer::query()
                ->when($email !== '', fn ($q) => $q->orWhere('email', $email))
                ->when($phone !== '', fn ($q) => $q->orWhere('phone', $phone))
                ->exists();

            if ($exists) {
                $this->skipped++;
                continue;
            }

            $customer = Customer::create([
                'name'      => $name,
                'phone'     => $phone ?: null,
                'email'     => $email !== '' ? $email : (Str::uuid() . '@placeholder.local'),
                'password'  => Hash::make(Str::random(16)),
                'is_active' => true,
            ]);

            if ($address !== '') {
                $customer->addresses()->create([
                    'line1'      => $address,
                    'city'       => '-',
                    'country'    => 'SA',
                    'is_default' => true,
                ]);
            }

            $this->created++;
        }
    }
}