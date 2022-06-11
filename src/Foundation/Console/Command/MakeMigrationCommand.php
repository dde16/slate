<?php declare(strict_types = 1);

namespace Slate\Foundation\Console\Command {

    use DateTime;
    use RuntimeException;
    use Slate\Data\Table;
    use Slate\Foundation\Console\Command;
    use Slate\IO\File;
    use Slate\Mvc\Env;

    class MakeMigrationCommand extends Command {
        public final const NAME = "make:migration";
        public const ARGUMENTS = [
            "migrationName" => [
                "aliases" => ["-n", "--name"]
            ]
        ];
        public final const DATETIME_FORMAT = "Y_m_d_Hisv";

        public function handle(string $migrationName): void {
            $migrationsNamespace = \Str::removeSuffix(Env::get("orm.namespace.migrations"), "\\");
            $migrationsPath      = Env::get("orm.path.migrations");

            $migrationFullName = "_".(new DateTime())->format(static::DATETIME_FORMAT)."_".$migrationName;
            $migrationPath = "{$migrationsPath}/{$migrationFullName}.php";

            $migrationFile = new File($migrationPath);
            $migrationFile->open("w");
            $migrationFile->write(<<<PHP
<?php

namespace $migrationsNamespace {
    use Slate\Neat\Migration;

    class $migrationFullName extends Migration {
        public function up(): void {
            
        }

        public function down(): void {

        }
    }
}
?>
PHP);

            $migrationFile->flush();
            $migrationFile->close();
        }
    }
}

?>