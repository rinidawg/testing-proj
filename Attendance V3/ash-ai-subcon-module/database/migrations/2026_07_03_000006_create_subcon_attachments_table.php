<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcon_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 16);   // delivery | trip | po
            $table->unsignedBigInteger('owner_id');
            $table->string('path');             // storage path (public disk)
            $table->string('original_name')->nullable();
            $table->string('mime', 64)->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcon_attachments');
    }
};
