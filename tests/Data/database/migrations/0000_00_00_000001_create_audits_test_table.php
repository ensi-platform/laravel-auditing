<?php

namespace Ensi\LaravelAuditing\Tests\Data\database\migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->nullableMorphs('subject');

            $table->string('event');
            $table->morphs('auditable');
            $table->nullableMorphs('root_entity');
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->jsonb('state')->nullable();

            $table->text('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->string('tags')->nullable();
            $table->timestamps(6);

            $table->uuid('transaction_uid')->nullable();
            $table->timestamp('transaction_time', 6)->nullable();
            $table->string('user_id')->nullable();
            $table->jsonb('extra')->nullable();

            $table->index(['subject_id', 'subject_type']);
            $table->index(['transaction_uid', 'created_at']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('audits');
    }
};
