<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $t) {
            $t->boolean('manual')->default(false)->after('active');
        });
    }
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $t) { $t->dropColumn('manual'); });
    }
};