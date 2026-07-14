<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('name_i18n')->nullable()->after('name');
            $table->json('family_i18n')->nullable()->after('family');
        });

        DB::table('users')->select('id', 'name', 'family')->orderBy('id')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update([
                    'name_i18n' => json_encode(['fa' => $user->name]),
                    'family_i18n' => json_encode(['fa' => $user->family]),
                ]);
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['name', 'family']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->renameColumn('name_i18n', 'name');
            $table->renameColumn('family_i18n', 'family');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name_plain')->nullable()->after('name');
            $table->string('family_plain')->nullable()->after('family');
        });

        DB::table('users')->select('id', 'name', 'family')->orderBy('id')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                $name = json_decode((string) $user->name, true) ?? [];
                $family = json_decode((string) $user->family, true) ?? [];

                DB::table('users')->where('id', $user->id)->update([
                    'name_plain' => $name[config('app.fallback_locale')] ?? reset($name) ?: '',
                    'family_plain' => $family[config('app.fallback_locale')] ?? reset($family) ?: '',
                ]);
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['name', 'family']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->renameColumn('name_plain', 'name');
            $table->renameColumn('family_plain', 'family');
        });
    }
};
