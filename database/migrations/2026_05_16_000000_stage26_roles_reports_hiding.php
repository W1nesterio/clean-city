<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'banned_at')) {
                $table->timestamp('banned_at')->nullable()->after('avatar_path');
            }
            if (!Schema::hasColumn('users', 'ban_reason')) {
                $table->string('ban_reason')->nullable()->after('banned_at');
            }
            if (!Schema::hasColumn('users', 'banned_by_user_id')) {
                $table->unsignedBigInteger('banned_by_user_id')->nullable()->after('ban_reason')->index();
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'deleted_by_user_id')) {
                $table->unsignedBigInteger('deleted_by_user_id')->nullable()->after('closed_at')->index();
            }
            if (!Schema::hasColumn('tickets', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('deleted_by_user_id')->index();
            }
            if (!Schema::hasColumn('tickets', 'delete_reason')) {
                $table->string('delete_reason')->nullable()->after('deleted_at');
            }
        });

        if (!Schema::hasTable('organization_user_blocks')) {
            Schema::create('organization_user_blocks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('created_by_user_id')->nullable()->index();
                $table->string('reason')->nullable();
                $table->boolean('active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ticket_hides')) {
            Schema::create('ticket_hides', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id')->index();
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->unsignedBigInteger('hidden_by_user_id')->nullable()->index();
                $table->string('reason')->nullable();
                $table->boolean('active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('organization_complaints')) {
            Schema::create('organization_complaints', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->index();
                $table->unsignedBigInteger('created_by_user_id')->index();
                $table->unsignedBigInteger('target_user_id')->nullable()->index();
                $table->unsignedBigInteger('ticket_id')->nullable()->index();
                $table->string('type', 40)->default('other')->index();
                $table->string('status', 30)->default('pending')->index();
                $table->string('title', 160);
                $table->text('description')->nullable();
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('resolution')->nullable();
                $table->timestamps();
            });
        }

        // Старые аккаунты admin оставляем рабочими, но новая роль главного админа — super_admin.
        DB::table('users')->where('role', 'admin')->update(['role' => 'super_admin']);
    }

    public function down(): void
    {
        // Не удаляем данные при откате, чтобы случайно не потерять историю заявок/банов.
    }
};
