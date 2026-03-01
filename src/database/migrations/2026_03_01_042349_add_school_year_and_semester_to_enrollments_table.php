<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('school_year')->after('subject_id');
            $table->string('semester')->after('school_year');

            $table->unique(
                ['student_id', 'subject_id', 'school_year', 'semester'],
                'enrollment_unique_constraint'
            );

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique('enrollment_unique_constraint');
            $table->dropColumn(['school_year', 'semester']);
        });
    }
};
