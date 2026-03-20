<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('email');
        });

        $users = DB::table('users')->select('id', 'name', 'email')->orderBy('id')->get();

        foreach ($users as $user) {
            $base = Str::of((string) $user->name)->lower()->slug('_')->value();
            if ($base === '') {
                $base = Str::before((string) $user->email, '@');
            }

            $base = preg_replace('/[^a-z0-9_]/', '', strtolower($base)) ?: 'user';
            $base = substr($base, 0, 40);

            $candidate = $base;
            $counter = 1;

            while (DB::table('users')->where('username', $candidate)->exists()) {
                $candidate = substr($base, 0, 40).'_'.$counter;
                $counter++;
            }

            DB::table('users')->where('id', $user->id)->update([
                'username' => $candidate,
            ]);
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
