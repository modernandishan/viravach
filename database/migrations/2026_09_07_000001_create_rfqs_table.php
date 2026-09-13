<?php

use App\Enums\RfqStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per quote request a public visitor sends to a company from its
     * published page. Buyers are anonymous — there is no user_id here on
     * purpose: the whole point is that a visitor can ask without registering,
     * so their identity is only the contact details they typed.
     *
     * The buyer's locale is captured at submission time (not derived later)
     * because it is the only signal the company owner has about which
     * language to answer in, and the site's locale can change under the
     * owner's own session afterwards.
     */
    public function up(): void
    {
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('buyer_name');
            $table->string('buyer_email');
            $table->string('buyer_phone')->nullable();
            $table->string('buyer_country')->nullable();
            $table->text('message');

            // app()->getLocale() at submission time — the reply language hint.
            $table->string('locale', 5);

            $table->string('status', 32)->default(RfqStatus::Pending->value);

            // Audit trail for the per-IP rate limiter; never shown to the
            // company owner.
            $table->string('ip_address');

            $table->timestamps();

            // Every listing of this table is scoped to one company, filtered
            // by status and ordered newest-first — the dashboard inbox, the
            // Filament table and the navigation badge counts all share this
            // shape, and the table grows unbounded.
            $table->index(['company_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfqs');
    }
};
