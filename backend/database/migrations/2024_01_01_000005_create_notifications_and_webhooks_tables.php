<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Table des notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('title');
            $table->text('content');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
            $table->index('type');
        });

        // Table des endpoints webhook
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('url');
            $table->string('description')->nullable();
            $table->json('events'); // Liste des événements à envoyer
            $table->string('secret', 64);
            $table->boolean('is_active')->default(true);
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(5);
            $table->timestamp('last_ping')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
        });

        // Table des logs de webhook
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained()->onDelete('cascade');
            $table->string('event');
            $table->json('payload');
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->boolean('success');
            $table->text('error_message')->nullable();
            $table->integer('attempt_count')->default(1);
            $table->timestamp('next_retry')->nullable();
            $table->timestamps();

            $table->index(['webhook_endpoint_id', 'created_at']);
            $table->index(['success', 'created_at']);
        });

        // Table des préférences de notification
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('email_preferences')->default('{}');
            $table->json('sms_preferences')->default('{}');
            $table->json('push_preferences')->default('{}');
            $table->json('webhook_preferences')->default('{}');
            $table->boolean('marketing_emails_enabled')->default(true);
            $table->boolean('transaction_emails_enabled')->default(true);
            $table->boolean('security_emails_enabled')->default(true);
            $table->timestamps();

            $table->unique('user_id');
        });

        // Table des statistiques de notification
        Schema::create('notification_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('total_notifications')->default(0);
            $table->integer('unread_notifications')->default(0);
            $table->integer('email_notifications_sent')->default(0);
            $table->integer('sms_notifications_sent')->default(0);
            $table->integer('push_notifications_sent')->default(0);
            $table->timestamp('last_notification_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_stats');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('notifications');
    }
};
