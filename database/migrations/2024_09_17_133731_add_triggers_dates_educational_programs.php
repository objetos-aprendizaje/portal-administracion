<?php

use App\Models\EducationalProgramsModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Controla que las fechas de los programas formativos sean consecutivas
     */
    public function up(): void
    {
        // Temporalmente se inhabilita

        /*         $tablePrefix = DB::getTablePrefix();
        $tableName = $tablePrefix . (new EducationalProgramsModel())->getTable();

        DB::unprepared("
            CREATE TRIGGER check_educational_programs_inscription_finish_date_insert
            BEFORE INSERT ON {$tableName}
            FOR EACH ROW
            BEGIN
                IF NEW.inscription_finish_date < NEW.inscription_start_date THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The inscription finish date cannot be earlier than the inscription start date';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER check_educational_programs_inscription_finish_date_before_update
            BEFORE UPDATE ON {$tableName}
            FOR EACH ROW
            BEGIN
                IF NEW.inscription_finish_date < NEW.inscription_start_date THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The inscription finish date cannot be earlier than the inscription start date';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER check_educational_programs_enrolling_start_date_before_insert
            BEFORE INSERT ON {$tableName}
            FOR EACH ROW
            BEGIN
                IF NEW.enrolling_start_date < NEW.inscription_finish_date THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The enrolling start date cannot be earlier than the inscription finish date';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER check_educational_programs_enrolling_start_date_before_update
            BEFORE UPDATE ON {$tableName}
            FOR EACH ROW
            BEGIN
                IF NEW.enrolling_start_date < NEW.inscription_finish_date THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The enrolling start date cannot be earlier than the inscription finish date';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER check_educational_programs_enrolling_finish_date_before_insert
            BEFORE INSERT ON {$tableName}
            FOR EACH ROW
            BEGIN
                IF NEW.enrolling_finish_date < NEW.enrolling_start_date THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The enrolling finish date cannot be earlier than the enrolling start date';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER check_educational_programs_enrolling_finish_date_before_update
            BEFORE UPDATE ON {$tableName}
            FOR EACH ROW
            BEGIN
                IF NEW.enrolling_finish_date < NEW.enrolling_start_date THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The enrolling finish date cannot be earlier than the enrolling start date';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER check_educational_programs_realization_start_date_before_insert
            BEFORE INSERT ON {$tableName}
            FOR EACH ROW
            BEGIN
                IF NEW.realization_start_date < NEW.enrolling_finish_date OR NEW.realization_start_date < NEW.inscription_finish_date
                THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The realization start date cannot be earlier than the enrolling finish date';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER check_educational_programs_realization_start_date_before_update
            BEFORE UPDATE ON {$tableName}
            FOR EACH ROW
            BEGIN
                IF NEW.realization_start_date < NEW.enrolling_finish_date OR NEW.realization_start_date < NEW.inscription_finish_date
                THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The realization start date cannot be earlier than the enrolling finish date';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER check_educational_programs_realization_finish_date_before_insert
            BEFORE INSERT ON {$tableName}
            FOR EACH ROW
            BEGIN
                IF NEW.realization_finish_date < NEW.realization_start_date THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The realization finish date cannot be earlier than the realization start date';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER check_educational_programs_realization_finish_date_before_update
            BEFORE UPDATE ON {$tableName}
            FOR EACH ROW
            BEGIN
                IF NEW.realization_finish_date < NEW.realization_start_date THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The realization finish date cannot be earlier than the realization start date';
                END IF;
            END
        "); */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
