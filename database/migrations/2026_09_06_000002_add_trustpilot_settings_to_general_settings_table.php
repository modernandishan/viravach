<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Viravach's own Trustpilot review widget, rendered in the footer's
     * trust area. The values are stored as SEPARATE fields (not one embed
     * snippet) so nothing pasted can inject markup: the footer builds the
     * widget div itself and every value is escaped into a data- attribute.
     * The Trustpilot bootstrap <script> stays hardcoded in the footer
     * component — it is the same infrastructure script for every business.
     */
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->boolean('trustpilot_enabled')->default(false);
            $table->string('trustpilot_business_unit_id')->nullable();
            $table->string('trustpilot_template_id')->nullable();
            $table->string('trustpilot_locale')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn([
                'trustpilot_enabled',
                'trustpilot_business_unit_id',
                'trustpilot_template_id',
                'trustpilot_locale',
            ]);
        });
    }
};
