<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The amCharts geodata feature id for this state (e.g. "IR-05" for
     * ilam in iranLow), mirroring how countries.iso2 serves the world
     * globe. Nullable and non-unique: countries whose map geodata is not
     * wired up (config/geo_maps.php) simply leave it null, and id schemes
     * are only unique within one country's geodata.
     */
    public function up(): void
    {
        Schema::table('states', function (Blueprint $table) {
            $table->string('geo_id')->nullable()->index()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('states', function (Blueprint $table) {
            $table->dropIndex(['geo_id']);
            $table->dropColumn('geo_id');
        });
    }
};
