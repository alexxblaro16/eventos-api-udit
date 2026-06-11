<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eventos creados por los organizadores.
     * Cada evento pertenece a un organizador (user) y a una categoría.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');      // organizador
            $table->unsignedBigInteger('category_id');
            $table->string('title');
            $table->text('description');
            $table->string('city');
            $table->string('venue')->nullable();
            $table->dateTime('starts_at');
            $table->integer('capacity');
            $table->string('status')->default('active'); // active | cancelled
            $table->string('cover_image')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->on('users')->references('id');
            $table->foreign('category_id')->on('categories')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
