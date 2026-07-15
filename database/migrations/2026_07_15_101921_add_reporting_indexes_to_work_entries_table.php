<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_entries', function (Blueprint $table) {
            $table->index(['user_id', 'work_date', 'start_time'], 'work_entries_user_date_start_index');
            $table->index(['work_date', 'start_time', 'user_id'], 'work_entries_date_start_user_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_entries', function (Blueprint $table) {
            $table->dropIndex('work_entries_user_date_start_index');
            $table->dropIndex('work_entries_date_start_user_index');
        });
    }
};
