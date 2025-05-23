<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->string('attachment_type')->nullable();
            $table->string('attachment_url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indices pour la performance
            $table->index(['sender_id', 'receiver_id']);
            $table->index(['receiver_id', 'is_read']);
            $table->index('created_at');
        });

        // Table pour les conversations archivées
        Schema::create('archived_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->boolean('is_read');
            $table->timestamp('read_at')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->foreignId('transaction_id')->nullable();
            $table->string('attachment_type')->nullable();
            $table->string('attachment_url')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at');

            // Index pour les recherches dans les archives
            $table->index(['sender_id', 'receiver_id', 'archived_at']);
        });

        // Table pour les statistiques de messagerie
        Schema::create('message_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('messages_sent')->default(0);
            $table->integer('messages_received')->default(0);
            $table->integer('unread_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('message_stats');
        Schema::dropIfExists('archived_messages');
        Schema::dropIfExists('messages');
    }
};
