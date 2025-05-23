<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Table des alertes SMS
        Schema::create('sms_alerts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['meteo', 'maladie', 'conseil', 'system']);
            $table->string('title');
            $table->text('content');
            $table->string('region')->nullable();
            $table->json('languages')->nullable(); // Pour le support multilingue
            $table->timestamp('sent_at')->nullable();
            $table->integer('recipients_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->json('delivery_status')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'region']);
            $table->index('sent_at');
        });

        // Table des données de drone
        Schema::create('drone_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('field_name');
            $table->string('region');
            $table->decimal('field_size', 10, 2); // en hectares
            $table->json('photos');
            $table->json('data'); // Données collectées (météo, santé des cultures, etc.)
            $table->json('analysis_results')->nullable();
            $table->timestamp('capture_date');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('processing_error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'capture_date']);
            $table->index(['region', 'capture_date']);
        });

        // Table pour les rapports d'analyse des données drone
        Schema::create('drone_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drone_data_id')->constrained()->onDelete('cascade');
            $table->string('report_type');
            $table->json('report_data');
            $table->string('pdf_url')->nullable();
            $table->timestamps();
        });

        // Table pour les alertes générées à partir des données drone
        Schema::create('drone_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drone_data_id')->constrained()->onDelete('cascade');
            $table->string('alert_type');
            $table->string('severity')->default('medium');
            $table->text('description');
            $table->json('affected_area')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['drone_data_id', 'severity']);
            $table->index('created_at');
        });

        // Table pour les statistiques d'utilisation des drones
        Schema::create('drone_usage_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('total_flights')->default(0);
            $table->decimal('total_area_covered', 10, 2)->default(0);
            $table->integer('total_photos')->default(0);
            $table->integer('total_alerts_generated')->default(0);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('drone_usage_stats');
        Schema::dropIfExists('drone_alerts');
        Schema::dropIfExists('drone_reports');
        Schema::dropIfExists('drone_data');
        Schema::dropIfExists('sms_alerts');
    }
};
