<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->text('additional_notes')->nullable();
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable();
            $table->json('steps')->nullable();
            $table->string('image_url')->nullable();
        });
    }

    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'user_id',
                'description',
                'additional_notes',
                'due_date',
                'due_time',
                'steps',
                'image_url'
            ]);
        });
    }
};

