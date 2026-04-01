<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->tableName(), function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('code', 6);
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName());
    }

    private function tableName(): string
    {
        return config('identity.table_prefix', 'identity_').'login_codes';
    }
};
