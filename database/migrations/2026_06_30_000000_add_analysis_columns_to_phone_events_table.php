<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_events', static function (Blueprint $table) {
            $table->foreignId('import_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('contact')->after('import_id');
            $table->string('number')->after('contact');
            $table->timestamp('first_seen_at')->nullable()->after('number');
            $table->timestamp('last_seen_at')->nullable()->after('first_seen_at');
            $table->unsignedInteger('calls_count')->default(0)->after('last_seen_at');
            $table->unsignedInteger('messages_count')->default(0)->after('calls_count');
            $table->unsignedInteger('data_count')->default(0)->after('messages_count');

            $table->unique(['import_id', 'contact', 'number']);
            $table->index('import_id');
            $table->index('contact');
            $table->index('number');
        });
    }

    public function down(): void
    {
        Schema::table('phone_events', static function (Blueprint $table) {
            $table->dropUnique(['import_id', 'contact', 'number']);
            $table->dropIndex(['import_id']);
            $table->dropIndex(['contact']);
            $table->dropIndex(['number']);
            $table->dropConstrainedForeignId('import_id');
            $table->dropColumn([
                'contact',
                'number',
                'first_seen_at',
                'last_seen_at',
                'calls_count',
                'messages_count',
                'data_count',
            ]);
        });
    }
};
