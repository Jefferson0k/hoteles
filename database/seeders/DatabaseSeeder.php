<?php


namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder{
    public function run(): void{
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            ProviderSeeder::class,
            CurrencySeeder::class,
            PaymentMethodSeeder::class,
            #RateTypeSeeder::class,
        ]);
    }
}