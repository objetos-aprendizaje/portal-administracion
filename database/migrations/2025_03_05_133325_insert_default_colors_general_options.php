<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        $colors = [
            [
                'name' => 'color_1',
                'value' => '#2B4C7E',
            ],
            [
                'name' => 'color_2',
                'value' => '#507AB9',
            ],
            [
                'name' => 'color_3',
                'value' => '#1F1F20',
            ],
            [
                'name' => 'color_4',
                'value' => '#D9D9D9',
            ],
        ];

        foreach ($colors as $color) {
            DB::table('general_options')->where('option_name', $color['name'])->update([
                'option_value' => $color['value'],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
