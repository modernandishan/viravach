<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whether the company's structured content is AI-generated or manually
     * entered. null means the company has never made an explicit choice
     * (strict validation applies everywhere until a mode is set); 'manual'
     * relaxes the schema's minimums and blocks the AI pipeline outright.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('content_mode', 10)->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('content_mode');
        });
    }
};
