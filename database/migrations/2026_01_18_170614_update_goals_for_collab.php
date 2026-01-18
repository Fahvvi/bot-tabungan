<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        // 1. Tambah kolom 'code' di tabel goals untuk invite link
        Schema::table('goals', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('name');
        });

        // 2. Buat tabel perantara (Pivot) goal_user
        Schema::create('goal_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // 3. Pindahkan data lama (agar goal yg sudah ada tidak error)
        $goals = \Illuminate\Support\Facades\DB::table('goals')->get();
        foreach ($goals as $goal) {
            // Isi kode unik random
            \Illuminate\Support\Facades\DB::table('goals')
                ->where('id', $goal->id)
                ->update(['code' => strtoupper(Str::random(6))]);

            // Hubungkan pemilik lama ke tabel pivot
            \Illuminate\Support\Facades\DB::table('goal_user')->insert([
                'goal_id' => $goal->id,
                'user_id' => $goal->user_id, // Asumsi kolom lama user_id masih ada
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('goal_user');
        Schema::table('goals', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};