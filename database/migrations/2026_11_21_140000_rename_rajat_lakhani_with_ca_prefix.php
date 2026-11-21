<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Show the partner as "CA Rajat Lakhani" everywhere (name was "Rajat Lakhani"). */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereRaw('LOWER(TRIM(name)) = ?', ['rajat lakhani'])
            ->update(['name' => 'CA Rajat Lakhani']);
    }

    public function down(): void
    {
        DB::table('users')->where('name', 'CA Rajat Lakhani')->update(['name' => 'Rajat Lakhani']);
    }
};
