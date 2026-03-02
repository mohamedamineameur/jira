<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('invitations')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE `invitations` MODIFY `token` varchar(96) NOT NULL');

                $indexExists = DB::selectOne("
                    SELECT COUNT(1) AS c
                    FROM information_schema.statistics
                    WHERE table_schema = DATABASE()
                      AND table_name = 'invitations'
                      AND index_name = 'invitations_token_unique'
                ");

                if (! $indexExists || (int) $indexExists->c === 0) {
                    DB::statement('ALTER TABLE `invitations` ADD UNIQUE `invitations_token_unique` (`token`)');
                }
            }

            return;
        }

        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('email');
            $table->enum('role', ['admin', 'member']);
            $table->string('token', 96)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('accepted')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
