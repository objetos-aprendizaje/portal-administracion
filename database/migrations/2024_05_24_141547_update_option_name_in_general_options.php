<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateOptionNameInGeneralOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('general_options')
            ->where('option_name', 'lane_featured_courses')
            ->update(['option_name' => 'lane_featured_courses']);

        DB::table('general_options')
            ->where('option_name', 'lane_featured_educationals_programs')
            ->update(['option_name' => 'lane_featured_educationals_programs']);

        DB::table('general_options')
            ->where('option_name', 'lane_featured_itineraries')
            ->update(['option_name' => 'lane_featured_itineraries']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('general_options')
            ->where('option_name', 'lane_featured_courses')
            ->update(['option_name' => 'lane_featured_courses']);

        DB::table('general_options')
            ->where('option_name', 'lane_featured_educationals_programs')
            ->update(['option_name' => 'lane_featured_educationals_programs']);

        DB::table('general_options')
            ->where('option_name', 'lane_featured_itineraries')
            ->update(['option_name' => 'lane_featured_itineraries']);
    }

}
