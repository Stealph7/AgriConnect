<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->enum('role', ['producteur', 'acheteur', 'cooperative', 'admin']);
            $table->string('region')->nullable();
            $table->string('profile_photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->string('unit'); // kg, tonnes, etc.
            $table->enum('season', ['principale', 'intermediaire']);
            $table->string('region');
            $table->json('photos')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('sms_alerts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['meteo', 'maladie', 'conseil']);
            $table->string('title');
            $table->text('content');
            $table->string('region')->nullable();
            $table->json('languages'); // ['fr', 'baoule', 'malinke']
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('drone_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('field_name');
            $table->string('region');
            $table->decimal('field_size', 10, 2); // en hectares
            $table->json('photos');
            $table->json('analysis_data')->nullable();
            $table->date('capture_date');
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price_per_unit', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->string('type');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('drone_data');
        Schema::dropIfExists('sms_alerts');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('products');
        Schema::dropIfExists('users');
    }
};
