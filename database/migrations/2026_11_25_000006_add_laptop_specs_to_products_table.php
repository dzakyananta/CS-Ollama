<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand', 50)->default('Microdata');
            $table->string('processor', 100)->default('Belum ditentukan');
            $table->string('ram', 24)->default('8 GB');
            $table->string('storage', 32)->default('256 GB SSD');
            $table->string('graphics', 100)->default('Integrated graphics');
            $table->string('screen', 80)->default('14 inci Full HD');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['brand', 'processor', 'ram', 'storage', 'graphics', 'screen']);
        });
    }
};